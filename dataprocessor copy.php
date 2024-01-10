<?php
require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/src/libs/helpers.php";

// php dataprocessor.php parse xauusd 2016,2017,2018,2019,2020,2021,2022,2023 2,5,15,30,60,120
// php dataprocessor.php compress xauusd 2016,2017,2018,2019,2020,2021,2022,2023 2,5,15,30,60,120
// php dataprocessor.php compact_week xauusd 2016 2023 1,2,5,15,30,60,120
// php dataprocessor.php compact_month xauusd 2016 2023 1440

use Carbon\Carbon;
use Basttyy\FxDataServer\libs\NumberConverter;
$command = isset($argv[1]) ? $argv[1] : 'parse'; /// compress|parse|compact_week
$tickers = isset($argv[2]) ? explode(',', strtoupper($argv[2])) : [];
$mins = isset($argv[3]) ? explode(',', $argv[3]) : "1";
$years = isset($argv[4]) ? explode(',', $argv[4]) : [];

$starty = isset($argv[4]) ? $argv[4] : 2016;
$endy = isset($argv[5]) ? $argv[5] : $starty;

$datetime = new Carbon();
///FRIDAY 1min candles count is 1320
///SUNDAY 1min candles count is 120
///SATURDAY 1min candles count is 0
///OTHERS 1min candles count is 1440
///FRIDAY and SUNDAY forms one day
///It's also possible that SUNDAY and MONDAY forms one day in this case, a part of monday goes to teu and a part of teu goes to wed and so on

