<?php

namespace UniversalViewer\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use UniversalViewer\View\Helper\UniversalViewer;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Service factory for the UniversalViewer view helper.
 */
class UniversalViewerFactory implements FactoryInterface
{
    /**
     * Create and return the UniversalViewer view helper
     *
     * @return UniversalViewer
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $currentTheme = $serviceLocator->get('Omeka\Site\ThemeManager')
            ->getCurrentTheme();
        return new UniversalViewer($currentTheme);
    }
}
