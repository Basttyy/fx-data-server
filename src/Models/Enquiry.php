<?php

namespace Basttyy\FxDataServer\Models;

use Basttyy\FxDataServer\Models\Model;

final class Enquiry extends Model
{
    protected $softdeletes = true;

    protected $table = 'enquiries';

    protected $primaryKey = 'id';

    //object properties
    public $id;
    public $created_at;
    public $updated_at;
    public $deleted_at;
    //add more Enquiry's properties here

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected const fillable = [
        'id', 'created_at', 'updated_at', 'deleted_at',
        //add more fillable columns here
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected const guarded = [
        'deleted_at', 'created_at', 'updated_at'
        //add more guarded columns here
    ];

    /**
     * Create a new Enquiry instance.
     *
     * @return void
     */
    public function __construct($values = [])
    {
        parent::__construct($values, $this);
    }
}
