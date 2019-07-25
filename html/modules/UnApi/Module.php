<?php
namespace UnApi;

use Omeka\Module\AbstractModule;
use Zend\EventManager\EventInterface;
use Zend\EventManager\SharedEventManagerInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager) {
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.layout',
            function (EventInterface $event) {
                $event->getTarget()->headLink(array(
                    'rel'   => 'unapi-server',
                    'type'  => 'application/xml',
                    'title' => 'unAPI',
                    'href'  => $event->getTarget()->url('unapi'),
                ));
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.browse.after',
            function (EventInterface $event) {
                $items = $event->getTarget()->items;
                foreach ($items as $item) {
                    echo sprintf('<abbr class="unapi-id" title="%s"></abbr>', $item->id());
                }
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.after',
            function (EventInterface $event) {
                $item = $event->getTarget()->item;
                echo sprintf('<abbr class="unapi-id" title="%s"></abbr>', $item->id());
            }
        );
    }
}

