<?php
require_once __DIR__."/../config.php";
require_once __DIR__."/libs/helpers.php";

$downloadMinuteData = function (string $ticker, int $period, int $from, int $incr, int $nums)
{
    if (!count(searchTicker($ticker))) {
        header("Content-type: application/json");
        http_response_code(404);
        echo(json_encode(["message" => "ticker does not exist"]));
        return true;
    }

    $data = false;
    // if ($nums > 1) {
    if (!$files = getMinutesFilesList($ticker, $period, $from, $incr, $nums)) {
        header("Content-type: application/json");
        http_response_code(404);
        echo(json_encode(["message" => "file not found or datetime not in range"]));
        return true;
    }

    // https://stackoverflow.com/questions/45179337/mime-content-type-returning-text-plain-for-css-and-js-files-only
    // https://stackoverflow.com/questions/7236191/how-to-create-a-custom-magic-file-database
    // Otherwise, you can use custom rules :
    $customMappings = [
        'js' => 'text/javascript', //'application/javascript',
        'css' => 'text/css',
    ];
    $ext = pathinfo($files[0], PATHINFO_EXTENSION);
    $mime = mime_content_type($files[0]);
    header("Content-type: text/csv");
    // header("Content-encoding: deflate");
    $i = 1;
    
    foreach ($files as $filePath) {
        // $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (array_key_exists($ext, $customMappings)) {
            $mime = $customMappings[$ext];
        }
        // header("Content-type: {$mime}");
        // if ($data) {
        //     echo $data;
        // } else {
            echo gzuncompress(file_get_contents($filePath));
            // if ($i < $nums)
            //     echo "*----*";
            $i++;
            //unlink($filePath);
        // }
    }
    return true;
};

$getMinTfCandles = function (string $ticker, int $from, int $nums, int $timeframe)
{
    header("Content-type: application/json");
    if (!$data = getCandles($ticker, $from, $nums, $timeframe)) {
        http_response_code(404);
        echo json_encode([
            "message" => "required csv file(s) were not found"
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            "message" => "data retrieved success",
            "count" => count($data),
            "data" => $data
        ]);
    }
    return true;
};

$searchMinTicker = function (string $query = "")
{
    header("Content-type: application/json");
    if ($query < "") {
        $tickers = searchTicker();
        http_response_code(200);
        echo json_encode([
            "message" => "all tickers list",
            "count" => count($tickers),
            "results" => $tickers
        ]);
    }

    $data = searchTicker($query);

    if ($data < 0) {
        http_response_code(404);
        echo json_encode([
            "message" => "no match was found"
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            "message" => "search results retrieved success",
            "count" => count($data),
            "results" => $data
        ]);
    }
    return true;
};