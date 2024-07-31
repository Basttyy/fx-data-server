<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Basttyy\FxDataServer\Models\Visit;
use Exception;
use LogicException;
use PDOException;

final class VisitController
{
    private $method;
    private $user;
    private $authenticator;
    private $visit;

    public function __construct($method = "show")
    {
        $this->method = $method;
        $this->user = new User();
        $this->visit = new Visit();
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
            case 'count':
                $resp = $this->count();
                break;
            case 'list':
                $resp = $this->list();
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        $resp;
    }

    private function show(string $id)
    {
        $id = sanitize_data($id);
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }

            if (!$this->visit->find((int)$id))
                return JsonResponse::notFound("unable to retrieve visit");

            return JsonResponse::ok("visit retrieved success", $this->visit->toArray());
        } catch (PDOException $e) {
            consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
            return JsonResponse::serverError("we encountered a db problem");
        } catch (LogicException $e) {
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function count()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }

            $query = isset($_GET) ? sanitize_data($_GET): [];

            if (count($query)) {
                foreach ($query as $k => $v) {
                    $this->visit->where($k, value: $v);
                }
            }
            $uniqueVisits = $this->visit->distinct('unique_visitor_id')->count();
            $totalVisits = $this->visit->count();
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

    private function list()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }

            if (!$visits = $this->visit->all(select: $this->visit->analytic))
                return JsonResponse::ok("no visit found in list", []);

            return JsonResponse::ok("visits retrieved success", $visits);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
}
