<?php

namespace Basttyy\FxDataServer\Models;

use Basttyy\FxDataServer\Models\Model;

final class CheapCountry extends Model
{
    protected $softdeletes = true;

    protected $table = 'cheap_countries';

    protected $primaryKey = 'id';

    //object properties
    public $id;
    public $created_at;
    public $updated_at;
    //add more CheapCountry's properties here

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'created_at', 'updated_at',
        //add more fillable columns here
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected $guarded = [
        'created_at', 'updated_at'
        //add more guarded columns here
    ];

    /**
     * Create a new CheapCountry instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct($this);
    }
}
