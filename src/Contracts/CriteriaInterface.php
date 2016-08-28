<?php

namespace Ideasvn\Repositories\Contracts;
use Ideasvn\Repositories\Criteria\Criteria;

/**
 * Interface CriteriaInterface
 * @package Ideasvn\Repository\Contracts
 */
interface CriteriaInterface {
    /**
     * @param bool $status
     * @return mixed
     */
    public function skipCriteria($status = true);

    /**
     * @return mixed
     */
    public function getCriteria();

    /**
     * @param Criteria $criteria
     * @return mixed
     */
    public function getByCriteria(Criteria $criteria);

    /**
     * @param Criteria $criteria
     * @return mixed
     */
    public function pushCriteria(Criteria $criteria);

    /**
     * @return mixed
     */
    public function applyCriteria();
}