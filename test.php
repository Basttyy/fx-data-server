<?php
require_once __DIR__."/vendor/autoload.php";

use Carbon\Carbon;

use Basttyy\FxDataServer\libs\NumberConverter;

$tickers = isset($argv[1]) ? explode(',', strtoupper($argv[1])) : [];
$years = isset($argv[2]) ? explode(',', $argv[2]) : [];

$datetime = new Carbon();

foreach ($tickers as $ticker) {
    $dest_path = __DIR__."/../../csv_backup_files/$ticker/";
    foreach ($years as $year) {
        echo "got to year $year".PHP_EOL;
        $datetime->setDate($year, 1, 1);
        $lastday = Carbon::create($year, 9, 24);

        while ($datetime->lte($lastday)) {
            $date_valid = true;
            // while ($date_valid) {
            //     $date_valid = $datetime->hour < 23 && !$datetime->isToday();
                while ($datetime->dayOfWeekIso !== 5) {
                    $datetime->addDay();
                }
                $file_path = __DIR__."/download/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}--21h_ticks.csv";
                str_replace('/', "\\", $file_path);
                $datetime->addDays(2);
                $file_path2 = __DIR__."/download/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}--21h_ticks.csv";
                str_replace('/', "\\", $file_path2);
                // echo $file_path.PHP_EOL;
                // echo $file_path2.PHP_EOL.PHP_EOL;
                if (file_exists($file_path) && file_exists($file_path2)) {
                    echo "duplicate:: moving $file_path2".PHP_EOL;
                    if (!file_exists($dest_path)) {
                        mkdir($dest_path);
                    }
                    if (!rename($file_path2, $dest_path."{$datetime->format('Y-m-d')}--21h_ticks.csv")) {
                        echo "fatal error, unable to move file";
                        exit (1);
                    }

                    // unlink($file_path2);
                    usleep(1000000);
                }
                usleep(10000);
            // }
        }
    }
}

exit (0);

foreach ($tickers as $ticker) {
    foreach ($years as $year) {
        echo "got to year $year".PHP_EOL;
        $datetime->setDate($year, 1, 1);
        $lastday = Carbon::create($year, 9, 24);

        
        while ($datetime->lte($lastday)) {
            $date_valid = true;
            // while ($date_valid) {
            //     $date_valid = $datetime->hour < 23 && !$datetime->isToday();
                $file_path = __DIR__."/download/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}--{$datetime->format('H')}h_ticks.csv";
                str_replace('/', "\\", $file_path);
                // consoleLog('info', $file_path);
                if (in_array($datetime->dayOfWeekIso, [6,7]) && file_exists($file_path)) {
                    echo "removing $file_path".PHP_EOL;
                    unlink($file_path);
                    usleep(400000);
                    // $datetime->addHour();
                }
                $datetime->addHour();
                usleep(20000);
            // }
        }
    }
}