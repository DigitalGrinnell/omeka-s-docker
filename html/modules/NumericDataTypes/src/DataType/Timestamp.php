<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use NumericDataTypes\Entity\NumericDataTypesNumber;
use NumericDataTypes\Form\Element\Timestamp as TimestampElement;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Zend\View\Renderer\PhpRenderer;

class Timestamp extends AbstractDateTimeDataType
{
    public function getName()
    {
        return 'numeric:timestamp';
    }

    public function getLabel()
    {
        return 'Timestamp';
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        if (!$this->isValid(['@value' => $value->value()])) {
            return ['@value' => $value->value()];
        }
        $date = $this->getDateTimeFromValue($value->value());
        $type = null;
        if (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute']) && isset($date['second']) && isset($date['offset_value'])) {
            $type = 'http://www.w3.org/2001/XMLSchema#dateTime';
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute']) && isset($date['offset_value'])) {
            $type = 'http://www.w3.org/2001/XMLSchema#dateTime';
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['offset_value'])) {
            $type = 'http://www.w3.org/2001/XMLSchema#dateTime';
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute']) && isset($date['second'])) {
            $type = 'http://www.w3.org/2001/XMLSchema#dateTime';
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour']) && isset($date['minute'])) {
            $type = null; // XSD has no datatype for truncated seconds
        } elseif (isset($date['month']) && isset($date['day']) && isset($date['hour'])) {
            $type = null; // XSD has no datatype for truncated minutes/seconds
        } elseif (isset($date['month']) && isset($date['day'])) {
            $type = 'http://www.w3.org/2001/XMLSchema#date';
        } elseif (isset($date['month'])) {
            $type = 'http://www.w3.org/2001/XMLSchema#gYearMonth';
        } else {
            $type = 'http://www.w3.org/2001/XMLSchema#gYear';
        }
        $jsonLd = ['@value' => $value->value()];
        if ($type) {
            $jsonLd['@type'] = $type;
        }
        return $jsonLd;
    }

    public function form(PhpRenderer $view)
    {
        $element = new TimestampElement('numeric-timestamp-value');
        $element->getValueElement()->setAttribute('data-value-key', '@value');
        return $view->formElement($element);
    }

    public function isValid(array $valueObject)
    {
        try {
            $this->getDateTimeFromValue($valueObject['@value']);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        return true;
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        // Store the datetime in ISO 8601, allowing for reduced accuracy.
        $date = $this->getDateTimeFromValue($valueObject['@value']);
        $value->setValue($date['date']->format($date['format_iso8601']));
        $value->setLang(null);
        $value->setUri(null);
        $value->setValueResource(null);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        if (!$this->isValid(['@value' => $value->value()])) {
            return $value->value();
        }
        // Render the datetime, allowing for reduced accuracy.
        $date = $this->getDateTimeFromValue($value->value());
        return $date['date']->format($date['format_render']);
    }

    public function getEntityClass()
    {
        return 'NumericDataTypes\Entity\NumericDataTypesTimestamp';
    }

    public function setEntityValues(NumericDataTypesNumber $entity, Value $value)
    {
        $date = $this->getDateTimeFromValue($value->getValue());
        $entity->setValue($date['date']->getTimestamp());
    }

    /**
     * numeric => [
     *   ts => [
     *     lt => [val => <date>, pid => <propertyID>],
     *     gt => [val => <date>, pid => <propertyID>],
     *   ],
     * ]
     */
    public function buildQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query)
    {
        if (isset($query['numeric']['ts']['lt']['val'])
            && isset($query['numeric']['ts']['lt']['pid'])
            && is_numeric($query['numeric']['ts']['lt']['pid'])
        ) {
            $value = $query['numeric']['ts']['lt']['val'];
            $propertyId = $query['numeric']['ts']['lt']['pid'];
            if ($this->isValid(['@value' => $value])) {
                $date = $this->getDateTimeFromValue($value);
                $number = $date['date']->getTimestamp();
                $this->addLessThanQuery($adapter, $qb, $propertyId, $number);
            }
        }
        if (isset($query['numeric']['ts']['gt']['val'])
            && isset($query['numeric']['ts']['gt']['pid'])
            && is_numeric($query['numeric']['ts']['gt']['pid'])
        ) {
            $value = $query['numeric']['ts']['gt']['val'];
            $propertyId = $query['numeric']['ts']['gt']['pid'];
            if ($this->isValid(['@value' => $value])) {
                $date = $this->getDateTimeFromValue($value);
                $number = $date['date']->getTimestamp();
                $this->addGreaterThanQuery($adapter, $qb, $propertyId, $number);
            }
        }
    }

    public function sortQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query, $type, $propertyId)
    {
        if ('timestamp' === $type) {
            $alias = $adapter->createAlias();
            $qb->addSelect("MIN($alias.value) as HIDDEN numeric_value");
            $qb->leftJoin(
                $this->getEntityClass(), $alias, 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                    $qb->expr()->eq("$alias.property", $propertyId)
                )
            );
            $qb->addOrderBy('numeric_value', $query['sort_order']);
        }
    }
}