// echo "$ticker : $year";
// exit(0);
if ($command === 'parse') {
    echo "got to parse".PHP_EOL;
    foreach ($tickers as $ticker) {
        foreach ($mins as $min) {
            foreach ($years as $year) {
                echo "got to year $year".PHP_EOL;
                $datetime->setDate($year, 1, 1);
                $lastday = Carbon::parse("last day of December $year");

                while ($datetime->lte($lastday)) {
                    $month = $datetime->month;
                    $day = $datetime->day;
                    $mn = $month < 10 ? "0$month" : "$month";
                    $dy = $day < 10 ? "0$day" : "$day";
                    $path = __DIR__."/minute_data/{$min}mins/$ticker/$year/$mn/";
                
                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }

                    if ((int)$min >= 2) {
                        if ((int)$min > 120 && $datetime->dayOfWeekIso === 7) {
                            // echo "data started with sunday, skipping this day".PHP_EOL;
                        } else if ((int)$min > 120 && $datetime->dayOfWeekIso === 5) {
                            $path2 = __DIR__."/minute_data/1mins/$ticker/$year/$mn/";
                            $date = $datetime;
                            $result_path = $path."$year-$mn-{$dy}_data.csv";
                            $paths[] = $path2."$year-$mn-{$dy}_data.csv";

                            $datetime->addDays(2);
                            $month = $datetime->month;
                            $day = $datetime->day;
                            $mn = $month < 10 ? "0$month" : "$month";
                            $dy = $day < 10 ? "0$day" : "$day";
                            $path2 = __DIR__."/minute_data/1mins/$ticker/$year/$mn/";
    
                            $paths[] = $path2."$year-$mn-{$dy}_data.csv";
                            // print_r($paths); echo PHP_EOL;
                            compoundMinute($ticker, $datetime, $result_path, (int)$min, 1, $paths);
                            $paths = [];
                        } else {
                            $path = $path."$year-$mn-{$dy}_data.csv";
                            echo $path.PHP_EOL;
                            compoundMinute($ticker, $datetime, $path, (int)$min, 1);
                        }
                        $datetime->addDays();
                    } else {
                        if (!$files = getFilesList2($ticker, $datetime)) {
                            echo "failed to get file list for $year-$month-$day".PHP_EOL;
                            continue;
                            // return $files;
                        }
                        $path = $path."$year-$mn-{$dy}_data.csv";
                        // echo $path.PHP_EOL;
                        generateMinuteData($files, $path, (int)$min);
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
} else if ($command === 'compact_week') {
    ['January', 'Febuary', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    foreach ($tickers as $ticker) {
        foreach ($mins as $min) {
            // Define the source directory and destination directory
            $sourceDir = __DIR__."/minute_data/{$min}mins/$ticker";
            $destinationDir = __DIR__."/minute_data/weekly/{$min}mins/$ticker";
        
            $date = Carbon::create($starty);
        
            while ($date->isoWeek !== 1) {
                $date->addDay();
            }
        
            while ($date->lte(Carbon::create($endy, 9, 24))) {
                $destyear = $date->year;
        
                // Create the destination directory if it doesn't exist
                if (!file_exists($destinationDir."/$destyear")) {
                    mkdir($destinationDir."/$destyear", 0777, true);
                }
        
                $weeks = $date->isoWeeksInYear;
                echo $weeks.PHP_EOL;
                // logger(__DIR__."/storage/logs/console.log")->info("$weeks weeks in $destyear");
                for ($i = 1; $i <= $weeks;  $i++) {
                    
                    // Construct the destination file path for the week
                    $destinationFilePath = "{$destinationDir}/{$destyear}/week{$i}_data.csv";

                    if (file_exists($destinationFilePath)) {
                        echo "$destinationFilePath exists, skipping".PHP_EOL;
                        $date->addWeek();
                        continue;
                    }

                    echo PHP_EOL;
                    echo $destinationFilePath.PHP_EOL.PHP_EOL;

                    $weekStartDate = $date->startOfWeek()->toDateString();
                    $weekEndDate = $date->endOfWeek()->toDateString();
        
                    // Create an array to hold daily data for the week
                    $weeklyData = [];
            
                    // Iterate through days within the week
                    $currentDate = Carbon::parse($weekStartDate);
                    while ($currentDate->lte(Carbon::parse($weekEndDate))) {
                        $month = format_int_leading_zero($currentDate->month);
                        $year = $currentDate->year; $day = format_int_leading_zero($currentDate->day);
                        $sourceFilePath = "{$sourceDir}/{$year}/$month/{$year}-{$month}-{$day}_data.csv";
                        echo $sourceFilePath.PHP_EOL;
                        // logger(__DIR__."/storage/logs/console.log")->info($sourceFilePath);
            
                        if (file_exists($sourceFilePath)) {
                            $dailyData = file_get_contents($sourceFilePath);
                            // echo $dailyData;
                            $weeklyData[] = $dailyData;
                        }
            
                        // Move to the next day
                        $currentDate->addDay();
                        $date->addDay();
                    }
            
                    // logger(__DIR__."/storage/logs/console.log")->info($destinationFilePath);
                    // logger(__DIR__."/storage/logs/console.log")->info("  ");
                    // logger(__DIR__."/storage/logs/console.log")->info("  ");
                    // Write the weekly data to the destination CSV file
                    if (!empty($weeklyData)) {
                        if (!$compressed = gzdeflate(implode("\n", $weeklyData), encoding: ZLIB_ENCODING_DEFLATE)) {
                            consoleLog('error', "unable to compress file");
                            return false;
                        }
                        file_put_contents($destinationFilePath, $compressed);
                    }
                    usleep(400000);
                }
            }
            echo "done with {$min}mins of ticker $ticker.";
        }
    }  
} else if ($command === 'compact_month') {
    foreach ($tickers as $ticker) {
        foreach ($mins as $min) {
            // Define the source directory and destination directory
            $sourceDir = __DIR__."/minute_data/{$min}mins/$ticker";
            $destinationDir = __DIR__."/minute_data/monthly/{$min}mins/$ticker";
        
            $date = Carbon::create($starty);
        
            // while ($date->isoWeek !== 1) {
            //     $date->addDay();
            // }
        
            while ($date->lte(Carbon::create($endy, 9, 24))) {
                $destyear = $date->year;
        
                // Create the destination directory if it doesn't exist
                if (!file_exists($destinationDir."/$destyear")) {
                    mkdir($destinationDir."/$destyear", 0777, true);
                }
        
                $month = $date->month;
                echo $month.PHP_EOL;
                // logger(__DIR__."/storage/logs/console.log")->info("$weeks weeks in $destyear");
                // for ($i = 1; $i <= 12;  $i++) {
                    
                    // Construct the destination file path for the week
                    $destinationFilePath = "{$destinationDir}/{$destyear}/month{$month}_data.csv";

                    if (file_exists($destinationFilePath)) {
                        echo "$destinationFilePath exists, skipping".PHP_EOL;
                        $date->addMonth();
                        continue;
                    }

                    echo PHP_EOL;
                    echo $destinationFilePath.PHP_EOL.PHP_EOL;

                    $monthStartDate = $date->startOfMonth()->toDateString();
                    $monthEndDate = $date->endOfMonth()->toDateString();
        
                    // Create an array to hold daily data for the week
                    $monthlyData = [];
            
                    // Iterate through days within the week
                    $currentDate = Carbon::parse($monthStartDate);
                    while ($currentDate->lte(Carbon::parse($monthEndDate))) {
                        $month = format_int_leading_zero($currentDate->month);
                        $year = $currentDate->year; $day = format_int_leading_zero($currentDate->day);
                        $sourceFilePath = "{$sourceDir}/{$year}/$month/{$year}-{$month}-{$day}_data.csv";
                        echo $sourceFilePath.PHP_EOL;
                        // logger(__DIR__."/storage/logs/console.log")->info($sourceFilePath);
            
                        if (file_exists($sourceFilePath)) {
                            $dailyData = file_get_contents($sourceFilePath);
                            // echo $dailyData;
                            $monthlyData[] = $dailyData;
                        }
            
                        // Move to the next day
                        $currentDate->addDay();
                        // $date->addDay();
                        if ($currentDate->greaterThan(Carbon::create($endy, 9, 24))) {
                            break;
                        }
                    }
                    $date = $currentDate;
            
                    // logger(__DIR__."/storage/logs/console.log")->info($destinationFilePath);
                    // logger(__DIR__."/storage/logs/console.log")->info("  ");
                    // logger(__DIR__."/storage/logs/console.log")->info("  ");
                    // Write the weekly data to the destination CSV file
                    if (!empty($monthlyData)) {
                        if (!$compressed = gzdeflate(implode("\n", $monthlyData), encoding: ZLIB_ENCODING_DEFLATE)) {
                            consoleLog('error', "unable to compress file");
                            return false;
                        }
                        file_put_contents($destinationFilePath, $compressed);
                    }
                    usleep(400000);
                // }
            }
            echo "done with {$min}mins of ticker $ticker.";
        }
    }  
} else if ($command === 'compact_year') {
    foreach ($tickers as $ticker) {
        foreach ($mins as $min) {
            // Define the source directory and destination directory
            $sourceDir = __DIR__."/minute_data/{$min}mins/$ticker";
            $destinationDir = __DIR__."/minute_data/yearly/{$min}mins/$ticker";
        
            $date = Carbon::create($starty);
        
            // while ($date->isoWeek !== 1) {
            //     $date->addDay();
            // }
        
            while ($date->lte(Carbon::create($endy, 9, 24))) {
                $destyear = $date->year;
        
                // Create the destination directory if it doesn't exist
                if (!file_exists($destinationDir)) {
                    mkdir($destinationDir, 0777, true);
                }
        
                $month = $date->month;
                echo $month.PHP_EOL;
                // logger(__DIR__."/storage/logs/console.log")->info("$weeks weeks in $destyear");
                // for ($i = 1; $i <= 12;  $i++) {
                    
                    // Construct the destination file path for the week
                    $destinationFilePath = "{$destinationDir}/{$destyear}_{$min}_data.csv";

                    if (file_exists($destinationFilePath)) {
                        echo "$destinationFilePath exists, skipping".PHP_EOL;
                        $date->addMonth();
                        continue;
                    }

                    echo PHP_EOL;
                    echo $destinationFilePath.PHP_EOL.PHP_EOL;

                    $yearStartDate = $date->startOfYear()->toDateString();
                    $yearEndDate = $date->endOfYear()->toDateString();
        
                    // Create an array to hold daily data for the week
                    $yearlyData = [];
            
                    // Iterate through days within the week
                    $currentDate = Carbon::parse($yearStartDate);
                    while ($currentDate->lte(Carbon::parse($yearEndDate))) {
                        $month = format_int_leading_zero($currentDate->month);
                        $year = $currentDate->year; $day = format_int_leading_zero($currentDate->day);
                        $sourceFilePath = "{$sourceDir}/{$year}/$month/{$year}-{$month}-{$day}_data.csv";
                        echo $sourceFilePath.PHP_EOL;
                        // logger(__DIR__."/storage/logs/console.log")->info($sourceFilePath);
            
                        if (file_exists($sourceFilePath)) {
                            $dailyData = file_get_contents($sourceFilePath);
                            // echo $dailyData;
                            $yearlyData[] = $dailyData;
                        }
            
                        // Move to the next day
                        $currentDate->addDay();
                        // $date->addDay();
                        if ($currentDate->greaterThan(Carbon::create($endy, 9, 24))) {
                            break;
                        }
                    }
                    $date = $currentDate;
            
                    // logger(__DIR__."/storage/logs/console.log")->info($destinationFilePath);
                    // logger(__DIR__."/storage/logs/console.log")->info("  ");
                    // logger(__DIR__."/storage/logs/console.log")->info("  ");
                    // Write the weekly data to the destination CSV file
                    if (!empty($yearlyData)) {
                        if (!$compressed = gzdeflate(implode("", $yearlyData), encoding: ZLIB_ENCODING_DEFLATE)) {
                            consoleLog('error', "unable to compress file");
                            return false;
                        }
                        file_put_contents($destinationFilePath, $compressed);
                    }
                    usleep(400000);
                // }
            }
            echo "done with {$min}mins of ticker $ticker.";
        }
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
    // echo $datetime->toString().PHP_EOL;
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

function compoundMinute(string $ticker, Carbon $datetime, string $result_path, int $timeframe, int $source_timeframe, array $source_files = [])
{
    if (file_exists($result_path)) {
        consoleLog('info', "file $result_path exists, skipping");
        return true;
    }

    if (count($source_files)) {
        foreach ($source_files as $file) {
            if (!file_exists($file)) {
                // consoleLog(0, "source file not found: skipping...");
                return false;
            }
            $files[] = $file;
        }
    } else {
        $file = __DIR__."/minute_data/{$source_timeframe}mins/$ticker/{$datetime->format('Y/m')}/{$datetime->format('Y-m-d')}_data.csv";

        if (!file_exists($file)) {
            // consoleLog(0, "source file not found: skipping...");
            return false;
        }
        $files[] = $file;
    }

    if (!$f_result = fopen($result_path, 'w')) {
        consoleLog('error', "unable to open destination file: $result_path");
        return false;
    }

    $datetime = 0;
    $minutes_count = 0; $canpush = false;
    $open = 0; $close = 0; $high = 0; $low = 0; $volume = 0;

    if (!$fh = fopen($files[0], 'r')) {
        consoleLog('error', "unable to open source file: $file");
        return false;
    }
    array_shift($files);
    
    while (1) {
        if (($csv_row = fgetcsv($fh, 60, ',')) === false) {
            if (!count($files)) {
                // consoleLog("info", "end of file detected");
                fputcsv($f_result, [$datetime, $open, $close, $high, $low, $volume]);
                // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                $datetime = 0; $minutes_count = 0; $canpush = false;
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
                    fputcsv($f_result, [$datetime, $open, $close, $high, $low, $volume]);
                    // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
                    $datetime = 0; $minutes_count = 0; $canpush = false;
                    $open = 0; $close = 0; $high = 0; $low = 0; $volume = 0;

                    fclose($fh);
                    unset($fh);
                    return false;
                }
                array_shift($files);
                continue;
            }
        }
        // print_r($csv_row);
        
        if ($minutes_count === 0 || $minutes_count >= $timeframe) {
            // $firsttime = 1;
            if ($canpush) {
                $minutes_count = 0;
                $canpush = false;
                fputcsv($f_result, [$datetime, $open, $close, $high, $low, $volume]);
                // fwrite($f_result, "$timestamp,$open,$close,$high,$low,$volume\n");
            }
            $canpush = true;
            $datetime = (float)$csv_row[0];
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
    //sleep(2);
    fclose($f_result);
    unset($f_result);

    return true;
}