<?php
require_once __DIR__."/../../vendor/autoload.php";

use Carbon\Carbon;

function joinCsvFast(array $files, string $result, bool $use_memory): bool|int
{
    $data = "";
    foreach ($files as $file) {
        $data .= file_get_contents($file);
    }
    if ($use_memory)
        return $data;
    if ($stat = file_put_contents($result, $data) === false)
        return $stat;
    
    return true;
}

function joinCsvSlow(array $files, string $result, bool $use_memory): bool|string
{
    if(!is_array($files)) {
        throw new Exception('`$files` must be an array');
    }
    $wh = $use_memory ? '' : fopen($result, 'w+');

    foreach ($files as $file) {
        $fh = fopen($file, 'r');
        while (!feof($fh)) {
            if ($use_memory) {
                $wh .= fgets($fh);
                continue;
            }
            fwrite($wh, fgets($fh));
        }
        fclose($fh);
        unset($fh);
        $use_memory ? $wh .= "\n" : fwrite($wh, "\n");  //usually last line doesn't have a newline
    }
    if ($use_memory) {
        return $wh;
    }
    fclose($wh);
    unset($wh);
    return true;
}

/**
 * Generate candle data from list of tick data files
 * 
 * @param array $files the tick data file paths
 * @param string $result
 * 
 * @return bool|array
 */
function processCandles(array $files, int $from, int $timeframe)
{
    if(!is_array($files)) {
        throw new Exception('`$files` must be an array');
    }
    //$wh = fopen($result, 'w+');

    $datetime = 0;
    $datalist = array();
    $firsttime = 0; $minutes = 0; $canpush = false;
    $timestamp = 0; $open = 0; $close = 0; $high = 0; $low = 0; $turnover = 0; $volume = 0;
    foreach ($files as $file) {
        if (!$fh = fopen($file, 'r')) {
            return $fh;
        }
        
        // while (!feof($fh)) {
        while (($csv_row = fgetcsv($fh, 60, ',')) !== false) {
            //date_time,bid,ask,volume,turnover
            // if (!$csv_row = fgetcsv($fh)) {
            //     return $csv_row;
            // }
            // $datetime = $datetime->createFromFormat("Y.m.d H:i:s", $csv_row[0]);
            // echo $datetime->getTimestamp().PHP_EOL;
            // echo $datetime->format("Y.m.d H:i:s.u").PHP_EOL;
            $datetime = (float)$csv_row[0];
            $minutes = $datetime/60;
            
            if ($firsttime === 0 || ($minutes - $firsttime) >= $timeframe) {
                if ($canpush) {
                    $data = [
                        'timestamp' => $timestamp*1000,
                        'open' => $open,
                        'close' => $close,
                        'high' => $high,
                        'low' => $low,
                        // 'turnover' => $turnover,
                        // 'volume' => $volume
                    ];
                    if (feof($fh)) {
                        break;
                    }
                    $canpush = false;
                    array_push($datalist, (object)$data);
                }
                $datetime = $csv_row[0];
                $timestamp = $datetime;
                $firsttime = $timestamp/60;
                $open = (float) $csv_row[1];
                $close = (float) $csv_row[1];
                $high = (float) $csv_row[1];
                $low = (float) $csv_row[1];
            } else {
                $canpush = true;
                $high = $high > (float) $csv_row[1] ? $high : (float) $csv_row[1];
                $low = $low < (float) $csv_row[1] ? $low : (float) $csv_row[1];
                $close = (float) $csv_row[1];
                
                if (feof($fh)) {
                    $data = [
                        'timestamp' => $timestamp*1000,
                        'open' => $open,
                        'close' => $close,
                        'high' => $high,
                        'low' => $low,
                        // 'turnover' => $turnover,
                        // 'volume' => $volume
                    ];
                    array_push($datalist, (object)$data);
                    // if (dataSize-- <= 1) {
                    //     break;
                    // }
                    $canpush = false;
                }
            }
            // print_r($datalist);
        }
        fclose($fh);
        unset($fh);
        //fwrite($wh, "\n");  //usually last line doesn't have a newline
    }

    return count($datalist) ? $datalist : false;
}

function getCandles(string $ticker, int $from, int $nums, int $timeframe): bool|array
{
    if (!$files = getFilesList($ticker, $from, $nums, $timeframe)) {
        return $files;
    }
    return processCandles($files, $from, $timeframe);
}

