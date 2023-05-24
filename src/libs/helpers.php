<?php

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
     * @param array|string $data
     * @return array|string
     */
    function sanitize_data(iterable|string $data): array|string
    {
        if (is_iterable($data)) {
            foreach ($data as $key => $dat) {
                $data[$key] = htmlspecialchars(strip_tags($dat));
            }
        } else {
            $data = htmlspecialchars(strip_tags($data));
        }

        return $data;
    }
}

if (!function_exists("consoleLog")) {
    function consoleLog($level, $msg) {
        file_put_contents("php://stdout", "[" . $level . "] " . $msg . "\n");
    }
}