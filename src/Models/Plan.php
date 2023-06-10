<?php
namespace Basttyy\FxDataServer\Models;

final class Plan extends Model
{
    const ENABLED = "enabled";
    const DISABLED = "disabled";

    protected $softdeletes = true;
    protected $table = 'users';
    protected $primaryKey = 'id';

    //oject properties
    public $id;
    public $description;
    public $price;
    public $status;
    public $features;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'description', 'price', 'status', 'features', 'deleted_at', 'created_at', 'updated_at'
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