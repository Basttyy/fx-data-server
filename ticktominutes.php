<?php
require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/src/libs/helpers.php";

use Carbon\Carbon;

$tickers = explode(',', strtoupper($argv[1]));
$years = explode(',', $argv[2]);
$mins = isset($argv[3]) ? explode(',', $argv[3]) : "1";

$datetime = new Carbon();
///FRIDAY 1min candles count is 1320
///SUNDAY 1min candles count is 120
///SATURDAY 1min candles count is 0
///OTHERS 1min candles count is 1440
///FRIDAY and SUNDAY forms one day

// echo "$ticker : $year";
// exit(0);
foreach ($tickers as $ticker) {
    foreach ($mins as $min) {
        foreach ($years as $year) {
            for ($month = 1; $month <= 12; $month++) {
                $days = $datetime->month($month)->daysInMonth;
                for ($day = 1; $day <= $days; $day++) {
                    $datetime->setDate((int)$year, $month, $day);
                    $datetime->setTime(0, 0);
                    
                    $mn = $month < 10 ? "0$month" : "$month";
                    $dy = $day < 10 ? "0$day" : "$day";
                    $path = __DIR__."/minute_data/{$min}mins/$ticker/$year/$mn/";

                    // if ((int)$min > 2) {
                    //     if (!$files = getFilesList3($ticker, $datetime, $min)) {
                    //         echo "failed to get file lists for $year-$month-$day".PHP_EOL;
                    //         continue;
                    //     }
                    //     compoundMinute();
                    //     continue;
                    // }
                    if (!$files = getFilesList2($ticker, $datetime)) {
                        echo "failed to get file list for $year-$month-$day".PHP_EOL;
                        continue;
                        // return $files;
                    }
            
                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }
                    $path = $path."$year-$mn-{$dy}_data.csv";
                    generateMinuteData($files, $path, (int)$min);
                    //sleep(1);
                }
                consoleLog("info", "done with month: $mn");
                //sleep(1);
            }
            consoleLog("info", "done with year: $year");
            //sleep(2);
        }
    }
}

function getFilesList3(string $ticker, Carbon &$datetime, int $mins): bool|array
{
    echo $datetime->toString().PHP_EOL;
    $files = array(); $i = 0; $s = 0; $date_valid = true;
    while ($date_valid) {
        $date_valid = $datetime->hour < 23 && !$datetime->isToday();
        $file_path = __DIR__."/minute_data/{$mins}min/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}_data.csv";
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
        
        $mins_count = 0;
        while (1) {
            $nums = $timeframe/60;

            if (($csv_row = fgetcsv($fh, 60, ',')) === false) {
                $timestamp = $timestamp*1000;
                // consoleLog("info", "end of file detected");
                if ($nums > 1 && $mins_count >= $nums) {
                    fputcsv($f_result, [$timestamp, $open, $close, $high, $low, $volume]);
                    // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                    $timestamp = 0; $datetime = 0; $firsttime = 0; $minutes = 0; $canpush = false;
                    $open = 0; $close = 0; $high = 0; $low = 0; $turnover = 0; $volume = 0; $multiplier = 0;
                    $mins_count = 0;
                } else if ($nums <= 1) {
                    fputcsv($f_result, [$timestamp, $open, $close, $high, $low, $volume]);
                    // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                    $timestamp = 0; $datetime = 0; $firsttime = 0; $minutes = 0; $canpush = false;
                    $open = 0; $close = 0; $high = 0; $low = 0; $turnover = 0; $volume = 0; $multiplier = 0;
                    $mins_count = 0;
                }

                break;
            }
            // print_r($csv_row);
            $datetime = (float)$csv_row[0];
            $minutes = $datetime/60;
            
            if ($firsttime === 0 || $minutes >= $firsttime) {
                if ($canpush) {
                    $timestamp = $timestamp*1000;
                    $canpush = false;
                    fputcsv($f_result, [$timestamp, $open, $close, $high, $low, $volume]);
                    // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                    $timestamp = $timestamp/1000;
                }
                $timestamp = $datetime;
                if ($firsttime === 0) {
                    $firsttime = $minutes+$timeframe/$nums;
                } else {
                    $firsttime += $timeframe;
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
        //sleep(2);
    }
    fclose($f_result);
    unset($f_result);

    return true;
}

function compoundMinute(array $files, string $result_path, int $timeframe, bool $use_bid = false)
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
                fputcsv($f_result, [$timestamp, $open, $close, $high, $low, $volume]);
                // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
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
                    fputcsv($f_result, [$timestamp, $open, $close, $high, $low, $volume]);
                    // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                    $timestamp = $timestamp/1000;
                }
                $timestamp = $datetime;
                if ($firsttime === 0) {
                    $firsttime = $minutes+1;
                } else {
                    $firsttime += $timeframe;
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
        //sleep(2);
    }
    fclose($f_result);
    unset($f_result);

    return true;
}