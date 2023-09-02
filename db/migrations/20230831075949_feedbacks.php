<?php
declare(strict_types=1);

use Basttyy\FxDataServer\Models\Feedback;
use Phinx\Migration\AbstractMigration;

final class Feedbacks extends AbstractMigration
{
    const TABLE_NAME = 'feedbacks';
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
        $table->addColumn('description', 'string')
            ->addColumn('pair', 'string', ['null' => true])
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('resolve_count', 'integer', ['default' => 0])
            ->addColumn('status', 'enum', ['values' => Feedback::PENDING.','.Feedback::REOPENED.','.Feedback::RESOLVED.','.Feedback::RESOLVING.','.Feedback::STALED, 'default' => Feedback::PENDING])
            ->addColumn('image', 'string', ["null" => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addColumn('date', 'timestamp', ['null' => true])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addTimestamps()
            ->create();
    }
}
