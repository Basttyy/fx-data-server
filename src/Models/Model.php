<?php

namespace Basttyy\FxDataServer\Models;
require_once __DIR__."/../libs/helpers.php";

use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\mysqly;
use DateTime;
use Exception;
use PDO;

abstract class Model
{
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
     * The operators for query
     * 
     * @var array|string
     */
    private $operators;

    /**
     * The booleans to add queries together
     * 
     * @var array|string
     */
    private $or_ands;

    /**
     * The filter key values
     * 
     * @var array|null
     */
    private $bind_or_filter;

    /**
     * Wether to run queries as transaction
     * 
     * @var bool
     */
    private $use_transaction;

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
        $this->or_ands = 'AND';
        $this->operators = '=';
    }

    private function resetInstance()
    {
        $this->bind_or_filter = null;
        $this->or_ands = 'AND';
        $this->operators = '=';
    }

    public function orderBy($column = "id", $direction = "ASC")
    {
        $this->order = "$column $direction";
        return $this;
    }

    /**
     * Fill a model with array of values
     * @param int $id
     * 
     * @return self
     */
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

    public function toArray($guard = true, $select = [])
    {
        $result = array();
        // if ($self == null) {
        //     $self = \get_called_class();
        // }
        // if ($self == null) {
        //     return $result;
        // }
        $obj_props = array_diff(array_keys(get_object_vars($this->child)), [
            'fillable', 'guarded', 'table', 'primaryKey', 'exists', 'db', 'builder',
            'connection', 'keyType', 'incrementing', 'perPage', 'wasRecentlyCreated', 'child'
        ]);
        if (sizeof($select)) {
            foreach ($select as $item) {
                if (Arr::exists($obj_props, $item, true)) {
                    $result[$item] = $this->child->{$item};
                }
            }
            return $result;
        }
        $items = $guard ? array_diff($this->child->fillable, $this->child->guarded) : $this->child->fillable;
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
    public function create(array $values, $is_protected = true, $select = [])
    {
        if (!$id = mysqly::insert($this->table, $values)) {
            return false;
        }
        if (count($select)) {
            $fields = $select;
        } else {
            $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        }
        if (!$model = mysqly::fetch($this->table, ['id' => $id], $fields)) {
            return true;
        }
        return $model[0];
    }

    /**
     * Find a model by its id
     * @param int $id
     * @param bool $is_protected
     * 
     * @return self|bool
     */
    public function find(int $id = 0, $is_protected = true)
    {
        $id = $id > 0 ? $id : $this->child->id;
        $query_arr = [];
        if ($this->bind_or_filter)
            $query_arr = $this->bind_or_filter;

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
        }
        $query_arr['id'] = $id;
        
        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;

        if (!$model = mysqly::fetch($this->table, $query_arr, $fields, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }
        $this->resetInstance();
        return $this->fill($model[0]);
    }

    /**
     * Find all elements of a model
     * 
     * @return array
     */
    public function all($is_protected = true, $select = [])
    {
        $query_arr = [];
        if ($this->bind_or_filter)
            $query_arr = $this->bind_or_filter;

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            $this->or_ands[] = "AND";
        }

        if (count($select)) {
            $fields = $select;
        } else {
            $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        }

        if (!$fields = mysqly::fetch($this->table, $query_arr, $fields, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }
        $this->resetInstance();
        return $fields;
    }

    /**
     * Find and count elements of a model
     * 
     * @return int
     */
    public function count($keys = [], $values = [])
    {
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        $i = 0;
        foreach ($keys as $key) {
            $query_arr[$key] = $values[$i];
            $i++;
        }
        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
        }

        if (!$count = mysqly::count($this->table, $query_arr, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }
        $this->resetInstance();
        return $count;
    }

    /**
     * Find a model by key and value
     * 
     * @param string $key
     * @param string $value
     * @param bool $is_protected
     * @return array|bool
     */
    public function findBy($key, $value, $is_protected = true, $select = [])
    {
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
        }
        $query_arr[$key] = $value;
        if ($this->order !== "")
            $query_arr['order_by'] = $this->order;
    
        if (count($select)) {
            $fields = $select;
        } else {
            $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        }

        if (!$model = mysqly::fetch($this->table, $query_arr, $fields, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }
        $this->resetInstance();
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
    public function findByArray(array $keys, array $values, $or_and = "AND", $is_protected = true, $select = [])
    {
        if (count($keys) !== count($values)) {
            return false;
        }

        $query_arr = [];

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
        }

        foreach ($keys as $pos => $key) {
            $query_arr[$key] = $values[$pos];
        }
        
        if (count($select)) {
            $fields = $select;
        } else {
            $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        }
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
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
        }
        $query_arr['username'] = $name;

        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        if (!$user = mysqly::fetch($this->table, $query_arr, $fields, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }
        if (count( $user ) < 1) {
            $this->resetInstance();
            return false;
        }

        $this->resetInstance();
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
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
        }
        $query_arr['email'] = $email;

        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        if (!$user = mysqly::fetch($this->table, $query_arr, $fields, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }
        if (count( $user ) < 1) {
            $this->resetInstance();
            return false;
        }

        $this->resetInstance();
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
        
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        if ($this->child->softdeletes && !$internal) {
            $query_arr['deleted_at'] = "IS NULL";
        }
        $query_arr['id'] = $id;

        if (!mysqly::update($this->table, $query_arr, $values, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }

        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        if (!$model = mysqly::fetch($this->table, $query_arr, $fields, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return true;
        }

        $this->resetInstance();
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
        
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        $query_arr['id'] = $id;
        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            if (!mysqly::update($this->table, $query_arr, ['deleted_at' => "now"], $this->operators, $this->or_ands)) {
                $this->resetInstance();
                return false;
            }
            $this->resetInstance();
            return true;
        }

        $val = mysqly::remove($this->table, $query_arr, $this->operators, $this->or_ands);
        $this->resetInstance();
        return $val;
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

        return $this->update(['deleted_at', null], $id, true);
    }

    public function where(string $column, string $operatorOrValue = null, $value = null, $boolean = "AND")
    {
        if (is_null($value) && !is_null($operatorOrValue) && str_contains($operatorOrValue, ' NULL')) {// only column and value was given but value is like `IS NULL` or `NOT NULL`
            is_string($this->operators) ? $this->operators = [$operatorOrValue] : array_push($this->operators, $operatorOrValue);
        }
        else if (is_null($value) && !is_null($operatorOrValue) && !str_contains($operatorOrValue, ' NULL')) {// only column and value was given
            is_string($this->operators) ? $this->operators = ['='] : array_push($this->operators, '=');
            $value = $operatorOrValue;
        } else {
            is_string($this->operators) ? $this->operators = [$operatorOrValue] : array_push($this->operators, $operatorOrValue);
        }


        is_string($this->or_ands) ? $this->or_ands = [$boolean] : array_push($this->or_ands, $boolean);
        is_null($this->bind_or_filter) ? $this->bind_or_filter = array($column => $value) : $this->bind_or_filter[$column] = $value;


        // if (is_null($operator) && !is_null($value)) { //operator was not given
        //     if (!str_contains($value, 'NULL'))
        //         is_string($this->operators) ? $this->operators = ['='] : array_push($this->operators, '=');
        // }
        // else if (!\is_null($operator) && \is_null($value) && str_contains($operator, 'NULL')) { //operator is `IS NULL` and value wasn't given
        //     // if (!str_contains($value, 'NULL'))
        //         is_string($this->operators) ? $this->operators = [$operator] : array_push($this->operators, $operator);
        // } else if (!is_null($operator) && !is_null($value)) {
        //     is_string($this->operators) ? $this->operators = [$operator] : array_push($this->operators, $operator);
        // } else {
        //     throw new Exception("invalid where statement $column, $operator, $value, $boolean");
        // }
        return $this;
    }
    
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, "OR");
    }

    public function beginTransaction()
    {
        $this->use_transaction = true;
    }

    // public function commit()
    // {
    //     $this->builder->commit();
    // }

    // public function rollback()
    // {
    //     $this->builder->rollback();
    // }
}