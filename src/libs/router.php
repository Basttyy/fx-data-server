<?php

namespace Basttyy\FxDataServer\libs;

use Basttyy\FxDataServer\Exceptions\NotFoundException;

class Router
{
    protected static $routes = [];
    protected static $middlewares = [];
    protected static $groupPrefix = '';
    protected static $routeName = '';
    protected static $currentRoute = '';

    public function __construct()
    {
        if (strtolower($_SERVER["REQUEST_METHOD"]) !== "options") {
            session_set_save_handler(new MysqlSessionHandler, true);
            session_start();
        }
    }

    public static function group(string $prefix, callable $method)
    {
        $previousPrefix = self::$groupPrefix;
        self::$groupPrefix = rtrim(self::$groupPrefix, '/') . '/' . ltrim($prefix, '/');

        call_user_func($method);

        self::$groupPrefix = $previousPrefix;
    }

    public static function middleware(string | array $middleware, callable $method)
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];

        $previousMiddlewares = self::$middlewares;
        self::$middlewares = array_merge(self::$middlewares, $middleware);

        call_user_func($method);

        self::$middlewares = $previousMiddlewares;
    }

    public static function name(string $name, callable $method)
    {
        $previousName = self::$routeName;
        self::$routeName = $name;

        call_user_func($method);

        self::$routeName = $previousName;
    }

    protected static function addRoute(string $method, string $route, callable|string|array $path_to_include)
    {
        $route = self::$groupPrefix . '/' . ltrim($route, '/');
        $route = rtrim($route, '/');
        $name = self::$routeName ? self::$routeName : $route;

        self::$routes[$method][$route] = [
            'callback' => $path_to_include,
            'middlewares' => self::$middlewares,
            'name' => $name,
        ];

        self::$routeName = '';
    }

    public static function get(string $route, callable|string|array $path_to_include)
    {
        self::addRoute('GET', $route, $path_to_include);
    }

    public static function post(string $route, callable|string|array $path_to_include)
    {
        self::addRoute('POST', $route, $path_to_include);
    }

    public static function put(string $route, callable|string|array $path_to_include)
    {
        self::addRoute('PUT', $route, $path_to_include);
    }

    public static function patch(string $route, callable|string|array $path_to_include)
    {
        self::addRoute('PATCH', $route, $path_to_include);
    }

    public static function delete(string $route, callable|string|array $path_to_include)
    {
        self::addRoute('DELETE', $route, $path_to_include);
    }

    public static function any(string $route, callable|string|array $path_to_include)
    {
        self::addRoute('ANY', $route, $path_to_include);
    }

    public static function dispatch(Request $request)
    {
        $requestMethod = $request->method();
        $requestUri = rtrim(filter_var($request->server('REQUEST_URI'), FILTER_SANITIZE_URL), '/');
        $requestUri = strtok($requestUri, '?');

        foreach (self::$routes[$requestMethod] ?? [] as $route => $data) {
            $routeParts = explode('/', $route);
            $requestUriParts = explode('/', $requestUri);

            if (count($routeParts) != count($requestUriParts)) {
                continue;
            }

            $parameters = [];
            $matched = true;

            for ($i = 0; $i < count($routeParts); $i++) {
                if (preg_match("/^[$]/", $routeParts[$i])) {
                    $routePart = ltrim($routeParts[$i], '$');
                    $parameters[$routePart] = $requestUriParts[$i];
                } elseif ($routeParts[$i] != $requestUriParts[$i]) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {
                self::$currentRoute = $route;

                foreach ($data['middlewares'] as $middleware) {
                    $middlewareInstance = new $middleware;
                    if ($middlewareInstance->handle($request)) {
                        return;
                    }
                }

                // foreach ($data['callback'] as $callback) {
                    $callback = $data['callback'];
                    if (is_callable($callback)) {
                        call_user_func_array($callback, array_merge([$request], $parameters));
                    } elseif (is_array($callback) && count($callback) > 1) {
                        [$controller, $method] = $callback;
                        $controllerInstance = new $controller;
                        call_user_func_array([$controllerInstance, $method], array_merge([$request], $parameters));
                    } elseif (is_string($callback)) {
                        include_once __DIR__ . "/$callback";
                    } else {
                        throw new NotFoundException('route not found');
                    }
                // }

                return;
            }
        }

        if (isset(self::$routes['ANY']['/404'])) {
            $data = self::$routes['ANY']['/404'];
            foreach ($data['callback'] as $callback) {
                if (is_callable($callback)) {
                    call_user_func($callback, $request);
                } elseif (is_array($callback) && count($callback) > 1) {
                    [$controller, $method] = $callback;
                    $controllerInstance = new $controller;
                    call_user_func([$controllerInstance, $method], $request);
                } elseif (is_string($callback)) {
                    include_once __DIR__ . "/$callback";
                } else {
                    throw new NotFoundException('route not found');
                }
            }
        }
    }

    public static function route($name, $parameters = [])
    {
        foreach (self::$routes as $method => $routes) {
            foreach ($routes as $route => $data) {
                if ($data['name'] === $name) {
                    foreach ($parameters as $key => $value) {
                        $route = str_replace('$' . $key, $value, $route);
                    }
                    return $route;
                }
            }
        }

        return null;
    }

    public static function out($text, bool $strip_tags = false)
    {
        if ($strip_tags) {
            echo htmlspecialchars(strip_tags($text));
        } else {
            echo htmlspecialchars($text);
        }
    }

    public static function set_csrf()
    {
        if (!isset($_SESSION["csrf"])) {
            $_SESSION["csrf"] = bin2hex(random_bytes(50));
        }
        echo '<input type="hidden" name="csrf" value="' . $_SESSION["csrf"] . '">';
    }

    public static function is_csrf_valid()
    {
        if (!isset($_SESSION['csrf']) || !isset($_POST['csrf'])) {
            return false;
        }
        if ($_SESSION['csrf'] != $_POST['csrf']) {
            return false;
        }
        return true;
    }
}
