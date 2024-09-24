<?php
namespace App\Http\Controllers\Api;

use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use App\Models\Visit;
use Exception;
use LogicException;
use PDOException;

final class VisitController
{
    public function show(Request $request, string $id)
    {
        $id = $id;
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }

            if (!$visit = Visit::getBuilder()->find((int)$id))
                return JsonResponse::notFound("unable to retrieve visit");

            return JsonResponse::ok("visit retrieved success", $visit->toArray());
        } catch (PDOException $e) {
            consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
            return JsonResponse::serverError("we encountered a db problem");
        } catch (LogicException $e) {
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function count(Request $request)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }

            $query = $request->query();

            $builder = Visit::getBuilder();
            if (count($query)) {
                foreach ($query as $k => $v) {
                    $builder->where($k, value: $v);
                }
            }
            $uniqueVisits = $builder->distinct('unique_visitor_id')->count();
            $totalVisits = $builder->count();
            if (!$totalVisits)
                return JsonResponse::ok("no visit found in list", 0);

            return JsonResponse::ok("visits count retrieved success", [
                'total_visits' => $totalVisits,
                'unique_visits' => $uniqueVisits,
            ]);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function list(Request $request)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }
            $page = $request->query('page');
            $per_page = $request->query('perpage');

            if (!$visits = Visit::getBuilder()->paginate($page, $per_page, select: Visit::analytic))
                return JsonResponse::ok("no visit found in list", []);

            return JsonResponse::ok("visits retrieved success", $visits->toArray('admin.visit-logs.list'));
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
}
