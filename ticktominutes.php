<?php
require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/src/libs/helpers.php";

// php ticktominutes.php parse xauusd 2016,2017,2018,2019,2020,2021,2022,2023 2,5,15,30,60,120

// if (!$fh = file_get_contents(storage_path()."2021-01-03--22h_ticks.csv")) {
//     consoleLog('error', "unable to read source file: $file");
//     return false;
// }
// if (!$compressed = gzdeflate($fh, encoding: ZLIB_ENCODING_DEFLATE)) {
//     consoleLog('error', "unable to compress file");
//     return false;
// }

// if (!file_put_contents(storage_path()."compressed.csv.gz", $compressed)) {
//     consoleLog('error', "unable to write compressed data to file: $result_path");
//     return false;
// }

// if (!$fh = file_get_contents(storage_path()."compressed.csv.gz")) {
//     consoleLog('error', "unable to read source file: $file");
//     return false;
// }
// if (!$compressed = gzuncompress($fh)) {
//     consoleLog('error', "unable to compress file");
//     return false;
// }

// if (!file_put_contents(storage_path()."uncompressed.csv", $compressed)) {
//     consoleLog('error', "unable to write compressed data to file: $result_path");
//     return false;
// }

// return 0;

use Carbon\Carbon;
$command = $argv[1]; /// compress|parse
$tickers = explode(',', strtoupper($argv[2]));
$years = explode(',', $argv[3]);
$mins = isset($argv[3]) ? explode(',', $argv[4]) : "1";

$datetime = new Carbon();
///FRIDAY 1min candles count is 1320
///SUNDAY 1min candles count is 120
///SATURDAY 1min candles count is 0
///OTHERS 1min candles count is 1440
///FRIDAY and SUNDAY forms one day

// echo "$ticker : $year";
// exit(0);
if ($command === 'parse') {
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
    
                        if ((int)$min >= 2) {
                            // if (!$files = getFilesList3($ticker, $datetime, 1)) {
                            //     echo "failed to get file lists for $year-$month-$day".PHP_EOL;
                            //     continue;
                            // }
                        } else if (!$files = getFilesList2($ticker, $datetime)) {
                            echo "failed to get file list for $year-$month-$day".PHP_EOL;
                            continue;
                            // return $files;
                        }
                
                        if (!is_dir($path)) {
                            mkdir($path, 0777, true);
                        }
                        $path = $path."$year-$mn-{$dy}_data.csv";
                        (int)$min >= 2 ? compoundMinute($ticker, $datetime, $path, (int)$min, 1) : generateMinuteData($files, $path, (int)$min);
                        //sleep(1);
                    }
                    consoleLog("info", "done with month: $mn");
                    //sleep(1);
                }
                consoleLog("info", "done with year: $year");
                //sleep(2);
            }
            consoleLog("info", "done with {$min}mins timeframe");
        }
        consoleLog("info", "done with pair: $ticker");
    }
} else if ($command === 'compress') {
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
                        $path = __DIR__."/minute_data/gziped/{$min}mins/$ticker/$year/$mn/";

                        if (!is_dir($path)) {
                            mkdir($path, 0777, true);
                        }
                        $path = $path."$year-$mn-{$dy}_data.csv";
                        compress($ticker, $datetime, $path, (int)$min);
                        //sleep(1);
                    }
                    consoleLog("info", "done with month: $mn");
                    //sleep(1);
                }
                consoleLog("info", "done with year: $year");
                //sleep(2);
            }
            consoleLog("info", "done with {$min}mins timeframe");
        }
        consoleLog("info", "done with pair: $ticker");
    }
}

