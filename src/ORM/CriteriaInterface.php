<?php

namespace PhpPond\ORM;


use Doctrine\ORM\QueryBuilder;

/**
 * Interface CriteriaInterface
 *
 * @package PhpPond\ORM
 * @author nick
 */
interface CriteriaInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return mixed
     */
    public function apply(QueryBuilder $queryBuilder);
}
