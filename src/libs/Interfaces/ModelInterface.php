<?php

namespace Basttyy\FxDataServer\libs\Interfaces;

use Closure;

interface ModelInterface
{
    /**
     * Order query by a culumn in a direction "ASC" or "DESC"
     * @param string $column
     * @param string $direction
     * 
     * @return self
     */
    public function orderBy($column = "id", $direction = "ASC");

    // public function addSelect()

    /**
     * Fill a model with array of values
     * @param array $values
     * 
     * @return self
     */
    public function fill($values);

    /**
     * Convert a model to key value pairs array
     * @param bool $guard 'wether to show or hide model guarded params'
     * @param array $select 'which parameters of the model to include, if given $guard will be ignored'
     * 
     * @return array
     */
    public function toArray($guard = true, $select = []);

    /**
     * exec() General SQL query execution
     * 
     * @param string $sql
     * @param array $bind
     * 
     * @return \PDOStatement|false $statement
     */
    public function raw($sql, $bind);
    
    /**
     * create a model from array values and save to db
     * @param array $values
     * @param bool $is_protected 'wether to hide or show protected values'
     * @param array $select 'what parameters of model to fetch in results'
     * 
     * @return array|bool
     */
    public function create($values, $is_protected = true, $select = []);

    /**
     * save a model object to DB
     * 
     * @return bool
     */
    public function save();

    /**
     * Find a model by its id
     * @param int $id
     * @param bool $is_protected 'wether to hide or show protected values'
     * 
     * @return self|false
     */
    public function find($id = 0, $is_protected = true);

    /**
     * Find a model by its id, execute the closure if not found
     * @param int $id
     * @param bool $is_protected 'wether to hide or show protected values'
     * @param callable $callable
     * 
     * @return self|false
     */
    public function findOr($id = 0, $is_protected = true, $callable);

    /**
     * Alias of Find with no id provided
     * Retrieves the first of all results of a query
     * @param bool $is_protected 'wether to hide or show protected values'
     * 
     * @return self|false
     */
    public function first($is_protected = true);

    /**
     * Retrieves the first of all results of a query
     * No previous or subsequent where clause is required
     * @param string $column
     * @param string|null $operatorOrValue
     * @param mixed $value
     * @param bool $is_protected 'wether to hide or show protected values'
     * 
     * @return self|false
     */
    public function firstWhere($column, $operatorOrValue = null, $value = null, $is_protected = true);

    /**
     * Retrieve model by key value or create it if it doesn't exist from array values
     * search and keyvalues will be used together while creating the model
     * @param array $search
     * @param array $keyvalues
     * @param bool $is_protected 'wether to hide or show protected values'
     * @param array $select 'what parameters of model to fetch in results'
     * 
     * @return array|bool
     */
    public function firstOrCreate($search, $keyvalues, $is_protected = true, $select = []);

    /**
     * Retrieve model by key value or instantiate it if it doesn't exist from array values
     * The model still needs to be save to the DB by calling save()
     * search and keyvalues will be used together while creating the model
     * @param array $search
     * @param array $keyvalues
     * @param bool $is_protected 'wether to hide or show protected values'
     * @param array $select 'what parameters of model to fetch in results'
     * 
     * @return array|bool
     */
    public function firstOrNew($search, $keyvalues, $is_protected = true, $select = []);

    /**
     * Find a model by key and value
     * 
     * @param string $key
     * @param string $value
     * @param bool $is_protected 'wether to hide or show protected values'
     * @param array $select 'what parameters of model to fetch in results'
     * 
     * @return array|false
     */
    public function findBy($key, $value, $is_protected = true, $select = []);
    
    /**
     * Find a model by a set of keys and values
     * 
     * @param array $keys
     * @param array $values
     * @param string $or_and 'wether to use OR or AND to join where clauses'
     * @param bool $is_protected 'wether to hide or show protected values'
     * @param array $select 'what parameters of model to fetch in results'
     * 
     * @return array|false
     */
    public function findByArray($keys, $values, $or_and = "AND", $is_protected = true, $select = []);

    /**
     * Find all elements of a model
     * 
     * @param bool $is_protected 'wether to hide or show protected values'
     * @param array $select 'what parameters of model to fetch in results'
     * 
     * @return array|false
     */
    public function all($is_protected = true, $select = []);

    
    /**
     * Alias for all(), Find all elements of a model
     * 
     * @param bool $is_protected 'wether to hide or show protected values'
     * @param array $select 'what parameters of model to fetch in results'
     * 
     * @return array|false
     */
    public function get($is_protected = true, $select = []);

    /**
     * Count total number of elements in a model from results of a query
     * 
     * @return int|false
     */
    public function count();
    
    /**
     * Given a column, return the avearage of all values of that
     * column from results of a query
     * 
     * @return int|false
     */
    public function avg();
    
    /**
     * Given a column, return the element in a model with greatest value of that
     * column from results of a query
     * @return int|false
     */
    public function max();
        
    /**
     * Given a column, return the element in a model with smallest value of that
     * column from results of a query
     * @return int|false
     */
    public function min();

    /**
     * update a model
     * 
     * @param array $values
     * @param int $id
     * @param bool $is_protected
     * 
     * @return self|bool
     */
    public function update($values, $id=0, $is_protected = true);

    /**
     * update a model
     * @param int $id
     * 
     * @return bool
     */
    public function delete($id = 0);

    /**
     * restore a soft deleted model
     * 
     * @param int $id
     * 
     * @return self|bool
     * @throws Exception
     */
    public function restore($id = 0);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param string|null $operatorOrValue
     * @param mixed $value
     * 
     * @return self
     */
    public function where($column, $operatorOrValue = null, $value = null);
    
    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereLike($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereNotLike($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereLessThan($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereGreaterThan($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereLessThanOrEqual($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereGreaterThanOrEqual($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereEqual($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereNotEqual($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param string|null $operatorOrValue
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhere($column, $operatorOrValue = null, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereLike($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereNotLike($column, $value = null);
    
    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereLessThan($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereGreaterThan($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereLessThanOrEqual($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereGreaterThanOrEqual($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereEqual($column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereNotEqual($column, $value = null);

    /**
     * Begin a Transaction (all subsequent statements will be executed in that transaction)
     */
    public function beginTransaction();

    // public function commit()
    // {
    //     $this->builder->commit();
    // }

    // public function rollback()
    // {
    //     $this->builder->rollback();
    // }
}