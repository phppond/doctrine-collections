<?php

namespace PhpPond\ORM;


use SplObjectStorage,
    RuntimeException,
    InvalidArgumentException;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\EntityRepository as DoctrineRepository,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Tools\Pagination\Paginator;

use PhpPond\Filters\FilterInterface,
    PhpPond\Filters\SqlFilterCriteria,
    PhpPond\Interfaces\EntityRepositoryInterface;

/**
 * Class DoctrineRepository
 *
 * @package CreativeMedia\ORM
 * @author nick
 */
class EntityEntityRepository extends DoctrineRepository implements EntityRepositoryInterface
{
    /** @internal */
    const ALIAS = 'entity';

    /**
     * @var SplObjectStorage|\Doctrine\ORM\QueryBuilder[]
     */
    private $collections;

    /**
     * @var SplObjectStorage|Paginator[]
     *
     * TODO: pagination could depend on QueryBuilder instance
     */
    private $paginations;

    /**
     * @param EntityManager $em
     * @param ClassMetadata $class
     */
    public function __construct($em, ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->collections = new SplObjectStorage();
        $this->paginations = new SplObjectStorage();
    }

    /**
     * @param string|null $alias
     * @param string|null $indexBy
     *
     * @return EntityCollection
     */
    public function all($alias = null, $indexBy = null)
    {
        $collection = $this->createCollection();
        $queryBuilder = $this->getQueryBuilderFor($collection);
        empty($alias) AND $alias = static::ALIAS;

        $queryBuilder->select($alias);
        $queryBuilder->from($this->getEntityName(), $alias, $indexBy);

        return $collection;
    }

    /**
     * @param FilterInterface $filter
     *
     * @return EntityCollection
     */
    public function findByFilter(FilterInterface $filter)
    {
        $collection = $this->all();
        $this->filterBy($filter, $collection);

        return $collection;
    }

    /**
     * @param EntityCollection $collection
     *
     * @return \Doctrine\ORM\QueryBuilder
     *
     * @throws InvalidArgumentException
     */
    protected function getQueryBuilderFor(EntityCollection $collection)
    {
        if (!$this->collections->contains($collection)) {
            throw new InvalidArgumentException('Collection detached from repository');
        }

        return $this->collections[$collection];
    }

    /**
     * @param EntityCollection $collection
     *
     * @return Paginator
     */
    protected function getPaginatorFor(EntityCollection $collection)
    {
        if (!$this->paginations->contains($collection)) {
            $queryBuilder = $this->getQueryBuilderFor($collection);
            $paginator = new Paginator($queryBuilder);
            $this->paginations->attach($collection, $paginator);
        }

        return $this->paginations[$collection];
    }

    /**
     * Specify additional Criteria for data selection
     *
     * @param CriteriaInterface $criteria
     * @param EntityCollection  $collection
     *
     * @return void
     */
    public function criteria(CriteriaInterface $criteria, EntityCollection $collection)
    {
        $criteria->apply($this->getQueryBuilderFor($collection));
    }

    /**
     * @param FilterInterface  $filter
     * @param EntityCollection $collection
     *
     * @return EntityCollection
     */
    public function filterBy(FilterInterface $filter, EntityCollection $collection)
    {
        $criteria = new SqlFilterCriteria($filter, $this->getFilterProperties($filter));
        $collection->criteria($criteria);

        return $collection;
    }

    /**
     * @param FilterInterface $filter
     *
     * @return array
     *
     * @throws RuntimeException
     */
    protected function getFilterProperties(FilterInterface $filter)
    {
        if (count($filter->getConditions()) === 0) {
            return [];
        }

        throw new RuntimeException('please define filter properties');
    }

    /**
     * Create EntityCollection and associate new QueryBuilder with it
     *
     * @return EntityCollection
     */
    protected function createCollection()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $collection = $this->newCollection();
        $this->collections->attach($collection, $qb);
        $collection->setRepository($this);

        return $collection;
    }

    /**
     * Lazy load data from DB to appropriate QueryBuilder specified by Collection
     *
     * @param EntityCollection $collection
     */
    public function findFor(EntityCollection $collection)
    {
        $qb = $this->getQueryBuilderFor($collection);
        $result = $qb->getQuery()->execute();
        is_array($result) or $result = array();
        $collection->fromArray($result);
        //  Collection is do not detached to be able get total()
    }

    /**
     * @param EntityCollection $collection
     *
     * @return integer
     */
    public function getTotal(EntityCollection $collection)
    {
        $paginator = $this->getPaginatorFor($collection);
        $count = count($paginator);

        return (int) $count;
    }

    /**
     * @param EntityCollection $collection
     */
    public function detach(EntityCollection $collection)
    {
        $this->paginations->detach($collection);
        $this->collections->detach($collection);
    }

    /**
     * Creates new instance of EntityCollection
     * This method should be overwritten in case when Collection should be another class
     * For example if Collection contains some business logic
     *
     * @return EntityCollection
     */
    protected function newCollection()
    {
        return new EntityCollection();
    }
}
