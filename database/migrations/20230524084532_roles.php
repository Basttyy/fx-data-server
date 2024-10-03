<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Roles extends AbstractMigration
{
    const TABLE_NAME = 'roles';
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
        //$table->addColumn('id', 'integer')
        $table->addColumn('name', 'string', ['limit' => 30])
            ->addColumn('previleges', 'string', ['limit' => 256])
            ->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['previleges'], ['unique' => true])
            ->create();
    }
}
