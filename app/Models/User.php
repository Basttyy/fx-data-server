<?php

namespace App\Models;

use App\Console\Jobs\SendVerifyEmail;
use Exception;
use Eyika\Atom\Framework\Support\Arr;
use Eyika\Atom\Framework\Support\Str;
use Eyika\Atom\Framework\Support\Database\Concerns\HasRelationships;
use Eyika\Atom\Framework\Support\Database\Concerns\UserAwareQueryBuilder;
use Eyika\Atom\Framework\Support\Database\Contracts\UserModelInterface;
use Eyika\Atom\Framework\Support\Database\DB;

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
    public $id;
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
    public bool $require_subscription;
    public string | null $referral_code;
    public string | null $access_token;
    public string | null $twofa_secret;
    public string | null $email2fa_token;
    public string | null $email2fa_expire;
    public string | null $twofa_types;
    public string $twofa_default_type;
    public string $status;
    public string $avatar;
    public int $points;
    public float | null $dollar_per_point;
    public $created_at;
    public $updated_at;
    public $deleted_at;

    /**
     * user twofa properties
     * 
     * @var array
     */
    const twofainfos = [
        'twofa_types', 'twofa_default_type'
    ];

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    const fillable = [
        'id', 'uuid', 'firstname', 'lastname', 'username', 'email', 'password',
        'phone', 'level', 'country', 'city', 'postal_code', 'address',
        'role_id', 'require_subscription', 'access_token', 'twofa_secret', 'email2fa_token', 'status',
        'avatar', 'created_at', 'updated_at', 'deleted_at', 'email2fa_expire',
        'twofa_types', 'twofa_default_type', 'referral_code', 'points', 'dollar_per_point'
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    const guarded = [
        'password', 'deleted_at', 'created_at', 'updated_at', 'role_id', 'access_token', 'twofa_secret', 'email2fa_token', 'email2fa_expire',
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

    public static function boot($user, $event)
    {
        parent::boot($user, $event);
        static::creating($user, $event, function ($user) {
            $user->referral_code = Str::random(10);
            $dollar_per_point = env("DOLLAR_PER_POINT") ?? 0.1;
            $user->dollar_per_point = $user->dollar_per_point ?? $dollar_per_point;
        });
        static::created($user, $event, function (self $user) {
            $mail_job = new SendVerifyEmail(array_merge($user->toArray(), ['email2fa_token' => $_SESSION['email2fa_token']]));
            $mail_job->init()->delay(5)->run();
        });
    }

    /**
     * @return Strategy[]
     */
    public function strategies ()
    {
        return $this->hasMany(Strategy::class);
    }

    /**
     * @return Referral[]|null
     */
    public function referrals ()
    {
        return $this->hasMany(Referral::class);
    }

    public function referredBy(): User|null
    {
        return $this->hasOne(Referral::class, 'user_id');
    }

    public function subscription(): Subscription|null
    {
        return $this->hasOne(Subscription::class);
    }

    public function exchangePointsForCash($points)
    {
        try {
            DB::beginTransaction();
            if ($this->points < $points) {
                throw new \Exception('Not enough points');
            }
            $dollar_per_point = env("DOLLAR_PER_POINT") ?? 0.1;
            $dollar_per_point = $this->dollar_per_point ?? $dollar_per_point;
            $cash = $points * $dollar_per_point; // Assume 1 point = $0.1
            $this->points -= $points;
            $this->save();
    
            // Logic to transfer cash to user
            // This could involve integrating with a payment gateway
            // For now, we will just log the cash amount
            $body = [
                'tx_ref' => transaction_ref('witdr_tx_'),
                'status' => Transaction::pending,
                'amount' => $cash,
                'currency' => 'usd',
                'type' => Transaction::outflow,
                'action' => Transaction::WITHDRAWAL,
                'user_id' => $this->id
            ];
            if (!$transaction = Transaction::getBuilder()->create(Arr::except($body, ['plan_id', 'duration']))) {
                throw new \Exception('Unable to initiate withdrawal transaction');
            }
            logger()->info("User {$this->id} exchanged {$points} points for \${$cash}");
            return $cash;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $this->points += $points;
            logger()->info("Unable to exchange User {$this->username}'s points {$points} for \${$cash}");
        }

        return $cash;
    }
}