<?php
namespace Mapping;

use Doctrine\ORM\Events;
use Mapping\Db\Event\Listener\DetachOrphanMappings;
use Omeka\Module\AbstractModule;
use Omeka\Permissions\Acl;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            null,
            'Mapping\Controller\Site\Index'
        );
        $acl->allow(
            [Acl::ROLE_AUTHOR,
                Acl::ROLE_EDITOR,
                Acl::ROLE_GLOBAL_ADMIN,
                Acl::ROLE_REVIEWER,
                Acl::ROLE_SITE_ADMIN,
            ],
            ['Mapping\Api\Adapter\MappingMarkerAdapter',
             'Mapping\Api\Adapter\MappingAdapter',
             'Mapping\Entity\MappingMarker',
             'Mapping\Entity\Mapping',
            ]
        );

        $acl->allow(
            null,
            ['Mapping\Api\Adapter\MappingMarkerAdapter',
                'Mapping\Api\Adapter\MappingAdapter',
                'Mapping\Entity\MappingMarker',
            ],
            ['show', 'browse', 'read', 'search']
            );

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $em->getEventManager()->addEventListener(
            Events::preFlush,
            new DetachOrphanMappings
        );
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('
CREATE TABLE mapping_marker (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, media_id INT DEFAULT NULL, lat DOUBLE PRECISION NOT NULL, lng DOUBLE PRECISION NOT NULL, `label` VARCHAR(255) DEFAULT NULL, INDEX IDX_667C9244126F525E (item_id), INDEX IDX_667C9244EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
CREATE TABLE mapping (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, bounds VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_49E62C8A126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
ALTER TABLE mapping_marker ADD CONSTRAINT FK_667C9244126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;
ALTER TABLE mapping_marker ADD CONSTRAINT FK_667C9244EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE SET NULL;
ALTER TABLE mapping ADD CONSTRAINT FK_49E62C8A126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;');
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('
DROP TABLE IF EXISTS mapping;
DROP TABLE IF EXISTS mapping_marker');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        // Add the map form to the item add and edit pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.form.after',
            function (Event $event) {
                echo $event->getTarget()->partial('mapping/index/form');
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.after',
            function (Event $event) {
                echo $event->getTarget()->partial('mapping/index/form');
            }
        );
        // Add the map to the item show page.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.after',
            function (Event $event) {
                echo $event->getTarget()->partial('mapping/index/show');
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.show.after',
            function (Event $event) {
                echo $event->getTarget()->partial('mapping/index/show');
            }
        );
        // Add the mapping fields to the site's map browse page.
        $sharedEventManager->attach(
            'Mapping\Controller\Site\Index',
            'view.advanced_search',
            function (Event $event) {
                $partials = $event->getParam('partials');
                $partials[] = 'mapping/index/advanced-search';
                $event->setParam('partials', $partials);
            }
        );
        // Add the "has_markers" filter to item search.
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            function (Event $event) {
                $query = $event->getParam('request')->getContent();
                if (isset($query['has_markers'])) {
                    $qb = $event->getParam('queryBuilder');
                    $itemAdapter = $event->getTarget();
                    $mappingMarkerAlias = $itemAdapter->createAlias();
                    $itemAlias = $itemAdapter->getEntityClass();
                    $qb->innerJoin(
                        'Mapping\Entity\MappingMarker', $mappingMarkerAlias,
                        'WITH', "$mappingMarkerAlias.item = $itemAlias.id"
                    );
                }
            }
        );
        // Add the Mapping term definition.
        $sharedEventManager->attach(
            '*',
            'api.context',
            function (Event $event) {
                $context = $event->getParam('context');
                $context['o-module-mapping'] = 'http://omeka.org/s/vocabs/module/mapping#';
                $event->setParam('context', $context);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.section_nav',
            [$this, 'addMapTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.section_nav',
            [$this, 'addMapTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.section_nav',
            [$this, 'addMapTab']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Representation\ItemRepresentation',
            'rep.resource.json',
            [$this, 'filterItemJsonLd']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'handleMapping']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'handleMarkers']
        );
    }

    /**
     * Add the map tab to section navigations.
     *
     * Event $event
     */
    public function addMapTab(Event $event)
    {
        $view = $event->getTarget();
        if ('view.show.section_nav' === $event->getName()) {
            // Don't render the mapping tab if there is no mapping data.
            $itemJson = $event->getParam('resource')->jsonSerialize();
            if (!isset($itemJson['o-module-mapping:marker'])
                && !isset($itemJson['o-module-mapping:mapping'])
            ) {
                return;
            }
        }
        $sectionNav = $event->getParam('section_nav');
        $sectionNav['mapping-section'] = $view->translate('Mapping');
        $event->setParam('section_nav', $sectionNav);
    }

    /**
     * Add the mapping and marker data to the item JSON-LD.
     *
     * Event $event
     */
    public function filterItemJsonLd(Event $event)
    {
        $item = $event->getTarget();
        $jsonLd = $event->getParam('jsonLd');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        // Add mapping data.
        $response = $api->search('mappings', ['item_id' => $item->id()]);
        foreach ($response->getContent() as $mapping) {
            // There's zero or one mapping per item.
            $jsonLd['o-module-mapping:mapping'] = $mapping->getReference();
        }
        // Add marker data.
        $response = $api->search('mapping_markers', ['item_id' => $item->id()]);
        foreach ($response->getContent() as $marker) {
            // There's zero or more markers per item.
            $jsonLd['o-module-mapping:marker'][] = $marker->getReference();
        }

        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Handle hydration for mapping data.
     *
     * @param Event $event
     */
    public function handleMapping(Event $event)
    {
        $itemAdapter = $event->getTarget();
        $request = $event->getParam('request');

        if (!$itemAdapter->shouldHydrate($request, 'o-module-mapping:mapping')) {
            return;
        }

        $mappingsAdapter = $itemAdapter->getAdapter('mappings');
        $mappingData = $request->getValue('o-module-mapping:mapping', []);

        $mappingId = null;
        $bounds = null;

        if (isset($mappingData['o:id']) && is_numeric($mappingData['o:id'])) {
            $mappingId = $mappingData['o:id'];
        }
        if (isset($mappingData['o-module-mapping:bounds'])
            && '' !== trim($mappingData['o-module-mapping:bounds'])
        ) {
            $bounds = $mappingData['o-module-mapping:bounds'];
        }

        if (null === $bounds) {
            // This request has no mapping data. If a mapping for this item
            // exists, delete it. If no mapping for this item exists, do nothing.
            if (null !== $mappingId) {
                // Delete mapping
                $subRequest = new \Omeka\Api\Request('delete', 'mappings');
                $subRequest->setId($mappingId);
                $mappingsAdapter->deleteEntity($subRequest);
            }
        } else {
            // This request has mapping data. If a mapping for this item exists,
            // update it. If no mapping for this item exists, create it.
            if ($mappingId) {
                // Update mapping
                $subRequest = new \Omeka\Api\Request('update', 'mappings');
                $subRequest->setId($mappingData['o:id']);
                $subRequest->setContent($mappingData);
                $mapping = $mappingsAdapter->findEntity($mappingData['o:id'], $subRequest);
                $mappingsAdapter->hydrateEntity($subRequest, $mapping, new \Omeka\Stdlib\ErrorStore);
            } else {
                // Create mapping
                $subRequest = new \Omeka\Api\Request('create', 'mappings');
                $subRequest->setContent($mappingData);
                $mapping = new \Mapping\Entity\Mapping;
                $mapping->setItem($event->getParam('entity'));
                $mappingsAdapter->hydrateEntity($subRequest, $mapping, new \Omeka\Stdlib\ErrorStore);
                $mappingsAdapter->getEntityManager()->persist($mapping);
            }
        }
    }

    /**
     * Handle hydration for marker data.
     *
     * @param Event $event
     */
    public function handleMarkers(Event $event)
    {
        $itemAdapter = $event->getTarget();
        $request = $event->getParam('request');

        if (!$itemAdapter->shouldHydrate($request, 'o-module-mapping:marker')) {
            return;
        }

        $item = $event->getParam('entity');
        $entityManager = $itemAdapter->getEntityManager();
        $markersAdapter = $itemAdapter->getAdapter('mapping_markers');
        $retainMarkerIds = [];

        // Create/update markers passed in the request.
        foreach ($request->getValue('o-module-mapping:marker', []) as $markerData) {
            if (isset($markerData['o:id'])) {
                $subRequest = new \Omeka\Api\Request('update', 'mapping_markers');
                $subRequest->setId($markerData['o:id']);
                $subRequest->setContent($markerData);
                $marker = $markersAdapter->findEntity($markerData['o:id'], $subRequest);
                $markersAdapter->hydrateEntity($subRequest, $marker, new \Omeka\Stdlib\ErrorStore);
                $retainMarkerIds[] = $marker->getId();
            } else {
                $subRequest = new \Omeka\Api\Request('create', 'mapping_markers');
                $subRequest->setContent($markerData);
                $marker = new \Mapping\Entity\MappingMarker;
                $marker->setItem($item);
                $markersAdapter->hydrateEntity($subRequest, $marker, new \Omeka\Stdlib\ErrorStore);
                $entityManager->persist($marker);
            }
        }

        // Delete existing markers not passed in the request.
        $existingMarkers = [];
        if ($item->getId()) {
            $dql = 'SELECT mm FROM Mapping\Entity\MappingMarker mm INDEX BY mm.id WHERE mm.item = ?1';
            $query = $entityManager->createQuery($dql)->setParameter(1, $item->getId());
            $existingMarkers = $query->getResult();
        }
        foreach ($existingMarkers as $existingMarkerId => $existingMarker) {
            if (!in_array($existingMarkerId, $retainMarkerIds)) {
                $entityManager->remove($existingMarker);
            }
        }
    }
}

