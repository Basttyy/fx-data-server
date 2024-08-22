<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Exception;
use Phinx\Wrapper\TextWrapper;

final class MigrateController
{
    private $wrap;
    
    public function __construct()
    {
        $app = require __DIR__ . '/../../../vendor/robmorgan/phinx/app/phinx.php';
        $this->wrap = new TextWrapper($app);

        $routes = [
            'status' => 'getStatus',
            'migrate' => 'getMigrate',
            'rollback' => 'getRollback',
            'seed' => 'getSeed',
        ];
    }

    public function status(Request $request)
    {
        try {
            // Get the environment and target version parameters.
            $env = sanitize_data($request->query('e'));
            $target = sanitize_data($request->query('t'));

            // Check if debugging is enabled.
            $debug = !empty($_GET['debug']) && filter_var($_GET['debug'], FILTER_VALIDATE_BOOLEAN);

            // Execute the command and determine if it was successful.
            $this->wrap->setOption('configuration', $_SERVER['DOCUMENT_ROOT']."/phinx.php");
            $output = $this->wrap->getStatus($env);
            $output = call_user_func([$this->wrap, 'getStatus'], $env, $target);
            $error = $this->wrap->getExitCode() > 0;

            // Finally, display the output of the command.
            if ($error) {
                $args = implode(', ', [var_export($env, true), var_export($target, true)]);
                return JsonResponse::serverError("OUTPUT: $output --- DEBUG: status($args)");
            } else {
                $args = implode(', ', [var_export($env, true), var_export($target, true)]);
                JsonResponse::ok("command executed success", [
                    "output" =>explode("\n", str_replace("\r", "", $output)),
                    "debug" => $debug ? "DEBUG: status($args)" : ""
                ]);
            }
        } catch (Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    public function migrate(Request $request)
    {
        try {
            // Get the environment and target version parameters.
            $env = sanitize_data($request->query('e'));
            $target = sanitize_data($request->query('t'));

            // Check if debugging is enabled.
            $debug = !empty($_GET['debug']) && filter_var($_GET['debug'], FILTER_VALIDATE_BOOLEAN);

            // Execute the command and determine if it was successful.
            $this->wrap->setOption('configuration', $_SERVER['DOCUMENT_ROOT']."/phinx.php");
            $output = call_user_func([$this->wrap, 'getMigrate'], $env, $target);
            $error = $this->wrap->getExitCode() > 0;

            // Finally, display the output of the command.
            if ($error) {
                $args = implode(', ', [var_export($env, true), var_export($target, true)]);
                return JsonResponse::serverError("OUTPUT: $output --- DEBUG: migrate($args)");
            } else {
                $args = implode(', ', [var_export($env, true), var_export($target, true)]);
                JsonResponse::ok("command executed success", [
                    "output" =>explode("\n", str_replace("\r", "", $output)),
                    "debug" => $debug ? "DEBUG: migrate($args)" : ""
                ]);
            }
        } catch (Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    public function rollback(Request $request)
    {
        try {
            // Get the environment and target version parameters.
            $env = sanitize_data($request->query('e'));
            $target = sanitize_data($request->query('t'));

            // Check if debugging is enabled.
            $debug = !empty($_GET['debug']) && filter_var($_GET['debug'], FILTER_VALIDATE_BOOLEAN);

            // Execute the command and determine if it was successful.
            $this->wrap->setOption('configuration', $_SERVER['DOCUMENT_ROOT']."/phinx.php");
            $output = call_user_func([$this->wrap, 'getRollback'], $env, $target);
            $error = $this->wrap->getExitCode() > 0;

            // Finally, display the output of the command.
            if ($error) {
                $args = implode(', ', [var_export($env, true), var_export($target, true)]);
                return JsonResponse::serverError("OUTPUT: $output --- DEBUG: rollback($args)");
            } else {
                $args = implode(', ', [var_export($env, true), var_export($target, true)]);
                JsonResponse::ok("command executed success", [
                    "output" =>explode("\n", str_replace("\r", "", $output)),
                    "debug" => $debug ? "DEBUG: rollback($args)" : ""
                ]);
            }
        } catch (Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    public function seed(Request $request)
    {
        try {
            // Get the environment and target version parameters.
            $env = sanitize_data($request->query('e'));
            $target = sanitize_data($request->query('t'));

            // Check if debugging is enabled.
            $debug = !empty($_GET['debug']) && filter_var($_GET['debug'], FILTER_VALIDATE_BOOLEAN);

            // Execute the command and determine if it was successful.
            $this->wrap->setOption('configuration', $_SERVER['DOCUMENT_ROOT']."/phinx.php");
            $output = call_user_func([$this->wrap, 'getSeed'], $env, $target);
            $error = $this->wrap->getExitCode() > 0;

            // Finally, display the output of the command.
            if ($error) {
                $args = implode(', ', [var_export($env, true), var_export($target, true)]);
                return JsonResponse::serverError("OUTPUT: $output --- DEBUG: seed($args)");
            } else {
                $args = implode(', ', [var_export($env, true), var_export($target, true)]);
                JsonResponse::ok("command executed success", [
                    "output" =>explode("\n", str_replace("\r", "", $output)),
                    "debug" => $debug ? "DEBUG: seed($args)" : ""
                ]);
            }
        } catch (Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }
}