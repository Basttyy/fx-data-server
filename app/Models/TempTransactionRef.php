<?php

namespace App\Models;

use App\Models\Model;

final class TempTransactionRef extends Model
{
    protected $softdeletes = false;

    protected $table = 'temp_transaction_refs';

    protected $primaryKey = 'id';

    //object properties
    public $id;
    public $user_id;
    public $tx_ref;
    public $access_code;
    public $created_at;
    public $updated_at;
    public $deleted_at;
    //add more TempTransactionRef's properties here

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected const fillable = [
        'id', 'user_id', 'tx_ref', 'access_code', 'created_at', 'updated_at'
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
     * Create a new TempTransactionRef instance.
     *
     * @return void
     */
    public function __construct($values = [])
    {
        parent::__construct($values, $this);
    }
}
