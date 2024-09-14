<?php
declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Phinx\Migration\AbstractMigration;

final class Users extends AbstractMigration
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
        $table->addColumn('username', 'string', ['limit' => 30])
            ->addColumn('email', 'string', ['limit' => 100])
            ->addColumn('verified', 'string', ['null' => true, 'limit' => 36])
            ->addColumn('uuid', 'string', ['null' => true, 'limit' => 36])
            ->addColumn('password', 'string', ['null' => true])
            ->addColumn('phone', 'string', ['default' => ''])
            ->addColumn('firstname', 'string')
            ->addColumn('lastname', 'string')
            ->addColumn('country', 'string', ['limit'=> 60, 'null' => true])
            ->addColumn('city', 'string', ['limit'=> 60, 'null' => true])
            ->addColumn('address', 'string', ['null' => true])
            ->addColumn('avatar', 'string', ['default' => "default_avatar.png"])
            ->addColumn('access_token', 'string', ['null' => true])
            ->addColumn('twofa_secret', 'string', ['null' => true])
            ->addColumn('email2fa_token', 'string', ['null' => true])
            ->addColumn('email2fa_expire', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('twofa_default_type', 'enum', ['values' => ','.User::EMAIL.','.User::GOOGLE2FA, 'default' => User::EMAIL, 'default' => ''])
            ->addColumn('twofa_types', 'string', ['null' => true, 'default' => ''])              //will contain comma seperated string of on or more of the above
            ->addColumn('postal_code', 'biginteger', ['null' => true, 'signed' => false])
            ->addColumn('status', 'string', ['default' => User::UNVERIFIED])
            ->addColumn('level', 'integer', ['default' => 1, 'signed' => false])
            ->addColumn('referral_code', 'string', ['null' => true])
            ->addColumn('points', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('dollar_per_point', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('role_id', 'integer', ['default' => Role::USER, 'signed' => false])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addTimestamps()
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['uuid', 'referral_code'], ['unique' => true])
            ->create();
    }
}
