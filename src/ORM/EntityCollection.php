<?php

namespace PhpPond\ORM;


use Doctrine\Common\Collections\ArrayCollection;

use PhpPond\Common\LazyCollectionTrait,
    PhpPond\ORM\EntityRepositoryInterface as Repository;

/**
 * Class EntityCollection
 *
 * @package PhpPond\ORM
 * @author nick
 */
class EntityCollection extends ArrayCollection
{
    use LazyCollectionTrait;

    /** @var Repository|EntityEntityRepository */
    private $repository;

    /** @var bool */
    private $isInit = false;

    /** @var null|int */
    private $total = null;

    /**
     * @param array $elements
     */
    public function __construct(array $elements = array())
    {
        parent::__construct($elements);

        // if Collection was init with data already that is mean no lazy loading required
        $numArgs = func_num_args();
        if ($numArgs >= 1) {
            $this->isInit = true;
            $this->total = $this->count();
        }
    }

    /**
     * Add criteria for data which should be selected
     *
     * @param CriteriaInterface $criteria
     *
     * @return EntityCollection|$this
     */
    public function criteria(CriteriaInterface $criteria)
    {
        $this->repository->criteria($criteria, $this);

        return $this;
    }

    /**
     * @return integer
     */
    public function total()
    {
        if ($this->total === null) {
            $this->total = $this->repository->getTotal($this);
        }

        return $this->total;
    }

    /**
     * @param Repository|EntityEntityRepository $repository
     */
    public function setRepository(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return Repository|EntityEntityRepository
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    /**
     * Allows to populate Collection with elements.
     * Clears all previous data before
     *
     * @param array $array
     */
    public function fromArray(array $array)
    {
        $this->clear();
        foreach ($array as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Load data from DB if it not loaded yet
     */
    protected function init()
    {
        if ($this->isInit) {
            return;
        }
        $this->isInit = true;
        $this->repository->findFor($this);
    }

    /**
     * detach from repository
     *
     * @return static
     */
    public function detach()
    {
        $this->repository->detach($this);

        return $this;
    }
}
