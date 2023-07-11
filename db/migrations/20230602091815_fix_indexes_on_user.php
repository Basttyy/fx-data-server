<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixIndexesOnUser extends AbstractMigration
{
    const TABLE_NAME = 'users';
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
        $table->removeIndex(['username', 'email', 'uuid'])
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['uuid'], ['unique' => true])
            ->update();
    }
}
