<?php

namespace Basttyy\FxDataServer\Models;
require_once __DIR__."\\..\\libs\\helpers.php";

use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\mysqly;
use Exception;

abstract class Model
{    
    //protected static $instance = null;
    /**
     * The connection name for the model.
     *
     * @var ConnectionInterface|null
     */
    //protected $db;

    //protected static $instance = null;
    /**
     * The connection name for the model.
     *
     * @var LazyConnectionPool|null
     */
    protected $db;

    /**
     * The query builder instance for the model
     * 
     * @var QueryBuilder
     */
    protected $builder;

    /**
     * The connection query instance for the model
     * 
     * @var Connection
     */
    protected $connection;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'created_at', 'updated_at', 'deleted_at'
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected $guarded = ['deleted_at'];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Indicates if the model was inserted during the current request lifecycle.
     *
     * @var bool
     */
    public $wasRecentlyCreated = false;

    /**
     * The name of the "created at" column.
     *
     * @var string|null
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    const UPDATED_AT = 'updated_at';

    /**
     * The child class that currently using the parent class
     * 
     * @var object
     */
    private $child;

    /**
     * Create a new model instance.
     *
     * @param array  $attributes
     * @param object $child
     * @return void
     */
    public function __construct(object $child = null)
    {
        $this->child = $child;
        mysqly::auth(env('DB_USER'), env('DB_PASSWORD'), env('DB_NAME'));
        $this->prepareModel();
    }

    protected function prepareModel()
    {
        // $this->builder->from = $this->table;
    }

    public function fill($values)
    {
        // if ($self == null) {
        //     $self = \get_called_class();
        //     self::$instance = new $self;
        // }
        // $items = self::$instance->fillable;
        // if ($self == null || count($values) < 1) {
        //     return $self;
        // }

        foreach ($this->child->fillable as $item) {
            if (Arr::exists($values, $item)) {
                $this->child->{$item} = $values[$item];
            }
        }
        return $this->child;
    }

    public function toArray($guard = true)
    {
        $result = array();
        // if ($self == null) {
        //     $self = \get_called_class();
        // }
        // if ($self == null) {
        //     return $result;
        // }
        $items = $guard ? array_diff($this->child->fillable, $this->child->guarded) : $this->child->fillable;
        $obj_props = array_diff(array_keys(get_object_vars($this->child)), [
            'fillable', 'guarded', 'table', 'primaryKey', 'exists', 'db', 'builder',
            'connection', 'keyType', 'incrementing', 'perPage', 'wasRecentlyCreated', 'child'
        ]);
        foreach ($items as $item) {
            if (Arr::exists($obj_props, $item, true)) {
                $result[$item] = $this->child->{$item};
            }
        }

        return $result;
    }
    
    /**
     * create a model from array values
     * @param array $values
     * 
     * @return PromiseInterface<array|bool>
     */
    public function create(array $values)
    {
        if (!$id = mysqly::insert($this->table, $values)) {
            return false;
        }
        if (!$model = mysqly::{$this->table}(['id' => $id], \array_diff($this->fillable, $this->guarded))) {
            return true;
        }
        return $model;
    }

    /**
     * Find a model by its id
     * @param int $id
     * 
     * @return self|bool
     */
    public function find(int $id)
    {
        $id = $id > 0 ? $id : $this->child->id;
        $query_arr = [];

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = null;
            //$this->builder->useSoftDelete = true;
        }
        $query_arr['id'] = $id;
        
        //$fields = \array_diff($this->fillable, $this->guarded);

