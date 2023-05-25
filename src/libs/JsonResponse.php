<?php

namespace Basttyy\FxDataServer\libs;

use Exception;

class JsonResponse
{
    public const STATUS_OK = 200;
    public const STATUS_NO_CONTENT = 204;
    public const STATUS_CREATED = 201;
    public const STATUS_BAD_REQUEST = 400;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_UNAUTHORIZED = 401;
    public const STATUS_INTERNAL_SERVER_ERROR = 500;

    public function __construct(int $status_code, $data = null)
    {
        //$this->logInfo("jsonresponse class called.");
        $body = $data ? json_encode($data) : null;
        http_response_code($status_code);
        header("Content-type: application/json");
        echo $body;
        // consoleLog(0, $body);
    }

    public static function ok($message = "", $data = null): bool
    {
        try {
            // consoleLog(0, "called json ok method");
            new self(self::STATUS_OK, ['message' => $message, 'data' => $data]);
            return true;
        } catch (Exception $ex) {
            // consoleLog(0, $ex->getMessage());
        }
    }

    public static function noContent(): bool
    {
        try {
            new self(self::STATUS_NO_CONTENT);
            return true;
        } catch (Exception $ex) {
            // consoleLog(0, $ex->getMessage());
        }
    }

    public static function created(string $message = '', $data = []): bool
    {
        try {
            new self(self::STATUS_CREATED, ['message' => $message, 'data' => $data]);
            return true;
        } catch (Exception $ex) {
            // consoleLog(0, $ex->getMessage());
        }
    }

    public static function badRequest(string $message="", string|array $error = ""): bool
    {
        try {
            new self(self::STATUS_BAD_REQUEST, ['message' => $message, 'error' => $error]);
            return true;
        } catch (Exception $ex) {
            // consoleLog(0, $ex->getMessage());
        }
    }

    public static function notFound(string $error): bool
    {
        try {
            new self(self::STATUS_NOT_FOUND, ['message' => $error]);
            return true;
        } catch (Exception $ex) {
            // consoleLog(0, $ex->getMessage());
        }
    }

    public static function unauthorized(string $message = "unauthorized request"): bool
    {
        try {
            new self(self::STATUS_UNAUTHORIZED, ['message' => $message]);
            return true;
        } catch (Exception $ex) {
            // consoleLog(0, $ex->getMessage());
        }
    }

    public static function serverError(string $message=""): bool
    {
        try {
            new self(self::STATUS_INTERNAL_SERVER_ERROR, ['message' => $message]);
            return true;
        } catch (Exception $ex) {
            // consoleLog(0, $ex->getMessage());
        }
    }

    // private function respond(int $statusCode, $body = null)
    // {
    //     consoleLog(0, "JsonResponse respond method called");
    //     try {
    //         return self::json($body)->withStatus($statusCode);
    //     } catch (Exception $ex) {
            //consoleLog(0, $ex->getMessage());
    //     }

    // }
}