<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Subscriptions extends AbstractMigration
{
    const TABLE_NAME = 'subscriptions';
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
        $table->addColumn('duration', 'integer')
            ->addColumn('total_cost', 'decimal')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('plan_id', 'integer', ['signed' => false])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('plan_id', 'plans', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addTimestamps()
            ->create();
    }
}
