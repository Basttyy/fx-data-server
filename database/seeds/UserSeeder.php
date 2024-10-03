<?php
// require __DIR__. "/../../src/libs/helpers.php";

use Dotenv\Dotenv;
use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        // $dotenv = strtolower(PHP_OS_FAMILY) === 'windows' ? Dotenv::createImmutable(__DIR__."\\..\\..\\") : Dotenv::createImmutable(__DIR__.'/../../');
        // $dotenv->safeLoad();

        // $dotenv->required(['TEST_USER_NAME', 'TEST_USER', 'TEST_PASS'])->notEmpty();
        $ids = $this->fetchRow("SELECT id FROM roles WHERE name = 'admin'");
        $id = !$ids ? 1 : $ids[0];
        $data = [
            [
                'username' => env('TEST_USER_NAME'),
                'email' => env('TEST_USER'),
                'password' => password_hash(env('TEST_PASS'), PASSWORD_BCRYPT),
                'phone' => '08123456789',
                'firstname' => 'Jhony',
                'lastname' => 'Basttyy',
                'status' => 'active',
                'role_id' => $id
            ]
        ];

        $users = $this->table('users');
        $users->insert($data)->saveData();
    }
}
