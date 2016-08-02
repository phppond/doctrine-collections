<?php
namespace PhpPond\ORM;


use PhpPond\Common\LazyCollectionTrait;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class EntityCollection
 *
 * @package PhpPond\ORM
 * @author nick
 */
class EntityCollection extends ArrayCollection
{
    use LazyCollectionTrait;

    /**
     * @var EntityRepository
     */
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
        }
    }

    /**
     * @param EntityRepository $repository
     */
    public function setRepository(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        return $this->repository;
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
