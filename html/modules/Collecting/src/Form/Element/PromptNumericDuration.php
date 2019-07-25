<?php
namespace Collecting\Form\Element;

use NumericDataTypes\Form\Element\Duration as DurationElement;
use Zend\InputFilter\InputProviderInterface;

class PromptNumericDuration extends DurationElement implements InputProviderInterface
{
    use PromptIsRequiredTrait;

    public function getInputSpecification()
    {
        return [
            'required' => $this->required,
        ];

    }
}
