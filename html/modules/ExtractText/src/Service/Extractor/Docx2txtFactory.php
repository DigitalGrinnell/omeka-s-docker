<?php
namespace ExtractText\Service\Extractor;

use ExtractText\Extractor\Docx2txt;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class Docx2txtFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Docx2txt($services->get('Omeka\Cli'));
    }
}
