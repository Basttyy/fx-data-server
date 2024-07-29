<?php

namespace Basttyy\FxDataServer\Models;

use Basttyy\FxDataServer\Models\Model;

final class RequestLog extends Model
{
    protected $softdeletes = false;

    protected $table = 'request_logs';

    protected $primaryKey = 'id';

    //object properties
    // public $id;
    public $ip;
    public $origin;
    public $method;
    public $uripath;
    public $body;
    public $created_at;
    public $updated_at;

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    public $analytic = [
        'id', 'created_at'
    ];

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'ip', 'origin', 'method', 'uripath', 'body', 'created_at', 'updated_at'
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected $guarded = [
        'deleted_at', 'created_at', 'updated_at'
    ];

    /**
     * Create a new user instance.
     *
     * @return void
     */
    public function __construct($values = [])
    {
        parent::__construct($values, $this);
    }
}