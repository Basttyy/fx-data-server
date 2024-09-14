<?php
namespace App\Models;

use Eyika\Atom\Framework\Support\Database\Concerns\HasRelationships;

final class Strategy extends Model
{
    use HasRelationships;

    protected $softdeletes = true;
    protected $table = 'strategies';
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
    protected const fillable = [
        'id', 'name', 'description', 'logo', 'user_id', 'pairs', 'deleted_at', 'created_at', 'updated_at'
    ];
    
    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected const guarded = [
        'deleted_at', 'created_at', 'updated_at'
    ];

    /**
     * Create a new Strategy instance.
     *
     * @return void
     */
    public function __construct($values = [])
    {
        parent::__construct($values, $this);
    }

    public function user(): User
    {
        return $this->belongsTo(User::class);
    }
}