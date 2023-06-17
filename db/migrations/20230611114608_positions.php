<?php
declare(strict_types=1);

use Basttyy\FxDataServer\Models\Position;
use Phinx\Migration\AbstractMigration;

final class Positions extends AbstractMigration
{
    const TABLE_NAME = 'positions';
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
        $table->addColumn('action', 'enum', ['values' => Position::BUY. "," .Position::SELL])
            ->addColumn('entry', 'decimal')
            ->addColumn('exit', 'decimal')
            ->addColumn('stoploss', 'decimal')
            ->addColumn('takeprofit', 'decimal')
            ->addColumn('pl', 'decimal')
            ->addColumn('opentime', 'timestamp')
            ->addColumn('closetime', 'timestamp')
            ->addColumn('partials', 'blob')
            ->addColumn('closetype', 'enum', ['values' => Position::MANUAL_CLOSE. "," .Position::BE. "," .Position::SL. "," .Position::TP])
            ->addColumn('test_session_id', 'integer', ['signed' => false])
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('deleted_at', 'timestamp', ['default' => null])
            ->addForeignKey('test_session_id', 'test_sessions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addTimestamps()
            ->create();
    }
}
