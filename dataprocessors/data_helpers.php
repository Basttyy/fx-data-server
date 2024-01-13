<?php
require_once __DIR__."/../vendor/autoload.php";
require_once __DIR__."/../src/libs/helpers.php";

use Carbon\Carbon;

function getTickFilesList2(string $ticker, Carbon &$datetime): bool|array
{
    // echo $datetime->toString().PHP_EOL;
    $files = array(); $i = 0; $s = 0; $date_valid = true;
    while ($date_valid) {
        $date_valid = $datetime->hour < 23 && !$datetime->isToday();
        $file_path = __DIR__."/../download/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}--{$datetime->format('H')}h_ticks.csv";
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

function generateMinuteDataFromTick(array $files, string $result_path, int $timeframe, bool $use_bid = false): bool
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

    $current_time = 0;
    $firsttime = 0; $canpush = false;
    $timestamp = 0; $open = 0; $close = 0; $high = 0; $low = 0; $turnover = 0; $volume = 0;
    $price_line = $use_bid ? 1 : 2; $prevtime = 0;
    foreach ($files as $file) {
        if (!$fh = fopen($file, 'r')) {
            consoleLog('error', "unable to open source file: $file");
            return false;
        }
        
        while (1) {
            if (($csv_row = fgetcsv($fh, 60, ',')) === false) {
                $timestamp = $timestamp*1000;
                    fputcsv($f_result, [$timestamp, $open, $close, $high, $low, $volume]);
                    $timestamp = 0; $current_time = 0; $firsttime = 0; $canpush = false;
                    $open = 0; $close = 0; $high = 0; $low = 0; $volume = 0;

                break;
            }
            // print_r($csv_row);
            $current_time = (float)$csv_row[0];
            
            if ($firsttime === 0 || $current_time - $prevtime >= 60) {
                if ($canpush) {
                    $canpush = false;
                    fputcsv($f_result, [$timestamp, $open, $close, $high, $low, $volume]);
                }
                $timestamp = $current_time;
                if ($firsttime === 0) {
                    $firsttime = $current_time;
                }
                $canpush = true;
                $open = (float) $csv_row[$price_line];
                $close = (float) $csv_row[$price_line];
                $high = (float) $csv_row[$price_line];
                $low = (float) $csv_row[$price_line];
                $volume = number_format((float)$volume + (float)$csv_row[$price_line+2], 2);
            } else {
                $canpush = true;
                $high = $high > (float) $csv_row[$price_line] ? $high : (float) $csv_row[$price_line];
                $low = $low < (float) $csv_row[$price_line] ? $low : (float) $csv_row[$price_line];
                $close = (float) $csv_row[$price_line];
                $volume = number_format((float)$volume + (float)$csv_row[$price_line+2], 2);
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

function compoundMinute(string $ticker, Carbon $datetim, string $result_path, int $timeframe, int $source_timeframe, array $source_files = [], $offerside = 'ASK', $filter = 'weekend', $zip = false)
{
    if (file_exists($result_path)) {
        consoleLog('info', "file $result_path exists, skipping");
        return true;
    }

    if (count($source_files)) {
        foreach ($source_files as $file) {
            if (!file_exists($file)) {
                consoleLog(0, "source file not found: skipping...");
                continue;
            }
            $files[] = $file;
        }
    } else {
        if ($source_timeframe < 60) {
            $file = __DIR__."/../minute_data/download/unfiltered/$offerside/{$source_timeframe}mins/$ticker/{$datetim->format('Y/m')}/{$datetim->format('Y-m-d')}_data.csv";
        }
        else if ($timeframe >= 60 && $timeframe < 1440) {
            $file = __DIR__."/../minute_data/download/unfiltered/$offerside/{$source_timeframe}mins/$ticker/{$datetim->format('Y')}/{$datetim->format('Y-m')}_data.csv";
            // $date = $datetim->clone();
        }
        else if ($timeframe == 1440) {
            $file = __DIR__."/../minute_data/download/unfiltered/$offerside/{$source_timeframe}mins/$ticker/{$datetim->format('Y')}_data.csv";
        }
        else {
            consoleLog(0, "wrong source timeframe");
            return false;
        }
        if (!file_exists($file)) {
            consoleLog(0, "source file not found: skipping...");
            return false;
        }
        $files[] = $file;
    }
    $date = $datetim->clone();

    if (!$f_result = fopen($result_path, 'w+')) {
        consoleLog('error', "unable to open destination file: $result_path");
        return false;
    }

    $currentime = $prevtime = 0;
    $minutes_count = 0; $canpush = false;
    $open = 0; $close = 0; $high = 0; $low = 0; $volume = 0;

    if (!$fh = fopen($files[0], 'r')) {
        consoleLog('error', "unable to open source file: $file");
        return false;
    }
    array_shift($files);
    if ($zip)
        $s_filter = stream_filter_append($f_result, "zlib.deflate", STREAM_FILTER_WRITE);
    
    if ($timeframe >= 60)
        fputs($f_result, "start-datetime: $datetim\r");
    while (1) {
        if (($csv_row = fgetcsv($fh, separator: ',')) === false) {
            $nopush = $open == 0 && $close == 0 && $high == 0 && $low == 0 && $volume == 0;
            if (!count($files)) {
                // consoleLog("info", "end of file detected");
                if (!$nopush)
                    fputcsv($f_result, [$currentime, $open, $close, $high, $low, $volume]);
                // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                $currentime = 0; $minutes_count = 0; $canpush = false;
                $open = 0; $close = 0; $high = 0; $low = 0; $volume = 0;

                fclose($fh);
                unset($fh);
                break;
            } else {
                fclose($fh);
                unset($fh);
                if (!$fh = fopen($files[0], 'r')) {
                    consoleLog('error', "unable to open source file: $file");

                    // consoleLog("info", "end of file detected");
                    if (!$nopush)
                        // fwrite($f_result, implode(',', [$currentime, $open, $close, $high, $low, $volume]).PHP_EOL);
                        fputcsv($f_result, [$currentime, $open, $close, $high, $low, $volume]);
                    // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                    $currentime = 0; $minutes_count = 0; $canpush = false;
                    $open = 0; $close = 0; $high = 0; $low = 0; $volume = 0;

                    fclose($fh);
                    unset($fh);
                    $stat = fstat($f_result);
                    if ($zip && $s_filter)
                        stream_filter_remove($s_filter);
                    fclose($f_result);
                    unset($f_result);
                    if ($stat['size'] === 0 && $timeframe === 0)
                        unlink($result_path);
                    return false;
                }
                array_shift($files);
                continue;
            }
        }
        // print_r($csv_row);
        
        $newtime = (int)$csv_row[0];
        $date->addSeconds($newtime - $prevtime);
        $prevtime = $newtime;
        
        if ((double)$csv_row[5] == 0) {
        // if ((double)$csv_row[5] == 0 && ($date->isFriday() || $date->isSaturday() || $date->isSunday())) {
            // consoleLog('info', 'skipping flats on sunday');
            continue;
        }
        
        if ($minutes_count === 0 || ($newtime - $currentime) >= $timeframe*60) {
            // $firsttime = 1;
            if ($canpush) {
                $minutes_count = 0;
                $canpush = false;
                fputcsv($f_result, [$currentime, $open, $close, $high, $low, $volume]);
                // fwrite($f_result, "$currentime,$open,$close,$high,$low,$volume\n");
            }
            $canpush = true;
            $currentime = $newtime;
            $open = (double) $csv_row[1];
            $close = (double) $csv_row[2];
            $high = (double) $csv_row[3];
            $low = (double) $csv_row[4];
            $volume = doubleval(number_format((double)$csv_row[5], 2, '.', ''));
        } else {
            $canpush = true;
            $high = $high > (double) $csv_row[3] ? $high : (double) $csv_row[3];
            $low = $low < (double) $csv_row[4] ? $low : (double) $csv_row[4];
            $close = (double) $csv_row[2];
            $volume = doubleval(number_format($volume + (double)$csv_row[5], 2, '.', ''));
        }
        $minutes_count = $source_timeframe;
        // sleep(1);
    }
    //sleep(2);
    $stat = fstat($f_result);
    if ($zip && $s_filter)
        stream_filter_remove($s_filter);
    fclose($f_result);
    unset($f_result);
    if ($stat['size'] === 0 && $timeframe === 0)
        unlink($result_path);

    return true;
}