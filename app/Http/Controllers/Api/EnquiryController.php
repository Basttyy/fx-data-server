<?php
namespace App\Http\Controllers\Api;

use Eyika\Atom\Framework\Support\Auth\Jwt\JwtAuthenticator;
use Eyika\Atom\Framework\Support\Auth\Jwt\JwtEncoder;
use Eyika\Atom\Framework\Http\JsonResponse;

use App\Models\Enquiry;
use App\Models\Role;
use App\Models\User;
use Exception;
use Eyika\Atom\Framework\Http\Request;
use LogicException;
use PDOException;

final class EnquiryController
{
    public function show(Request $request, Enquiry $enquiry)
    {
        try {
            return JsonResponse::ok('enquiry retrieved success', $enquiry);

        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a db problem');
        } catch (LogicException $e) {
            return JsonResponse::serverError('we encountered a runtime problem');
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    public function list()
    {
        try {
            $enquiries = Enquiry::getBuilder()->all();
            if (!$enquiries)
                return JsonResponse::ok('no enquiry found in list', []);

            return JsonResponse::ok("enquiry's retrieved success", $enquiries);
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a problem');
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }
}
