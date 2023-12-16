<?php
declare(strict_types=1);

use Basttyy\FxDataServer\Models\Blog;
use Phinx\Migration\AbstractMigration;

final class Blogs extends AbstractMigration
{
    const TABLE_NAME = 'blogs';
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
        $table->addColumn('title', 'string', [ 'limit' => '512'])
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('slug', 'string', ['limit' => '512', 'null' => false])
            ->addColumn('text', 'string', ['limit' => '256', 'null' => true])
            ->addColumn('draft_text', 'string', ['limit' => '256', 'null' => true])
            ->addColumn('description', 'string', ['limit' => '1024', 'default' => ''])
            ->addColumn('banner', 'string', ['limit' => '256', 'null' => true])
            ->addColumn('section', 'string')
            ->addColumn('status', 'enum', ['values' => Blog::DRAFT.','.Blog::PUBLISHED.','.Blog::PUBLISHED_DRAFT, 'default' => Blog::DRAFT])
            ->addColumn('published_at', 'string', ['default' => ''])
            ->addColumn('publish_updated_at', 'string', ['default' => ''])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addTimestamps()
            ->create();
    }
}
