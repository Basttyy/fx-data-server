<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Blog;
use Basttyy\FxDataServer\Models\PostComment;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class PostCommentController
{
    private $method;
    private $user;
    private $authenticator;
    private $post_comment;
    private $post;

    public function __construct($method = 'show')
    {
        $this->method = $method;
        $this->user = new User();
        $this->post_comment = new PostComment();
        $this->post = new Blog();
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
                $resp = $this->list();
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
            if (!$this->post_comment->find((int)$id))
                return JsonResponse::notFound('unable to retrieve post comment');

            return JsonResponse::ok('post comment retrieved success', $this->post_comment->toArray());
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a db problem');
        } catch (LogicException $e) {
            return JsonResponse::serverError('we encountered a runtime problem');
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function list()
    {
        try {
            $post_comment = $this->post_comment->all();
            if (!$post_comment)
                return JsonResponse::ok('no post comment found in list', []);

            return JsonResponse::ok('post comment retrieved success', $post_comment);
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a problem');
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function create()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'post_id' => 'required|int',
                'post_comment_id' => 'required|int',
                'username' => 'sometimes|string',
                'text' => 'required|string',
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$this->post->find($body['post_id'])) {
                return JsonResponse::badRequest("blog post with given id not found");
            }

            if (!$post_comment = $this->post_comment->create($body)) {
                return JsonResponse::serverError('unable to create post comment');
            }

            return JsonResponse::ok('post comment creation successful', $post_comment);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('post comment already exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function update(string $id)
    {
        try {
            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }

            $id = sanitize_data($id);
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            
            $statuses = PostComment::APPROVED .' '. PostComment::PENDING .' '. PostComment::REJECTED;
            if ($validated = Validator::validate($body, [
                'status' => "sometimes|string|in:$statuses",
                // 'text' => 'sometimes|string',
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$this->post_comment->update($body, (int)$id)) {
                return JsonResponse::notFound('unable to update post comment not found');
            }

            return JsonResponse::ok('post comment updated successfull', $this->post_comment->toArray());
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function delete(int $id)
    {
        try {
            $id = sanitize_data($id);

            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            // Uncomment this for role authorization
            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't delete a post comment");
            }

            if (!$this->post_comment->delete((int)$id)) {
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
