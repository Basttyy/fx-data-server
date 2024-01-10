<?php
require_once __DIR__."/../vendor/autoload.php";
require_once __DIR__."/../src/libs/helpers.php";
require_once __DIR__."/data_helpers.php";

use Carbon\Carbon;

['January', 'Febuary', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
foreach ($tickers as $ticker) {
    foreach ($mins as $min) {
        // Define the source directory and destination directory
        $sourceDir = __DIR__."/../minute_data/download/$offerside/{$min}mins/$ticker";
        $zipPath = $zip ? 'compressed' : ''; $zipExt = $zip ? '.gz' : '';
        $destinationDir = __DIR__."/../minute_data/$compressedPath/$offerside/weekly/{$min}mins/$ticker$zipExt";
    
        $date = Carbon::create($starty);
    
        while ($date->isoWeek !== 1) {
            $date->addDay();
        }
    
        while ($date->lte(Carbon::create($endy, 12, 31))) {
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
                $destinationFilePath = "{$destinationDir}/{$destyear}/week{$i}_data.csv.gz";

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
                        if (empty($weeklyData))
                            $heading = "start-datetime: $currentDate";
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
                    $heading = "$heading - end-datetime: $currentDate\r";
                    array_unshift($weeklyData, $heading);

                    $fd = fopen($destinationFilePath, "w+");
                    if ($zip) {
                        $s_filter = stream_filter_append($fd, "zlib.deflate", STREAM_FILTER_WRITE);
                        // if (!$compressed = gzdeflate(implode("\n", $weeklyData), encoding: ZLIB_ENCODING_DEFLATE)) {
                        //     consoleLog('error', "unable to compress file");
                        //     return false;
                        // }
                    } else {
                        // file_put_contents($destinationFilePath, implode("\n", $weeklyData));
                    }
                    
                    fwrite($fd, implode("\n", $weeklyData));

                    if ($s_filter)
                        stream_filter_remove($s_filter);

                    fclose($fd);
                }
                usleep(400000);
            }
        }
        echo "done with {$min}mins of ticker $ticker.";
    }
}