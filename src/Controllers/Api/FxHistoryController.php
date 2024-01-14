<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;

class FxHistoryController
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

    public function __invoke(string $ticker = "", string $offerside = "", int $period = null, int $year = 0, int $month = 0, int $week = 0, string $query = "", bool $faster = null)
    {
        switch ($this->method) {
            case 'download_minute_data':
                $resp = $this->downloadMinutesData($ticker, $offerside, $period, $year, $month, $week);
                break;
            case 'download_tick_data':
                $resp = $this->downloadTickData($ticker, $year, $week, $faster);
                break;
            case 'get_timeframe_candles':
                $resp = $this->getTimeframeCandles($ticker, $year, $week, $period);
                break;
            case 'search_ticker':
                $resp = $this->searchTicker($query);
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        $resp;
    }

    private function downloadMinutesData (string $ticker, string $offerside, int $period, int $year, int $month, int $week)
    {
        if (!count(searchTicker($ticker))) {
            return JsonResponse::notFound("ticker does not exist");
        }
    
        if ($period < 60 && $period > 0) {
            $wk = $week < 10 ? "0$week" : "$week";
            $file_path =
                env('APP_ENV') === 'local' ?
                "{$_SERVER['DOCUMENT_ROOT']}/minute_data/$offerside/weekly/{$period}mins/$ticker/$year/week$wk"."_data.csv.gz" :
                "{$_SERVER['DOCUMENT_ROOT']}/../../minute_data/$offerside/weekly/{$period}mins/$ticker/$year/week$wk"."_data.csv.gz";
        } else if ($period >= 60 && $period < 1440) {
            $mn = $month < 10 ? "0$month" : "$month";
            $file_path =
                env('APP_ENV') === 'local' ?
                "{$_SERVER['DOCUMENT_ROOT']}/minute_data/$offerside/monthly/{$period}mins/$ticker/$year/month-$mn"."_data.csv.gz" :
                "{$_SERVER['DOCUMENT_ROOT']}/../../minute_data/$offerside/monthly/{$period}mins/$ticker/$year/month-$mn"."_data.csv.gz";
        } else if ($period >= 1440) {
            $file_path =
                env('APP_ENV') === 'local' ?
                "{$_SERVER['DOCUMENT_ROOT']}/minute_data/$offerside/yearly/{$period}mins/$ticker/{$year}-$period"."min_data.csv.gz" :
                "{$_SERVER['DOCUMENT_ROOT']}/../../minute_data/$offerside/yearly/{$period}mins/$ticker/{$year}-$period"."min_data.csv.gz";
        } else {
            return JsonResponse::badRequest("period $period invalid or out of range");
        }

        if (file_exists($file_path)) {
            $data = gzinflate(file_get_contents($file_path))."\n";
            if ($data) {
                $ext = pathinfo($file_path, PATHINFO_EXTENSION);
                header("Content-Type: text/$ext; charset=utf-8");
                echo $data;
                return true;
            }
        }

        return JsonResponse::notFound("files not found or date  $year/month_$month/week_$week out of range");
    }

    // private function downloadMinutesData (string $ticker, int $period, int $from, int $incr, int $nums)
    // {
    //     // if (!$this->authenticator->validate()) {
    //     //     return JsonResponse::unauthorized();
    //     // }
    //     // if (!$is_user = $this->authenticator->verifyRole($this->user, 'user')) {
    //     //     return JsonResponse::unauthorized('you are not authorized to access this resource');
    //     // }

    //     if (!count(searchTicker($ticker))) {
    //         return JsonResponse::notFound("ticker does not exist");
    //     }

    //     $files = [];
    //     $date = Carbon::createFromTimestamp($from);
    //     $data = ''; $file_path = ''; $meta = '';

    //     for ($i = 0; $i < $nums; $i++) {
    //         $week = $date->isoWeek;
    //         $month = $date->month;
    //         $year = $date->year;       
    //         if ($period < 240 && $period > 0) {
    //             if ($month === 1 && $week > 50) {
    //                 $year--;
    //             }
    //             $file_path =
    //                 env('APP_ENV') === 'local' ?
    //                 "{$_SERVER['DOCUMENT_ROOT']}/minute_data/weekly/{$period}mins/$ticker/$year/week$week"."_data.csv" :
    //                 "{$_SERVER['DOCUMENT_ROOT']}/../../minute_data/weekly/{$period}mins/$ticker/$year/week$week"."_data.csv";
            
    //             if ($i === 0) {
    //                 $meta = "$week";
    //             } else if ($i === $nums-1) {
    //                 $meta = "$meta - $week";
    //             }
    //             $incr ? array_push($files, $file_path) : array_unshift($files, $file_path);
    //             $incr ? $date->addWeek() : $date->subWeek();
    //         } else if ($period === 240) {
    //             $file_path =
    //                 env('APP_ENV') === 'local' ?
    //                 "{$_SERVER['DOCUMENT_ROOT']}/minute_data/monthly/{$period}mins/$ticker/$year/month$month"."_data.csv" :
    //                 "{$_SERVER['DOCUMENT_ROOT']}/../../minute_data/monthly/{$period}mins/$ticker/$year/month$month"."_data.csv";
            
    //             if ($i === 0) {
    //                 $meta = "$month";
    //             } else if ($i === $nums-1) {
    //                 $meta = "$meta - $month";
    //             }
    //             $incr ? array_push($files, $file_path) : array_unshift($files, $file_path);
    //             $incr ? $date->addMonth() : $date->subMonth();
    //         } else if ($period > 240) {
    //             $file_path =
    //                 env('APP_ENV') === 'local' ?
    //                 "{$_SERVER['DOCUMENT_ROOT']}/minute_data/yearly/{$period}mins/$ticker/{$year}_$period"."_data.csv" :
    //                 "{$_SERVER['DOCUMENT_ROOT']}/../../minute_data/yearly/{$period}mins/$ticker/{$year}_$period"."_data.csv";
            
    //             if ($i === 0) {
    //                 $meta = "$week";
    //             } else if ($i === $nums-1) {
    //                 $meta = "$meta - $week";
    //             }
    //             $incr ? array_push($files, $file_path) : array_unshift($files, $file_path);
    //             $incr ? $date->addYear() : $date->subYear();
    //         } else {
    //             return JsonResponse::badRequest("period $period invalid or out of range");
    //         }
    //     }

    //     if (!empty($files)) {
    //         foreach ($files as $file) {
    //             logger()->info($file);
    //             if (file_exists($file)) {
    //                 $data .= gzuncompress(file_get_contents($file))."\n";
    //             }
    //         }
    //         if ($data !== '') {
    //             $ext = pathinfo($file_path, PATHINFO_EXTENSION);
    //             header("Content-Type: text/$ext; charset=utf-8");
    //             header("Filename: $meta");
    //             echo $data;
    //             return true;
    //         }
    //     }
    //     return JsonResponse::notFound("files not found or date $from out of range");
    // }

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