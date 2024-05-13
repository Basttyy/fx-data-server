<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\CheapCountry;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class CheapCountryController
{
    private $method;
    private $user;
    private $authenticator;
    private $cheap_country;

    public function __construct($method = 'show')
    {
        $this->method = $method;
        $this->user = new User();
        $this->cheap_country = new CheapCountry();
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role();
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
    }

    public function __invoke(string $id = null)
    {
        switch ($this->method) {
            case 'show':
                $resp = $this->show($id);
                break;
            case 'list':
                $resp = $this->list();
                break;
            case 'create':
                $resp = $this->create();
                break;
            case 'update':
                $resp = $this->update($id);
                break;
            case 'delete':
                $resp = $this->delete($id);
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        $resp;
    }

    private function show(string $id)
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
    
            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't view this data");
            }
            $id = sanitize_data($id);
            if (!$this->cheap_country->find((int)$id))
                return JsonResponse::notFound('unable to retrieve cheap country');

            return JsonResponse::ok('cheap country retrieved success', $this->cheap_country->toArray());
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a db problem');
        } catch (LogicException $e) {
            return JsonResponse::serverError('we encountered a runtime problem');
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function list()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
    
            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't view this data");
            }
            $cheap_countrys = $this->cheap_country->all();
            if (!$cheap_countrys)
                return JsonResponse::ok('no cheap country found in list', []);

            return JsonResponse::ok("cheap country's retrieved success", $cheap_countrys);
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a problem');
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function create()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
                
            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't perform this operation");
            }
            
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            $status = 'some, values';

            if ($validated = Validator::validate($body, [
                'foo' => 'required|string',
                'bar' => 'sometimes|numeric',
                'baz' => "sometimes|string|in:$status",
                //add more validation rules here
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$cheap_country = $this->cheap_country->create($body)) {
                return JsonResponse::serverError('unable to create cheap country');
            }

            return JsonResponse::created('cheap country creation successful', $cheap_country);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('cheap country already exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function update(string $id)
    {
        try {
            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
                
            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't perform this operation");
            }
            
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }

            $id = sanitize_data($id);
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            $status = 'some, values';

            if ($validated = Validator::validate($body, [
                'foo' => 'sometimes|boolean',
                'bar' => 'sometimes|numeric',
                'baz' => "sometimes|string|in:$status",
                //add more validation rules here
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$this->cheap_country->update($body, (int)$id)) {
                return JsonResponse::notFound('unable to update cheap country not found');
            }

            return JsonResponse::ok('cheap country updated successfull', $this->cheap_country->toArray());
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function delete(int $id)
    {
        try {
            $id = sanitize_data($id);

            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            //Uncomment this for role authorization
            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't delete a cheap country");
            }

            if (!$this->cheap_country->delete((int)$id)) {
                return JsonResponse::notFound('unable to delete cheap country or cheap country not found');
            }

            return JsonResponse::ok('cheap country deleted successfull');
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }
}
