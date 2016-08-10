<?php
namespace PhpPond\Filters;

use Doctrine\ORM\Query\Expr;

/**
 * Class DoctrineFilterCompiler
 *
 * @package PhpPond\Filters
 * @author nick
 */
class DoctrineFilterCompiler extends FilterCompiler
{

    /**
     * Returns the select fields as a comma-separated string
     *
     * @throws \Exception
     */
    public function getSelect()
    {
        throw new \Exception('SELECT not ready');
    }

    /**
     * @return string The compiled query which can be run to get a count of the number of rows
     *
     * @throws \Exception
     */
    public function getWhere()
    {
        throw new \Exception('WHERE not ready');
    }

    /**
     * @param string $mapped
     * @param bool   $isAscending
     *
     * @return Expr\OrderBy
     */
    protected function compileOrdering($mapped, $isAscending)
    {
        return new Expr\OrderBy($mapped, $isAscending ? 'ASC' : 'DESC');
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
     * @return string
     *
     * @throws \Exception
     */
    public function getHaving()
    {
        throw new \Exception('HAVING not ready');
    }

}
