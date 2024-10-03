<?php


use Phinx\Seed\AbstractSeed;

class CheapCountrySeeder extends AbstractSeed
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
        $data = [
            [
                'name' => 'nigeria',
                'continent' => 'africa'
            ],
            [
                'name' => 'ghana',
                'continent' => 'africa'
            ],
            [
                'name' => 'togo',
                'continent' => 'africa'
            ],
            [
                'name' => 'cameroon',
                'continent' => 'africa'
            ],
            [
                'name' => 'kenya',
                'continent' => 'africa'
            ],
            [
                'name' => 'ivory coast',
                'continent' => 'africa'
            ]
        ];

        $countries = $this->table('cheap_countries');
        $countries->insert($data)->saveData();
    }
}
