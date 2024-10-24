<?php
namespace App\Http\Controllers\Api;

use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Validator;
use App\Models\Position;
use DateTime;
use Exception;
use Eyika\Atom\Framework\Support\Arr;
use LogicException;
use PDOException;

final class PositionController
{
    public function show(Request $request, Position $position)
    {
        try {
            $user = $request->auth_user;
            $is_admin = Guard::roleIs($user, 'admin');

            if (!$position->find())
                return JsonResponse::notFound("unable to retrieve position");

            if (!$is_admin && $position->user_id !== $user->id) {
                return JsonResponse::unauthorized("you can't view this position");
            }
                
            if ($is_admin === false && $position->user_id != $user->id) {
                return JsonResponse::unauthorized("you can't view this position");
            }

            return JsonResponse::ok("position retrieved success", $position->toArray());
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
            $user = $request->auth_user;
            $is_admin = Guard::roleIs($user, 'admin');
            $query = Arr::except($request->query(), ['page', 'perpage']);
            $is_query = false;

            $page = $request->query('page');
            $per_page = $request->query('perpage');

            if ($is_admin) {
                if (count($query)) {
                    $builder = Position::getBuilder();
                    $first = true;
                    foreach ($query as $key => $value) {
                        if ($first) {
                            $first = false;
                            $builder->where($key, $value);
                        } else $builder->orWhere($key, $value);
                    }
                    $positions = $builder->paginate($page, $per_page);
                } else {
                    $positions = Position::getBuilder()->paginate($page, $per_page);
                }
                if (!$positions)
                    return JsonResponse::ok("no position found in list", []);
                $positions = $positions->toArray($is_query ? 'positions.query' : 'positions.list');
            } else {
                if (count($query)) {
                    $query['user_id'] = $user->id;
                    $builder = Position::getBuilder();
                    $first = true;
                    foreach ($query as $key => $value) {
                        if ($first) {
                            $first = false;
                            $builder->where($key, $value);
                        } else $builder->orWhere($key, $value);
                    }
                    $positions = $builder->get();
                } else {
                    $positions = Position::getBuilder()->where("user_id", $user->id)->get();
                }
                if (!$positions)
                    return JsonResponse::ok("no position found in list", []);
            }

            return JsonResponse::ok("positions retrieved success", $positions);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function list_user(Request $request, string $id)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin') && $user->id !== $id) {
                return JsonResponse::unauthorized("you can't view this user's positions");
            }
            if (!$positions = Position::getBuilder()->findBy("user_id", $id))
                return JsonResponse::ok("no positions found in list", []);

            return JsonResponse::ok("positions retrieved success", $positions);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
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

            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("only users can open a position");
            }
            
            $body = $request->input();
            $actions = Position::BUY.', '.Position::SELL.', '. Position::BUY_LIMIT.', '. Position::BUY_STOP.', '. Position::SELL_LIMIT.', '. Position::SELL_STOP;

