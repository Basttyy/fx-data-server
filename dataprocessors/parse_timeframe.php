<?php
require_once __DIR__."/../vendor/autoload.php";
require_once __DIR__."/../src/libs/helpers.php";
require_once __DIR__."/data_helpers.php";

use Carbon\Carbon;

$compressedPath = $zip ? 'compressed' : ''; $zipExt = $zip ? '.gz' : '';
foreach ($tickers as $ticker) {
    foreach ($mins as $min) {
        foreach ($years as $year) {
            echo "got to year $year".PHP_EOL;
            $datetime->setDate($year, 1, 1)->setTime(0, 0, 0, 0);
            $lastday = Carbon::parse("last day of December $year");

            while ($datetime->lte($lastday)) {
                $month = $datetime->month;
                $day = $datetime->day;
                $mn = $month < 10 ? "0$month" : "$month";
                $dy = $day < 10 ? "0$day" : "$day";

                if ((int)$min < 60) {
                    $source_tf = 1;
                    $path = __DIR__."/../minute_data/download/$offerside/{$min}mins/$ticker/$year/$mn/";
                    
                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }
                    $path = $path."$year-$mn-{$dy}_data.csv";
                    echo $path.PHP_EOL;
                    compoundMinute($ticker, $datetime, $path, (int)$min, $source_tf);
                    $datetime->addDays();
                } else if ((int)$min >= 60 && (int)$min < 1440) {
                    $source_tf = 60;
                    $path = __DIR__."/../minute_data/$compressedPath/$offerside/monthly/{$min}mins/$ticker/$year/";
                    
                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }
                    $path = $path."month-{$mn}_data.csv$zipExt";
                    echo $path.PHP_EOL;
                    compoundMinute($ticker, $datetime, $path, (int)$min, $source_tf, offerside: $offerside, zip: $zip);
                    $datetime->addMonth();
                // } else if ((int)$min >= 240 && (int)$min < 1440) {
                //     // consoleLog('info', 'using 240min timeframe');
                //     $source_tf = 60;
                //     $path = __DIR__."/../minute_data/download/filtered/$offerside/{$min}mins/$ticker/$year/";
                                        
                //     if (!is_dir($path)) {
                //         mkdir($path, 0777, true);
                //     }
                //     $result_path = $path."$year-{$mn}_data.csv";
                //     compoundMinute($ticker, $datetime, $result_path, (int)$min, $source_tf);
                //     $paths = [];
                //     $datetime->addMonth();
                } else if ((int)$min >= 1440) {
                    // consoleLog('info', 'using 1440min timeframe');
                    $source_tf = 1440;
                    $path = __DIR__."/../minute_data/$compressedPath/$offerside/yearly/{$min}mins/$ticker/";
                                        
                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }
                    $result_path = $path."$year-{$min}min_data.csv$zipExt";
                    compoundMinute($ticker, $datetime, $result_path, (int)$min, $source_tf, offerside: $offerside, zip: $zip);
                    $paths = [];
                    $datetime->addMonth();
                }
                
                // $datetime->addDays();
                usleep(100000);
            }

            consoleLog("info", "done with year: $year");
            usleep(300000);
        }
        consoleLog("info", "done with {$min}mins timeframe");
    }
    consoleLog("info", "done with pair: $ticker");
}

$datetime = $datetime;