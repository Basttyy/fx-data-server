<?php

namespace Basttyy\FxDataServer\libs\Interfaces;

interface ModelInterface
{
    /**
     * Initialize the model instance parameters
     * 
     * @return void
     */
    public function prepareModel(): void;

    /**
     * Reset the query builder instance
     * 
     * @return void
     */
    public function resetInstance(): void;

    /**
     * Order query by a culumn in a direction "ASC" or "DESC"
     * @param string $column
     * @param string $direction
     * 
     * @return self
     */
    public function orderBy($column = "id", $direction = "ASC");

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
    public function raw(string $sql, $bind);
    
    /**
     * create a model from array values
     * @param array $values
     * @param bool $is_protected 'wether to hide or show protected values'
     * @param array $select 'what parameters of model to fetch in results'
     * 
     * @return array|bool
     */
    public function create(array $values, $is_protected = true, $select = []);

    /**
     * Find a model by its id
     * @param int $id
     * @param bool $is_protected 'wether to hide or show protected values'
     * 
     * @return self|false
     */
    public function find(int $id = 0, $is_protected = true);

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
    public function findByArray(array $keys, array $values, $or_and = "AND", $is_protected = true, $select = []);

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
     * Count total number of elements in a model from results of a query
     * 
     * @return int|false
     */
    public function count();

    /**
     * update a model
     * 
     * @param array $values
     * @param int $id
     * @param bool $is_protected
     * 
     * @return self|bool
     */
    public function update(array $values, int $id=0, $is_protected = true);

    /**
     * update a model
     * =
     * @param int $id
     * 
     * @return bool
     */
    public function delete(int $id = 0);

    /**
     * restore a soft deleted model
     * 
     * @param int $id
     * 
     * @return self|bool
     * @throws Exception
     */
    public function restore(int $id = 0);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param string|null $operatorOrValue
     * @param mixed $value
     * 
     * @return self
     */
    public function where(string $column, string $operatorOrValue = null, $value = null);
    
    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereLike(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereNotLike(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereLessThan(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereGreaterThan(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereLessThanOrEqual(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereGreaterThanOrEqual(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereEqual(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function whereNotEqual(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param string|null $operatorOrValue
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhere(string $column, string $operatorOrValue = null, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereLike(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereNotLike(string $column, $value = null);
    
    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereLessThan(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereGreaterThan(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereLessThanOrEqual(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereGreaterThanOrEqual(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereEqual(string $column, $value = null);

    /**
     * Add a where clause to the query instance
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function orWhereNotEqual(string $column, $value = null);

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