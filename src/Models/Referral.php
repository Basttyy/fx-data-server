<?php

namespace Basttyy\FxDataServer\Models;

use Basttyy\FxDataServer\libs\Traits\HasRelationships;
use Basttyy\FxDataServer\Models\Model;

final class Referral extends Model
{
    use HasRelationships;

    protected $softdeletes = true;

    protected $table = 'referrals';

    protected $primaryKey = 'id';

    //object properties
    public $id;
    public $created_at;
    public $updated_at;
    public $deleted_at;
    //add more Referral's properties here

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'created_at', 'updated_at', 'deleted_at',
        //add more fillable columns here
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected $guarded = [
        'deleted_at', 'created_at', 'updated_at'
        //add more guarded columns here
    ];

    /**
     * Create a new Referral instance.
     *
     * @return void
     */
    public function __construct($values = [])
    {
        parent::__construct($values, $this);
    }

    public function referredUsers ()
    {
        return $this->hasMany(User::class, 'referred_user_id', 'id');
    }

    public function referee ()
    {
        return $this->hasMany(User::class, 'user_id', 'id');
    }
}