        if (!$model = mysqly::{$this->table}($query_arr, $this->fillable)) {
            return false;
        }
        return $this->fill($model);
    }

    /**
     * Find all elements of a model
     * 
     * @return array|Exception
     */
    public function all()
    {
        
        if ($this->child->softdeletes) {
            $this->builder->where('deleted_at', null);
            //$this->builder->useSoftDelete = true;
        }

        $fields = \array_diff($this->fillable, $this->guarded);
        
        $items = $this->builder->orderBy('id')->get($fields)->then(
            function (Collection $items) {
                return $items->toArray();
            },
            function (Exception $e) {
                return $e;
            }
        );

        //print_r($users);

        return $items;
    }

    /**
     * Find a model by key and value
     * 
     * @param string $name
     * @return PromiseInterface<array|bool>
     */
    public function findBy(string $key, string $value)
    {
        if ($this->child->softdeletes) {
            $this->builder->where('deleted_at', null);
            //$this->builder->useSoftDelete = true;
        }
        
        $fields = \array_diff($this->fillable, $this->guarded);
        return $this->builder->where($key, null, $value)->get($fields);
    }

    
    /**
     * Find a model by a set of keys and values
     * 
     * @param array $keys
     * @param array $values
     * 
     * @return PromiseInterface<array|bool>
     */
    public function findByArray(array $keys, array $values)
    {
        //TODO: might need to change reject to resolve as yield seems not to handle reject.
        if (count($keys) !== count($values)) {
            return \React\Promise\reject(false);
        }

        if ($this->child->softdeletes) {
            $this->builder->where('deleted_at', null);
            //$this->builder->useSoftDelete = true;
        }

        foreach ($keys as $pos => $key) {
            $this->builder->where($key, $values[$pos]);
        }
        
        $fields = \array_diff($this->fillable, $this->guarded);
        return $this->builder->get($fields);
    }

    /**
     * Find a user by the username
     * 
     * @param string $name
     * @return PromiseInterface<self|bool>
     */
    public function findByUsername($name)
    {
        
        if ($this->child->softdeletes) {
            $this->builder->where('deleted_at', null);
            //$this->builder->useSoftDelete = true;
        }
        
        //$fields = \array_diff($this->fillable, $this->guarded);
        return $this->builder->where('name', '=', $name)->first($this->fillable)->then(
                function ($user) {
                    if (!$user)
                        return false;
                    if (count($user) < 1)
                        return false;
                    
                    return $this->fill($user, $this);
                },
                function (Exception $ex) {
                    return $ex;
                }
            );
    }

    /**
     * Find a user by the email
     * 
     * @param string $email
     * @return self|Exception|bool
     */
    public function findByEmail(string $email)
    {
        $query_arr = [];

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = null;
            //$this->builder->useSoftDelete = true;
        }
        $query_arr['email'] = $email;

        if (!$user = mysqly::{$this->table}($query_arr, $this->fillable)) {
            return false;
        }
        if (count( $user ) < 1) {
            return false;
        }

        print_r($user);
        return $this->fill($user);
    }

    /**
     * update a model
     * 
     * @param array $values
     * @param int $id
     * @param bool $internal
     * @return PromiseInterface<self|Exception|bool>
     */
    public function update(array $values, int $id=0, $internal = false)
    {
        $id = $id > 0 ? $id : $this->child->id;

        echo "got to update".PHP_EOL;
        
        if ($this->child->softdeletes && !$internal) {
            $this->builder->where('deleted_at', null);
        }

        return $this->builder->where('id', $id)->update($values)->then(
            function ($status) use ($id) {
                if (!(bool)$status) {
                    return false;
                }
                return $this->builder->find($id, \array_diff($this->fillable, $this->guarded))->then(
                    function ($item) {
                        if (!(bool)$item) {
                            return false;
                        }
                        return $this->fill($item);
                    },
                    function ($ex) {
                        return $ex;
                    }
                );
            },
            function (Exception|false $e) {
                return $e;
            }
        );
    }

    /**
     * update a model
     * 
     * @param int $id
     * @return PromiseInterface<Exception|bool>
     */
    public function delete(int $id = 0)
    {
        $id = $id > 0 ? $id : $this->child->id;
        
        if ($this->child->softdeletes) {
            $this->builder->where('deleted_at', null);
            //$this->builder->useSoftDelete = true;
        }

        return $this->builder->delete($id)->then(
            function (int $val) {
                return !$val ? false : true;
            },
            function (Exception $e) {
                return $e;
            }
        );
    }

    /**
     * restore a soft deleted model
     * 
     * @param int $id
     * @return PromiseInterface<self|Exception|bool>
     */
    public function restore(int $id = 0)
    {
        //TODO: might need to change reject to resolve as yield seems not to handle reject. 
        if (!$this->child->softdeletes) {
            return \React\Promise\reject(new Exception("this model does not support softdeleting"));
        }
        $id = $id > 0 ? $id : $this->child->id;

        return $this->update(['deleted_at', null], $id, true);
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_null($value) && $boolean == 'and')
            return $this->builder->where($column, $operator);
            
        if (!\is_null($operator) && !\is_null($value))
            return $this->builder->where($column, $operator, $value);
        
        if (!\is_null($operator) && !\is_null($value) && $boolean != 'and')
            return $this->builder->where($column, $operator, $value, $boolean);
    }
    
    public function orWhere($column, $operator = null, $value = null)
    {
        if (is_null($value))
            return $this->builder->orWhere($column, $operator);
            
        if (!\is_null($operator) && !\is_null($value))
            return $this->builder->orWhere($column, $operator, $value);
    }

    public function beginTransaction()
    {
        $this->builder->beginTransaction();
    }

    public function commit()
    {
        $this->builder->commit();
    }

    public function rollback()
    {
        $this->builder->rollback();
    }
}