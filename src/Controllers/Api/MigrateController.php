<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\libs\JsonResponse;
use Exception;

final class MigrateController
{
    public function __construct()
    {
        
    }

    public function __invoke()
    {
        try {
            if (!$this->execEnabled()) {
                return JsonResponse::badRequest("executable functions are not enabled");
            }
            $projectRoot = $_SERVER["DOCUMENT_ROOT"];

            // Change the current working directory to the project root directory
            chdir($projectRoot);
            $phpIniPath = php_ini_loaded_file();
            $phpPath = dirname($phpIniPath) . DIRECTORY_SEPARATOR . 'php';
    
            $resp = exec("/bin/bash ./vendor/bin/phinx migrate", $output, $result_code);
    
            return JsonResponse::ok("command executed with following result", [
                "output" => $output,
                "result_code" => $result_code,
                "resp" => $resp,
                "path" => $phpPath
            ]);
        } catch (Exception $e) {
            return JsonResponse::serverError($e->getMessage());
        }
    }

    function execEnabled() 
    {
        $arrDisabled = explode(',', ini_get('disable_functions'));
        return (!in_array('exec', $arrDisabled));
    }
}