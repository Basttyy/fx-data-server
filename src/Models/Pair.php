<?php
namespace Basttyy\FxDataServer\Models;

final class Pair extends Model
{
    const ENABLED = "enabled";
    const DISABLED = "disabled";

    protected $softdeletes = true;
    protected $table = 'pairs';
    protected $primaryKey = 'id';

    //oject properties
    public $id;
    public $name;
    public $description;
    public $decimal_places;
    public $status;
    public $dollar_per_pip;
    public $history_start;
    public $history_end;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'description', 'decimal_places', 'status', 'dollar_per_pip', 'history_start', 'history_end', 'deleted_at', 'created_at', 'updated_at'
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