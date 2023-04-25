<?php
require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."\\src\\helpers.php";

use Carbon\Carbon;

$tickers = explode(',', strtoupper($argv[1]));
$years = explode(',', $argv[2]);

$datetime = new Carbon();

// echo "$ticker : $year";
// exit(0);
foreach ($tickers as $ticker) {
    foreach ($years as $year) {
        for ($month = 1; $month <= 12; $month++) {
            $days = $datetime->month($month)->daysInMonth;
            for ($day = 1; $day <= $days; $day++) {
                $datetime->setDate((int)$year, $month, $day);
                $datetime->setTime(0, 0);
                if (!$files = getFilesList2($ticker, $datetime)) {
                    echo "failed to get file list for $year-$month-$day".PHP_EOL;
                    continue;
                    // return $files;
                }
                $path = __DIR__."/minute_data/$ticker/$year/$month/";
        
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                }
                $path = $path."$year-$month-{$day}_data.csv";
                generateMinuteData($files, $path, 1);
                sleep(1);
            }
            consoleLog("info", "done with month: $month");
            sleep(1);
        }
        consoleLog("info", "done with year: $year");
        sleep(2);
    }
}

function getFilesList2(string $ticker, Carbon &$datetime): bool|array
{
    echo $datetime->toString().PHP_EOL;
    $files = array(); $i = 0; $s = 0; $date_valid = true;
    while ($date_valid) {
        $date_valid = $datetime->hour < 23 && !$datetime->isToday();
        $file_path = __DIR__."/download/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}--{$datetime->format('H')}h_ticks.csv";
        str_replace('/', "\\", $file_path);
        // consoleLog('info', $file_path);
        if (!file_exists($file_path)) {
            $datetime->addHour();
            continue;
        }
        array_push($files, $file_path);
        $datetime->addHour();
        $i++;
        // sleep(1);
    }
    if (count($files))
        return $files;
    return false;
}

function generateMinuteData(array $files, string $result_path, int $timeframe, bool $use_bid = false): bool
{
    if (file_exists($result_path)) {
        consoleLog('info', "file $result_path exists, skipping");
        return true;
    }
    if(!is_array($files)) {
        throw new Exception('`$files` must be an array');
        return false;
    }

    if (!$f_result = fopen($result_path, 'w')) {
        consoleLog('error', "unable to open destination file: $result_path");
        return false;
    }

    $datetime = 0;
    $firsttime = 0; $minutes = 0; $canpush = false; $multiplier = 0;
    $timestamp = 0; $open = 0; $close = 0; $high = 0; $low = 0; $turnover = 0; $volume = 0;
    $price_line = $use_bid ? 1 : 2;
    foreach ($files as $file) {
        if (!$fh = fopen($file, 'r')) {
            consoleLog('error', "unable to open source file: $file");
            return false;
        }
        
        while (1) {
            if (($csv_row = fgetcsv($fh, 60, ',')) === false) {
                $timestamp = $timestamp*1000;
                // consoleLog("info", "end of file detected");
                fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                $timestamp = 0; $datetime = 0; $firsttime = 0; $minutes = 0; $canpush = false;
                $open = 0; $close = 0; $high = 0; $low = 0; $turnover = 0; $volume = 0; $multiplier = 0;

                break;
            }
            // print_r($csv_row);
            $datetime = (float)$csv_row[0];
            $minutes = $datetime/60;
            
            if ($firsttime === 0 || $minutes >= $firsttime) {
                if ($canpush) {
                    $timestamp = $timestamp*1000;
                    $canpush = false;
                    fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                    $timestamp = $timestamp/1000;
                }
                $timestamp = $datetime;
                if ($firsttime === 0) {
                    $firsttime = (float)(strstr((string)$minutes, '.', true))+1;
                } else {
                    $firsttime = $firsttime + $timeframe;
                }
                $canpush = true;
                $open = (float) $csv_row[$price_line];
                $close = (float) $csv_row[$price_line];
                $high = (float) $csv_row[$price_line];
                $low = (float) $csv_row[$price_line];
                $volume = number_format(((float)$volume + (float)$csv_row[$price_line+2])/2, 5);
            } else {
                $canpush = true;
                $high = $high > (float) $csv_row[$price_line] ? $high : (float) $csv_row[$price_line];
                $low = $low < (float) $csv_row[$price_line] ? $low : (float) $csv_row[$price_line];
                $close = (float) $csv_row[$price_line];
                $volume = number_format(((float)$volume + (float)$csv_row[$price_line+2])/2, 5);
            }
            // sleep(1);
        }
        fclose($fh);
        unset($fh);
        sleep(2);
    }
    fclose($f_result);
    unset($f_result);

    return true;
}