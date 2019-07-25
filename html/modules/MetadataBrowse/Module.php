<?php

namespace MetadataBrowse;

use MetadataBrowse\Form\ConfigForm;
use Omeka\Module\AbstractModule;
use Omeka\Entity\Value;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Mvc\Controller\AbstractController;
use Zend\View\Renderer\PhpRenderer;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;
use Omeka\Permissions\Acl;

class Module extends AbstractModule
{

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            [
                Acl::ROLE_EDITOR,
                Acl::ROLE_GLOBAL_ADMIN,
                Acl::ROLE_SITE_ADMIN,
            ],
            ['MetadataBrowse\Controller\Admin\Index']
            );
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $logger = $serviceLocator->get('Omeka\Logger');
        $settings = $serviceLocator->get('Omeka\Settings');
        $settings->delete('metadata_browse_properties');
        $settings->delete('metadata_browse_use_globals');

        $api = $serviceLocator->get('Omeka\ApiManager');
        $sites = $api->search('sites', [])->getContent();
        $siteSettings = $serviceLocator->get('Omeka\Settings\Site');

        foreach ($sites as $site) {
            $siteSettings->setTargetId($site->id());
            $siteSettings->delete('metadata_browse_properties');
        }
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator)
    {
        //fix the double json encoding that was stored
        if (version_compare($oldVersion, '0.2.1-alpha', '<')) {
            $settings = $serviceLocator->get('Omeka\Settings');
            $globalProperties = json_decode($settings->get('metadata_browse_properties'));
            $settings->set('metadata_browse_properties', $globalProperties);

            $api = $serviceLocator->get('Omeka\ApiManager');
            $sites = $api->search('sites', [])->getContent();
            $siteSettings = $serviceLocator->get('Omeka\Settings\Site');

            foreach ($sites as $site) {
                $siteSettings->setTargetId($site->id());
                $currentSiteSettings = json_decode($siteSettings->get('metadata_browse_properties'));
                $siteSettings->set('metadata_browse_properties', $currentSiteSettings);
            }
        }
    }

    public function getConfig()
    {
        return include __DIR__.'/config/module.config.php';
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
                'Omeka\Api\Representation\ValueRepresentation',
                'rep.value.html',
                [$this, 'repValueHtml']
                );

        $triggerIdentifiers = [
                'Omeka\Controller\Admin\Item',
                'Omeka\Controller\Admin\ItemSet',
                'Omeka\Controller\Site\Item',
                'Omeka\Controller\Site\ItemSet',
                ];
        foreach ($triggerIdentifiers as $identifier) {
            $sharedEventManager->attach(
                $identifier,
                'view.show.after',
                [$this, 'addCSS']
            );

            $sharedEventManager->attach(
                $identifier,
                'view.browse.after',
                [$this, 'addCSS']
            );
        }
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $params = $controller->params()->fromPost();
        if (isset($params['propertyIds'])) {
            $propertyIds = $params['propertyIds'];
        } else {
            $propertyIds = [];
        }
        $globalSettings = $this->getServiceLocator()->get('Omeka\Settings');
        $globalSettings->set('metadata_browse_properties', $propertyIds);
        $globalSettings->set('metadata_browse_use_globals', $params['metadata_browse_use_globals']);
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $globalSettings = $this->getServiceLocator()->get('Omeka\Settings');
        $filteredPropertyIds = json_encode($globalSettings->get('metadata_browse_properties'));
        $escape = $renderer->plugin('escapeHtml');
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        $html = '';
        $html .= "<script type='text/javascript'>
        var filteredPropertyIds = $filteredPropertyIds;
        </script>
        ";
        $formElementManager = $this->getServiceLocator()->get('FormElementManager');
        $form = $formElementManager->get(ConfigForm::class, []);
        $html .= "<p>" . $translator->translate("If checked, the properties selected below will be linked on the admin side, overriding all site-specific settings. Each site's own settings will be reflected on the public side. Otherwise, the admin side will reflect the aggregated settings for all sites; anything selected to be a link in any site will be a link on the admin side.") . "</p>";
        $html .= $renderer->formCollection($form, false);
        $html .= "<div id='metadata-browse-properties'><p>".$escape($translator->translate('Choose properties from the sidebar to be searchable on the admin side.')).'</p></div>';
        $html .= $renderer->partial('metadata-browse/property-template', ['escape' => $escape, 'translator' => $translator]);
        $renderer->headScript()->appendFile($renderer->assetUrl('js/metadata-browse.js', 'MetadataBrowse'));
        $renderer->headLink()->appendStylesheet($renderer->assetUrl('css/metadata-browse.css', 'MetadataBrowse'));
        $renderer->htmlElement('body')->appendAttribute('class', 'sidebar-open');
        $selectorHtml = $renderer->propertySelector($translator->translate('Select properties to be searchable'));
        $html .= "<div class='sidebar active'>$selectorHtml</div>";

        return $html;
    }

    public function addCSS($event)
    {
        $view = $event->getTarget();
        $view->headLink()->appendStylesheet($view->assetUrl('css/metadata-browse.css', 'MetadataBrowse'));
    }

    public function repValueHtml($event)
    {
        $target = $event->getTarget();
        $propertyId = $target->property()->id();

        $routeMatch = $this->getServiceLocator()->get('Application')
                        ->getMvcEvent()->getRouteMatch();
        $routeMatchParams = $routeMatch->getParams();
        //setup the route params to pass to the Url helper. Both the route name and its parameters go here
        $routeParams = [
                'action' => 'browse',
        ];
        if ($routeMatch->getParam('__ADMIN__')) {
            $globalSettings = $this->getServiceLocator()->get('Omeka\Settings');
            if ($globalSettings->get('metadata_browse_use_globals')) {
                $filteredPropertyIds = $globalSettings->get('metadata_browse_properties', []);
            } else {
                $api = $this->getServiceLocator()->get('Omeka\ApiManager');
                $sites = $api->search('sites', [])->getContent();
                $siteSettings = $this->getServiceLocator()->get('Omeka\Settings\Site');
                $filteredPropertyIds = [];
                foreach ($sites as $site) {
                    $siteSettings->setTargetId($site->id());
                    $currentSettings = $siteSettings->get('metadata_browse_properties', []);
                    $filteredPropertyIds = array_merge($currentSettings, $filteredPropertyIds);
                }
            }

            $routeParams['route'] = 'admin/default';
        } else {
            $siteSettings = $this->getServiceLocator()->get('Omeka\Settings\Site');
            $filteredPropertyIds = $siteSettings->get('metadata_browse_properties', []);
            $siteSlug = $routeMatch->getParam('site-slug');
            $routeParams['route'] = 'site';
            $routeParams['site-slug'] = $siteSlug.'/'.$target->resource()->getControllerName();
        }

        $url = $this->getServiceLocator()->get('ViewHelperManager')->get('Url');
        if (in_array($propertyId, $filteredPropertyIds)) {
            $controllerName = $target->resource()->getControllerName();
            $routeParams['controller'] = $controllerName;

            $translator = $this->getServiceLocator()->get('MvcTranslator');
            $params = $event->getParams();
            $html = $params['html'];
            switch ($target->type()) {
                case 'resource':
                    $searchTarget = $target->valueResource()->id();
                    $searchUrl = $this->resourceSearchUrl($url, $routeParams, $propertyId, $searchTarget);
                    break;
                case 'uri':
                    $searchTarget = $target->uri();
                    $searchUrl = $this->uriSearchUrl($url, $routeParams, $propertyId, $searchTarget);
                    break;
                case 'literal':
                    $searchTarget = $html;
                    $searchUrl = $this->literalSearchUrl($url, $routeParams, $propertyId, $searchTarget);
                    break;
                default:
                    $resource = $target->valueResource();
                    $uri = $target->uri();
                    if ($resource) {
                        $searchTarget = $target->valueResource()->id();
                        $searchUrl = $this->resourceSearchUrl($url, $routeParams, $propertyId, $searchTarget);
                    } elseif ($uri) {
                        $searchUrl = $this->uriSearchUrl($url, $routeParams, $propertyId, $uri);
                    } else {
                        $searchTarget = $html;
                        $searchUrl = $this->literalSearchUrl($url, $routeParams, $propertyId, $searchTarget);
                    }
            }

            switch ($controllerName) {
                case 'item':
                    $controllerLabel = 'items';
                break;
                case 'item-set':
                    $controllerLabel = 'item sets';
                break;
                default:
                    $controllerLabel = $controllerName;
                break;
            }
            $text = sprintf($translator->translate('See all %s with this value'), $translator->translate($controllerLabel));
            $link = "<a class='metadata-browse-link' href='$searchUrl'>$text</a>";
            $event->setParam('html', "$html $link");
        }
    }

    protected function literalSearchUrl($url, $routeParams, $propertyId, $searchTarget)
    {
        $searchUrl = $url($routeParams['route'],
              $routeParams,
              ['query' => ['Search' => '',
                                     'property[0][property]' => $propertyId,
                                     'property[0][type]' => 'eq',
                                     'property[0][text]' => $searchTarget,
                           ],
                      ]
          );

        return $searchUrl;
    }

    protected function uriSearchUrl($url, $routeParams, $propertyId, $searchTarget)
    {
        $searchUrl = $url($routeParams['route'],
              $routeParams,
                ['query' => ['Search' => '',
                    'property[0][property]' => $propertyId,
                    'property[0][type]' => 'eq',
                    'property[0][text]' => $searchTarget,
                ],
            ]
          );

        return $searchUrl;
    }

    protected function resourceSearchUrl($url, $routeParams, $propertyId, $searchTarget)
    {
        $searchUrl = $url($routeParams['route'],
              $routeParams,
            ['query' => ['Search' => '',
                'property[0][property]' => $propertyId,
                'property[0][type]' => 'res',
                'property[0][text]' => $searchTarget,
            ],
            ]
          );

        return $searchUrl;
    }
}
