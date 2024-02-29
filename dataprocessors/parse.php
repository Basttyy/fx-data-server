<?php
require_once __DIR__."../vendor/autoload.php";
require_once __DIR__."../src/libs/helpers.php";
require_once __DIR__."/data_helpers.php";

use Carbon\Carbon;

foreach ($tickers as $ticker) {
    foreach ($years as $year) {
        echo "got to year $year".PHP_EOL;
        $datetime->setDate($year, 1, 1);
        $lastday = Carbon::parse("last day of December $year");
        
        while ($datetime->lte($lastday)) {
            $month = $datetime->month;
            $day = $datetime->day;
            $mn = $month < 10 ? "0$month" : "$month";
            $dy = $day < 10 ? "0$day" : "$day";

            $path = __DIR__."/../minute_data/download/1mins/$offerside/$ticker/$year/$mn/";
                            
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            if (!$files = getTickFilesList2($ticker, $datetime)) {
                echo "failed to get file list for $year-$month-$day".PHP_EOL;
                continue;
                // return $files;
            }
            $path = $path."$year-$mn-{$dy}_data.csv";
            // echo $path.PHP_EOL;
            generateMinuteDataFromTick($files, $path, (int)$min);
        }
    }
}