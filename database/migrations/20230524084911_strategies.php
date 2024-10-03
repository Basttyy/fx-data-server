<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Strategies extends AbstractMigration
{
    const TABLE_NAME = 'strategies';
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
        $table->addColumn('name', 'string', ['limit' => 228])
            ->addColumn('description', 'string')
            ->addColumn('logo', 'string', ['null' => true])
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('pairs', 'string', ["null" => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addTimestamps()
            ->create();
    }
}
