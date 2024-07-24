<?php
namespace Basttyy\FxDataServer\Models;

final class Blog extends Model
{
    const DRAFT = 'draft';
    const PUBLISHED_DRAFT = 'published_draft';
    const PUBLISHED = 'published';

    const SECTIONS = ['general', 'guide', 'features', 'course', 'training'];

    protected $softdeletes = true;
    protected $table = 'blogs';
    protected $primaryKey = 'id';

    //oject properties
    // public $id;
    public $user_id;
    public $title;
    public $slug;
    public $description;
    public $text;
    public $draft_text;
    public $section;
    public $banner;
    public $status;
    public $published_at;
    public $publish_updated_at;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'user_id', 'title', 'slug', 'description', 'section', 'banner', 'text', 'draft_text', 'status', 'published_at', 'publish_updated_at', 'deleted_at', 'created_at', 'updated_at'
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
     * Create a new Blog instance.
     *
     * @return void
     */
    public function __construct($values = [])
    {
        parent::__construct($values, $this);
    }
}