<?php

namespace Basttyy\FxDataServer\libs;

use Basttyy\FxDataServer\libs\Str;

class Validator {
    private static array $req_obj;

    public function __construct(array $_req_obj = [])
    {
        self::$req_obj = $_req_obj;
    }

    public static function validate(array $req_obj, array $params, string $separator = '|'): bool|array
    {
        $errors = [];
        foreach ($params as $paramKey => $paramValue) {
            $validations = explode($separator, $paramValue);
            $resp = (new self($req_obj))->validateValue($paramKey, $validations);
            if (!is_array($resp)) {
                continue;
            }
            $errors[$paramKey] = $resp;  //Correction needded, it should be key value pair
        }

        if (count($errors) < 0) {
            return false;
        }
        return $errors;
    }

    private function validateValue(string $param, array $validations): bool|array
    {
        $errors = [];
        if ($param === null || $validations === null) {
            return true;
        }
        foreach ($validations as $validation) {
            $resp = $this->getError($param, $validation);
            if ($resp == '') {
                continue;
            }

            array_push($errors, $resp);
        }
        if (count($errors) < 1)
            return true;

        return $errors;
    }

    private function getError(string $param, string $type): string 
    {
        $paramval = $this->getParamValue($param);
        if ($paramval === false && $type != 'required') {
            return '';
        }
        switch ($type) {
            case 'required':
                if ($paramval != false)
                    $resp = '';
                else
                    $resp = "{$param} is required";
                break;
            case 'string':
                $stat = is_string($paramval);
                $resp = !$stat ? "{$param} should be a string" : '';
                break;
            case 'bool':
                $stat = !is_bool($paramval);
                $resp = $stat ? "{$param} should be a boolean" : '';
                break;
            case 'boolean':
                $stat = !is_bool($paramval);
                $resp = $stat ? "{$param} should be a boolean" : '';
                break;
            case 'float':
                $stat = is_float($paramval) || is_numeric($paramval) && ((float) $paramval != (int) $paramval) || is_numeric($paramval) && stripos($paramval,'.');
                $resp = !$stat ? "{$param} should be a float" : '';
                break;
            case 'double':
                $stat = is_double($paramval) || is_numeric($paramval) && ((double) $paramval != (int) $paramval) || is_numeric($paramval) && stripos($paramval,'.');
                $resp = !$stat ? "{$param} should be a double" : '';
                break;
            case 'integer':
                $stat = is_integer($paramval) || is_int($paramval) || is_numeric($paramval) && !stripos($paramval,'.');
                $resp = !$stat ? "{$param} should be an integer" : '';
                break;
            case 'int':
                $stat = is_integer($paramval) || is_int($paramval) || is_numeric($paramval) && !stripos($paramval,'.');
                $resp = !$stat ? "{$param} should be an integer" : '';
                break;
            case 'numeric':
                $stat = is_numeric($paramval);
                $resp = !$stat ? "{$param} should be a numeric" : '';
                break;
            case 'url':
                $stat = is_link($paramval);
                $resp = !$stat ? "{$param} should be an url" : '';
                break;
            case 'file':
                $stat = is_file($paramval);
                $resp = !$stat ? "{$param} should be a file" : '';
                break;
            case 'array':
                $stat = is_array($paramval);
                $resp = !$stat ? "{$param} should be an array" : '';
                break;
            default:
                $resp = $this->performAdvanceValidation($type, $param, $paramval);
        }
        return $resp;
    }

    private function performAdvanceValidation(string $type, $param, $paramval)
    {
        if (Str::contains($type, ":")) {
            $items = explode(':', $type);

            print_r($items);

            switch ($items[0]) {
                case 'max':
                    $resp = $paramval > (int)$items[1] ? "{$param} should not be greater than {$items[1]}" : '';
                    break;
                case 'min':
                    $resp = $paramval < (int)$items[1] ? "{$param} should not be less than {$items[1]}" : '';
                    break;
                case 'in':
                    echo $paramval.PHP_EOL;
                    $resp = !Arr::exists(explode(', ', $items[1]), $paramval, true) ? "{$param} should contain one of {$items[1]}" : '';
                    break;
                case 'not_in':
                    $resp = Arr::exists(explode(', ', $items[1]), $paramval, true) ? "{$param} should not contain any of {$items[1]}" : '';
                    break;
                default:
                    $resp = '';
            }
        } else { $resp = ''; }

        return $resp;
    }

    private function getParamValue(string $param): int|bool|float|string|array
    {
        if (!array_key_exists($param, self::$req_obj)) {
            return false;
        }
        return self::$req_obj[$param];
    }
}