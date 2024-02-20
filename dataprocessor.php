<?php
require_once __DIR__."/vendor/autoload.php";

// parse_str(implode('&', array_slice($argv, 1)), $_GET);

// php dataprocessor.php -c parse -t xauusd -y 2016,2017,2018,2019,2020,2021,2022,2023 -T 1
// php dataprocessor.php -c parse_timeframe -t xauusd -y 2016,2017,2018,2019,2020,2021,2022,2023 -T 1,2,5,10,15,30
// php dataprocessor.php -c compress -t xauusd -y 2016,2017,2018,2019,2020,2021,2022,2023 -T 2,5,15,30,60,120
// php dataprocessor.php -c compact_week -t xauusd -s 2016 -e 2023 -T 1,2,5,15,30,60,120
// php dataprocessor.php -c compact_month -t xauusd -s 2016 -e 2023 -T 1440
// php dataprocessor.php -c parse_timeframe -t eurusd -T=240 -S=60 -y=2016,2017,2018,2019,2020,2021,2022,2023

use Carbon\Carbon;
use Basttyy\FxDataServer\libs\NumberConverter;

$short_options = "c:t:T:S::by::s::e::z";
$long_options = ["command:", "tickers:", "timeframes:", "source_timeframe::", "usebid", "years::", "startyear::", "endyear::", "zip"];
$options = getopt($short_options, $long_options);

$usebid = isset($options["b"]) || isset($options["usebid"]);
$zip = isset($options["z"]) || isset($options["zip"]);        /// Wether to zip the result or not
$command = isset($options["c"]) ? $options["c"] : $options["command"]; /// compress|parse|compact_week
$tickers = isset($options["t"]) ? explode(",", strtoupper($options["t"])) : explode(",", strtoupper($options["tickers"]));
$mins = isset($options["T"]) ? explode(",", $options["T"]) : explode(",", $options["timeframes"]);

if (isset($options["S"]) || isset($options["source_timeframe"]))
    $source_tf = isset($options["S"]) ? $options["S"] : $options["source_timeframe"];
if (isset($options["y"]) || isset($options["years"]))
    $years = isset($options["y"]) ? explode(",", $options["y"]) : explode(",", $options["years"]);
if (isset($options["s"]) || isset($options["startyear"]))
    $starty = isset($options["s"]) ? $options["s"] : $options["startyear"];
if (isset($options["e"]) || isset($options["endyear"]))
    $endy = isset($options["e"]) ? $options["e"] : $options["endyear"];

print_r($options);

$datetime = new Carbon();

$offerside = $usebid ? 'BID' : 'ASK';
///FRIDAY 1min candles count is 1320
///SUNDAY 1min candles count is 120
///SATURDAY 1min candles count is 0
///OTHERS 1min candles count is 1440
///FRIDAY and SUNDAY forms one day
///It's also possible that SUNDAY and MONDAY forms one day in this case, a part of monday goes to teu and a part of teu goes to wed and so on

function minsLessThan(array $mins, int $timeframe) {
    while ($min = next($mins)) {
        if ((int)$min >= $timeframe)
            return false;
    }
    return true;
}

if ($command == 'parse') {
    echo "got to parse".PHP_EOL;
    require_once __DIR__."/dataprocessors/parse.php";
} else if ($command === 'parse_timeframe') {
    echo "got to parse timeframe".PHP_EOL;
    require_once __DIR__."/dataprocessors/parse_timeframe.php";
} else if ($command === 'compact_week' && minsLessThan($mins, 60)) {
    echo "compacting to weekly data".PHP_EOL;
    require_once __DIR__."/dataprocessors/compact_weekly.php";
} else {
    consoleLog('Warning', "Wrong or unknown command $command");
}