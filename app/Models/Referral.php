<?php

namespace App\Models;

use App\Models\Model;
use Eyika\Atom\Framework\Support\Database\Concerns\HasRelationships;

final class Referral extends Model
{
    use HasRelationships;

    protected $softdeletes = false;

    protected $table = 'referrals';

    protected $primaryKey = 'id';

    //object properties
    // public $id;
    public int $user_id;
    public int $referred_user_id;
    public $created_at;
    public $updated_at;
    //add more Referral's properties here

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected const fillable = [
        'id', 'user_id', 'referred_user_id', 'created_at', 'updated_at',
        //add more fillable columns here
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected const guarded = [
        'created_at', 'updated_at'
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
}
