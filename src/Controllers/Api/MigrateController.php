<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\libs\JsonResponse;
use Exception;
use Phinx\Wrapper\TextWrapper;

final class MigrateController
{
    private $wrap;
    
    public function __construct()
    {
        $app = require __DIR__ . '/../../../vendor/robmorgan/phinx/app/phinx.php';
        $this->wrap = new TextWrapper($app);
    }

    public function __invoke()
    {
        try {
            // Mapping of route names to commands.
            $routes = [
                'status' => 'getStatus',
                'migrate' => 'getMigrate',
                'rollback' => 'getRollback',
                'seed' => 'getSeed',
            ];
            $command = sanitize_data($_GET['command']) ?? null;

            if (!$command) {
                // $command = 'status';
                return JsonResponse::notFound("requested url is not found");
            }

            // Verify that the command exists, or list available commands.
            if (!isset($routes[$command])) {
                $commands = implode(', ', array_keys($routes));
                return JsonResponse::badRequest("command not found! valid commands are", array_keys($routes));
            }

            // Get the environment and target version parameters.
            $env = sanitize_data($_GET['e']) ?? null;
            $target = sanitize_data($_GET['t']) ?? null;

            // Check if debugging is enabled.
            $debug = !empty($_GET['debug']) && filter_var($_GET['debug'], FILTER_VALIDATE_BOOLEAN);

            // Execute the command and determine if it was successful.
            $this->wrap->setOption('configuration', $_SERVER['DOCUMENT_ROOT']."/phinx.php");
            $output = call_user_func([$this->wrap, $routes[$command]], $env, $target);
            $error = $this->wrap->getExitCode() > 0;

            // Finally, display the output of the command.
            if ($error) {
                $args = implode(', ', [var_export($env, true), var_export($target, true)]);
                return JsonResponse::serverError("OUTPUT: $output --- DEBUG: $command($args)");
            } else {
                $args = implode(', ', [var_export($env, true), var_export($target, true)]);
                JsonResponse::ok("command executed success", [
                    "output" =>explode("\n", str_replace("\r", "", $output)),
                    "debug" => $debug ? "DEBUG: $command($args)" : ""
                ]);
            }
        } catch (Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }
}