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
                'history_end' => '2023-06-30 00:00:00',
                'exchange' => 'Tradingio',
                'market' => Pair::FX,
                'short_name' => 'GBPUSD',
                'ticker' => 'GBPUSD',
                'min_move' => '0.00001',
                'price_precision' => '5',
                'volume_precision' => '5',
                'price_currency' => 'usd'
            ],
            [
                'name' => 'EURUSD',
                'description' => 'European Euro vs US Dollar',
                'status' => Pair::ENABLED,
                'dollar_per_pip' => 10,
                'history_start' => '2016-01-01 00:00:00',
                'history_end' => '2023-06-30 00:00:00',
                'exchange' => 'Tradingio',
                'market' => Pair::FX,
                'short_name' => 'EURUSD',
                'ticker' => 'EURUSD',
                'min_move' => '0.00001',
                'price_precision' => '5',
                'volume_precision' => '5',
                'price_currency' => 'usd'
            ],
            [
                'name' => 'XAUUSD',
                'description' => 'Gold vs US Dollar',
                'status' => Pair::ENABLED,
                'dollar_per_pip' => 10,
                'history_start' => '2016-01-01 00:00:00',
                'history_end' => '2023-06-30 00:00:00',
                'exchange' => 'Tradingio',
                'market' => Pair::COMODITY,
                'short_name' => 'XAUUSD',
                'ticker' => 'XAUUSD',
                'min_move' => '0.001',
                'price_precision' => '3',
                'volume_precision' => '3',
                'price_currency' => 'usd'
            ]
        ];

        $pairs = $this->table('pairs');
        $pairs->insert($data)->saveData();
    }
}
