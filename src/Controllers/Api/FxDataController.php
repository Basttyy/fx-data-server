<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Carbon\Carbon;

class FxDataController
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
            case 'currency_conversion_data';
                $resp = $this->currencyConversionData($ticker, $offerside);
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        return $resp;
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

    private function currencyConversionData (string $ticker, int $year)
    {
        // $date = new Carbon($date);

        // $year = $date->yearIso;
        // $month = $date->month;
        // $day = $date->day;

        $file_path = env('APP_ENV') === 'local' ?
            "{$_SERVER['DOCUMENT_ROOT']}/minute_data/ASK/yearly/1440mins/$ticker/{$year}-1440"."min_data.csv.gz" :
            "{$_SERVER['DOCUMENT_ROOT']}/../../minute_data/ASK/yearly/1440mins/$ticker/{$year}-1440"."min_data.csv.gz";

        if (file_exists($file_path)) {
            $data = gzinflate(file_get_contents($file_path))."\n";
            if ($data) {
                $ext = pathinfo($file_path, PATHINFO_EXTENSION);
                header("Content-Type: text/$ext; charset=utf-8");
                echo $data;
                return true;
            }
        }

        return JsonResponse::notFound("file not found or date  $year out of range");
    }
}