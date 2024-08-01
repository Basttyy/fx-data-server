<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\CheapCountry;
use Exception;
use LogicException;
use PDOException;

final class CheapCountryController
{
    public function show(Request $request, string $id)
    {
        try {
            $user = $request->auth_user;
    
            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't view this data");
            }
            $id = sanitize_data($id);
            if (!$cheap_country = CheapCountry::getBuilder()->find((int)$id))
                return JsonResponse::notFound('unable to retrieve cheap country');

            return JsonResponse::ok('cheap country retrieved success', $cheap_country->toArray());
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a db problem');
        } catch (LogicException $e) {
            return JsonResponse::serverError('we encountered a runtime problem');
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    public function list(Request $request)
    {
        try {
            $user = $request->auth_user;
    
            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't view this data");
            }
            $cheap_countries = CheapCountry::getBuilder()->all();
            if (!$cheap_countries)
                return JsonResponse::ok('no cheap country found in list', []);

            return JsonResponse::ok("cheap country's retrieved success", $cheap_countries);
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a problem');
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    public function create(Request $request, )
    {
        try {
            $user = $request->auth_user;
                
            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't perform this operation");
            }
            
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }
            $body = sanitize_data($request->input());

            $continents = CheapCountry::AFRICA.', '.CheapCountry::ANTARTICA.', '.CheapCountry::ASIA.', '.CheapCountry::EUROPE
                        .', '.CheapCountry::NORTH_AMERICA.', '.CheapCountry::OCEANIA.', '.CheapCountry::SOUTH_AMERICA;

            if ($validated = Validator::validate($body, [
                'name' => 'required|string',
                'continent' => "required|string|in:$continents",
                //add more validation rules here
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$cheap_country = CheapCountry::getBuilder()->create($body)) {
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

    public function update(Request $request, string $id)
    {
        try {
            $user = $request->auth_user;
                
            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't perform this operation");
            }
            
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }

            $id = sanitize_data($id);
            
            $body = sanitize_data($request->input());
            $continents = CheapCountry::AFRICA.', '.CheapCountry::ANTARTICA.', '.CheapCountry::ASIA.', '.CheapCountry::EUROPE
                        .', '.CheapCountry::NORTH_AMERICA.', '.CheapCountry::OCEANIA.', '.CheapCountry::SOUTH_AMERICA;

            if ($validated = Validator::validate($body, [
                'name' => 'sometimes|string',
                'continent' => "sometimes|string|in:$continents",
                //add more validation rules here
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$cheap_country = CheapCountry::getBuilder()->update($body, (int)$id)) {
                return JsonResponse::notFound('unable to update cheap country not found');
            }

            return JsonResponse::ok('cheap country updated successfull', $cheap_country->toArray());
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    public function delete(Request $request, int $id)
    {
        try {
            $id = sanitize_data($id);

            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't delete a cheap country");
            }

            if (!CheapCountry::getBuilder()->delete((int)$id)) {
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
