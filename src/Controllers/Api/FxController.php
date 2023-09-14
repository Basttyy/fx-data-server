<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;

class FxController
{
    private $method;
    private $user;
    private $authenticator;

    public function __construct($method = "download_minute_data")
    {
        $this->method = $method;
        $this->user = new User();
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role();
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
    }

    public function __invoke(string $ticker = "", string $period = "", int $from = null, int $incr = null, int $nums = null, string $query = "", bool $faster = null)
    {
        switch ($this->method) {
            case 'download_minute_data':
                $resp = $this->downloadMinuteData($ticker, $period, $from, $incr, $nums);
                break;
            case 'download_tick_data':
                $resp = $this->downloadTickData($ticker, $from, $nums, $faster);
                break;
            case 'get_timeframe_candles':
                $resp = $this->getTimeframeCandles($ticker, $from, $nums, $period);
                break;
            case 'search_ticker':
                $resp = $this->searchTicker($query);
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        $resp;
    }

    private function downloadMinuteData (string $ticker, string $period, int $from, int $incr, int $nums)
    {
        if (!$this->authenticator->validate()) {
            return JsonResponse::unauthorized();
        }
        if (!$is_admin = $this->authenticator->verifyRole($this->user, 'user')) {
            return JsonResponse::unauthorized('you are not authorized to access this resource');
        }

        if (!count(searchTicker($ticker))) {
            return JsonResponse::notFound("ticker does not exist");
        }
    
        // if (!$files = getMinutesFilesList($ticker, $period, $from, $incr, $nums)) {
        if (!$files = getWeeksMinuteList($ticker, $period, $from)) {
            return JsonResponse::notFound('file not found or datetime not in range');
        }
    
        // https://stackoverflow.com/questions/45179337/mime-content-type-returning-text-plain-for-css-and-js-files-only
        // https://stackoverflow.com/questions/7236191/how-to-create-a-custom-magic-file-database
        // Otherwise, you can use custom rules 

        $ext = pathinfo($files[0], PATHINFO_EXTENSION);
        header("Content-type: $ext");
        
        $data = '';// $len = sizeof($files);
        foreach ($files as $filePath) {
            if (file_exists($filePath)) {
                // $len--;
                $data .= gzuncompress(file_get_contents($filePath));
                // $data .= $len ? "\n" : '';
            }
        }
        echo $data;
        return true;
    }

    private function downloadTickData (string $ticker, int $from, int $nums, bool $faster)
    {
        $data = false;
        if ($nums > 1) {
            $files = getFilesList($ticker, $from, $nums);

            $filePath = __DIR__."/../download/$ticker/temp/".(string)time().".csv";
            if (!$data = joinCsvFast($files, $filePath, $faster)) {
                out(json_encode(["message" => "unable to join csv files"]));
                return true;
            }
        } else {
            $filePath = "{$_SERVER['DOCUMENT_ROOT']}/..{$_SERVER["REQUEST_URI"]}";
        }
        
        if (!$data && !file_exists($filePath)) {
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
        if (array_key_exists($ext, $customMappings)) {
            $mime = $customMappings[$ext];
        }
        header("Content-type: {$mime}");
        header("Content-encoding: deflate");
        if ($data) {
            echo $data;
        } else {
            echo file_get_contents($filePath);
            unlink($filePath);
        }
        return true;
    }

    private function getTimeframeCandles(string $ticker, int $from, int $nums, int $period)
    {
        header("Content-type: application/json");
        if (!$data = getCandles($ticker, $from, $nums, $period)) {
            return JsonResponse::notFound("required csv file(s) were not found");
        } else {
            header("Content-encoding: deflate");
            return JsonResponse::ok("data retrieved success", [
                "count" => count($data),
                "data" => $data
            ]);
        }
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