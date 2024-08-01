<?php
require_once __DIR__."/../../../vendor/autoload.php";
require_once __DIR__."/../../libs/helpers.php";

use Basttyy\FxDataServer\libs\Str;

$command = $argv[1] ? $argv[1] : 'random-quote';
$classname = $argv[2] ? $argv[2] : '';

consoleLog('info', $command. '     '. $classname);

$exit = 0;

switch ($command) {
    case 'random-qoute':
        consoleLog('info', 'this is a basic random quote');
        // $exit = 1;
        break;
    case 'create-model':
        createModel($classname);
        break;
    case 'create-controller':
        createController($classname, '');
        break;
    case 'create-api-controller':
        createController($classname);
        break;
    default:
        consoleLog('error', 'invalid command');
}
exit($exit);

function createModel (string $name) {
    $name = Str::pascal($name);
    $name_lower = Str::plural(Str::snake($name));
    $model_folder = strtolower(PHP_OS_FAMILY) === "windows" ? __DIR__."\\..\\..\\Models\\" : __DIR__."/../../Models/";
$model_template = "<?php

namespace Basttyy\FxDataServer\Models;

use Basttyy\FxDataServer\Models\Model;

final class {$name} extends Model
{
    protected \$softdeletes = true;

    protected \$table = '$name_lower';

    protected \$primaryKey = 'id';

    //object properties
    public \$id;
    public \$created_at;
    public \$updated_at;
    public \$deleted_at;
    //add more $name's properties here

    /**
     * Indicates what database attributes of the model can be filled at once
     * 
     * @var array
     */
    protected const fillable = [
        'id', 'created_at', 'updated_at', 'deleted_at',
        //add more fillable columns here
    ];

    /**
     * Indicates what database attributes of the model can be exposed outside the application
     * 
     * @var array
     */
    protected const guarded = [
        'deleted_at', 'created_at', 'updated_at'
        //add more guarded columns here
    ];

    /**
     * Create a new $name instance.
     *
     * @return void
     */
    public function __construct(\$values = [])
    {
        parent::__construct(\$values, \$this);
    }
}
";

    if (file_exists($model_folder.$name.'.php')) {
        consoleLog("error", "model with name $name already exists");
        exit (0);
    }
    file_put_contents($model_folder.$name.'.php', $model_template);
    consoleLog('info', "model with name $name created successfully");
}

function createController (string $name, $type = 'api') {
    $name = Str::pascal($name);
    $controller_str = Str::snake($name);
    $controller_str_spc = str_replace('_', ' ', $controller_str);
    $controller_str_plural = Str::plural($controller_str);
    $controller_str_spc_plural = Str::plural($controller_str_spc);
    if ($type === 'api') {
        $api = '\Api';
        $controller_folder = strtolower(PHP_OS_FAMILY) === "windows" ? __DIR__."\\..\\..\\Controllers\\Api\\" : __DIR__."/../../Controllers/Api/";
    } else {
        $api = '';
        $controller_folder = strtolower(PHP_OS_FAMILY) === "windows" ? __DIR__."\\..\\..\\Controllers\\" : __DIR__."/../../Controllers/";
    }

$controller_template = "<?php
namespace Basttyy\FxDataServer\Controllers$api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\Models\\$name;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class {$name}Controller
{
    private function show(Request \$request, string \$id)
    {
        \$id = sanitize_data(\$id);
        try {
            if (!\$this->{$controller_str}->find((int)\$id))
                return JsonResponse::notFound('unable to retrieve $controller_str_spc');

            return JsonResponse::ok('$controller_str_spc retrieved success', \$this->{$controller_str}->toArray());
        } catch (PDOException \$e) {
            return JsonResponse::serverError('we encountered a db problem');
        } catch (LogicException \$e) {
            return JsonResponse::serverError('we encountered a runtime problem');
        } catch (Exception \$e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function list(Request \$request)
    {
        try {
            \$$controller_str_plural = \$this->{$controller_str}->all();
            if (!\$$controller_str_plural)
                return JsonResponse::ok('no $controller_str_spc_plural found in list', []);

            return JsonResponse::ok(\"$controller_str_spc_plural retrieved success\", \$$controller_str_plural);
        } catch (PDOException \$e) {
            return JsonResponse::serverError('we encountered a problem');
        } catch (Exception \$e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function create(Request \$request)
    {
        try {
            if ( !\$request->hasBody()) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }

            \$body = sanitize_data(\$request->input());
            \$status = 'some, values';

            if (\$validated = Validator::validate(\$body, [
                'foo' => 'required|string',
                'bar' => 'sometimes|numeric',
                'baz' => \"sometimes|string|in:\$status\",
                //add more validation rules here
            ])) {
                return JsonResponse::badRequest('errors in request', \$validated);
            }

            if (!\$$controller_str = \$this->{$controller_str}->create(\$body)) {
                return JsonResponse::serverError('unable to create $controller_str_spc');
            }

            return JsonResponse::created('$controller_str_spc creation successful', \$$controller_str);
        } catch (PDOException \$e) {
            if (str_contains(\$e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('$controller_str_spc already exist');
            else \$message = 'we encountered a problem';
            
            return JsonResponse::serverError(\$message);
        } catch (Exception \$e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function update(Request \$request, string \$id)
    {
        try {
            if ( !\$request->hasBody()) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }

            \$id = sanitize_data(\$id);

            \$body = sanitize_data(\$request->input());

            \$status = 'some, values';

            if (\$validated = Validator::validate(\$body, [
                'foo' => 'sometimes|boolean',
                'bar' => 'sometimes|numeric',
                'baz' => \"sometimes|string|in:\$status\",
                //add more validation rules here
            ])) {
                return JsonResponse::badRequest('errors in request', \$validated);
            }

            if (!\$this->{$controller_str}->update(\$body, (int)\$id)) {
                return JsonResponse::notFound('unable to update $controller_str_spc not found');
            }

            return JsonResponse::ok('$controller_str_spc updated successfull', \$this->{$controller_str}->toArray());
        } catch (PDOException \$e) {
            if (str_contains(\$e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else \$message = 'we encountered a problem';
            
            return JsonResponse::serverError(\$message);
        } catch (Exception \$e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function delete(Request \$request, int \$id)
    {
        try {
            \$id = sanitize_data(\$id);

            \$user = \$request->auth_user;

            // Uncomment this for role authorization
            // if (!Guard::roleIs(\$user, 'admin')) {
            //     return JsonResponse::unauthorized(\"you can't delete a $controller_str_spc\");
            // }

            if (!\$this->{$controller_str}->delete((int)\$id)) {
                return JsonResponse::notFound('unable to delete $controller_str_spc or $controller_str_spc not found');
            }

            return JsonResponse::ok('$controller_str_spc deleted successfull');
        } catch (PDOException \$e) {
            if (str_contains(\$e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else \$message = 'we encountered a problem';
            
            return JsonResponse::serverError(\$message);
        } catch (Exception \$e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }
}
";

    if (file_exists($controller_folder.$name.'Controller.php')) {
        consoleLog("error", "controller with name $name already exists");
        exit (0);
    }
    file_put_contents($controller_folder.$name.'Controller.php', $controller_template);
    consoleLog('info', "controller with name $name created successfully");
}