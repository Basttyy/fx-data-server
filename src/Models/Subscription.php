<?php

namespace Basttyy\FxDataServer\Models;

use Basttyy\FxDataServer\Models\Model;

final class Subscription extends Model
{
    protected $softdeletes = true;

    protected $table = 'subscriptions';

    protected $primaryKey = 'id';

    //object properties
    // public $id;
    public $duration;
    public $user_id;
    public $plan_id;
    public $created_at;
    public $expires_at;
    public $updated_at;

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected const fillable = [
        'id', 'duration', 'user_id', 'plan_id', 'created_at', 'expires_at', 'updated_at'
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected const guarded = [
        'created_at', 'updated_at'
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