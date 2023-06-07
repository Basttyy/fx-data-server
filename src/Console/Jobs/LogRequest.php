<?php
namespace Basttyy\FxDataServer\Console\Jobs;

use Basttyy\FxDataServer\Console\QueueInterface;
use Basttyy\FxDataServer\Console\ShouldQueue;
use Basttyy\FxDataServer\Libraries\Helpers\Arr;
use Basttyy\FxDataServer\Libraries\Helpers\Validator;
use Basttyy\FxDataServer\Models\Account;
use Basttyy\FxDataServer\Models\AccountProcess;
use Basttyy\FxDataServer\Models\Broker;
use Exception;

class LogRequest implements QueueInterface
{
    use ShouldQueue;

    private $conn;
    private $body;
    private $account;
    private $process;

    public function __construct(Account $account, AccountProcess $process, WebSocket $conn, array $body)
    {
        $this->account = $account;
        $this->process = $process;
        $this->conn = $conn;
        $this->body = $body;
    }

    public function handle(): void
    {
            
        $platform = Broker::MT4.', '.Broker::MT5. ', '.Broker::CTRADER;
        $status = Account::ACTIVE.', '.Account::INACTIVE;
        $mode = Account::TRADING_CLIENT.', '.Account::SLAVE_CLIENT.', '.Account::MASTER_CLIENT;

        if ($validated = Validator::validate($this->body, [
            'name' => 'required|string',
            'account_login' => 'required|string',
            'mode' => "sometimes|in:$mode",
            'balance' => "sometimes|double|min:0",
            'equity' => "sometimes|double|min:0",
            'server_platform' => "required|string|in:$platform",
            'server_address' => "sometimes|string",
            'server_name' => "required|string",
            'server_type' => "required|string",
        ])) {
            echo "cannot validate".PHP_EOL;
            print_r($validated);
            $this->conn->send("request\error :- ".base64_encode(json_encode([
                'message' => 'errors in request',
                'errors' => $validated
            ])));
        }
        echo "validation completed".PHP_EOL;

        $config_url = project_root(). "\\ecosystem.config.js";


        // $result = yield \React\Promise\resolve(shell_exec("pm2 start $config_url -- /portable > nul && pm2 show devmt4_7"));
        $result = shell_exec("pm2 start $config_url -- /portable > nul && pm2 show devmt4_7");
        // $result = yield \React\Promise\resolve(shell_exec("pm2 start ../postscript.config.js -- ../mt4/app_1/config.ini /portable"));
        echo "shell process started".PHP_EOL;
        echo $result.PHP_EOL;

        $client_data = $this->getDataFromPm2Resp($result);

        ///TODO: Investigate why PID file path returned by pm2 is not found when run from code above
        ///TODO: But is usually found/availabe when above command is run on terminal directly using
        ///TODO: php -r "echo shell_exec('pm2 start C:\Users\TradingIo\Documents\projects\tcpmtbridge\ecosystem.config.js -- /portable > nul && pm2 show devmt4_7');"
        ///TODO: for now, the next two lines will be commented out till that issue is resolved
        // $pid = yield file_get_contents_async($client_data['pid_path']);

        // $client_data['pid'] = $pid;
        print_r($client_data);
        // return;

        $account = await($this->account->create(Arr::only($this->body, ['name', 'account_login', 'mode', 'server_platform', 'server_name', 'server_address', 'server_type'])));

        $process = await($this->process->create([
            "pm2_id" => $client_data['script_id'],
            "account_id" => $account['id'],
            "name" => $client_data['name'],
            "namespace" => $client_data['namespace'],
            "version" => $client_data['version'],
            "pid" => $client_data['pid_path'],
            "uptime" => $client_data['uptime'],
            "restarts" => $client_data['restarts'],
            "status" => $client_data['status'],
            "user" => $client_data['user'],
            "watching" => $client_data['watch_&_reload']
        ]));
        
        // $this->release(20);
    }

    private function getDataFromPm2Resp(string $str, $is_describe = true): array|string
    {
        $data = [];

        try {
            if ($is_describe) {
                $removals = ["\r", "┌", "Divergent", "Add your"];
                foreach ($removals as $removal) {
                    $str2 = $removal === "Divergent" || $removal === "Add your" ? strstr($str, $removal, true) : strstr($str, $removal, false);
                    if ($str2) {
                       $str = $str2;
                    }
                }
            
                $replaces = ["┌", "─", "┬", "┴", "│", "┐\r\n", "\r\n└┘\r\n"];
            
                foreach ($replaces as $replace) {
                    $str = str_replace($replace, "", $str);
                }
           
                $str2 = "";
                $space_found = false;
                $newline = false;
            
                $length = strlen($str);
                for ($index = 0; $index < $length; $index++) {
                   // if ($str[$index] === "\r" || $str[$index+1] === "\r") {
                   //     $newline = true;
                   //     continue;
                   // }
                   if ($str[$index] != " ") {
                       $str2 = $newline ? $str2."\r".$str[$index] : $str2.$str[$index];
                       $space_found = false; $newline = false;
                   }
                   else if (!$space_found) {
                       $str2 = $newline ? $str2."\r"."*" : $str2."*";
                       $space_found = true; $newline = false;
                   }
                   else if ($space_found) {
               
                   }
                }
               //  echo $str.PHP_EOL;
               //  echo $str2.PHP_EOL;
            
                $lines = explode("\n", $str2);
           
                //print_r($lines);
                
               $data = [];
               $len = count($lines);
           
               foreach($lines as $key => $line) {
                   $line = substr($line, 1, strlen($line)-2);
                   //    $line = $len - $key <= 1 ? substr($line, 1, strlen($line)-2) : substr($line, 1, strlen($line)-3);
                   //echo $line.PHP_EOL;
                   $arr = explode('*', $line);
                   $len2 = count($arr);
                   $key2 = $arr[$len2 - 1];
                   unset($arr[$len2-1]);
            
                   $data[implode("_", $arr)] = $key2;
               }
            } else {
                $removals = ["\r", "┌", "Module"];
                foreach ($removals as $removal) {
                    $str = $removal === "Module" ? strstr($str, $removal, true) : strstr($str, $removal, false);
                }
            
                $replaces = ["┌", "─", "┬", "┼", "┴", "│", "┐\r\n", "├┤\r\n", "\r\n└┘\r\n"];
            
                foreach ($replaces as $replace) {
                    $str = str_replace($replace, "", $str);
                }
                $str2 = "";
                $space_found = false;
            
                $length = strlen($str);
                for ($index = 0; $index < $length; $index++) {
                    if ($str[$index] != " ") {
                        $str2 = $str2.$str[$index];
                        $space_found = false;
                    }
                    else if (!$space_found) {
                        $str2 = $str2."*";
                        $space_found = true;
                    }
                    else if ($space_found) {
                        
                    }
                }
            
                $lines = explode("\n", $str2);
            
                $param_arr = explode("*", str_replace('↺', 'restarts', str_replace(' ', "", substr($lines[0], 1, -2))));
                $values_arr = explode("*", str_replace(" ", "", substr($lines[1], 1, -1)));
                
                $data = array_combine($param_arr, $values_arr);
            }
        } catch (Exception $ex) {
            logger()->info($ex->getMessage(), $ex->getTrace());
            return $ex->getMessage();
        }

        return $data;
    }
}