function getFilesList(string $ticker, int $from, int $nums): bool|array
{
    // return true;
    $datetime = new DateTimeImmutable();
    // $datetime->setTimestamp($from);
    $datetime = $datetime->setTimestamp($from);
    $files = array(); $i = 0; $s = 0;
    while ($i < $nums) {
        $file_path =
            env('APP_ENV') === 'local' ?
            "{$_SERVER['DOCUMENT_ROOT']}/../../download/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}--{$datetime->format('H')}h_ticks.csv" :
            "{$_SERVER['DOCUMENT_ROOT']}/../../download/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}--{$datetime->format('H')}h_ticks.csv";
        
        if (!file_exists($file_path)) {
            $datetime = $datetime->sub(new DateInterval('PT1H'));
            continue;
        }
        array_unshift($files, $file_path);
        $datetime = $datetime->sub(new DateInterval('PT1H'));
        $i++;
    }
    if (count($files))
        return $files;
    return false;
}

function getMinutesFilesList(string $ticker, int $timeframe, int &$from, int $increment, int $nums): bool|array
{
    $datetime = new Carbon();
    $datetime = $datetime->setTimestamp($from);
    $from = 0;
    $files = array(); $i = 0;
    while ($i < $nums) {
        if ($datetime->greaterThan(Carbon::now()) || $datetime->lessThan(Carbon::createFromFormat('Y/m/d', '2016/01/01'))) {
            return false;
        }
        $file_path =
            env('APP_ENV') === 'local' ?
            "{$_SERVER['DOCUMENT_ROOT']}/../../minute_data/gziped/{$timeframe}mins/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}_data.csv" :
            "{$_SERVER['DOCUMENT_ROOT']}/../../minute_data/gziped/{$timeframe}mins/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}_data.csv";
        
        if (!file_exists($file_path)) {
            $datetime = (bool)$increment === 1 ? $datetime->addDay() : $datetime->subDay();
            continue;
        }
        if ($from === 0)
            $from = $datetime->getTimestamp();
        array_unshift($files, $file_path);
        $datetime = (bool)$increment ? $datetime->addDay() : $datetime->subDay();
        $i++;
        if ($i > $nums+10)
            break;
    }
    if (count($files))
        return $files;
    return false;
}

function getWeeksMinuteList(string $ticker, int $timeframe, int &$from, int $incr, int $nums): bool|array
{
    $files = array();

    $days = getWeekDates($from);

    foreach ($days as $day) {
        $file_path =
            env('APP_ENV') === 'local' ?
            "{$_SERVER['DOCUMENT_ROOT']}/minute_data/{$timeframe}mins/$ticker/" . str_replace('-', '/', substr($day, 0, 7)) . "/{$day}_data.csv" :
            "{$_SERVER['DOCUMENT_ROOT']}/../../minute_data/{$timeframe}mins/$ticker/" . str_replace('-', '/', substr($day, 0, 7)) . "/{$day}_data.csv";
        
        array_push($files, $file_path);
    }

    if (count($files))
        return $files;
    return false;
}

function getWeekDates($timestamp, $includeSat = false) {
    $date = new DateTime();
    $date->setTimestamp($timestamp);

    // Find the start (Sunday) and end (Saturday) of the week
    $date->modify('this week -1 day');
    $startOfWeek = $date->format('Y-m-d');
    $date->modify('+5 days');
    if ($includeSat) {
        $date->modify('+1 day'); // Include Saturday
    }
    $endOfWeek = $date->format('Y-m-d');

    // Create an array of dates for each day of the week
    $weekDates = [];
    $currentDate = new DateTime($startOfWeek);

    while ($currentDate <= new DateTime($endOfWeek)) {
        $weekDates[] = $currentDate->format('Y-m-d');
        $currentDate->modify('+1 day');
    }

    return $weekDates;
}

// function getWeekDates($timestamp, $include_sat = false) {
//     $date = new DateTime();
//     $date->setTimestamp($timestamp);

//     // Find the start (Monday) and end (Sunday) of the week
//     $date->modify('this week');
//     $startOfWeek = $date->format('Y-m-d');
//     $date->modify('this week +6 days');
//     $endOfWeek = $date->format('Y-m-d');

//     // Create an array of dates for each day of the week
//     $weekDates = []; $i = 0;
//     $currentDate = new DateTime($startOfWeek);

//     while ($currentDate <= new DateTime($endOfWeek)) {
//         $i++;
//         if ($i == 6) {
//             $currentDate->modify('+1 day');
//             continue;
//         }
//         $weekDates[] = $currentDate->format('Y-m-d');
//         $currentDate->modify('+1 day');
//     }

//     return $weekDates;
// }