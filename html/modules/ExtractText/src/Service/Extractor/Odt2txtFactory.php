<?php
namespace ExtractText\Service\Extractor;

use ExtractText\Extractor\Odt2txt;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class Odt2txtFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Odt2txt($services->get('Omeka\Cli'));
    }
}
