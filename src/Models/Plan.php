<?php
namespace Basttyy\FxDataServer\Models;

final class Plan extends Model
{
    const ENABLED = "enabled";
    const DISABLED = "disabled";

    const INTERVALS = [
        'year',
        'bi-annual',
        'month',
        'week',
        'day',
    ];

    protected $softdeletes = true;
    protected $table = 'plans';
    protected $primaryKey = 'id';

    //oject properties
    public $id;
    public $name;
    public $description;
    public $duration_interval;
    public $price;
    public $status;
    public $features;
    public $third_party_id;
    public $plan_token;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'description', 'duration_interval', 'price', 'status', 'features', 'plan_token', 'third_party_id', 'deleted_at', 'created_at', 'updated_at'
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