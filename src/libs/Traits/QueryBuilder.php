<?php

namespace Basttyy\FxDataServer\libs\Traits;

use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\Interfaces\ModelInterface;
use Basttyy\FxDataServer\libs\Interfaces\UserModelInterface;
use Basttyy\FxDataServer\libs\mysqly;
use Basttyy\FxDataServer\Models\Model;
use DateTime;
use Exception;
use Hybridauth\Exception\NotImplementedException;
use PDO;

trait QueryBuilder
{
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
     * The operators for query
     * 
     * @var array|string
     */
    protected $operators;

    /**
     * The booleans to add queries together
     * 
     * @var array|string
     */
    protected $or_ands;

    /**
     * The filter key values
     * 
     * @var array|null
     */
    protected $bind_or_filter;

    /**
     * Wether queries are currently running in a transaction
     * 
     * @var bool
     */
    protected $transaction_mode;

    /**
     * Sets how the query should be ordered
     * 
     * @var string
     */
    protected $order = "";

    /**
     * The child class that currently using the parent class
     * 
     * @var Model|Model&UserModelInterface
     */
    protected $child;

    /**
     * Indicates if the model already has a copy in the db or not
     * 
     * @var bool
     */
    private $exists_in_db = false;
    public static function getBuilder()
    {
        $classname = get_called_class();
        return new $classname;
    }

    private function prepareModel()
    {
        $this->or_ands = 'AND';
        $this->operators = '=';
    }

    protected function resetInstance()
    {
        $this->bind_or_filter = null;
        $this->or_ands = 'AND';
        $this->operators = '=';
        $this->order = '';
        $this->transaction_mode = false;
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

    public function raw($sql, $bind)
    {
        return mysqly::exec($sql, $bind);
    }

    public function create($values, $is_protected = true, $select = [])
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

    public function save()
    {
        $values = Arr::where($this->toArray(), function ($v, $k) {      // to be used to filter out empty values in future
            return true;
        }, ARRAY_FILTER_USE_BOTH);

        if ($this->child->{$this->child->primaryKey} === 0) {
            if (!$id = mysqly::insert($this->table, $values)) {
                return false;
            }

            $fields = $this->fillable;
            if (!$model = mysqly::fetch($this->table, ['id' => $id], $fields)) {
                return true;
            }
        } else {
            $model = $this->_update($values, $this->child->{$this->child->primaryKey}, true, should_fill: false);
            if (!$model)
                return false;
        }

        array_key_exists($this->child->{$this->child::UPDATED_AT}, $model[0]) ? $this->child->{$this->child::UPDATED_AT} = $model[$this->child->{$this->child::UPDATED_AT}] : null;
        array_key_exists($this->child->{$this->child->primaryKey}, $model[0]) ? $this->child->{$this->child->primaryKey} = $model[$this->child->{$this->child->primaryKey}] : null;

        return true;
    }

    public function find($id = 0, $is_protected = true)
    {
        $id = $id > 0 ? $id : $this->child->{$this->child->primaryKey};
        $query_arr = [];
        if ($id === 0 && isset($this->child->id)) {
            $id = $this->child->id;
        }
        if ($this->bind_or_filter)
            $query_arr = $this->bind_or_filter;

        
        if ($id < 0)
            $query_arr['id'] = $id;
        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            is_string($this->or_ands) ? $this->or_ands = ["AND"] : array_push($this->or_ands, "AND");
        }
        
        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;

        if (!$model = mysqly::fetch($this->table, $query_arr, $fields, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }
        $this->resetInstance();
        return $this->fill($model[0]);
    }

    public function findOr($id = 0, $is_protected = true, $callable = null)
    {
        throw new NotImplementedException('oops! this feature is yet to be implemented');
    }

    public function first($is_protected = true)
    {
        return $this->find(is_protected: $is_protected);
    }

    public function firstWhere($column, $operatorOrValue = null, $value = null, $is_protected = true)
    {
        throw new NotImplementedException('oops! this feature is yet to be implemented');
    }

    public function firstOrCreate($search, $keyvalues, $is_protected = true, $select = [])
    {
        throw new NotImplementedException('oops! this feature is yet to be implemented');
    }

    public function firstOrNew($search, $keyvalues, $is_protected = true, $select = [])
    {
        throw new NotImplementedException('oops! this feature is yet to be implemented');
    }

    public function findBy($key, $value, $is_protected = true, $select = [])
    {
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        $query_arr[$key] = $value;
        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            is_string($this->or_ands) ? $this->or_ands = ["AND"] : array_push($this->or_ands, "AND");
        }
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

    public function findByArray($keys, $values, $or_and = "AND", $is_protected = true, $select = [])
    {
        if (count($keys) !== count($values)) {
            return false;
        }

        $query_arr = [];

        foreach ($keys as $pos => $key) {
            $query_arr[$key] = $values[$pos];
            is_string($this->or_ands) ? $this->or_ands = [$or_and] : array_push($this->or_ands, $or_and);
        }
        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            array_push($this->or_ands, "AND");
        }
        
        if (count($select)) {
            $fields = $select;
        } else {
            $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        }
        if (!$fields = mysqly::fetch($this->table, $query_arr, $fields)) {
            return false;
        }
        return $fields;
    }

