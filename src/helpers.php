<?php

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