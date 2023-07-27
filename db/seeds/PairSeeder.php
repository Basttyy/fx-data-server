<?php

use Basttyy\FxDataServer\Models\Pair;
use Phinx\Seed\AbstractSeed;

class PairSeeder extends AbstractSeed
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
                'name' => 'GBPUSD',
                'description' => 'Great Britain Pound vs US Dollar',
                'status' => Pair::ENABLED,
                'dollar_per_pip' => 10,
                'history_start' => '2016-01-01 00:00:00',
                'history_end' => '2023-07-30 00:00:00',
                'exchange' => 'Dukascopy',
                'market' => Pair::FX,
                'short_name' => 'GBPUSD',
                'ticker' => 'GBPUSD',
                'price_precision' => '5',
                'volume_precision' => '5',
                'price_currency' => 'usd'
            ],
            [
                'name' => 'EURUSD',
                'description' => 'European Union Euro vs US Dollar',
                'status' => Pair::ENABLED,
                'dollar_per_pip' => 10,
                'history_start' => '2016-01-01 00:00:00',
                'history_end' => '2023-07-30 00:00:00',
                'exchange' => 'Dukascopy',
                'market' => Pair::FX,
                'short_name' => 'EURUSD',
                'ticker' => 'EURUSD',
                'price_precision' => '5',
                'volume_precision' => '5',
                'price_currency' => 'usd'
            ],
            [
                'name' => 'XAUUSD',
                'description' => 'Gold vs US Dollar',
                'status' => Pair::ENABLED,
                'dollar_per_pip' => 1,
                'history_start' => '2016-01-01 00:00:00',
                'history_end' => '2023-07-30 00:00:00',
                'exchange' => 'Dukascopy',
                'market' => Pair::COMODITY,
                'short_name' => 'XAUUSD',
                'ticker' => 'XAUUSD',
                'price_precision' => '2',
                'volume_precision' => '2',
                'price_currency' => 'usd'
            ]
        ];

        $roles = $this->table('pairs');
        $roles->insert($data)->saveData();
    }
}
