<?php
// Copyright Monwoo 2017, service@monwoo.com
// Enabling CORS in bultin dev to test locally with multiples servers
// used to replace lack of .htaccess support inside php builting webserver.
// call with :
// php -S localhost:20334 -t . server.php
$CORS_ORIGIN_ALLOWED = "http://127.0.0.1:5173";  // or '*' for all
$file_path = "download";
// header("Access-Control-Allow-Origin: $CORS_ORIGIN_ALLOWED");

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

    $datetime = new DateTimeImmutable();
    $datalist = array();
    $firsttime = 0; $minutes = 0; $canpush = false;
    $timestamp = 0; $open = 0; $close = 0; $high = 0; $low = 0; $turnover = 0; $volume = 0;
    foreach ($files as $file) {
        $fh = fopen($file, 'r');
        while (!feof($fh)) {
            //date_time,bid,ask,volume,turnover
            $csv_row = fgetcsv($fh, null, ',');

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
                    array_push($datalist, (object)$data);
                }
                $timestamp = $datetime->createFromFormat("YYYY.MM.DD HH:mm:ss.SSS", $csv_row[0])->getTimestamp();
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
        }
        fclose($fh);
        unset($fh);
        //fwrite($wh, "\n");  //usually last line doesn't have a newline
    }

    return true;
}

function getCandles(string $ticker, int $from, int $nums, int $timeframe): bool|array
{
    $datetime = new DateTime();
    $datetime->setTimestamp($from);
    // $datetime = $datetime->setTimestamp($from);
    $files = array(); $i = 0;
    while ($i > $nums) {
        $file_path = "{$_SERVER['DOCUMENT_ROOT']}download/$ticker/{$datetime->format('YYYY/MM')}/{$datetime->format('YYYY--MM--DD')}--{$datetime->format('HH')}h_ticks.csv";
        echo $file_path.PHP_EOL;
        if (!file_exists($file_path)) {
            consoleLog('info', "File not found Error for : " . $$file_path);
            continue;
        }
        array_push($files, $file_path);
        $datetime->sub(new DateInterval('PT1H'));
        $i++;
    }
    if (!sizeof($files))
        return false;
    return processCandles($files, $from, $timeframe);
}

consoleLog(0, "request came to server");

function applyCorsHeaders($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
}

if (preg_match('/\.(?:png|jpg|jpeg|gif)$/', $_SERVER["REQUEST_URI"])) {
    consoleLog('info', "Transparent routing for : " . $_SERVER["REQUEST_URI"]);
    http_response_code(400);
    header("Content-type: application/json");
    echo json_encode(["message" => "Bad Request data"]);
} else if (preg_match('/^.*$/i', $_SERVER["REQUEST_URI"])) {
    $filePath = "{$_SERVER['DOCUMENT_ROOT']}{$_SERVER["REQUEST_URI"]}";
    $from = $_GET['from'];
    $hours = $_GET['hours'];
        
    applyCorsHeaders($CORS_ORIGIN_ALLOWED);

    if (!file_exists($filePath)) {
        consoleLog('info', "File not found Error for : " . $_SERVER["REQUEST_URI"]);
        // return false;
        http_response_code(404);
        header("Content-type: application/json");
        echo "File not Found : {$filePath}";
        return true;
    }
    $mime = mime_content_type($filePath);
    // https://stackoverflow.com/questions/45179337/mime-content-type-returning-text-plain-for-css-and-js-files-only
    // https://stackoverflow.com/questions/7236191/how-to-create-a-custom-magic-file-database
    // Otherwise, you can use custom rules :
    $customMappings = [
        'js' => 'text/javascript', //'application/javascript',
        'css' => 'text/css',
    ];
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    // consoleLog('Debug', $ext);
    if (array_key_exists($ext, $customMappings)) {
        $mime = $customMappings[$ext];
    }
    consoleLog('info', "CORS added to file {$mime} : {$filePath}");
    header("Content-type: {$mime}");
    echo file_get_contents($filePath);
    return true;
} else {
    consoleLog('info', "Not catched by routing, Transparent serving for : "
    . $_SERVER["REQUEST_URI"]);
    return false; // Let php bultin server serve
}