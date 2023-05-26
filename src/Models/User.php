<?php

namespace Basttyy\FxDataServer\Models;

final class User extends Model
{
    const INACTIVE = "inactive";
    const ACTIVE = "active";
    protected $softdeletes = true;

    protected $table = 'users';

    protected $primaryKey = 'id';

    //object properties
    public $id;
    public $uuid;
    public $firstname;
    public $lastname;
    public $username;
    public $email;
    public $password;
    public $phone;
    public $level;
    public $country;
    public $city;
    public $postal_code;
    public $address;
    public $role_id;
    public $access_token;
    public $twofa_secret;
    public $email2fa_token;
    public $status;
    public $avatar;
    public $created_at;
    public $updated_at;
    public $deleted_at;

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'uuid', 'firstname', 'lastname', 'username', 'email', 'password',
        'phone', 'level', 'country', 'city', 'postal_code', 'address',
        'role_id', 'access_token', 'twofa_secret', 'email2fa_token', 'status',
        'avatar', 'created_at', 'updated_at', 'deleted_at'
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected $guarded = [
        'password', 'deleted_at', 'role_id', 'access_token', 'twofa_secret', 'email2fa_token',
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