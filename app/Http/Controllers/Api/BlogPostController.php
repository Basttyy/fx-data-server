<?php
namespace App\Http\Controllers\Api;

use App\Models\BlogPost;
use DateTime;
use Exception;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Support\Facade\Storage;
use Eyika\Atom\Framework\Support\Validator;
use HTMLPurifier;
use HTMLPurifier_Config;
use LogicException;
use PDOException;

final class BlogPostController
{
    public function show(Request $request, BlogPost $blog)
    {
        try {
            return JsonResponse::ok("blog retrieved success", $blog);
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
            $page = $request->query('page');
            $per_page = $request->query('perpage');
            $blogs = BlogPost::getBuilder()->paginate($page, $per_page);

            if (!$blogs)
                return JsonResponse::ok("no blog found in list", []);

            return JsonResponse::ok("blogs retrieved success", $blogs->toArray('blog-posts.list'));
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem".$e->getMessage());
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem".$e->getMessage());
        }
    }

    public function list_user(Request $request, string $id)
    {
        try {
            $blogs = BlogPost::getBuilder()->findBy("user_id", $id);
            
            if (!$blogs)
                return JsonResponse::ok("no blog found in list", []);

            return JsonResponse::ok("blogs retrieved success", $blogs);
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

            $data = $request->input();
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);
            $text = $purifier->purify($data['text']);
            unset($data['text']);

            $body = sanitize_data($data);
            $body['text'] = $text;

            $statuses = BlogPost::DRAFT. ', '. BlogPost::PUBLISHED. ', '. BlogPost::PUBLISHED_DRAFT;
            $sections = implode(', ', BlogPost::SECTIONS);

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
            $body['section'] = isset($body['section']) ? $body['section'] : BlogPost::SECTIONS[0];

            $mdpath = '/uploads/blogs/';

            // if (!file_exists($mdpath))
            //     mkdir($mdpath, 0777, true);

            $targetmd = uniqid().'.md';
            Storage::put($mdpath . $targetmd, $body['text']);
            // file_put_contents($mdpath . $targetmd, $body['text']);
            if (isset($body['status']) && $body['status'] === BlogPost::PUBLISHED) {
                $body['text'] = storage('public')->url($mdpath.$targetmd); //"public/uploads/blogs/$targetmd";
            } else {
                $body['draft_text'] = storage('public')->url($mdpath.$targetmd); // "public/uploads/blogs/$targetmd";
                $body['text'] = null;
            }
            if (isset($body['banner'])) {
                $banner = base64_decode($body['banner']);
                $path = '/uploads/blogs/banners/';
                
                // if (!file_exists($path))
                //     mkdir($path, 0777, true);
                
                $target_file = uniqid(). '.jpg';
                $body['banner'] = storage('public')->url($path.$target_file); //"/uploads/blogs/$target_file";
    
                // file_put_contents($path . $target_file, $banner);
                Storage::put($path . $target_file, $banner);
            }
            if (isset($body['status']) && $body['status'] == 'published') {   
                $dateTime = new DateTime();
                $body['published_at'] = $dateTime->format('F j, Y');
            }

            if (!$blog = BlogPost::getBuilder()->create($body)) {
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

    public function update(Request $request, BlogPost $blog)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

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

            $statuses = BlogPost::DRAFT. ', '. BlogPost::PUBLISHED. ', '. BlogPost::PUBLISHED_DRAFT;
            $sections = implode(', ', BlogPost::SECTIONS);

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

            if (isset($body['text'])) {
                $mdpath = '/uploads/blogs/'; // storage_path().'files/uploads/blogs/';

                // if (!file_exists($mdpath))
                //     mkdir($mdpath, 0777, true);
    
                $targetmd = uniqid().'.md';
                if (Storage::put($mdpath . $targetmd, $body['text'])) {
                    // if (isset($body['status']) && $body['status'] === Blog::PUBLISHED) {
                    //     logger()->info('status exists in body');
                    //     if (file_exists(storage_path().'files'. str_replace('/public', '', $blog->draft_text))) unlink(storage_path().'files'. str_replace('/public', '', $blog->draft_text));
                    //     if (file_exists(storage_path().'files'. str_replace('/public', '', $blog->text))) unlink(storage_path().'files'. str_replace('/public', '', $blog->text));
                    //     $body['text'] = "/public/uploads/blogs/$targetmd";
                    //     $body['draft_text'] = null;
                    // } else {
                        if ($blog->draft_text && Storage::exists($blog->draft_text)) Storage::delete($blog->draft_text);
                        $body['draft_text'] = Storage::url($mdpath.$targetmd);
                        unset($body['text']);
                    // }
                }
            } else if (isset($body['status']) && $body['status'] === BlogPost::PUBLISHED) {
                if ($blog->text && Storage::exists($blog->text)) Storage::delete($blog->text);
                $body['text'] = $blog->draft_text;
                $body['draft_text'] = null;
            }
            if (isset($body['banner'])) {
                $prev_banner = $blog->banner;
                $banner = base64_decode($body['banner']);
                $path = '/uploads/blogs/';
                
                // if (!file_exists($path))
                //     mkdir($path, 0777, true);
                
                $target_file = uniqid(). '.jpg';
    
                if (Storage::put($path . $target_file, $banner)) {
                    storage()->delete($prev_banner);
                    $body['banner'] = "/uploads/blogs/$target_file";
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
            $body['last_updated_by'] = $user->id;

            if (!$blog->update($body)) {
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

    public function delete(Request $request, BlogPost $blog)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("only admin can delete blog");
            }

            $text = $blog->text;
            $draft_text = $blog->draft_text;

            if (!$blog->delete()) {
                return JsonResponse::serverError("unable to delete blog");
            }

            if (!empty($text))
                storage()->delete($blog->text);
            if (!empty($draft_text))
                storage()->delete($blog->draft_text);

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
