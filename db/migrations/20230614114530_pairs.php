<?php
declare(strict_types=1);

use Basttyy\FxDataServer\Models\Pair;
use Phinx\Migration\AbstractMigration;

final class Pairs extends AbstractMigration
{
    const TABLE_NAME = 'pairs';
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
        $table->addColumn('name', 'string')
            ->addColumn('description', 'string', ['null' => true])
            ->addColumn('decimal_places', 'integer', ['default' => 2])
            ->addColumn('status', 'enum', ['values' => Pair::ENABLED.','.Pair::DISABLED, 'default' => Pair::ENABLED])
            ->addColumn('dollar_per_pip', 'decimal')
            ->addColumn('history_start', 'timestamp')
            ->addColumn('history_end', 'timestamp')
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addIndex('name', ['unique' => true])
            ->addTimestamps()
            ->create();
    }
}
