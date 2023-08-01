<?php
declare(strict_types=1);

use Basttyy\FxDataServer\Models\Plan;
use Phinx\Migration\AbstractMigration;

final class Plans extends AbstractMigration
{
    const TABLE_NAME = 'plans';
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
        $table->addColumn('name', 'string', ['limit' => 30])
            ->addColumn('description', 'string')
            ->addColumn('price', 'decimal', ['precision' => 5, 'scale' => 2])
            ->addColumn('status', 'string', ['default' => Plan::ENABLED])
            ->addColumn('features', 'string')
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addTimestamps()
            ->create();
    }
}
