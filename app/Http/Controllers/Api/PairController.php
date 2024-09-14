<?php
namespace App\Http\Controllers\Api;

use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Validator;
use App\Models\Pair;
use Exception;
use Eyika\Atom\Framework\Support\Facade\Storage;
use LogicException;
use PDOException;

final class PairController
{
    public function show(Request $request, Pair $pair)
    {
        try {
            return JsonResponse::ok("pair retrieved success", $pair->toArray(select: $pair::pairinfos));
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (LogicException $e) {
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
    
    public function list(Request $request)
    {
        try {
            if (!$pairs = Pair::getBuilder()->all())
                return JsonResponse::ok("no pair found in list", []);

            return JsonResponse::ok("pairs retrieved success", $pairs);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
  
    public function listonlypair(Request $request)
    {
        try {
            if (!$pairs = Pair::getBuilder()->all(select: Pair::pairinfos))
                return JsonResponse::ok("no pair found in list", []);

            return JsonResponse::ok("pairs retrieved success", $pairs);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function query(Request $request, string $id)
    {
        try {
            $params = $request->query();;

            if ($validated = Validator::validate($params, [
                'name' => 'sometimes|string',
                'searchstring' => 'sometimes|string',
                'exchange' => 'sometimes|string',
                'market' => 'sometimes|string',
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }
            if ($id == 0)
                $select = [];
            else if ($id == 1)
                $select = Pair::pairinfos;
            else
                $select = Pair::symbolinfos;

            // if (!$pairs = $this->pair->findByArray(array_keys($params), array_values($params), select: $select))
            $searchstring = '';
            $pairs = [];
            if (isset($params['searchstring'])) {
                $searchstring = $params['searchstring'];
                if (!$pairs1 = Pair::getBuilder()->whereLike('name', "$searchstring")
                            ->orWhereLike('short_name', "$searchstring")
                            ->orWhereLike('ticker', "$searchstring")
                            ->orWhereLike('description', "$searchstring")
                            ->orWhere('status', "$searchstring")
                            // ->orWhere('history_start', $searchstring)
                            // ->orWhere('history_end', $searchstring)
                            ->all(select: $select))
                    return JsonResponse::ok("no piar found in list1", []);
                
                while ($pair = current($pairs1)) {
                    $push = true;
                    if ((isset($params['exchange'])) && $params['exchange'] != '' && $pair['exchange'] != $params['exchange']) {
                        $push = false;
                    }
                    if ((isset($params['market'])) && $params['market'] != '' && $pair['market'] != $params['market']) {
                        $push = false;
                    }
    
                    if ($push)
                        array_push($pairs, $pair);
                    next($pairs1);
                }
            } else if (isset($name)) {
                if (!$pairs = Pair::getBuilder()->where('name', $params['name'])->all())
                    return JsonResponse::ok("no piar found in list2", []);
            }

            if (!count($pairs))
                return JsonResponse::ok("no piar found in list3", []);

            return JsonResponse::ok("pairs retrieved success", $pairs);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem ".$e->getMessage());
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem ".$e->getMessage());
        }
    }

    public function create(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }
            
            $body = $request->input();
            $status = Pair::DISABLED.', '.Pair::ENABLED;
            $markets = Pair::FX.', '.Pair::COMODITY.', '.Pair::CRYPTO.', '.Pair::STOCKS.', '.Pair::INDEX;

            if ($validated = Validator::validate($body, [
                'name' => 'required|string',
                'description' => 'required|string',
                'status' => "sometimes|string|in:$status",
                'dollar_per_pip' => 'required|float',
                'history_start' => 'required|string',
                'history_end' => 'required|string',
                'exchange' => 'sometimes|string',
                'market' => "required|in:$markets",
                'short_name' => 'required|string',
                'ticker' => 'required|string',
                'timezone' => 'required|string|in:'.Pair::TIMEZONES,
                'min_move' => 'required|float',
                'price_precision' => 'required|int',
                'volume_precision' => 'required|int',
                'price_currency' => 'required|string',
                'type' => 'sometimes|string',
                'logo' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $image = base64_decode($body['logo']);
            $path = 'uploads/pairs/';
            
            $target_file = uniqid(). '.jpg';
            Storage::put($path . $target_file, $image);
            $body['logo'] = storage('public')->url($path.$target_file);

            if (!$pair = Pair::getBuilder()->create($body, false)) {
                return JsonResponse::serverError("unable to create pair");
            }

            return JsonResponse::created("pair creation successful", $pair);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('pair already exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    public function update(Request $request, Pair $pair)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }
            
            $body = $request->input();
            $status = Pair::DISABLED.', '.Pair::ENABLED;
            $markets = Pair::FX.', '.Pair::COMODITY.', '.Pair::CRYPTO.', '.Pair::STOCKS.', '.Pair::INDEX;

            if ($validated = Validator::validate($body, [
                'name' => 'sometimes|string',
                'description' => 'sometimes|string',
                'status' => "sometimes|string|in:$status",
                'dollar_per_pip' => 'sometimes|float',
                'history_start' => 'sometimes|string',
                'history_end' => 'sometimes|string',
                'exchange' => 'sometimes|string',
                'market' => "sometimes|in:$markets",
                'short_name' => 'sometimes|string',
                'ticker' => 'sometimes|string',
                'timezone' => 'required|string|in:'.Pair::TIMEZONES,
                'min_move' => 'sometimes|float',
                'price_precision' => 'sometimes|int',
                'volume_precision' => 'sometimes|int',
                'price_currency' => 'sometimes|string',
                'type' => 'sometimes|string',
                'logo' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (isset($body['logo'])) {
                $prev_logo = $pair->logo;
                $logo = base64_decode($body['logo']);
                $path = 'uploads/pairs/';
                
                $target_file = uniqid(). '.jpg';
    
                if (Storage::put($path . $target_file, $logo)) {
                    Storage::delete(str_replace('/storage', '', $prev_logo));
                    $body['logo'] = storage('public')->url($path.$target_file);
                }
            }

            if (!$pair = Pair::getBuilder()->update($body, $pair->id)) {
                return JsonResponse::notFound("unable to update pair");
            }
            /** @var Pair $pair */

            return JsonResponse::ok("pair updated successfully", $pair->toArray(select: $pair::pairinfos));
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    public function delete(Request $request, Pair $pair)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }

            $logo = $pair->logo;

            if (!Pair::getBuilder()->delete($pair->id)) {
                return JsonResponse::notFound("unable to delete pair or pair not found");
            }
            Storage::delete(str_replace('/storage', '', $logo));

            return JsonResponse::ok("pair deleted successfully");
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }
}
