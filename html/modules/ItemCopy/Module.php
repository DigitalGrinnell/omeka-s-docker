<?php
namespace ItemCopy;
use Omeka\Module\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
/**
 * ItemCopy
 */
/**
 * The ItemCopy plugin.
 */
class Module extends AbstractModule
{
   
    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach('Omeka\Controller\Admin\Item',
            'view.browse.after', array($this, 'addItemCopyJs'));
    }

    public function addItemCopyJs(Event $event) {
        $view = $event->getTarget();
         $view->headScript()->appendFile($view->assetUrl('item-copy.js', 'ItemCopy'));
    }
}
