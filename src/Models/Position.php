<?php
namespace Basttyy\FxDataServer\Models;

final class Position extends Model
{
    const BUY = "buy";
    const SELL = "sell";

    const SL = 'stoploss';
    const TP = 'takeprofit';
    const BE = 'breakeven';
    const MANUAL_CLOSE = 'manualclose';

    protected $softdeletes = true;
    protected $table = 'positions';
    protected $primaryKey = 'id';

    //oject properties
    public $id;
    public $action;
    public $entry;
    public $exit;
    public $stoploss;
    public $takeprofit;
    public $pl;
    public $opentime;
    public $closetime;
    public $partials;
    public $closetype;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'action', 'entry', 'exit', 'stoploss', 'takeprofit', 'pl', 'opentime', 'closetime', 'partials', 'closetype', 'deleted_at', 'created_at', 'updated_at'
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