    public function all($is_protected = true, $select = [])
    {
        $query_arr = [];
        if ($this->bind_or_filter)
            $query_arr = $this->bind_or_filter;

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            is_array($this->or_ands) ? $this->or_ands[] = "AND" : $this->or_ands = "AND";
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

    public function get($is_protected = true, $select = [])
    {
        return $this->all($is_protected, $select);
    }

    public function count()
    {
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        $i = 0;
        // foreach ($keys as $key) {
        //     $query_arr[$key] = $values[$i];
        //     $i++;
        // }
        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            is_string($this->or_ands) ? $this->or_ands = ["AND"] : array_push($this->or_ands, "AND");
        }

        if (!$count = mysqly::count($this->table, $query_arr, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }
        $this->resetInstance();
        return $count;
    }

    public function avg()
    {
        throw new NotImplementedException('oops! this feature is yet to be implemented');
    }

    public function max()
    {
        throw new NotImplementedException('oops! this feature is yet to be implemented');
    }
    
    public function min()
    {
        throw new NotImplementedException('oops! this feature is yet to be implemented');
    }

    public function update($values, $id=0, $is_protected = true)
    {
        return $this->_update($values, $id, is_protected: $is_protected);
    }

    public function delete($id = 0)
    {
        $id = $id > 0 ? $id : $this->child->{$this->child->primaryKey};
        
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        $query_arr['id'] = $id;
        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
            is_string($this->or_ands) ? $this->or_ands = ["AND"] : array_push($this->or_ands, "AND");
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

    public function restore($id = 0)
    {
        if (!$this->child->softdeletes) {
            throw new Exception("this model does not support soft deleting");
        }
        $id = $id > 0 ? $id : $this->child->{$this->child->primaryKey};

        return $this->_update(['deleted_at', null], $id, true);
    }

    public function where($column, $operatorOrValue = null, $value = null)
    {
        return $this->_where($column, $operatorOrValue, $value, 'AND');
    }
    
    public function whereLike($column, $value = null)
    {
        return $this->_where($column, 'LIKE', $value, 'AND');
    }

    public function whereNotLike($column, $value = null)
    {
        return $this->_where($column, 'NOT LIKE', $value, 'AND');
    }

    public function whereLessThan($column, $value = null)
    {
        return $this->_where($column, '<', $value, 'AND');
    }

    public function whereGreaterThan($column, $value = null)
    {
        return $this->_where($column, '>', $value, 'AND');
    }

    public function whereLessThanOrEqual($column, $value = null)
    {
        return $this->_where($column, '<=', $value, 'AND');
    }

    public function whereGreaterThanOrEqual($column, $value = null)
    {
        return $this->_where($column, '>=', $value, 'AND');
    }

    public function whereEqual($column, $value = null)
    {
        return $this->_where($column, '=', $value, 'AND');
    }

    public function whereNotEqual($column, $value = null)
    {
        return $this->_where($column, '!=', $value, 'AND');
    }

    public function orWhere($column, $operatorOrValue = null, $value = null)
    {
        return $this->_where($column, $operatorOrValue, $value, 'OR');
    }

    public function orWhereLike($column, $value = null)
    {
        return $this->_where($column, 'LIKE', $value, 'OR');
    }

    public function orWhereNotLike($column, $value = null)
    {
        return $this->_where($column, 'NOT LIKE', $value, 'OR');
    }
    
    public function orWhereLessThan($column, $value = null)
    {
        return $this->_where($column, '<', $value, 'OR');
    }

    public function orWhereGreaterThan($column, $value = null)
    {
        return $this->_where($column, '>', $value, 'OR');
    }

    public function orWhereLessThanOrEqual($column, $value = null)
    {
        return $this->_where($column, '<=', $value, 'OR');
    }

    public function orWhereGreaterThanOrEqual($column, $value = null)
    {
        return $this->_where($column, '>=', $value, 'OR');
    }

    public function orWhereEqual($column, $value = null)
    {
        return $this->_where($column, '=', $value, 'OR');
    }

    public function orWhereNotEqual($column, $value = null)
    {
        return $this->_where($column, '!=', $value, 'OR');
    }

    public function beginTransaction()
    {
        mysqly::beginTransaction();
        $this->transaction_mode = true;
    }

    public function commit()
    {
        mysqly::commit();
    }

    public function rollback()
    {
        mysqly::rollback();
    }

    /**
     * update a model
     * 
     * @param array $values
     * @param int $id
     * @param bool $internal
     * @return self|bool|array
     */
    private function _update(array $values, int $id=0, $internal = false, $is_protected = true, $should_fill = true)
    {
        $id = $id > 0 ? $id : $this->child->{$this->child->primaryKey};
        
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        $query_arr['id'] = $id;
        if ($this->child->softdeletes && !$internal) {
            $query_arr['deleted_at'] = "IS NULL";
            is_string($this->or_ands) ? $this->or_ands = ["AND"] : array_push($this->or_ands, "AND");
        }

        $count = mysqly::update($this->table, $query_arr, $values, $this->operators, $this->or_ands);

        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        if (!$model = mysqly::fetch($this->table, $query_arr, $fields, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }

        $this->resetInstance();
        if ($should_fill)
            return $this->fill($model[0]);

        return $model[0];
    }

    private function _where(string $column, string $operatorOrValue = null, $value = null, $boolean = "AND")
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
}