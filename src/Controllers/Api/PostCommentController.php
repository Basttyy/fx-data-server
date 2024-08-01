<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\PostComment;
use Exception;
use LogicException;
use PDOException;

final class PostCommentController
{
    public function show(Request $request, string $id)
    {
        $id = sanitize_data($id);
        try {
            if (!$post_comment = PostComment::getBuilder()->find((int)$id))
                return JsonResponse::notFound('unable to retrieve post comment');

            return JsonResponse::ok('post comment retrieved success', $post_comment->toArray());
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
            
            if (!$post_comments = PostComment::getBuilder()->all())
                return JsonResponse::ok('no post comment found in list', []);

            return JsonResponse::ok('post comment retrieved success', $post_comments);
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
            if ($user ?? null && Guard::roleIs($user, 'admin')) {
                    $post_comments = $builder->where('post_id', $post_id)->all();
            } else {
                $params = count($_GET) ? sanitize_data($_GET) : [];
                $post_comments = isset($params['post_comment_id']) ?
                            $builder->where('status', PostComment::APPROVED)
                                ->where('post_id', $post_id)
                                ->where('post_comment_id', $params['post_comment_id'])->all() :
                            $builder->where('status', PostComment::APPROVED)
                                ->where('post_id', $post_id)->all();
            }
            
            if (!$post_comments)
                return JsonResponse::ok('no post comment found in list', []);

            return JsonResponse::ok('post comment retrieved success', $post_comments);
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
            
            $body = sanitize_data($request->input());
            $post_id = sanitize_data($post_id);
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

    public function update(Request $request, string $id)
    {
        try {
            $user = $request->auth_user;
            
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }
            
            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't update a post comment");
            }

            $id = sanitize_data($id);
            
            $body = sanitize_data($request->input());
            
            $statuses = PostComment::APPROVED .', '. PostComment::REJECTED;
            if ($validated = Validator::validate($body, [
                'status' => "sometimes|string|in:$statuses",
                // 'text' => 'sometimes|string',
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$tpost_comment = PostComment::getBuilder()->update($body, (int)$id)) {
                return JsonResponse::notFound('unable to update post comment not found');
            }

            return JsonResponse::ok('post comment updated successfull', $tpost_comment->toArray());
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    public function delete(Request $request, int $id)
    {
        try {
            $id = sanitize_data($id);

            $user = $request->auth_user;

            // Uncomment this for role authorization
            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't delete a post comment");
            }

            if (!PostComment::getBuilder()->delete((int)$id)) {
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
