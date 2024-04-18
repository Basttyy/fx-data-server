<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Transactions extends AbstractMigration
{
    const TABLE_NAME = 'transactions';
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
        $table->addColumn('status', 'string')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('transaction_id', 'integer', ['signed' => false])
            ->addColumn('subscription_id', 'integer', ['signed' => false])
            ->addColumn('amount', 'decimal', ['precision' => 9, 'scale' => 2])
            ->addColumn('currency', 'string')
            ->addColumn('tx_ref', 'string')
            ->addColumn('third_party_ref', 'string')
            ->addColumn('type', 'string')
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('subscription_id', 'subscriptions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addTimestamps()
            ->create();
    }
}
