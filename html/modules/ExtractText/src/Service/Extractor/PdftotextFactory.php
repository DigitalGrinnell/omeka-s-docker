<?php
namespace ExtractText\Service\Extractor;

use ExtractText\Extractor\Pdftotext;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PdftotextFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Pdftotext($services->get('Omeka\Cli'));
    }
}
