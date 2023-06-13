<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RequestLog extends AbstractMigration
{
    const TABLE_NAME = 'request_logs';
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
        $table->addColumn('ip', 'string')
            ->addColumn('method', 'string')
            ->addColumn('uripath', 'string')
            ->addColumn('body', 'string', ['null' => true])
            ->addTimestamps()
            ->create();
    }
}
