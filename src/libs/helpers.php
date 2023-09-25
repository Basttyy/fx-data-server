<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

if (! function_exists('json_response')) {
    /**
     * Returns a json response for PHP http request
     * 
     * @param int $status_code
     * @param array $data
     */
    function json_response(int $status_code, array $data)
    {
        http_response_code($status_code);
        header("Content-type: application/json");
        echo json_encode($data);
        return true;
    }
}

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        //$dotenv = Dotenv::createImmutable(__DIR__);
        //$dotenv->load();
        return \array_search($key, $_ENV) === false ? $default : $_ENV[$key];
    }
}

if (! function_exists('sanitize_data')) {
    /**
     * Sanitize an array of data or string data
     * 
     * @param array|string|int $data
     * @return array|string|int
     */
    function sanitize_data($data)
    {
        if (is_iterable($data)) {
            foreach ($data as $key => $dat) {
                $data[$key] = is_int($dat) || is_null($dat) ? $dat : htmlspecialchars(strip_tags($dat));
            }
        } else {
            $data = is_int($data) || is_null($data) ? $data : htmlspecialchars(strip_tags($data));
        }

        return $data;
    }
}

if (!function_exists("consoleLog")) {
    function consoleLog($level, $msg) {
        file_put_contents("php://stdout", "[" . $level . "] " . $msg . "\n");
    }
}

if (! function_exists('storage_path')) {
    function storage_path()
    {
        return strtolower(PHP_OS_FAMILY) === "windows" ? __DIR__."\\..\\..\\storage\\" : __DIR__."/../../storage/";
    }
}

if (! function_exists('logger')) {
    function logger($path = null, $level = Logger::DEBUG, $bubble = true, $filePermission = 0664, $useLocking = false)
    {
        $logger_path = strtolower(PHP_OS_FAMILY) === "windows" ? "logs\\custom.log" : "logs/custom.log";
        $path = is_null($path) ? storage_path().$logger_path : $path;
        $log = new Logger('tradingio');
        return $log->pushHandler(new StreamHandler($path, $level, $bubble, $filePermission, $useLocking));
    }
}

if (! function_exists('format_int_leading_zero')) {
    function format_int_leading_zero(int $number): string
    {
        if (is_numeric($number) && $number < 10) {
            return "0" . $number;
        } else {
            return strval($number); // Convert to string without leading zero
        }
    }
}

if (! function_exists('validate_data_structure')) {
    /**
     * Validate that an array data matches a given structure
     * 
     * @param object $data
     * @param array $structure
     * @return bool
     */
    function validate_data_structure($data, $structure)
    {
        foreach ($structure as $key => $value) {
            if (!property_exists($data, $key)) {
                return false;
            }
    
            if (is_array($value)) {
                if (!is_array($data->$key) || !validate_data_structure($data->$key, $value)) {
                    return false;
                }
            }
        }
    
        return true;
    }
}

if (! function_exists('get_weeks_in_year')) {
    /**
     * Get the number of weeks in a given year
     * 
     * @param string $year
     * 
     * @return int
     */
    function get_weeks_in_year($year) {
        // Create a DateTime object for January 4th of the year
        $date = new DateTime($year . '-01-04');
    
        // Get the week number of January 4th
        $january4WeekNumber = (int)$date->format('W');
    
        // If January 4th falls on or before Thursday, it's week 1 of the year
        if ($date->format('N') <= 4) {
            return $january4WeekNumber;
        } else {
            // Otherwise, the week containing January 1st belongs to the previous year
            return $january4WeekNumber - 1;
        }
    }
}