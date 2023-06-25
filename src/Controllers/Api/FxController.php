<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\libs\JsonResponse;

class FxController
{
    private $method;

    public function __construct($method = "")
    {
        $this->method = $method;
    }

    public function __invoke(string $ticker = "", string $timeframe = "", string $query = "", int $from = null, int $incr = null, int $nums = null, bool $faster = null)
    {
        switch ($this->method) {
            case 'download_minute_data':
                $resp = $this->downloadMinuteData($ticker, $from, $incr, $nums);
                break;
            case 'download_tick_data':
                $resp = $this->downloadTickData($ticker, $from, $nums, $faster);
                break;
            case 'get_timeframe_candles':
                $resp = $this->getTimeframeCandles($ticker, $from, $nums, $timeframe);
                break;
            case 'search_ticker':
                $resp = $this->searchTicker($query);
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        $resp;
    }

    private function downloadMinuteData (string $ticker, int $from, int $incr, int $nums)
    {
        if (!count(searchTicker($ticker))) {
            header("Content-type: application/json");
            http_response_code(404);
            consoleLog(0, "request ticker does not exist");
            out(json_encode(["message" => "ticker does not exist"]));
            return true;
        }

        $data = false;
        // if ($nums > 1) {
        if (!$files = getMinutesFilesList($ticker, $from, $incr, $nums)) {
            header("Content-type: application/json");
            http_response_code(404);
            consoleLog(0, "file not found or datetime not in range");
            out(json_encode(["message" => "file not found or datetime not in range"]));
            return true;
        }

            //$filePath = __DIR__."/../download/$ticker/temp/".(string)time().".csv";
            // if (!$data = joinCsvFast($files, $filePath, $faster)) {
            //     consoleLog(0, "someting happend, couldn't join csv");
            //     http_response_code(500);
            //     out(json_encode(["message" => "unable to join csv files"]));
            //     return true;
            // }
        // } else {
        //     $filePath = "{$_SERVER['DOCUMENT_ROOT']}{$_SERVER["REQUEST_URI"]}";
        // }
        
        // if (!$data && !file_exists($filePath)) {
        //     consoleLog('info', "File not found Error for : " . $_SERVER["REQUEST_URI"]);
        //     // return false;
        //     http_response_code(404);
        //     header("Content-type: application/json");
        //     echo "File not Found : {$filePath}";
        //     return true;
        // }

        // https://stackoverflow.com/questions/45179337/mime-content-type-returning-text-plain-for-css-and-js-files-only
        // https://stackoverflow.com/questions/7236191/how-to-create-a-custom-magic-file-database
        // Otherwise, you can use custom rules :
        $customMappings = [
            'js' => 'text/javascript', //'application/javascript',
            'css' => 'text/css',
        ];
        $ext = pathinfo($files[0], PATHINFO_EXTENSION);
        $mime = mime_content_type($files[0]);
        header("Content-type: {$mime}");
        // consoleLog('info', "CORS added to file {$mime} : {$filePath}");
        consoleLog('info', 'got total files of: '.count($files));
        $i = 1;
        foreach ($files as $filePath) {
            // $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            // consoleLog('Debug', $ext);
            if (array_key_exists($ext, $customMappings)) {
                $mime = $customMappings[$ext];
            }
            // header("Content-type: {$mime}");
            // if ($data) {
            //     echo $data;
            // } else {
                echo file_get_contents($filePath);
                consoleLog('info', "file $filePath sent");
                $i++;
                //unlink($filePath);
            // }
        }
        return true;
    }

    private function downloadTickData (string $ticker, int $from, int $nums, bool $faster)
    {
        $data = false;
        if ($nums > 1) {
            $files = getFilesList($ticker, $from, $nums);

            $filePath = __DIR__."/../download/$ticker/temp/".(string)time().".csv";
            if (!$data = joinCsvFast($files, $filePath, $faster)) {
                consoleLog(0, "someting happend, couldn't join csv");
                out(json_encode(["message" => "unable to join csv files"]));
                return true;
            }
        } else {
            $filePath = "{$_SERVER['DOCUMENT_ROOT']}{$_SERVER["REQUEST_URI"]}";
        }
        
        if (!$data && !file_exists($filePath)) {
            consoleLog('info', "File not found Error for : " . $_SERVER["REQUEST_URI"]);
            // return false;
            http_response_code(404);
            header("Content-type: application/json");
            echo "File not Found : {$filePath}";
            return true;
        }

        $mime = $data ? mime_content_type($filePath) : 'text/csv';
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
        if ($data) {
            echo $data;
        } else {
            echo file_get_contents($filePath);
            unlink($filePath);
        }
        return true;
    }

    private function getTimeframeCandles(string $ticker, int $from, int $nums, int $timeframe)
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
    }

    private function searchTicker(string $query)
    {
        $symbols = [
            (object) [
                'exchange' => '',
                'market' => 'fx',
                'name' => 'Euro against US Dollar',
                'shortName' => 'EURUSD',
                'ticker' => 'EURUSD',
                'priceCurrency' => 'usd',
                'type' => 'ADRC',
            ],
            (object) [
                'exchange' => '',
                'market' => 'fx',
                'name' => 'Great Britain Pound against US Dollar',
                'shortName' => 'GBPUSD',
                'ticker' => 'GBPUSD',
                'priceCurrency' => 'usd',
                'type' => 'ADRC',
            ],
            (object) [
                'exchange' => '',
                'market' => 'fx',
                'name' => 'Goldspot vs United State Dollar',
                'shortName' => 'XAUUSD',
                'ticker' => 'XAUUSD',
                'priceCurrency' => 'usd',
                'type' => 'ADRC',
            ],
        ];
        header("Content-type: application/json");
        if ($query < "") {
            http_response_code(200);
            echo json_encode([
                "message" => "all tickers list",
                "count" => count($symbols),
                "results" => $symbols
            ]);
        }

        $query = strtolower($query);

        $data = array_filter($symbols, function ($symbol) use ($query) {
            return str_contains(strtolower($symbol->exchange), $query)
            || str_contains(strtolower($symbol->market), $query)
            || str_contains(strtolower($symbol->name), $query)
            || str_contains(strtolower($symbol->ticker), $query)
            || str_contains(strtolower($symbol->priceCurrency), $query)
            || str_contains(strtolower($symbol->type), $query);
        });
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
    }
}