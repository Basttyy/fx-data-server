<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Blog;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use DateTime;
use Exception;
use HTMLPurifier;
use HTMLPurifier_Config;
use LogicException;
use PDOException;

final class BlogController
{
    public function show(Request $request, string $id)
    {
        $id = sanitize_data($id);
        try {
            if (!$blog = Blog::getBuilder()->find((int)$id))
                return JsonResponse::notFound("unable to retrieve blog");

            return JsonResponse::ok("blog retrieved success", $blog->toArray());
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (LogicException $e) {
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
    
    public function list(Request $request, )
    {
        try {
            $blogs = Blog::getBuilder()->all();

            if (!$blogs)
                return JsonResponse::ok("no blog found in list", []);

            return JsonResponse::ok("blogs retrieved success", $blogs);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem".$e->getMessage());
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem".$e->getMessage());
        }
    }

    public function list_user(Request $request, string $id)
    {
        try {
            if (!JwtAuthenticator::validate()) {
                return JsonResponse::unauthorized();
            }

            $id = sanitize_data($id);
            $blogs = Blog::getBuilder()->findBy("user_id", $id);
            
            if (!$blogs)
                return JsonResponse::ok("no blog found in list", []);

            return JsonResponse::ok("blogs retrieved success", $blogs);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function create(Request $request, )
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("only admins can post blog");
            }

            $data = $request->input();
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);
            $text = $purifier->purify($data['text']);
            unset($data['text']);

            $body = sanitize_data($data);
            $body['text'] = $text;

            $statuses = Blog::DRAFT. ', '. Blog::PUBLISHED. ', '. Blog::PUBLISHED_DRAFT;
            $sections = implode(', ', Blog::SECTIONS);

            if ($validated = Validator::validate($body, [
                'title' => 'required|string',
                'slug' => 'required|string',
                'text' => 'required|string',
                'description' => 'sometimes|string',
                'section' => "sometimes|string|in:$sections",
                'status' => "sometimes|string|in:$statuses",
                'banner' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $body['user_id'] = $user->id;
            $body['section'] = isset($body['section']) ? $body['section'] : Blog::SECTIONS[0];

            $mdpath = storage_path().'files/uploads/blogs/';

            if (!file_exists($mdpath))
                mkdir($mdpath, 0777, true);

            $targetmd = uniqid().'.md';
            file_put_contents($mdpath . $targetmd, $body['text']);
            if (isset($body['status']) && $body['status'] === Blog::PUBLISHED) {
                $body['text'] = "public/uploads/blogs/$targetmd";
            } else {
                $body['draft_text'] = "public/uploads/blogs/$targetmd";
                $body['text'] = null;
            }
            if (isset($body['banner'])) {
                $banner = base64_decode($body['banner']);
                $path = storage_path(). 'files/uploads/blogs/banners/';
                
                if (!file_exists($path))
                    mkdir($path, 0777, true);
                
                $target_file = uniqid(). '.jpg';
                $body['banner'] = "public/uploads/blogs/$target_file";
    
                file_put_contents($path . $target_file, $banner);
            }
            if (isset($body['status']) && $body['status'] == 'published') {   
                $dateTime = new DateTime();
                $body['published_at'] = $dateTime->format('F j, Y');
            }

            if (!$blog = Blog::getBuilder()->create($body)) {
                return JsonResponse::serverError("unable to create blog");
            }

            return JsonResponse::created("blog creation successful", $blog);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('blog already exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;
            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("only admins can post blog");
            }

            $data = $request->input();
            
            if (isset($data['text'])) {
                $config = HTMLPurifier_Config::createDefault();
                $purifier = new HTMLPurifier($config);
                $text = $purifier->purify($data['text']);
                unset($data['text']);
    
                $body = sanitize_data($data);
                $body['text'] = $text;
            } else {
                $body = sanitize_data($data);
            }

            $id = sanitize_data($id);
            $statuses = Blog::DRAFT. ', '. Blog::PUBLISHED. ', '. Blog::PUBLISHED_DRAFT;
            $sections = implode(', ', Blog::SECTIONS);

            if ($validated = Validator::validate($body, [
                'title' => 'sometimes|string',
                'slug' => 'sometimes|string',
                'text' => 'sometimes|string',
                'description' => 'sometimes|string',
                'section' => "sometimes|string|in:$sections",
                'status' => "sometimes|string|in:$statuses",
                'banner' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }
            if (!$blog = Blog::getBuilder()->find($id)) {
                return JsonResponse::notFound('blog not found');
            }
            if (isset($body['text'])) {
                $mdpath = storage_path().'files/uploads/blogs/';

                if (!file_exists($mdpath))
                    mkdir($mdpath, 0777, true);
    
                $targetmd = uniqid().'.md';
                if (file_put_contents($mdpath . $targetmd, $body['text'])) {
                    // if (isset($body['status']) && $body['status'] === Blog::PUBLISHED) {
                    //     logger()->info('status exists in body');
                    //     if (file_exists(storage_path().'files'. str_replace('/public', '', $blog->draft_text))) unlink(storage_path().'files'. str_replace('/public', '', $blog->draft_text));
                    //     if (file_exists(storage_path().'files'. str_replace('/public', '', $blog->text))) unlink(storage_path().'files'. str_replace('/public', '', $blog->text));
                    //     $body['text'] = "/public/uploads/blogs/$targetmd";
                    //     $body['draft_text'] = null;
                    // } else {
                        if ($blog->draft_text && file_exists(storage_path().'files'. str_replace('public', '', $blog->draft_text))) unlink(storage_path().'files'. str_replace('public', '', $blog->draft_text));
                        $body['draft_text'] = "public/uploads/blogs/$targetmd";
                        unset($body['text']);
                    // }
                }
            } else if (isset($body['status']) && $body['status'] === Blog::PUBLISHED) {
                if ($blog->text && file_exists(storage_path().'files'. str_replace('public', '', $blog->text))) unlink(storage_path().'files'. str_replace('public', '', $blog->text));
                $body['text'] = $blog->draft_text;
                $body['draft_text'] = null;
            }
            if (isset($body['banner'])) {
                $prev_banner = $blog->banner;
                $banner = base64_decode($body['banner']);
                $path = storage_path(). 'files/uploads/blogs/';
                
                if (!file_exists($path))
                    mkdir($path, 0777, true);
                
                $target_file = uniqid(). '.jpg';
    
                if (file_put_contents($path . $target_file, $banner)) {
                    unlink(storage_path().'files'. str_replace('public', '', $prev_banner));
                    $body['banner'] = "public/uploads/blogs/$target_file";
                }
            }
            if (isset($body['status']) && $body['status'] == 'published') {
                if ($blog->published_at == '') {
                    $dateTime = new DateTime();
                    $body['published_at'] = $dateTime->format('F j, Y');
                } else {
                    $dateTime = new DateTime();
                    $body['publish_updated_at'] = $dateTime->format('F j, Y');
                }
            }

            if (!$blog->update($body, (int)$id)) {
                return JsonResponse::notFound("unable to update blog");
            }

            return JsonResponse::ok("blog updated successfull", $blog->toArray());
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

    public function delete(Request $request, int $id)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("only admin can delete blog");
            }
            $id = sanitize_data($id);

            if (!$blog = Blog::getBuilder()->find((int)$id)) {
                return JsonResponse::notFound("blog does not exist");
            }

            unlink(storage_path().'files'. str_replace('public', '', $blog->text));

            // logger()->info('testing', $blog->toArray());

            if (!$blog->delete()) {
                return JsonResponse::serverError("unable to delete blog");
            }

            return JsonResponse::ok("blog deleted successfully");
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