function getFilesList3(string $ticker, Carbon &$datetime, int $mins): bool|array
{
    echo $datetime->toString().PHP_EOL;
    $files = array(); $i = 0; $s = 0; $date_valid = true;
    while ($date_valid) {
        $date_valid = $datetime->hour < 23 && !$datetime->isToday();
        $file_path = __DIR__."/minute_data/{$mins}mins/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}_data.csv";
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
            // $nums = $timeframe/60;

            if (($csv_row = fgetcsv($fh, 60, ',')) === false) {
                $timestamp = $timestamp*1000;
                // consoleLog("info", "end of file detected");
                // if ($nums > 1 && $mins_count >= $nums) {
                //     fputcsv($f_result, [$timestamp, $open, $close, $high, $low, $volume]);
                //     // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                //     $timestamp = 0; $datetime = 0; $firsttime = 0; $minutes = 0; $canpush = false;
                //     $open = 0; $close = 0; $high = 0; $low = 0; $turnover = 0; $volume = 0; $multiplier = 0;
                //     $mins_count = 0;
                // } else if ($nums <= 1) {
                    fputcsv($f_result, [$timestamp, $open, $close, $high, $low, $volume]);
                    // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                    $timestamp = 0; $datetime = 0; $firsttime = 0; $minutes = 0; $canpush = false;
                    $open = 0; $close = 0; $high = 0; $low = 0; $turnover = 0; $volume = 0; $multiplier = 0;
                    $mins_count = 0;
                // }

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
                    $firsttime = $minutes+$timeframe;
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

function compress(string $ticker, Carbon $datetime, string $result_path, int $timeframe)
{
    if (file_exists($result_path)) {
        consoleLog('info', "file $result_path exists, skipping");
        return true;
    }

    $file = __DIR__."/minute_data/{$timeframe}mins/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}_data.csv";

    if (!file_exists($file)) {
        // consoleLog(0, "source file not found: skipping...");
        return false;
    }

    if (!$fh = file_get_contents($file)) {
        consoleLog('error', "unable to read source file: $file");
        return false;
    }

    if (!$compressed = gzdeflate($fh, encoding: ZLIB_ENCODING_DEFLATE)) {
        consoleLog('error', "unable to compress file");
        return false;
    }

    if (!file_put_contents($result_path, $compressed)) {
        consoleLog('error', "unable to write compressed data to file: $result_path");
        return false;
    }

    return true;
}

function compoundMinute(string $ticker, Carbon $datetime, string $result_path, int $timeframe, int $source_timeframe)
{
    if (file_exists($result_path)) {
        consoleLog('info', "file $result_path exists, skipping");
        return true;
    }

    $file = __DIR__."/minute_data/{$source_timeframe}mins/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}_data.csv";

    if (!file_exists($file)) {
        // consoleLog(0, "source file not found: skipping...");
        return false;
    }

    if (!$f_result = fopen($result_path, 'w')) {
        consoleLog('error', "unable to open destination file: $result_path");
        return false;
    }

    $datetime = 0;
    $minutes_count = 0; $canpush = false;
    $open = 0; $close = 0; $high = 0; $low = 0; $volume = 0;

    if (!$fh = fopen($file, 'r')) {
        consoleLog('error', "unable to open source file: $file");
        return false;
    }
    
    while (1) {
        if (($csv_row = fgetcsv($fh, 60, ',')) === false) {
            // consoleLog("info", "end of file detected");
            fputcsv($f_result, [$datetime, $open, $close, $high, $low, $volume]);
            // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
            $datetime = 0; $minutes_count = 0; $canpush = false;
            $open = 0; $close = 0; $high = 0; $low = 0; $volume = 0;

            break;
        }
        // print_r($csv_row);
        $datetime = (float)$csv_row[0]*1000;
        
        if ($minutes_count === 0 || $minutes_count >= $timeframe) {
            // $firsttime = 1;
            if ($canpush) {
                $minutes_count = 0;
                $canpush = false;
                fputcsv($f_result, [$datetime, $open, $close, $high, $low, $volume]);
                // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
            }
            $canpush = true;
            $open = (float) $csv_row[1];
            $close = (float) $csv_row[2];
            $high = (float) $csv_row[3];
            $low = (float) $csv_row[4];
            $volume = number_format(((float)$volume + (float)$csv_row[5])/2, 5);
        } else {
            $canpush = true;
            $high = $high > (float) $csv_row[3] ? $high : (float) $csv_row[3];
            $low = $low < (float) $csv_row[4] ? $low : (float) $csv_row[4];
            $close = (float) $csv_row[2];
            $volume = number_format(((float)$volume + (float)$csv_row[5])/2, 5);
        }
        $minutes_count += $source_timeframe;
        // sleep(1);
    }
    fclose($fh);
    unset($fh);
    //sleep(2);
    fclose($f_result);
    unset($f_result);

    return true;
}