            if ($validated = Validator::validate($body, [
                'test_session_id' => 'required|int|exist:test_sessions,id',
                'action' => "required|string|in:$actions",
                'lotsize' => 'required|float',
                'entrypoint' => 'required|float',
                'stoploss' => 'sometimes|float',
                'takeprofit' => 'sometimes|float',
                'pl' => 'sometimes|double',
                'currentprice' => 'required|float',
                'pair' => 'required|string|exist:pairs,name'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (str_contains($body['action'], 'buy')) {
                if (isset($body['takeprofit']) && $body['takeprofit'] <= $body['entrypoint']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['stoploss']) && $body['stoploss'] >= $body['entrypoint']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['takeprofit']) && isset($body['stoploss']) && $body['stoploss'] > $body['takeprofit']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if ($body['action'] === 'buylimit' && $body['entrypoint'] >= $body['currentprice']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                else if ($body['action'] === 'buystop' && $body['entrypoint'] <= $body['currentprice']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
            }
            if (str_contains($body['action'], 'sell')) {
                if (isset($body['takeprofit']) && $body['takeprofit'] >= $body['entrypoint']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['stoploss']) && $body['stoploss'] <= $body['entrypoint']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['takeprofit']) && isset($body['stoploss']) && $body['stoploss'] < $body['takeprofit']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if ($body['action'] === 'selllimit' && $body['entrypoint'] <= $body['currentprice']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                else if ($body['action'] === 'sellstop' && $body['entrypoint'] >= $body['currentprice']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
            }

            $body['user_id'] = $user->id;
            if (in_array($body['action'], [Position::BUY, Position::SELL])) {
                $date = new DateTime();
                $body['entrytime'] = $date->format('Y-m-d H:i:s.u');
            }

            if (!$position = Position::getBuilder()->create( Arr::except($body, ['currentprice']))) {
                return JsonResponse::serverError("unable to create position");
            }

            return JsonResponse::ok("position creation successful", $position);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('position already exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    public function unsetSLorTP(Request $request, Position $position, string $tporsl)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("only users can update position");
            }

            $tporsl = sanitize_data($tporsl);
            $types = 'takeprofit, stoploss';

            if ($validated = Validator::validate(['tporsl' => $tporsl], [
                'tporsl' => "required|string|in:$types"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if ($position->user_id !== $user->id) {
                return JsonResponse::unauthorized("you can't update this position");
            }

            if (!$position->update([$tporsl => null], is_protected: false)) {
                return JsonResponse::notFound("unable to removed position $tporsl");
            }

            return JsonResponse::ok("position $tporsl removed successfully", $position);
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

    public function update(Request $request, Position $position)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("only users can update position");
            }

            if ($position->user_id !== $user->id) {
                return JsonResponse::unauthorized("you can't update this position");
            }
            
            $body = $request->input();
            $actions = Position::BUY.', '.Position::SELL.', '. Position::BUY_LIMIT.', '. Position::BUY_STOP.', '. Position::SELL_LIMIT.', '. Position::SELL_STOP;
            $closetypes = Position::SL.', '. Position::TP.', '. Position::BE.', '. Position::MANUAL_CLOSE.', '.Position::CANCEL;

            if ($validated = Validator::validate($body, [
                'action' => "sometimes|string|in:$actions",
                'entrypoint' => 'sometimes|float',
                'exitpoint' => 'sometimes|float',
                'stoploss' => 'sometimes|float',
                'takeprofit' => 'sometimes|float',
                'lotsize' => 'sometimes|float',
                'pips' => 'sometimes|float',
                'pl' => 'sometimes|double',
                'partials' => 'sometimes|string',
                'exittype' => "sometimes|string|in:$closetypes",
                'pair' => 'sometimes|string|exist:pairs,name',
                'currentprice' => 'required|float'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $entrypoint = $body['entrypoint'] ?? $position->entrypoint;

            if (str_contains($body['action'] ?? '', 'buy')) {
                if (isset($body['takeprofit']) && $body['takeprofit'] <= $entrypoint) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['stoploss']) && $body['stoploss'] >= $entrypoint) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['takeprofit']) && isset($body['stoploss']) && $body['stoploss'] > $body['takeprofit']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if ($body['action'] === 'buylimit' && $entrypoint >= $body['currentprice']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                else if ($body['action'] === 'buystop' && $entrypoint <= $body['currentprice']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
            }
            if (str_contains($body['action'] ?? '', 'sell')) {
                if (isset($body['takeprofit']) && $body['takeprofit'] >= $entrypoint) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['stoploss']) && $body['stoploss'] <= $entrypoint) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['takeprofit']) && isset($body['stoploss']) && $body['stoploss'] < $body['takeprofit']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if ($body['action'] === 'selllimit' && $entrypoint <= $body['currentprice']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                else if ($body['action'] === 'sellstop' && $entrypoint >= $body['currentprice']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
            }

            if (in_array($body['action'] ?? '', [Position::BUY, Position::SELL])) {
                $date = new DateTime();
                $body['entrytime'] = $date->format('Y-m-d H:i:s.u');
            }
            if ($body['exittype'] ?? null && $body['exitpoint'] ?? null) {
                $date = new DateTime();
                $body['exittime'] = $date->format('Y-m-d H:i:s.u');
            }
            // if (isset($body['pips'])) {
            //     $body['pl'] = 
            // }

            /** there should be a trasaction between here, but that's if the commented code is uncommented
             * But the commented code can only be uncommented in a live trading server
             */

            if (!$position->update($request->only([ 'action' , 'entrypoint', 'exitpoint', 'stoploss', 'takeprofit',
                                            'lotsize', 'pips', 'pl', 'partials', 'exittype', 'pair'
                ]))) {
                return JsonResponse::notFound("unable to update position");
            }
            // if (isset($body['exittype']) && isset($body['action']) && in_array($body['action'], [Position::BUY, Position::SELL])) {
            //     if ($session->find($position->test_session_id)) {
            //         $balance = $session->current_bal + $body['pl'];
            //         $session->update(['balance' => $balance]);
            //     }
            // }
            /** and here */

            return JsonResponse::ok("position updated successfully", $position->toArray());
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

    public function delete(Request $request, Position $position)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("only users can create strategy");
            }

            if ($position->user_id !== $user->id) {
                return JsonResponse::unauthorized("you can't update this position");
            }

            if (!$position->delete()) {
                return JsonResponse::notFound("unable to delete position");
            }

            return JsonResponse::ok("position deleted successfully");
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
