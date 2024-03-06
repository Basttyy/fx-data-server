<?php
namespace Basttyy\FxDataServer\Models;

final class Pair extends Model
{
    const ENABLED = "enabled";
    const DISABLED = "disabled";

    const FX = 'fx';
    const CRYPTO = 'crypto';
    const STOCKS = 'stock';
    const COMODITY = 'commodity';
    const INDEX = 'index';
    const FUTURES = 'futures';

    protected $softdeletes = true;
    protected $table = 'pairs';
    protected $primaryKey = 'id';

    //oject properties
    public $id;
    public $name;
    public $description;
    public $status;
    public $history_start;
    public $history_end;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    //object symbol properties
    public $exchange;
    public $market;
    public $short_name;
    public $ticker;
    public $price_precision;
    public $volume_precision;
    public $price_currency;
    public $dollar_per_pip;
    public $type;
    public $logo;

    /**
     * object properties that are used by Pair
     * 
     * @var array
     */
    public $pairinfos = [
        'id', 'name', 'description', 'price_precision', 'status', 'history_start', 'history_end'
    ];
    /**
     * object properties that are used by SymbolInfo in Klinecharts only
     * 
     * @var array
     */
    public $symbolinfos = [
        'description', 'exchange', 'market', 'short_name', 'ticker', 'price_precision', 'volume_precision', 'price_currency', 'dollar_per_pip', 'type', 'logo'
    ];

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'description', 'status', 'dollar_per_pip', 'exchange', 'market', 'short_name', 'ticker', 'price_precision', 'volume_precision', 'price_currency', 'type', 'logo', 'history_start', 'history_end', 'deleted_at', 'created_at', 'updated_at'
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
     * Create a new plan instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct($this);
    }
}