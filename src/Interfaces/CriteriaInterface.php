<?php

namespace PhpPond\Interfaces;


use Doctrine\ORM\QueryBuilder;

/**
 * Interface CriteriaInterface
 *
 * @package PhpPond\Interfaces
 * @author nick
 */
interface CriteriaInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return mixed
     */
    public function apply($queryBuilder);
}
