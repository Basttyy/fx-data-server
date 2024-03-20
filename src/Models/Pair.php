<?php
namespace Basttyy\FxDataServer\Models;

final class Pair extends Model
{
    const ENABLED = "enabled";
    const DISABLED = "disabled";

    const FX = 'forex';
    const CRYPTO = 'crypto';
    const STOCKS = 'stock';
    const COMODITY = 'commodity';
    const INDEX = 'index';
    const FUTURES = 'futures';

    const TIMEZONES =
        "Etc/UTC, Africa/Casablanca, Africa/Johannesburg, Africa/Lagos, Africa/Nairobi, ".
        "Africa/Tunis, America/Anchorage, America/Argentina/Buenos_Aires, America/Bogota, ".
        "America/Caracas, America/Chicago, America/El_Salvador, America/Juneau, America/Lima, ".
        "America/Los_Angeles, America/Mexico_City, America/New_York, America/Phoenix, ".
        "America/Santiago, America/Sao_Paulo, America/Toronto, America/Vancouver, Asia/Almaty, ".
        "Asia/Ashkhabad, Asia/Bahrain, Asia/Bangkok, Asia/Chongqing, Asia/Colombo, Asia/Dhaka, ".
        "Asia/Dubai, Asia/Ho_Chi_Minh, Asia/Hong_Kong, Asia/Jakarta, Asia/Jerusalem, Asia/Karachi, ".
        "Asia/Kathmandu, Asia/Kolkata, Asia/Kuwait, Asia/Manila, Asia/Muscat, Asia/Nicosia, ".
        "Asia/Qatar, Asia/Riyadh, Asia/Seoul, Asia/Shanghai, Asia/Singapore, Asia/Taipei, ".
        "Asia/Tehran, Asia/Tokyo, Asia/Yangon, Atlantic/Reykjavik, Australia/Adelaide, ".
        "Australia/Brisbane, Australia/Perth, Australia/Sydney, Europe/Amsterdam, Europe/Athens, ".
        "Europe/Belgrade, Europe/Berlin, Europe/Bratislava, Europe/Brussels, Europe/Bucharest, ".
        "Europe/Budapest, Europe/Copenhagen, Europe/Dublin, Europe/Helsinki, Europe/Istanbul, ".
        "Europe/Lisbon, Europe/London, Europe/Luxembourg, Europe/Madrid, Europe/Malta, ".
        "Europe/Moscow, Europe/Oslo, Europe/Paris, Europe/Prague, Europe/Riga, Europe/Rome, ".
        "Europe/Stockholm, Europe/Tallinn, Europe/Vienna, Europe/Vilnius, Europe/Warsaw, ".
        "Europe/Zurich, Pacific/Auckland, Pacific/Chatham, Pacific/Fakaofo, Pacific/Honolulu, ".
        "Pacific/Norfolk, US/Mountain, Africa/Cairo"
    ;

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
    public $timezone;
    public $min_move;
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
        'description', 'exchange', 'market', 'short_name', 'ticker', 'timezone', 'min_move', 'price_precision', 'volume_precision', 'price_currency', 'dollar_per_pip', 'type', 'logo'
    ];

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'description', 'status', 'dollar_per_pip', 'exchange', 'market', 'short_name', 'ticker', 'timezone', 'min_move', 'price_precision', 'volume_precision', 'price_currency', 'type', 'logo', 'history_start', 'history_end', 'deleted_at', 'created_at', 'updated_at'
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