<?php

namespace Ideasvn\Repositories\Eloquent;

use Ideasvn\Repositories\Contracts\CriteriaInterface;
use Ideasvn\Repositories\Contracts\RepositoryInterface;
use Ideasvn\Repositories\Criteria\Criteria;
use Ideasvn\Repositories\Exceptions\RepositoryException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Container\Container as App;
use Illuminate\Database\Eloquent\Model;

abstract class Repository implements RepositoryInterface, CriteriaInterface
{
    /**
     * @var App
     */
    private $app;

    /**
     * @var
     */
    protected $model;

    /**
     * @var
     */
    protected $newModel;

    /**
     * @var Collection
     */
    protected $criteria;

    /**
     * @var bool
     */
    protected $skipCriteria = false;

    /**
     * @var bool
     */
    protected $preventCriteriaOverwriting = true;

    public function __construct(App $app, Collection $collection)
    {
        $this->app = $app;
        $this->criteria = $collection;
        $this->resetScope();
        $this->makeModel();
    }

    /**
     * @return mixed
     */
    public abstract function model();

    /**
     * @param array $columns
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        $this->applyCriteria();
        return $this->model->get($columns);
    }

    /**
     * @param array $relations
     * @return $this
     */
    public function with(array $relations)
    {
        $this->model = $this->model->with($relations);
        return $this;
    }

    public function lists($value, $key = null)
    {
        $this->applyCriteria();
        $lists = $this->model->lists($value, $key);
        if (is_array($lists)) {
            return $lists;
        }
        return $lists->all();
    }

    /**
     * @param int $perPage
     * @param array $columns
     * @return mixed
     */
    public function paginate($perPage = 15, $columns = ['*'])
    {
        $this->applyCriteria();
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function saveModel(array $data)
    {
        foreach ($data as $k => $v) {
            $this->model->$k = $v;
        }
        return $this->model->save();
    }

    /**
     * @param array $data
     * @param $id
     * @param string $attribute
     * @return mixed
     */
    public function update(array $data, $id, $attribute = 'id') {
        return $this->model->where($attribute, '=', $id)->update($data);
    }

    /**
     * @param array $data
     * @param $id
     * @return bool
     */
    public function updateRich(array $data, $id) {
        if(!($model = $this->model->find($id))) {
            return false;
        }

        return $model->fill($data)->save();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id) {
        return $this->model->destroy($id);
    }

    /**
     * @param $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, $columns = ['*']) {
        $this->applyCriteria();
        return $this->model->find($id, $columns);
    }

    /**
     * @param $attribute
     * @param $value
     * @param array $columns
     * @return mixed
     */
    public function findBy($attribute, $value, $columns = ['*']) {
        $this->applyCriteria();
        return $this->model->where($attribute, '=', $value)->first($columns);
    }

    /**
     * @param $attribute
     * @param $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = ['*'])
    {
        $this->applyCriteria();
        return $this->model->where($attribute, '=', $value)->get($columns);
    }

    public function findWhere($where, $columns = ['*'], $or = false)
    {
        $this->applyCriteria();

        $model = $this->model;

        foreach ($where as $field => $value) {
            if($value instanceof \Closure) {
                $model = (!$or) ? $model->where($value) : $model->orWhere($value);
            } elseif(is_array($value)) {
                if(count($value) === 3) {
                    list($field, $operator, $search) = $value;
                    $model = (!$or) ? $model->where($field, $operator, $search) : $model->orWhere($field, $operator, $search);
                } elseif(count($value) === 2) {
                    list($field, $search) = $value;
                    $model = (!$or) ? $model->where($field, '=', $search) : $model->orWhere($field, '=', $search);
                }
            } else {
                $model = (!$or) ? $model->where($field, '=', $value) : $model->orWhere($field, '=', $value);
            }
        }

        return $model->get($columns);
    }

    /**
     * @return Model
     */
    public function makeModel() {
        return $this->setModel($this->model());
    }

    /**
     * @param $eloquentModel
     * @return Model
     * @throws RepositoryException
     */
    public function setModel($eloquentModel) {
        $this->newModel = $this->app->make($eloquentModel);
        if(!$this->newModel instanceof Model) {
            throw new RepositoryException("Class {$this->newModel} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $this->newModel;
    }

    /**
     * @return $this
     */
    public function resetScope() {
        $this->skipCriteria(false);
        return $this;
    }

    /**
     * @param bool $status
     * @return $this
     */
    public function skipCriteria($status = true)
    {
        $this->skipCriteria = $status;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param Criteria $criteria
     * @return $this
     */
    public function getByCriteria(Criteria $criteria)
    {
        $this->model = $criteria->apply($this->model, $this);
        return $this;
    }

    /**
     * @param Criteria $criteria
     * @return $this
     */
    public function pushCriteria(Criteria $criteria)
    {
        if($this->preventCriteriaOverwriting) {
            // Find existing criteria
            $key = $this->criteria->search(function ($item) use ($criteria) {
               return is_object($item) && (get_class($item) == get_class($criteria));
            });

            //Remove old criteria
            if(is_int($key)) {
                $this->criteria->offsetUnset($key);
            }
        }

        $this->criteria->push($criteria);
        return $this;
    }

    public function applyCriteria()
    {
        if($this->skipCriteria() === true) {
            return $this;
        }

        foreach ($this->getCriteria() as $criteria) {
            if($criteria instanceof Criteria) {
                $this->model = $criteria->apply($this->model, $this);
            }
        }
        return $this;
    }
}