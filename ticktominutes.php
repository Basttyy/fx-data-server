<?php
require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."\\src\\helpers.php";

use Carbon\Carbon;

$ticker = strtoupper($argv[1]);
$years = explode(',', $argv[2]);

$datetime = new Carbon();

// echo "$ticker : $year";
// exit(0);

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
            // continue;
            generateMinuteData($files, $path, 60);
            sleep(1);
        }
        consoleLog("info", "done with month: $month");
        sleep(1);
    }
    consoleLog("info", "done with year: $year");
    sleep(2);
}

function getFilesList2(string $ticker, Carbon &$datetime): bool|array
{
    // consoleLog("info", "the vars are $ticker:  $from:   $nums");
    // return true;
    // $datetime->setTimestamp($from);
    echo $datetime->toString();
    $files = array(); $i = 0; $s = 0; $date_valid = true;
    while ($date_valid) {
        $date_valid = $datetime->hour < 23;
        $file_path = __DIR__."/download/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}--{$datetime->format('H')}h_ticks.csv";
        str_replace('/', "\\", $file_path);
        consoleLog('info', $file_path);
        if (!file_exists($file_path)) {
            $datetime->addHour();
            consoleLog('Error', "File not found Error for : " . $file_path.PHP_EOL);
            // sleep(1);
            continue;
        }
        array_push($files, $file_path);
        $datetime->addHour();
        $i++;
        // sleep(1);
    }
    // echo "it got to end of the day".PHP_EOL;
    // print_r($files);
    // exit(0);
    if (count($files))
        return $files;
    return false;
}

function generateMinuteData(array $files, string $result_path, int $timeframe, bool $use_bid = false): bool
{
    echo "got to generate minute data".PHP_EOL;
    $count = count($files);
    consoleLog("info", "files count is: $count");
    if (file_exists($result_path)) {
        consoleLog('info', "file $result_path exists, skipping");
        return true;
    }
    if(!is_array($files)) {
        throw new Exception('`$files` must be an array');
        return false;
    }
    //echo "checked if files are array".PHP_EOL;

    if (!$f_result = fopen($result_path, 'w')) {
        consoleLog('error', "unable to open destination file: $result_path");
        return false;
    }
    //echo "destination file opened".PHP_EOL;

    $datetime = 0;
    $datalist = array();
    $firsttime = 0; $minutes = 0; $canpush = false;
    $timestamp = 0; $open = 0; $close = 0; $high = 0; $low = 0; $turnover = 0; $volume = 0;
    $price_line = $use_bid ? 1 : 2;
    $i = 1;
    foreach ($files as $file) {
        consoleLog('info', "currently reading file $i");
        $i++;
        continue;
        if (!$fh = fopen($file, 'r')) {
            consoleLog('error', "unable to open source file: $file");
            return false;
        }
        
        // while (!feof($fh)) {
        while (($csv_row = fgetcsv($fh, 60, ',')) !== false) {
            //consoleLog('info', 'got a csv row');
            //date_time,bid,ask,volume,turnover
            // if (!$csv_row = fgetcsv($fh)) {
            //     consoleLog('info', "unable to read csv line");
            //     return $csv_row;
            // }
            // $datetime = $datetime->createFromFormat("Y.m.d H:i:s", $csv_row[0]);
            // echo $datetime->getTimestamp().PHP_EOL;
            // echo $datetime->format("Y.m.d H:i:s.u").PHP_EOL;
            $datetime = (float)$csv_row[0];
            $minutes = $datetime/60;
            
            if ($firsttime === 0 || ($minutes - $firsttime) >= $timeframe) {
                //consoleLog("info", "firsttime or timeframe full");
                if ($canpush) {
                    //consoleLog("info", "canpush");
                    $timestamp = $timestamp*1000;
                    if (feof($fh)) {
                        consoleLog("info", "got to end of file in canpush");
                        break;
                    }
                    $canpush = false;
                    fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                    //consoleLog('info', 'wrote line to dest csv');
                    $timestamp = $timestamp/1000;
                    // array_push($datalist, (object)$data);
                }
                $datetime = $csv_row[0];
                $timestamp = $datetime;
                $firsttime = $timestamp/60;
                $open = (float) $csv_row[$price_line];
                $close = (float) $csv_row[$price_line];
                $high = (float) $csv_row[$price_line];
                $low = (float) $csv_row[$price_line];
                $volume = ((float)$volume + (float)$csv_row[$price_line+2])/2;
            } else {
                //consoleLog("info", "not firsttime or not timeframe full");
                $canpush = true;
                $high = $high > (float) $csv_row[$price_line] ? $high : (float) $csv_row[$price_line];
                $low = $low < (float) $csv_row[$price_line] ? $low : (float) $csv_row[$price_line];
                $close = (float) $csv_row[$price_line];
                $volume = ((float)$volume + (float)$csv_row[$price_line+2])/2;
                
                if (feof($fh)) {
                    $timestamp = $timestamp*1000;

                    fwrite($f_result, "end of file detected\n");
                    fwrite($f_result, `$timestamp,$open,$close,$high,$low,$volume\n`);
                    $timestamp = $timestamp/1000;
                    // array_push($datalist, (object)$data);
                    // if (dataSize-- <= 1) {
                    //     break;
                    // }
                    $canpush = false;
                }
            }
            // print_r($datalist);
            // sleep(1);
        }
        fclose($fh);
        unset($fh);
        sleep(2);
    }
    // fwrite($f_result, "\n");  //usually last line doesn't have a newline
    fclose($f_result);
    unset($f_result);

    return true;
}