<?php

namespace Basttyy\FxDataServer\Models;

use Basttyy\FxDataServer\libs\Interfaces\UserModelInterface;
use Basttyy\FxDataServer\libs\Traits\UserAwareQueryBuilder;

final class User extends Model implements UserModelInterface
{
    use UserAwareQueryBuilder;
    
    const INACTIVE = "inactive";
    const UNVERIFIED = "unverified";
    const ACTIVE = "active";

    const EMAIL = 'email';
    const PHONE = 'phone';
    const GOOGLE2FA = 'google2fa';

    protected $softdeletes = true;

    protected $table = 'users';

    protected $primaryKey = 'id';

    //object properties
    public $id;
    public $uuid;
    public $firstname;
    public $lastname;
    public string $username;
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
    public $email2fa_expire;
    public $twofa_types;
    public $twofa_default_type;
    public $status;
    public $avatar;
    public $created_at;
    public $updated_at;
    public $deleted_at;

    /**
     * user twofa properties
     * 
     * @var array
     */
    public $twofainfos = [
        'twofa_types', 'twofa_default_type'
    ];

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'uuid', 'firstname', 'lastname', 'username', 'email', 'password',
        'phone', 'level', 'country', 'city', 'postal_code', 'address',
        'role_id', 'access_token', 'twofa_secret', 'email2fa_token', 'status',
        'avatar', 'created_at', 'updated_at', 'deleted_at', 'email2fa_expire',
        'twofa_types', 'twofa_default_type'
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected $guarded = [
        'password', 'deleted_at', 'role_id', 'access_token', 'twofa_secret', 'email2fa_token', 'email2fa_expire',
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