<?php
namespace Basttyy\FxDataServer\Models;

final class TestSession extends Model
{
    protected $softdeletes = true;
    protected $table = 'test_sessions';
    protected $primaryKey = 'id';

    //oject properties
    public $id;
    public $starting_bal;
    public $current_bal;
    public $equity;
    public $strategy_id;
    public $user_id;
    public $pair;
    public $chart;
    public $chart_timestamp;
    public $chart_ui;
    public $start_date;
    public $end_date;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    public $listkeys = [
        'id', 'starting_bal', 'current_bal', 'equity', 'strategy_id', 'user_id', 'pair', 'chart_timestamp', 'chart_ui', 'start_date', 'end_date', 'deleted_at', 'created_at', 'updated_at'
    ];
    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected $fillable = [
        'id', 'starting_bal', 'current_bal', 'equity', 'strategy_id', 'user_id', 'pair', 'chart', 'chart_timestamp', 'chart_ui', 'start_date', 'end_date', 'deleted_at', 'created_at', 'updated_at'
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
     * Create a new TestSession instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct($this);
    }
}