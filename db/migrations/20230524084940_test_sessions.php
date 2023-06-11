<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TestSessions extends AbstractMigration
{
    const TABLE_NAME = 'test_sessions';
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table($this::TABLE_NAME);
        $table->addColumn('starting_bal', 'decimal')
            ->addColumn('current_bal', 'decimal')
            ->addColumn('strategy_id', 'integer', ['null' => true])
            ->addColumn('chart_id', 'integer', ['null' => true])
            ->addColumn('pairs', 'string')
            ->addColumn('chart', 'mediumblob', ['null' => true])  //chart data should be a compressed serialized array of three objects ['overlays', 'style', 'positions']
            ->addColumn('chart_timestamp', 'string', ['null' => true])
            ->addColumn('start_date', 'timestamp')
            ->addColumn('end_date', 'timestamp')
            ->addColumn('current_date', 'timestamp')
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('strategy_id', 'strategies', options: ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
            ->addTimestamps()
            ->create();
    }
}
