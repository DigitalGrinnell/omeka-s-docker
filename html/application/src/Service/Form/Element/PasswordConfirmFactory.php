<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Omeka\Form\Element\PasswordConfirm;
use Zend\ServiceManager\Factory\FactoryInterface;

class PasswordConfirmFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        $passwordConfig = isset($config['password']) ? $config['password'] : [];
        $fieldset = new PasswordConfirm;
        $fieldset->setPasswordConfig($passwordConfig);
        return $fieldset;
    }
}
