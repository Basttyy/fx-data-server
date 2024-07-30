<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\Geolocation\IP2Location\WebService;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Plan;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Basttyy\FxDataServer\libs\Traits\Flutterwave;
use Basttyy\FxDataServer\Models\CheapCountry;
use Exception;
use GuzzleHttp\Client;
use LogicException;
use PDOException;

final class PlanController
{
    use Flutterwave;
    private $method;
    private $user;
    private $authenticator;
    private $plan;

    public function __construct($method = "show")
    {
        $this->method = $method;
        $this->user = new User();
        $this->plan = new Plan();
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
                $resp = $this->list($id);
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
        $id = sanitize_data($id);
        try {
            if (!$this->plan->find((int)$id))
                return JsonResponse::notFound("unable to retrieve plan");

            return JsonResponse::ok("plan retrieved success", $this->plan->toArray());
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a db problem");
        } catch (LogicException $e) {
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function list(string $standard = null)
    {
        try {
            if (is_null($standard)) {
                $plans = $this->plan->all();
                return JsonResponse::ok("plans retrieved success", $plans);
            }

            if ($standard == 'low') {
                $plans = $this->plan->where('for_cheap_regions', 1)->get();
            } else if ($standard == 'high') {
                $plans = $this->plan->where('for_cheap_regions', 0)->get();
            } else if ($this->authenticator->validate()) {
                if ($this->authenticator->verifyRole($this->user, 'admin')) {
                    $plans = $this->plan->all();
                } else {
                    $ischeapcountry = CheapCountry::getBuilder()->where('name', $this->user->country)->count();

                    $plans = $ischeapcountry ? $this->plan->where('for_cheap_regions', 1)->get() : $this->plan->where('for_cheap_regions', 0)->get();
                }
            } else {
                $ipaddress = getenv('HTTP_X_FORWARDED_FOR') ? getenv('HTTP_X_FORWARDED_FOR') : getenv('REMOTE_ADDR');

                // $ws = new \IP2Location\WebService(env('IP2LOC_API_KEY'), 'WS25', false);
                $ws = new WebService(env('IPLOC_API_KEY'));           // Not using SSL for faster response time
                $records = $ws->lookup($ipaddress, language: 'en');

                $ischeapcountry = $records != false ? CheapCountry::getBuilder()->where('name', $records['country_name'])->count() : false;
                $plans = $ischeapcountry ? $this->plan->where('for_cheap_regions', 1)->get() : $this->plan->where('for_cheap_regions', 0)->get();
            }
            if (!$plans)
                return JsonResponse::ok('no plan found in list', []);

            $data = ['plans' => $plans, 'standard' => $standard];
            if (isset($ischeapcountry))
                $data['standard'] = $ischeapcountry ? 'low' : 'high';

            return JsonResponse::ok("plans retrieved success", $data);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem ");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem ");
        }
    }

    private function create()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't create a plan");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            $status = Plan::DISABLED.', '.Plan::ENABLED;
            $intervals = implode(', ', Plan::INTERVALS);

            if ($validated = Validator::validate($body, [
                'name' => 'required|string',
                'description' => 'required|string',
                'price' => 'required|numeric',
                'status' => "sometimes|string|in:$status",
                'features' => 'required|string',
                'for_cheap_regions' => 'required|numeric',
                'currency' => 'required|string',
                'duration_interval' => "required|string|in:$intervals"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $this->plan->beginTransaction();

            $body['price'] = (float)$body['price'];
            $body['for_cheap_regions'] = (int)$body['for_cheap_regions'];

            if (!$plan = $this->plan->create(Arr::except($body, 'currency'))) {
                return JsonResponse::serverError("unable to create plan");
            }
            $this->plan->fill($plan);

            // \Flutterwave\Flutterwave::bootstrap();

            // $paymentPlansService = new \Flutterwave\Service\PaymentPlan();

            $convertInterval = function() use ($body) {
                if ($body['duration_interval'] === 'day') {
                    return 'daily';
                }
                return $body['duration_interval'].'ly';
            };

            $response = $this->createPaymentPlan($body['price'], $body['currency'], $body['name'], $convertInterval());

            // $payload = new \Flutterwave\Payload();

            // $payload->set('amount', 5000);
            // $payload->set('name', '');
            // $payload->set('interval', $convertInterval());

            // $response = $paymentPlansService->create($payload);

            if ($response->status !== 'success') {
                $this->plan->rollback();
                return JsonResponse::serverError("unable to create plan");
            }

            if (!$this->plan->update([
                'plan_token' => $response->data->plan_token,
                'third_party_id' => $response->data->id
            ])) {
                $this->plan->rollback();
                return JsonResponse::serverError("unable to create plan");
            }

            $this->plan->commit();

            ///TODO:: send campaign notification to users/subscribers about the new plan

            return JsonResponse::created("plan creation successful", $plan);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('plan already exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "";
            return JsonResponse::serverError("we encountered a problem ".$message);
        }
    }

    private function update(string $id)
    {
        try {
            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            $id = sanitize_data($id);

            if (!$this->authenticator->verifyRole($user, 'admin')) {
                return JsonResponse::unauthorized("you can't update a plan");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            $status = Plan::DISABLED.', '.Plan::ENABLED;
            $intervals = implode(', ', Plan::INTERVALS);

            if ($validated = Validator::validate($body, [
                'name' => 'sometimes|string',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric',
                'status' => "sometimes|string|in:$status",
                'for_cheap_regions' => 'sometimes|numeric',
                'features' => 'sometimes|string',
                'duration_interval' => "sometimes|string|in:$intervals"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $body['price'] = (float)$body['price'];
            $body['for_cheap_regions'] = (int)$body['for_cheap_regions'];

            if (!$this->plan->update($body, (int)$id)) {
                return JsonResponse::notFound("unable to update plan not found");
            }

            return JsonResponse::ok("plan updated successfull", $this->plan->toArray());
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

    private function delete(int $id)
    {
        try {
            $id = sanitize_data($id);

            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't delete a plan");
            }

            // echo "got to pass login";
            if (!$this->plan->delete((int)$id)) {
                return JsonResponse::notFound("unable to delete plan or plan not found");
            }

            return JsonResponse::ok("plan deleted successfull");
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
