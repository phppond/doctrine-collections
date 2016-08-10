<?php

namespace PhpPond\Filters;


use InvalidArgumentException;

use PhpPond\Interfaces\FilterInterface;
use PhpPond\Interfaces\FilterCompilerInterface;

/**
 * Class FilterCompiler
 *
 * @package PhpPond\Filters
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class FilterCompiler implements FilterCompilerInterface
{
    /** @var array */
    protected $select = [];
    /** @var  string */
    protected $where;
    /** @var  array */
    protected $order;
    /** @var  string */
    protected $limit;
    /** @var  string */
    protected $having;

    /** @var array */
    protected $parameters = [ ];
    /** @var string */
    protected $parameterPrefix = 'p';

    /** @var array */
    protected $propertyMap;

    /**
     * Allows us to compile filters which refer to non-existent properties
     * This is useful in some edge cases
     *
     * @var boolean
     */
    protected $skipMissingProperties = false;

    /**
     * @param array           $propertyMap
     * @param FilterInterface $filter
     *
     * @throws InvalidArgumentException
     */
    public function compile(array $propertyMap, FilterInterface $filter)
    {
        // SELECT $fieldList FROM $tableNames WHERE [conditions] ORDER BY [orderings] [limit]
        // SELECT COUNT(*) FROM $tableNames WHERE [conditions] ORDER BY [orderings] [limit]
        // Calculate the WHERE clause
        $this->reset($propertyMap);
        $this->compileFilterSelect($filter);
        $this->compileFilterConditions($filter);
        $this->compileFilterOrderings($filter);
        $this->compileFilterLimit($filter);
    }

    /**
     * Return an array of key/value pairs to bind to the prepared query
     * array(array(key,value))
     *
     * @return array<array<string>>
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param boolean $isSkip
     */
    public function setSkipMissingProperties($isSkip)
    {
        $this->skipMissingProperties = $isSkip;
    }

    /**
     * @param string $prefix
     */
    public function setParameterPrefix($prefix = 'p')
    {
        $this->parameterPrefix = $prefix;
    }

    /**
     * @param array $propertyMap
     */
    protected function reset(array $propertyMap)
    {
        $this->select = [];
        $this->where = '';
        $this->having = '';
        $this->order = [];
        $this->limit = '';
        $this->parameters = [];

        $this->propertyMap = $propertyMap;
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    private function isSkipProperty($property)
    {
        return (bool) $this->skipMissingProperties
            && !array_key_exists($property, $this->propertyMap);
    }

    /**
     * @param FilterInterface $filter
     */
    protected function compileFilterSelect(FilterInterface $filter)
    {
        foreach ($filter->getSelect() as $alias => $property) {
            if ($this->isSkipProperty($property)) {
                continue;
            }
            $mapped = $this->getMappedProperty($property);
            $this->select[] = $this->compileSelect($mapped, $alias);
        }
    }

    /**
     * @param string $mapped
     * @param string $alias
     *
     * @return string
     */
    protected function compileSelect($mapped, $alias)
    {
        return $mapped . ' AS ' . $alias;
    }

    /**
     * @param FilterInterface $filter
     */
    protected function compileFilterOrderings(FilterInterface $filter)
    {
        foreach ($filter->getOrderings() as $ordering) {
            list($property, $isAscending) = $ordering;

            if ($this->isSkipProperty($property)) {
                continue;
            }
            $mapped = $this->getMappedProperty($property);
            $this->order[] = $this->compileOrdering($mapped, $isAscending);
        }
    }

    /**
     * @param string $mapped
     * @param bool   $isAscending
     *
     * @return string
     */
    protected function compileOrdering($mapped, $isAscending)
    {
        return $mapped . ' ' . ($isAscending ? 'ASC' : 'DESC');
    }

    /**
     * @return array
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param FilterInterface $filter
     */
    protected function compileFilterLimit(FilterInterface $filter)
    {
        $start = $filter->getStart();
        $limit = $filter->getLimit();

        if ($start !== null && $start > 0) {
            if ($limit === null) {
                throw new InvalidArgumentException('A limit value must be supplied when start is set');
            }
            $this->limit = $start.', '.$limit;
        } else {
            if ($limit !== null) {
                $this->limit = $limit;
            }
        }
    }

    /**
     * @return string
     */
    protected function getParameterPrefix()
    {
        return $this->parameterPrefix;
    }

    /**
     * @param FilterInterface $filter
     */
    protected function compileFilterConditions(FilterInterface $filter)
    {
        $conjunction = '';

        foreach ($filter->getConditions() as $condition) {
            if ($this->isSkipProperty($condition->getPropertyName())) {
                continue;
            }

            if ($condition->isGroup()) {
                $this->compileFilterGroupCondition($condition, $conjunction);
                if (count($condition->getConditions())) {
                    $conjunction = ' AND ';
                }
            } else {
                $this->compileFilterCondition($condition, $conjunction);
                $conjunction = ' AND ';
            }
        }
    }

    /**
     * @param FilterCondition $condition
     * @param string          $conjunction
     */
    protected function compileFilterGroupCondition(FilterCondition $condition, $conjunction)
    {
        if (!count($condition->getConditions())) {
            return;
        }

        $this->where .= $conjunction . '(';
        $or = '';
        foreach ($condition->getConditions() as $orCondition) {
            $this->compileFilterCondition($orCondition, $or);
            $or = ' OR ';
        }
        $this->where .= ')';
    }

    /**
     * @param FilterCondition $condition
     * @param string          $conjunction
     *
     * @throws InvalidArgumentException
     */
    protected function compileFilterCondition(FilterCondition $condition, $conjunction)
    {
        $where = $this->compileInnerCondition($condition);

        if (!empty($where)) {
            $this->where .= $conjunction;

            if ($condition->isNegated()) {
                $this->where .= 'NOT (' . $where . ')';
            } else {
                $this->where .= $where;
            }
        }
    }

    /**
     * @param FilterCondition $condition
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileInnerCondition(FilterCondition $condition)
    {
        $mapped = $this->getMappedProperty($condition->getPropertyName());

        return $this->compileMappedCondition($condition, $mapped);
    }

    /**
     * @param FilterCondition $condition
     * @param string          $mapped
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileMappedCondition(FilterCondition $condition, $mapped)
    {

        $value = $condition->getValue();
        $where = '';

        if ($condition->isLike()) {
            $where .= $this->compileLikeCondition($mapped, $value);
        }

        if ($condition->isEquals()) {
            $where .= $this->compileEqualsCondition($mapped, $value);
        }

        if ($condition->isGT()) {
            $where .= $this->compileScalarCondition($mapped, ' > ', $value);
        }

        if ($condition->isGTE()) {
            $where .= $this->compileScalarCondition($mapped, ' >= ', $value);
        }

        if ($condition->isLT()) {
            $where .= $this->compileScalarCondition($mapped, ' < ', $value);
        }

        if ($condition->isLTE()) {
            $where .= $this->compileScalarCondition($mapped, ' <= ', $value);
        }

        if ($condition->isIn()) {
            $where .= $this->compileInCondition($mapped, (array) $value);
        }

        return $where;
    }

    /**
     * @param string $mapped
     * @param mixed  $value
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileLikeCondition($mapped, $value)
    {
        if ($value === null) {
            throw new InvalidArgumentException('NULL is not allowed for a LIKE filter');
        }

        return $mapped . ' LIKE ' . $this->pushLikeParameter($value);
    }

    /**
     * @param string $mapped
     * @param mixed  $value
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileEqualsCondition($mapped, $value)
    {
        $where = '';
        if ($value === null) {
            $where .= $mapped . ' IS NULL ';
        } else {
            $where .= $this->compileScalarCondition($mapped, '=', $value);
        }

        return $where;
    }

    /**
     * @param string $mapped
     * @param array  $value
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileInCondition($mapped, array $value)
    {
        $where = '';
        $where .= $mapped . ' IN (';
        $sComma = '';
        foreach ($value as $mInValue) {
            $where .= $sComma . $this->pushParameter($mInValue);
            $sComma = ', ';
        }
        $where .= ')';

        return $where;
    }

    /**
     * @param string $mapped
     * @param string $condition
     * @param mixed  $value
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileScalarCondition($mapped, $condition, $value)
    {
        if ($value === null) {
            throw new InvalidArgumentException('NULL is not allowed for a "' . $condition . '" filter');
        }

        /*
         * ToDo: how we can detect is $value can be resolved (scalar OR DateTime)
        if (!is_scalar($value)) {
            throw new InvalidArgumentException('A scalar is expected for a "' . $condition . '" filter');
        }
        */

        return $mapped . ' ' . $condition . ' ' . $this->pushParameter($value);
    }

    /**
     * @param $property
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function getMappedProperty($property)
    {
        if (!array_key_exists($property, $this->propertyMap)) {
            throw new InvalidArgumentException('Unknown property "' . $property . '"');
        }

        return $this->propertyMap[$property];
    }

    /**
     * Push the parameter to the array and return the identifier to use for it
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function pushParameter($value)
    {

        if ($value === null) {
            return 'NULL';
        }

        $id = $this->getParameterPrefix() . count($this->parameters);
        $this->parameters[$id] = $value;

        return ':' . $id;
    }

    /**
     * @param $value
     *
     * @return string
     */
    protected function pushLikeParameter($value)
    {
        $value = str_replace('*', '%', $value);

        return $this->pushParameter($value);
    }

}
