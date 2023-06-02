<?php

namespace Basttyy\FxDataServer\Models;
require_once __DIR__."/../libs/helpers.php";

use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\mysqly;
use Exception;
use PDO;

abstract class Model
{
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
     * Sets how the query should be ordered
     * 
     * @var string
     */
    protected $order = "";

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
        mysqly::auth(env('DB_USER'), env('DB_PASS'), env('DB_NAME'));
        $this->prepareModel();
    }

    protected function prepareModel()
    {
        // $this->builder->from = $this->table;
    }

    public function orderBy($column = "id", $direction = "ASC")
    {
        $this->order = "$column $direction";
        return $this;
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

    public function raw(string $sql, $bind)
    {
        return mysqly::exec($sql, $bind);
    }
    
    /**
     * create a model from array values
     * @param array $values
     * 
     * @return array|bool
     */
    public function create(array $values, $is_protected = true)
    {
        if (!$id = mysqly::insert($this->table, $values)) {
            return false;
        }
        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        if (!$model = mysqly::fetch($this->table, ['id' => $id], $fields)) {
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
    public function find(int $id = 0, $is_protected = true)
    {
        $id = $id > 0 ? $id : $this->child->id;
        $query_arr = [];

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            //$this->builder->useSoftDelete = true;
        }
        $query_arr['id'] = $id;
        
        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;

        if (!$model = mysqly::fetch($this->table, $query_arr, $fields)) {
            return false;
        }
        return $this->fill($model[0]);
    }

    /**
     * Find all elements of a model
     * 
     * @return array
     */
    public function all($is_protected = true)
    {
        $query_arr = [];

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            //$this->builder->useSoftDelete = true;
        }

        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;

        if (!$fields = mysqly::fetch($this->table, $query_arr, $fields)) {
            return false;
        }
        return $fields;
    }

    /**
     * Find a model by key and value
     * 
     * @param string $key
     * @param string $value
     * @param bool $is_protected
     * @return array|bool
     */
    public function findBy($key, $value, $is_protected = true)
    {
        $query_arr = [];

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            //$this->builder->useSoftDelete = true;
        }
        $query_arr[$key] = $value;
        if ($this->order !== "")
            $query_arr['order_by'] = $this->order;

        
        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;

        if (!$model = mysqly::fetch($this->table, $query_arr, $fields)) {
            return false;
        }
        return $model;
    }
    
    /**
     * Find a model by a set of keys and values
     * 
     * @param array $keys
     * @param array $values
     * 
     * @return array|bool
     */
    public function findByArray(array $keys, array $values, $or_and = "AND", $is_protected = true)
    {
        if (count($keys) !== count($values)) {
            return false;
        }

        $query_arr = [];

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            //$this->builder->useSoftDelete = true;
        }

        foreach ($keys as $pos => $key) {
            $query_arr[$key] = $values[$pos];
            // $this->builder->where($key, $values[$pos]);
        }
        
        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        if (!$fields = $or_and === "AND" ? mysqly::fetch($this->table, $query_arr, $fields) : mysqly::fetchOr($this->table, $query_arr, $fields)) {
            return false;
        }
        return $fields;
    }

    /**
     * Find a user by the username
     * 
     * @param string $name
     * @return self|bool
     */
    public function findByUsername($name, $is_protected = true)
    {
        $query_arr = [];

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            //$this->builder->useSoftDelete = true;
        }
        $query_arr['username'] = $name;

        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        if (!$user = mysqly::fetch($this->table, $query_arr, $fields)) {
            return false;
        }
        if (count( $user ) < 1) {
            return false;
        }

        return $this->fill($user[0]);
    }

    /**
     * Find a user by the email
     * 
     * @param string $email
     * @return self|bool
     */
    public function findByEmail(string $email, $is_protected = true)
    {
        $query_arr = [];

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            //$this->builder->useSoftDelete = true;
        }
        $query_arr['email'] = $email;

        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        if (!$user = mysqly::fetch($this->table, $query_arr, $fields)) {
            return false;
        }
        if (count( $user ) < 1) {
            return false;
        }

        return $this->fill($user[0]);
    }

    /**
     * update a model
     * 
     * @param array $values
     * @param int $id
     * @param bool $internal
     * @return self|bool
     */
    public function update(array $values, int $id=0, $internal = false, $is_protected = true)
    {
        $id = $id > 0 ? $id : $this->child->id;
        
        $query_arr = [];

        if ($this->child->softdeletes && !$internal) {
            $query_arr['deleted_at'] = "IS NULL";
            //$this->builder->useSoftDelete = true;
        }
        $query_arr['id'] = $id;

        if (!$stat = mysqly::update($this->table, $query_arr, $values)) {
            return false;
        }

        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        if (!$model = mysqly::fetch($this->table, $query_arr, $fields)) {
            return true;
        }

        return $this->fill($model[0]);
    }

    /**
     * update a model
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id = 0)
    {
        $id = $id > 0 ? $id : $this->child->id;
        
        $query_arr = [];

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            //$this->builder->useSoftDelete = true;
        }
        $query_arr['id'] = $id;

        return mysqly::remove($this->table, $query_arr);
    }

    /**
     * restore a soft deleted model
     * 
     * @param int $id
     * @return self|bool
     */
    public function restore(int $id = 0)
    {
        if (!$this->child->softdeletes) {
            throw new Exception("this model does not support soft deleting");
        }
        $id = $id > 0 ? $id : $this->child->id;

        //return $this->update(['deleted_at', null], $id, true);
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