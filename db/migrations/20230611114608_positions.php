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
            ->addColumn('entrypoint', 'decimal')
            ->addColumn('exitpoint', 'decimal', ['null' => true])
            ->addColumn('stoploss', 'decimal', ['null' => true])
            ->addColumn('takeprofit', 'decimal', ['null' => true])
            ->addColumn('pl', 'decimal', ['null' => true])
            ->addColumn('entrytime', 'timestamp')
            ->addColumn('exittime', 'timestamp', ['null' => true])
            ->addColumn('partials', 'blob', ['null' => true])
            ->addColumn('exittype', 'enum', ['values' => Position::MANUAL_CLOSE. "," .Position::BE. "," .Position::SL. "," .Position::TP, 'null' => true])
            ->addColumn('test_session_id', 'integer', ['signed' => false])
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('deleted_at', 'timestamp', ['default' => null])
            ->addForeignKey('test_session_id', 'test_sessions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addTimestamps()
            ->create();
    }
}
