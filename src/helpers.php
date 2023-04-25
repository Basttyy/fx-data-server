<?php

function consoleLog($level, $msg) {
    file_put_contents("php://stdout", "[" . $level . "] " . $msg . "\n");
}

function joinCsvFast(array $files, string $result, bool $use_memory): bool|string
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
        //consoleLog('info', "foreach");
        if (!$fh = fopen($file, 'r')) {
            //consoleLog('info', "unable to open file");
            return $fh;
        }
        
        // while (!feof($fh)) {
        while (($csv_row = fgetcsv($fh, 60, ',')) !== false) {
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
                // consoleLog("info", "firsttime or timeframe full");
                if ($canpush) {
                    // consoleLog("info", "canpush");
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
                // consoleLog("info", "not firsttime or not timeframe full");
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
    // consoleLog("info", "the vars are $ticker:  $from:   $nums");
    // return true;
    $datetime = new DateTimeImmutable();
    // $datetime->setTimestamp($from);
    $datetime = $datetime->setTimestamp($from);
    $files = array(); $i = 0; $s = 0;
    while ($i < $nums) {
        $file_path = "{$_SERVER['DOCUMENT_ROOT']}/download/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}--{$datetime->format('H')}h_ticks.csv";
        str_replace('/', "\\", $file_path);
        consoleLog(0, $file_path.PHP_EOL);
        if (!file_exists($file_path)) {
            $datetime = $datetime->sub(new DateInterval('PT1H'));
            consoleLog('info', "File not found Error for : " . $file_path);
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