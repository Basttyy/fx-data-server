<?php
namespace App\Http\Controllers\Api;

use App\Models\BlogPost;
use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Support\Auth\Jwt\JwtAuthenticator;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Validator;
use App\Models\PostComment;
use Exception;
use LogicException;
use PDOException;

final class PostCommentController
{
    public function show(Request $request, PostComment $post_comment)
    {
        try {
            return JsonResponse::ok('post comment retrieved success', $post_comment);

        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a db problem');
        } catch (LogicException $e) {
            return JsonResponse::serverError('we encountered a runtime problem');
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    public function listall(Request $request)
    {
        try {
            $user = $request->auth_user;
            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized('you are not allowed to update comment');
            }
            $page = $request->query('page');
            $per_page = $request->query('perpage');
            
            if (!$post_comments = PostComment::getBuilder()->paginate($page, $per_page))
                return JsonResponse::ok('no post comment found in list', []);

            return JsonResponse::ok('post comment retrieved success', $post_comments->toArray('admin.blog.comments.list'));
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a problem'.$e->getMessage());
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem'.$e->getMessage());
        }
    }

    public function list(Request $request, string $post_id)
    {
        try {
            $user = $request->auth_user;
            $builder = PostComment::getBuilder();
            $page = $request->query('page');
            $per_page = $request->query('perpage');

            if (Guard::roleIs($user, 'admin')) {
                    $post_comments = $builder->where('post_id', $post_id)->paginate($page, $per_page);
            } else {
                $params = count($_GET) ? sanitize_data($_GET) : [];
                $post_comments = isset($params['post_comment_id']) ?
                            $builder->where('status', PostComment::APPROVED)
                                ->where('post_id', $post_id)
                                ->where('post_comment_id', $params['post_comment_id'])->paginate($page, $per_page) :
                            $builder->where('status', PostComment::APPROVED)
                                ->where('post_id', $post_id)->paginate($page, $per_page);
            }
            
            if (!$post_comments)
                return JsonResponse::ok('no post comment found in list', []);

            return JsonResponse::ok('post comment retrieved success', $post_comments->toArray('blog-posts.comments.list'));
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a problem'.$e->getMessage());
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem'.$e->getMessage());
        }
    }

    public function create(Request $request, string $post_id)
    {
        try {            
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }
            $user = $request->auth_user;
            
            $body = $request->input();
            $body['post_id'] = $post_id;

            if ($validated = Validator::validate($body, [
                'text' => 'required|string',
                'post_id' => 'required|int|exist:posts,id',
                'username' => 'sometimes|string',
                'post_comment_id' => 'sometimes|int',
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!isset($body['username']) && JwtAuthenticator::validate()) {
                $body['username'] = $user->username;
            }
            $body['status'] = PostComment::PENDING;

            if (!$post_comment = PostComment::getBuilder()->create($body)) {
                return JsonResponse::serverError('unable to create post comment');
            }

            return JsonResponse::created('post comment creation successful', $post_comment);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('post comment already exist');
            else $message = 'we encountered a problem'.$e->getMessage();
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem'.$e->getMessage());
        }
    }

    public function update(Request $request, PostComment $post_comment)
    {
        try {
            $user = $request->auth_user;
            
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }
            
            $body = $request->input();
            
            $statuses = PostComment::APPROVED .', '. PostComment::REJECTED;
            if ($validated = Validator::validate($body, [
                'status' => "sometimes|string|in:$statuses",
                // 'text' => 'sometimes|string',
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$post_comment->update($body, is_protected: false)) {
                return JsonResponse::notFound('unable to update post comment not found');
            }

            return JsonResponse::ok('post comment updated successfull', $post_comment);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    public function delete(Request $request, PostComment $post_comment)
    {
        try {
            if (!$post_comment->delete()) {
                return JsonResponse::notFound('unable to delete post comment or post comment not found');
            }

            return JsonResponse::ok('post comment deleted successfull');
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }
}
