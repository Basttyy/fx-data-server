<?php
namespace App\Http\Controllers\Api;

use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Support\Auth\Jwt\JwtAuthenticator;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Validator;
use App\Http\Services\Geolocation\IP2Location\IpLocService;
use App\Models\Plan;
use App\Http\Traits\PaymentGateway;
use App\Models\CheapCountry;
use Exception;
use Eyika\Atom\Framework\Support\Arr;
use Eyika\Atom\Framework\Support\Database\DB;
use LogicException;
use PDOException;

final class PlanController
{
    use PaymentGateway;

    public function show(Request $request, Plan $plan)
    {
        try {
            return JsonResponse::ok("plan retrieved success", $plan->toArray());
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a db problem");
        } catch (LogicException $e) {
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function list(Request $request, string $standard = null)
    {
        try {
            $builder = Plan::getBuilder();
            if (count($request->query())) {

            }
            if (is_null($standard)) {
                $plans = $builder->all();
                return JsonResponse::ok("plans retrieved success", $plans);
            }

            $user = JwtAuthenticator::validate();
            if ($standard == 'low') {
                $plans = $builder->where('for_cheap_regions', 1)->get();
            } else if ($standard == 'high') {
                $plans = $builder->where('for_cheap_regions', 0)->get();
            } else if ($user && Guard::roleIs($user, 'admin')) {
                $plans = $builder->all();
            } else if ($user && $user->country ?? null) {
                $ischeapcountry = CheapCountry::getBuilder()->where('name', $user->country)->count();

                $plans = $ischeapcountry ? $builder->where('for_cheap_regions', 1)->get() : $builder->where('for_cheap_regions', 0)->get();
            } else {
                $ipaddress = getenv('HTTP_X_FORWARDED_FOR') ? getenv('HTTP_X_FORWARDED_FOR') : getenv('REMOTE_ADDR');

                // $ws = new \IP2Location\IpLocService(env('IP2LOC_API_KEY'), 'WS25', false);
                $ws = new IpLocService(env('IPLOC_API_KEY'));           // Not using SSL for faster response time
                $records = $ws->lookup($ipaddress, language: 'en');

                $ischeapcountry = $records != false ? CheapCountry::getBuilder()->where('name', $records['country_name'])->count() : false;
                $plans = $ischeapcountry ? $builder->where('for_cheap_regions', 1)->get() : $builder->where('for_cheap_regions', 0)->get();
            }
            if (!$plans)
                return JsonResponse::ok('no plan found in list', []);

            $data = ['plans' => $plans, 'standard' => $standard];
            if (isset($ischeapcountry))
                $data['standard'] = $ischeapcountry ? 'low' : 'high';

            return JsonResponse::ok("plans retrieved success", $data);
        } catch (PDOException $e) {
            logger()->info($e->getMessage(), $e->getTrace());
            return JsonResponse::serverError("we encountered a problem ");
        } catch (Exception $e) {
            logger()->info($e->getMessage(), $e->getTrace());
            return JsonResponse::serverError("we encountered a problem ");
        }
    }

    public function create(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't create a plan");
            }
            
            $body = $request->input();
            $status = Plan::DISABLED.', '.Plan::ENABLED;
            $intervals = implode(', ', Plan::INTERVALS);
            $currencies = implode(', ', $this->getSupportedCurrencies());

            if ($validated = Validator::validate($body, [
                'name' => 'required|string',
                'description' => 'required|string',
                'price' => 'required|numeric',
                'status' => "sometimes|string|in:$status",
                'features' => 'required|string',
                'for_cheap_regions' => 'required|numeric',
                'currency' => "required|string|in:$currencies",
                'duration_interval' => "required|string|in:$intervals"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $plan = new Plan;
            DB::beginTransaction();

            $body['price'] = (float)$body['price'];
            $body['for_cheap_regions'] = (int)$body['for_cheap_regions'];

            if (!$plan->create($body)) {
                DB::rollback();
                return JsonResponse::serverError("unable to create plan");
            }

            $response = $this->createPaymentPlan($body['price'], $body['currency'], $body['name'], $this->convertInterval($body['duration_interval']), $body['description']);

            if ($response->status !== 'success') {
                DB::rollback();
                return JsonResponse::serverError("unable to create plan");
            }

            if (!$plan->update([
                'plan_token' => $this->providerIs('paystack') ? $response->data->plan_code : $response->data->plan_token,
                'third_party_id' => $response->data->id
            ])) {
                DB::rollback();
                return JsonResponse::serverError("unable to create plan");
            }

            DB::commit();

            ///TODO:: send campaign notification to users/subscribers about the new plan

            return JsonResponse::created("plan creation successful", $plan);
        } catch (PDOException $e) {
            DB::rollback();
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('plan already exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            DB::rollback();
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "";
            return JsonResponse::serverError("we encountered a problem ".$message);
        }
    }

    public function update(Request $request, Plan $plan)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't update a plan");
            }
            
            $body = $request->input();

            $status = Plan::DISABLED.', '.Plan::ENABLED;
            $intervals = implode(', ', Plan::INTERVALS);
            $currencies = implode(', ', $this->getSupportedCurrencies());

            if ($validated = Validator::validate($body, [
                'name' => 'sometimes|string',
                'description' => 'sometimes|string',
                // 'price' => 'sometimes|numeric',
                // 'currency' => "sometimes|string|in:$currencies",
                'status' => "sometimes|string|in:$status",
                'for_cheap_regions' => 'sometimes|numeric',
                'features' => 'sometimes|string',
                // 'duration_interval' => "sometimes|string|in:$intervals"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (isset($body['price']))
                $body['price'] = (float)$body['price'];

            if (isset($body['for_cheap_regions']))
                $body['for_cheap_regions'] = (int)$body['for_cheap_regions'];

            DB::beginTransaction();
            if (!$plan = $plan->update(Arr::only($body, ['name', 'status', 'for_cheap_regions', 'features', 'description']))) {
                DB::rollback();
                return JsonResponse::notFound("unable to update plan not found");
            }

            $response = $this->updatePaymentPlan($plan->third_party_id, $plan->name ?? null, $plan->price, $plan->currency, $this->convertInterval($plan->duration_interval), $plan->description, $plan->status == 'enabled' ? 'active' : 'inactive');

            DB::commit();
            return JsonResponse::ok("plan updated successfull", $plan->toArray());
        } catch (PDOException $e) {
            DB::rollback();
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = "we encountered a problem";

            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            DB::rollback();
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    public function delete(Request $request, Plan $plan)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't delete a plan");
            }

            DB::beginTransaction();
            // echo "got to pass login";
            if (!$plan->delete()) {
                DB::rollback();
                return JsonResponse::serverError("unable to delete plan");
            }

            $this->cancelPaymentPlan($plan->third_party_id);

            DB::commit();
            return JsonResponse::ok("plan deleted successfull");
        } catch (PDOException $e) {
            DB::rollback();
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = "we encountered a problem";

            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            DB::rollback();
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    private function convertInterval (string $interval = null) {
        if ($interval === null)
            return null;

        if ($interval === 'day') {
            return 'daily';
        }
        return $interval.'ly';
    }
}
