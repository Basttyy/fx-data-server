<?php
namespace Basttyy\FxDataServer\Models;

final class Strategy extends Model
{
    protected $softdeletes = true;
    protected $table = 'strategy';
    protected $primaryKey = 'id';

    //oject properties
    public $id;
    public $name;
    public $description;
    public $logo;
    public $user_id;
    public $pairs;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'description', 'logo', 'user_id', 'pairs', 'deleted_at', 'created_at', 'updated_at'
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
     * Create a new Strategy instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct($this);
    }
}