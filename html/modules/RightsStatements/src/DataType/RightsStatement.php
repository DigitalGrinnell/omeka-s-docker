<?php
namespace RightsStatements\DataType;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\DataType\Uri;
use Omeka\Entity\Value;
use Zend\Form\Element\Select;
use Zend\View\Renderer\PhpRenderer;

class RightsStatement extends Uri
{
    /**
     * @var array
     */
    protected $statements = [
        'http://rightsstatements.org/vocab/InC/1.0/' =>
            'In Copyright',
        'http://rightsstatements.org/vocab/InC-OW-EU/1.0/' =>
            'In Copyright - EU Orphan Work',
        'http://rightsstatements.org/vocab/InC-EDU/1.0/' =>
            'In Copyright - Educational Use Permitted',
        'http://rightsstatements.org/vocab/InC-NC/1.0/' =>
            'In Copyright - Non-Commercial Use Permitted',
        'http://rightsstatements.org/vocab/InC-RUU/1.0/' =>
            'In Copyright - Rights-Holder(s) Unlocatable or Unidentifiable',
        'http://rightsstatements.org/vocab/NoC-CR/1.0/' =>
            'No Copyright - Contractual Restrictions',
        'http://rightsstatements.org/vocab/NoC-NC/1.0/' =>
            'No Copyright - Non-Commercial Use Only',
        'http://rightsstatements.org/vocab/NoC-OKLR/1.0/' =>
            'No Copyright - Other Known Legal Restrictions',
        'http://rightsstatements.org/vocab/NoC-US/1.0/' =>
            'No Copyright - United States',
        'http://rightsstatements.org/vocab/CNE/1.0/' =>
            'Copyright Not Evaluated',
        'http://rightsstatements.org/vocab/UND/1.0/' =>
            'Copyright Undetermined',
        'http://rightsstatements.org/vocab/NKC/1.0/' =>
            'No Known Copyright',
    ];

    public function getName()
    {
        return 'rights_statement';
    }

    public function getLabel()
    {
        return 'Rights Statement';
    }

    public function form(PhpRenderer $view)
    {
        $select = new Select('rights-statement');
        $select
            ->setAttribute('data-value-key', '@id')
            ->setEmptyOption('Select Below')
            ->setValueOptions($this->statements);
        return $view->formSelect($select);

    }

    public function isValid(array $valueObject)
    {
        return parent::isValid($valueObject) && isset($this->statements[$valueObject['@id']]);
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $uri = $valueObject['@id'];
        if (isset($this->statements[$uri])) {
            $valueObject['o:label'] = $this->statements[$uri];
        }
        parent::hydrate($valueObject, $value, $adapter);
    }
}
