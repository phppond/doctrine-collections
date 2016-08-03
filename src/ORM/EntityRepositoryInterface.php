<?php

namespace PhpPond\ORM;


use PhpPond\Filters\FilterInterface;

/**
 * Interface RepositoryInterface
 *
 * @package PhpPond\ORM
 * @author nick
 */
interface EntityRepositoryInterface
{

    /**
     * @param string|mixed|null $alias
     * @param string|mixed|null $indexBy
     *
     * @return EntityCollection
     */
    public function all($alias = null, $indexBy = null);

    /**
     * @param FilterInterface $filter
     *
     * @return EntityCollection
     */
    public function findByFilter(FilterInterface $filter);

    /**
     * Specify additional Criteria for data selection
     *
     * @param CriteriaInterface $criteria
     * @param EntityCollection  $collection
     *
     * @return void
     */
    public function criteria(CriteriaInterface $criteria, EntityCollection $collection);

    /**
     * Lazy load data from DB to appropriate QueryBuilder specified by Collection
     *
     * @param EntityCollection $collection
     */
    public function findFor(EntityCollection $collection);

    /**
     * @param EntityCollection $collection
     */
    public function detach(EntityCollection $collection);

}
