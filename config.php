<?php

function searchTicker(string $query = "")
{
    $tickers = [
        (object) [
            'shortName' => 'EURUSD', 'ticker' => 'EURUSD', 'name' => 'Euro against US Dollar', 'exchange' => '',
            'market' => 'fx', 'priceCurrency' => 'usd', 'type' => 'ADRC', 'pricePrecision' => 5, 'volumePrecision' => 4
        ],
        (object) [
            'shortName' => 'GBPUSD', 'ticker' => 'GBPUSD', 'name' => 'Great Britain Pound against US Dollar', 'exchange' => '',
            'market' => 'fx', 'priceCurrency' => 'usd', 'type' => 'ADRC', 'pricePrecision' => 5, 'volumePrecision' => 4
        ],
        (object) [
            'shortName' => 'XAUUSD', 'ticker' => 'XAUUSD', 'name' => 'Goldspot vs United State Dollar', 'exchange' => '',
            'market' => 'fx', 'priceCurrency' => 'usd', 'type' => 'ADRC', 'pricePrecision' => 3, 'volumePrecision' => 3
        ],
    ];

    $query = strtolower($query);

    if ($query = "")
        return $tickers;

    $data = array_filter($tickers, function ($symbol) use ($query) {
        return str_contains(strtolower($symbol->exchange), $query)
        || str_contains(strtolower($symbol->market), $query)
        || str_contains(strtolower($symbol->name), $query)
        || str_contains(strtolower($symbol->ticker), $query)
        || str_contains(strtolower($symbol->priceCurrency), $query)
        || str_contains(strtolower($symbol->type), $query);
    });

    return $data;
}