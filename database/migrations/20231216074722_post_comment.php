<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class PostComment extends AbstractMigration
{
    const TABLE_NAME = 'post_comments';
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
        $table->addColumn('post_id', 'integer', ['signed' => false])
            ->addColumn('post_comment_id', 'integer', ['signed' => false])
            ->addColumn('username', 'string', ['limit' => 128, 'default' => ''])
            ->addColumn('text', 'string', ['null' => false, 'limit' => 1024])
            ->addColumn('status', 'string', ['null' => false])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('post_id', 'blog_posts', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('post_comment_id', 'post_comments', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addTimestamps()
            ->create();
    }
}
