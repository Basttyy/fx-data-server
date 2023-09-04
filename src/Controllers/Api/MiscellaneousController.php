<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\Console\Jobs\SendContactUs;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\Pair;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class MiscellaneousController
{
    private $method;
    private $user;
    private $authenticator;
    private $pair;

    // Define the landingType interface as an array to match the expected structure
    const landingType = [
        'heading' => [
            'background_image' => '',
            'header_text' => '',
            'header_description' => '',
            'keynotes' => [
                [
                    'header' => '',
                    'bodies' => [],
                ]
            ]
        ],
        'features' => [
            [
                'header' => '',
                'bodies' => [],
                'key_list' => [],
                'link' => '',
                'side_card' => [
                    'background_image' => '',
                    'header' => '',
                    'bodies' => []
                ]
            ]
        ],
        'team' => [
            'header' => '',
            'description' => '',
            'members' => [
                [
                    'img' => '',
                    'name' => '',
                    'role' => '',
                    'socials' => []
                ]
            ]
        ],
        'pricing' => [
            'header' => '',
            'description' => ''
        ],
        'contact' => [
            'header' => '',
            'description' => ''
        ],
        'footer' => [
            'header' => '',
            'description' => '',
            'socials' => [],
            'links' => [
                'header' => '',
                'links' => [
                    [
                        'title' => '',
                        'url' => ''
                    ]
                ]
            ],
            'other_links' => [
                'header' => '',
                'links' => [
                    [
                        'title' => '',
                        'url' => ''
                    ]
                ]
            ]
        ]
    ];

    public function __construct($method = "contact_us")
    {
        $this->method = $method;
        $this->user = new User();
        $this->pair = new Pair();
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role();
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
    }

    public function __invoke(string $query = null)
    {
        switch ($this->method) {
            case 'search_ticker':
                $resp = $this->searchTicker($query);
                break;
            case 'contact_us':
                $resp = $this->contact_us();
                break;
            case 'fetch_landing':
                $resp = $this->fetchLanding();
                break;
            case 'update_landing':
                $resp = $this->updateLanding();
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        $resp;
    }

    private function searchTicker(string $query)
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            $query = sanitize_data($query);
            foreach ($this->pair->symbolinfos as $info) {
                $values[] = $query;
            }
            
            if (!$tickers = $this->pair->findByArray($this->pair->symbolinfos, $values, 'OR', select: $this->pair->symbolinfos)) {
                return JsonResponse::ok("no ticker found in list", []);
            }
            return JsonResponse::ok("tickers searched success", $tickers);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function contact_us()
    {
        try {
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'fullname' => 'required|string',
                'email' => 'required|string',
                'message' => 'required|string',
                'subject' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!isset($body['enquiry']))
                $body['subject'] = 'I have an enquiry';
            $contact = new SendContactUs($body, ['fullname', 'email', 'subject', 'message']);

            $contact->init()->delay(5)->run();

            return JsonResponse::ok("enquiry submitted successfully");
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

    private function fetchLanding()
    {
        if (! $data = file_get_contents(storage_path().'files/landing.json')) {
            return JsonResponse::notFound('landing page data not found');
        }
        header("Content-type: application/json");
        http_response_code(200);
        echo $data;
        return true;
    }

    private function updateLanding()
    {
        if (!$this->authenticator->validate()) {
            return JsonResponse::unauthorized();
        }

        // Check if the request has a body
        if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
            //return "body is required" response;
            return JsonResponse::badRequest("bad request", "body is required");
        }
        
        $inputJSON = file_get_contents('php://input');

        $body = sanitize_data(json_decode(base64_decode($inputJSON, true), true));

        if (!validate_data_structure($body, $this::landingType)) {
            return JsonResponse::badRequest("invalid json data");
        }
        $path = storage_path().'landing.json';

        if (file_exists($path))
            unlink($path);
        if (!$file = fopen($path, 'w'))
            return JsonResponse::serverError('something happened, please try again');

        if (!fwrite($file, json_encode($body)))
            return JsonResponse::serverError('something happened, please try again');

        fclose($file);
        return JsonResponse::ok('lading page data updated successfully');
    }
}
