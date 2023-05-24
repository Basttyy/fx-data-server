<?php

namespace Basttyy\FxDataServer\Models;

use Basttyy\FxDataServer\Models\Model;

final class Role extends Model
{
    const INACTIVE = "inactive";
    const ACTIVE = "active";
    protected $softdeletes = true;

    protected $table = 'roles';

    protected $primaryKey = 'id';

    //object properties
    public $id;
    public $name;
    public $previleges;
    public $created_at;
    public $updated_at;
    public $deleted_at;

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'previleges', 'created_at', 'updated_at', 'deleted_at'
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected $guarded = [
        'deleted_at', 'role_id', 'access_token'
    ];

    /**
     * Create a new user instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct($this);
    }
}