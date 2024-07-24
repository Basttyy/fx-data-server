<?php

namespace Basttyy\FxDataServer\Models;

use Basttyy\FxDataServer\libs\Interfaces\UserModelInterface;
use Basttyy\FxDataServer\libs\Str;
use Basttyy\FxDataServer\libs\Traits\HasRelationships;
use Basttyy\FxDataServer\libs\Traits\UserAwareQueryBuilder;

final class User extends Model implements UserModelInterface
{
    use UserAwareQueryBuilder, HasRelationships;
    
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
    // public $id;
    public string | null $uuid;
    public string $firstname;
    public string $lastname;
    public string $username;
    public string $email;
    public string | null $password;
    public string $phone;
    public int | null $level;
    public string | null $country;
    public string | null $city;
    public string | null $postal_code;
    public string | null $address;
    public int $role_id;
    public string | null $access_token;
    public string | null $twofa_secret;
    public string | null $email2fa_token;
    public string | null $email2fa_expire;
    public string | null $twofa_types;
    public string $twofa_default_type;
    public string $status;
    public string $avatar;
    public string $referral_code;
    public int $points;
    public string $created_at;
    public string | null $updated_at;
    public string | null $deleted_at;

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
        'twofa_types', 'twofa_default_type', 'referral_code', 'points'
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
    public function __construct($values = [])
    {
        parent::__construct($values, $this);
    }

    /**
     * @return Strategy[]
     */
    public function strategies ()
    {
        return $this->hasMany(Strategy::class);
    }

    public static function boot($model)
    {
        logger()->info('boot called');
        // parent::boot();
        static::creating($model, function ($model) {
            logger()->info('adding refferal code');
            $model->referral_code = Str::random(10);
        });
        logger()->info('boot done');
    }
}