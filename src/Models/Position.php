<?php
namespace Basttyy\FxDataServer\Models;

final class Position extends Model
{
    const BUY = "buy";
    const SELL = "sell";
    const BUY_STOP = 'buystop';
    const BUY_LIMIT = 'buylimit';
    const SELL_STOP = 'sellstop';
    const SELL_LIMIT = 'selllimit';

    const SL = 'stoploss';
    const TP = 'takeprofit';
    const BE = 'breakeven';
    const MANUAL_CLOSE = 'manualclose';

    protected $softdeletes = true;
    protected $table = 'positions';
    protected $primaryKey = 'id';

    //oject properties
    public $id;
    public $test_session_id;
    public $user_id;
    public $action;
    public $entrypoint;
    public $exitpoint;
    public $stoploss;
    public $takeprofit;
    public $pl;
    public $entrytime;
    public $exittime;
    public $partials;
    public $exittype;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'test_session_id', 'user_id', 'action', 'entrypoint', 'exitpoint', 'stoploss', 'takeprofit', 'pl', 'entrytime', 'exittime', 'partials', 'exittype', 'deleted_at', 'created_at', 'updated_at'
    ];
    
    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected $guarded = [
        'deleted_at', 'created_at', 'updated_at'
    ];

    /**
     * Create a new position instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct($this);
    }
}