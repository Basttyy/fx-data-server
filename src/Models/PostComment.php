<?php

namespace Basttyy\FxDataServer\Models;

use Basttyy\FxDataServer\Models\Model;

final class  PostComment extends Model
{
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    protected $softdeletes = true;

    protected $table = 'post_comments';

    protected $primaryKey = 'id';

    //object properties
    public $id;
    public $post_id;
    public $post_comment_id;
    public $username;
    public $text;
    public $status;
    public $created_at;
    public $updated_at;
    public $deleted_at;
    //add more PostComment's properties here

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'post_id', 'post_comment_id', 'username', 'text', 'status', 'created_at', 'updated_at', 'deleted_at',
        //add more fillable columns here
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected $guarded = [
        'deleted_at', 'updated_at'
        //add more guarded columns here
    ];

    /**
     * Create a new PostComment instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct($this);
    }
}
