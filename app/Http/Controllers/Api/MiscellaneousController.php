<?php
namespace App\Http\Controllers\Api;

use App\Console\Jobs\SendContactUs;
use Eyika\Atom\Framework\Support\Auth\Jwt\JwtAuthenticator;
use Eyika\Atom\Framework\Support\Arr;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Validator;
use App\Models\Enquiry;
use App\Models\Pair;
use Exception;
use PDOException;

final class MiscellaneousController
{
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

    public function searchTicker(Request $request, string $query)
    {
        try {
            $user = $request->auth_user;
            $query = sanitize_data($query);
            foreach (Pair::symbolinfos as $info) {
                $values[] = $query;
            }
            
            if (!$tickers = Pair::getBuilder()->findByArray(Pair::symbolinfos, $values, 'OR', select: Pair::symbolinfos)) {
                return JsonResponse::ok("no ticker found in list", []);
            }
            return JsonResponse::ok("tickers searched success", $tickers);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function contact_us(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            
            $body = $request->input();

            if ($validated = Validator::validate($body, [
                'fullname' => 'required|string',
                'email' => 'required|string',
                'message' => 'required|string',
                'subject' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $body['subject'] = $body['subject'] ?? 'I have an enquiry';
            $contact = new SendContactUs($body);

            $contact->init()->delay(5)->run();

            Enquiry::getBuilder()->create(Arr::only($body, ['fullname', 'email', 'message', 'subject']));

            return JsonResponse::ok("enquiry submitted successfully");
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    public function fetchLanding(Request $request)
    {
        if (! $data = file_get_contents(public_path('landing.json'))) {
            return JsonResponse::notFound('landing page data not found');
        }
        header("Content-type: application/json");
        http_response_code(200);
        echo $data;
        return true;
    }

    public function updateLanding(Request $request)
    {
        if (!JwtAuthenticator::validate()) {
            return JsonResponse::unauthorized();
        }

        // Check if the request has a body
        if ( !$request->hasBody()) {
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
