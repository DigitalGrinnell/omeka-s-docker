<?php
namespace ExtractText\Service\Extractor;

use ExtractText\Extractor\Catdoc;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class CatdocFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Catdoc($services->get('Omeka\Cli'));
    }
}
