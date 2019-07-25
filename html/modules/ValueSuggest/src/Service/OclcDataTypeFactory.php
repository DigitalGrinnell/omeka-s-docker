<?php
namespace ValueSuggest\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class OclcDataTypeFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $name = ucfirst(strtolower(substr($requestedName, strrpos($requestedName, ':') + 1)));
        $dataType = sprintf('ValueSuggest\DataType\Oclc\%s', $name);
        return new $dataType($services);
    }
}
