<?php

namespace PhpPond\Filters;


/**
 * Class SqlFilterCompiler
 *
 * @package PhpPondFilters\Interfaces
 *
 * @author  Nick G. Lavrik <nick.lavrik@gmail.com>
 */
class SqlFilterCompiler extends FilterCompiler
{

    /**
     * Returns the select fields as a comma-separated string
     *
     * @param bool $withKeyword
     *
     * @return string
     *
     * @deprecated
     */
    public function getSelect($withKeyword = true)
    {
        if (empty($this->select)) {
            return '';
        }

        return $withKeyword ? 'SELECT ' . implode(', ', $this->select) : $this->select;
    }

    /**
     * @param bool $withKeyword
     *
     * @return string
     */
    public function getOrder($withKeyword = true)
    {
        $order = parent::getOrder();

        if (empty($order)) {
            return '';
        }

        $order = implode(', ', $order);

        return $withKeyword ? 'ORDER BY ' . $order : $order;
    }

    /**
     * @param bool $withKeyword
     *
     * @return string
     */
    public function getLimit($withKeyword = true)
    {
        if (empty($this->limit)) {
            return '';
        }

        return $withKeyword ? 'LIMIT ' . $this->limit : $this->limit;
    }

    /**
     * @param boolean $withKeyword
     *
     * @return string The compiled query which can be run to get a count of the number of rows
     */
    public function getWhere($withKeyword = true)
    {
        if (empty($this->where)) {
            return '';
        }

        return $withKeyword ? 'WHERE ' . $this->where : $this->where;
    }

    /**
     * @param bool $withKeyword
     *
     * @return string
     */
    public function getHaving($withKeyword = true)
    {
        if (empty($this->having)) {
            return '';
        }

        return $withKeyword ? 'HAVING ' . $this->having : $this->having;
    }

}
