<?php

namespace Basttyy\FxDataServer\Models;

use Basttyy\FxDataServer\Models\Model;

final class Transaction extends Model
{
    protected $softdeletes = false;

    protected $table = 'transactions';

    protected $primaryKey = 'id';

    //object properties
    public $id;
    public $user_id;
    public $transaction_id;
    public $subscription_id;
    public $status;
    public $amount;
    public $currency;
    public $tx_ref;
    public $third_party_ref;
    public $type;
    public $created_at;
    public $updated_at;

    const SUBSCRIPTION = 'subscription';
    const WITHDRAWAL = 'withdrawal';    

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'status', 'user_id', 'transaction_id', 'subscription_id', 'amount', 'currency', 'tx_ref', 'third_party_ref', 'type', 'created_at', 'updated_at',
        //add more fillable columns here
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected $guarded = [
        'user_id', 'transaction_id', 'subscription_id', 'third_party_ref', 'updated_at'
        //add more guarded columns here
    ];

    /**
     * Create a new Transaction instance.
     *
     * @return void
     */
    public function __construct($values = [])
    {
        parent::__construct($values, $this);
    }
}
