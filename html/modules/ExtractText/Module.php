<?php
namespace ExtractText;

use Doctrine\Common\Collections\Criteria;
use Omeka\Entity\Item;
use Omeka\Entity\Media;
use Omeka\Entity\Property;
use Omeka\Entity\Resource;
use Omeka\Entity\Value;
use Omeka\File\Store\Local;
use Omeka\Module\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    /**
     * Text property cache
     *
     * @var Omeka\Entity\Property|false
     */
    protected $textProperty;

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $services)
    {
        // Import the ExtractText vocabulary if it doesn't already exist.
        $api = $services->get('Omeka\ApiManager');
        $response = $api->search('vocabularies', [
            'namespace_uri' => 'http://omeka.org/s/vocabs/o-module-extracttext#',
            'limit' => 0,
        ]);
        if (0 === $response->getTotalResults()) {
            $importer = $services->get('Omeka\RdfImporter');
            $importer->import(
                'file',
                [
                    'o:namespace_uri' => 'http://omeka.org/s/vocabs/o-module-extracttext#',
                    'o:prefix' => 'extracttext',
                    'o:label' => 'Extract Text',
                    'o:comment' =>  null,
                ],
                [
                    'file' => __DIR__ . '/vocabs/extracttext.n3',
                    'format' => 'turtle',
                ]
            );
        }
    }

    public function getConfigForm(PhpRenderer $view)
    {
        $extractors = $this->getServiceLocator()->get('ExtractText\ExtractorManager');
        $html = '
        <table class="tablesaw tablesaw-stack">
            <thead>
            <tr>
                <th>' . $view->translate('Extractor') . '</th>
                <th>' . $view->translate('Available') . '</th>
            </tr>
            </thead>
            <tbody>';
        foreach ($extractors->getRegisteredNames() as $extractorName) {
            $extractor = $extractors->get($extractorName);
            $isAvailable = $extractor->isAvailable()
                ? sprintf('<span style="color: green;">%s</span>', $view->translate('Yes'))
                : sprintf('<span style="color: red;">%s</span>', $view->translate('No'));
            $html .= sprintf('
            <tr>
                <td>%s</td>
                <td>%s</td>
            </tr>', $extractorName, $isAvailable);
        }
        $html .= '
            </tbody>
        </table>';
        return $html;
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        /**
         * Before ingesting a media file, extract its text and set it to the
         * media. This will only happen when creating the media.
         */
        $sharedEventManager->attach(
            '*',
            'media.ingest_file.pre',
            function (Event $event) {
                $textProperty = $this->getTextProperty();
                if (false === $textProperty) {
                    return; // The text property doesn't exist. Do nothing.
                }
                $tempFile = $event->getParam('tempFile');
                $this->setTextToMedia(
                    $tempFile->getTempPath(),
                    $event->getTarget(),
                    $textProperty,
                    $tempFile->getMediaType()
                );
            }
        );
        /**
         * After hydrating an item, aggregate its media's text and set it to the
         * item. This happens when creating and updating the item. Refreshes the
         * media's text first if the "extract_text_refresh" flag is passed in
         * the request.
         */
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            function (Event $event) {
                $textProperty = $this->getTextProperty();
                if (false === $textProperty) {
                    return; // The text property doesn't exist. Do nothing.
                }
                $item = $event->getParam('entity');
                $data = $event->getParam('request')->getContent();
                $action = isset($data['extract_text_action']) ? $data['extract_text_action'] : 'default';
                $this->setTextToItem($item, $textProperty, $action);
            }
        );
        /**
         * Add the ExtractText radio buttons to the resource batch update form.
         */
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_elements',
            function (Event $event) {
                $form = $event->getTarget();
                $valueOptions = [
                    'clear' => 'Clear text', // @translate
                    '' => '[No action]', // @translate
                ];
                $store = $this->getServiceLocator()->get('Omeka\File\Store');
                if ($store instanceof Local) {
                    // Files must be stored locally to refresh extracted text.
                    $valueOptions = ['refresh' => 'Refresh text'] + $valueOptions; // @translate
                }
                $form->add([
                    'name' => 'extract_text_action',
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                        'label' => 'Extract text', // @translate
                        'value_options' => $valueOptions,
                    ],
                    'attributes' => [
                        'value' => '',
                        'data-collection-action' => 'replace',
                    ],
                ]);
            }
        );
        /**
         * Don't require the ExtractText radio buttons in the resource batch
         * update form.
         */
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_input_filters',
            function (Event $event) {
                $inputFilter = $event->getParam('inputFilter');
                $inputFilter->add([
                    'name' => 'extract_text_action',
                    'required' => false,
                ]);
            }
        );
        /**
         * When preprocessing the batch update data, authorize the "extract_text
         * _action" key. This will signal the process to refresh or clear the
         * text while updating each item in the batch.
         */
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.preprocess_batch_update',
            function (Event $event) {
                $adapter = $event->getTarget();
                $data = $event->getParam('data');
                $rawData = $event->getParam('request')->getContent();
                if (isset($rawData['extract_text_action'])
                    && in_array($rawData['extract_text_action'], ['refresh', 'clear'])
                ) {
                    $data['extract_text_action'] = $rawData['extract_text_action'];
                }
                $event->setParam('data', $data);
            }
        );
        /**
         * Add an "Extract text" tab to the item edit page.
         */
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.section_nav',
            function (Event $event) {
                $view = $event->getTarget();
                $sectionNavs = $event->getParam('section_nav');
                $sectionNavs['extract-text'] = $view->translate('Extract text');
                $event->setParam('section_nav', $sectionNavs);
            }
        );
        /**
         * Add an "Extract text" section to the item edit page.
         */
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.after',
            function (Event $event) {
                $view = $event->getTarget();
                $store = $this->getServiceLocator()->get('Omeka\File\Store');
                $refreshRadioButton = null;
                if ($store instanceof Local) {
                    // Files must be stored locally to refresh extracted text.
                    $refreshRadioButton = sprintf(
                        '<label><input type="radio" name="extract_text_action" value="refresh">%s</label>',
                        $view->translate('Refresh text')
                    );
                }
                $html = sprintf('
                <div id="extract-text" class="section">
                    <div class="field">
                        <div class="field-meta">
                            <label for="extract_text_action">%s</label>
                        </div>
                        <div class="inputs">
                            %s
                            <label><input type="radio" name="extract_text_action" value="clear">%s</label>
                            <label><input type="radio" name="extract_text_action" value="" checked="checked">%s</label>
                        </div>
                    </div>
                </div>',
                $view->translate('Extract text'),
                $refreshRadioButton,
                $view->translate('Clear text'),
                $view->translate('[No action]'));
                echo $html;
            }
        );
    }

    /**
     * Get the text property, caching on first pass.
     *
     * @return Omeka\Entity\Property|false
     */
    public function getTextProperty()
    {
        if (isset($this->textProperty)) {
            return $this->textProperty;
        }
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $textProperty = $entityManager->createQuery('
            SELECT p FROM Omeka\Entity\Property p
            JOIN p.vocabulary v
            WHERE p.localName = :localName
            AND v.namespaceUri = :namespaceUri
        ')->setParameters([
            'localName' => 'extracted_text',
            'namespaceUri' => 'http://omeka.org/s/vocabs/o-module-extracttext#',
        ])->getOneOrNullResult();
        $this->textProperty = (null === $textProperty) ? false : $textProperty;
        return $this->textProperty;
    }

    /**
     * Set extracted text to a media.
     *
     * @param string $filePath
     * @param Media $media
     * @param Property $textProperty
     * @param string $mediaType
     * @return null|false
     */
    public function setTextToMedia($filePath, Media $media, Property $textProperty, $mediaType = null)
    {
        if (null === $mediaType) {
            // Fall back on the media type set to the media.
            $mediaType = $media->getMediaType();
        }
        $text = $this->extractText($filePath, $mediaType);
        if (false === $text) {
            // Could not extract text from the file.
            return false;
        }
        $text = trim($text);
        $this->setTextToTextProperty($media, $textProperty, ('' === $text) ? null : $text);
    }

    /**
     * Set extracted text to an item.
     *
     * There are three actions this method can perform:
     *
     * - default: aggregates text from child media and set it to the item.
     * - refresh: same as default but (re)extracts text from files first.
     * - clear: clears all extracted text from item and child media.
     *
     * @param Item $item
     * @param Property $textProperty
     * @param string $action default|refresh|clear
     */
    public function setTextToItem(Item $item, Property $textProperty, $action = 'default')
    {
        $store = $this->getServiceLocator()->get('Omeka\File\Store');
        $itemTexts = [];
        $itemMedia = $item->getMedia();
        // Order by position in case the position was changed on this request.
        $criteria = Criteria::create()->orderBy(['position' => Criteria::ASC]);
        foreach ($itemMedia->matching($criteria) as $media) {
            // Files must be stored locally to refresh extracted text.
            if (('refresh' === $action) && ($store instanceof Local)) {
                $filePath = $store->getLocalPath(sprintf('original/%s', $media->getFilename()));
                $this->setTextToMedia($filePath, $media, $textProperty);
            }
            $mediaValues = $media->getValues();
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('property', $textProperty))
                ->andWhere(Criteria::expr()->eq('type', 'literal'));
            foreach($mediaValues->matching($criteria) as $mediaValueTextProperty) {
                if ('clear' === $action) {
                    $mediaValues->removeElement($mediaValueTextProperty);
                } else {
                    $itemTexts[] = $mediaValueTextProperty->getValue();
                }
            }
        }
        $itemText = trim(implode(PHP_EOL, $itemTexts));
        $this->setTextToTextProperty($item, $textProperty, ('' === $itemText) ? null : $itemText);
    }

    /**
     * Extract text from a file.
     *
     * @param string $filePath
     * @param string $mediaType
     * @param array $options
     * @return string|false
     */
    public function extractText($filePath, $mediaType = null, array $options = [])
    {
        if (!@is_file($filePath)) {
            // The file doesn't exist.
            return false;
        }
        if (null === $mediaType) {
            // Fall back on PHP's magic.mime file.
            $mediaType = mime_content_type($filePath);
        }
        $extractors = $this->getServiceLocator()->get('ExtractText\ExtractorManager');
        try {
            $extractor = $extractors->get($mediaType);
        } catch (ServiceNotFoundException $e) {
            // No extractor assigned to the media type.
            return false;
        }
        if (!$extractor->isAvailable()) {
            // The extractor is unavailable.
            return false;
        }
        // extract() should return false if it cannot extract text.
        return $extractor->extract($filePath, $options);
    }

    /**
     * Set text as a text property value of a resource.
     *
     * Clears all existing text property values from the resource before setting
     * the value. Pass anything but a string to $text to just clear the values.
     *
     * @param Resource $resource
     * @param Property $textProperty
     * @param string $text
     */
    public function setTextToTextProperty(Resource $resource, Property $textProperty, $text)
    {
        // Clear values.
        $criteria = Criteria::create()->where(Criteria::expr()->eq('property', $textProperty));
        $resourceValues = $resource->getValues();
        foreach ($resourceValues->matching($criteria) as $resourceValueTextProperty) {
            $resourceValues->removeElement($resourceValueTextProperty);
        }
        // Create and add the value.
        if (is_string($text)) {
            $value = new Value;
            $value->setResource($resource);
            $value->setType('literal');
            $value->setProperty($textProperty);
            $value->setValue($text);
            $resourceValues->add($value);
        }
    }
}
