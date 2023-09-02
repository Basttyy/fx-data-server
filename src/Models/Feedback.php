<?php
namespace Basttyy\FxDataServer\Models;

final class Feedback extends Model
{
    const PENDING = 'pending';
    const REOPENED = 'reopened';
    const RESOLVING = 'resolving';
    const STALED = 'staled';
    const RESOLVED = 'resolved';

    protected $softdeletes = true;
    protected $table = 'feedbacks';
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
        'id', 'description', 'pair', 'user_id', 'image', 'resolve_count', 'status', 'date', 'deleted_at', 'created_at', 'updated_at'
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