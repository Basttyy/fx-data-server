<?php

use Basttyy\FxDataServer\Controllers\Api\Auth\AuthController;
use Basttyy\FxDataServer\Controllers\Api\Auth\CaptchaController;
use Basttyy\FxDataServer\Controllers\Api\Auth\TwoFaController;
use Basttyy\FxDataServer\Controllers\Api\BlogController;
use Basttyy\FxDataServer\Controllers\Api\CheapCountryController;
use Basttyy\FxDataServer\Controllers\Api\FeedbackController;
use Basttyy\FxDataServer\Controllers\Api\FxDataController;
use Basttyy\FxDataServer\Controllers\Api\MigrateController;
use Basttyy\FxDataServer\Controllers\Api\MiscellaneousController;
use Basttyy\FxDataServer\Controllers\Api\PairController;
use Basttyy\FxDataServer\Controllers\Api\PlanController;
use Basttyy\FxDataServer\Controllers\Api\PositionController;
use Basttyy\FxDataServer\Controllers\Api\PostCommentController;
use Basttyy\FxDataServer\Controllers\Api\ReferralController;
use Basttyy\FxDataServer\Controllers\Api\StrategyController;
use Basttyy\FxDataServer\Controllers\Api\SubscriptionController;
use Basttyy\FxDataServer\Controllers\Api\TestSessionController;
use Basttyy\FxDataServer\Controllers\Api\TransactionController;
use Basttyy\FxDataServer\Controllers\Api\UserController;
use Basttyy\FxDataServer\Controllers\Api\UserExplicitController;
use Basttyy\FxDataServer\Controllers\Api\VisitController;
use Basttyy\FxDataServer\Controllers\NotFoundController;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\libs\Router;
use Basttyy\FxDataServer\Middlewares\AuthMiddleware;
use Basttyy\FxDataServer\Middlewares\VisitLoggerMiddleware;

Router::middleware(VisitLoggerMiddleware::class, function () {
    /// Auth routes
    Router::group('/auth', function () {
        Router::post('/login', [AuthController::class, 'login']);
        Router::get('/login', [AuthController::class, 'loginOauth']);
        Router::get('/captcha', [CaptchaController::class, 'generate']);
        Router::post('/captcha', [CaptchaController::class, 'comparePhrase']);
        Router::middleware(AuthMiddleware::class, function () {
            Router::get('/refresh-token', [AuthController::class, 'refreshToken']);
            Router::get('/twofa/mode/$mode', [TwoFaController::class, 'generate']);
            Router::post('/twofa/mode/$mode', [TwoFaController::class, 'verifyCode']);
            Router::get('/twofa/mode/$mode/status/$status', [TwoFaController::class, 'twofaonoff']);
        });
    });
    /// User Routes
    Router::group('/users', function () {
        Router::get('/$id', [UserController::class, 'show']);
        Router::get('', [UserController::class, 'list']);
        Router::get('/query/$query', [UserController::class, 'list']);
        Router::post('', [UserController::class, 'create']);
        Router::put('/$id', [UserController::class, 'update']);
        Router::delete('/$id', [UserController::class, 'delete']);
        /// User Special Routes
        Router::post('/method/$method', [UserExplicitController::class, 'index']);
        Router::post('/exchange-points', [UserController::class, 'exchangePoints']);
    });
    /// Plan Routes
    Router::group('/plans', function () {
        Router::get('/$id', new PlanController());
        Router::get('', new PlanController('list'));
        Router::get('/standard/$standard', new PlanController('list'));
        Router::get('/query/$query', new PlanController('list'));
        Router::post('', new PlanController('create'));
        Router::put('/$id', new PlanController('update'));
        Router::delete('/$id', new PlanController('delete'));
    });
    /// Subscription Routes
    Router::group('/subscriptions', function () {
        Router::get('/$id', new SubscriptionController());
        Router::put('/$id/cancel', new SubscriptionController('cancel'));
        Router::get('', new SubscriptionController('list'));
        Router::get('/all/count', new SubscriptionController('count'));
        Router::get('/all/count/$query', new SubscriptionController('count'));
        Router::get('/query/$query', new SubscriptionController('list'));
        Router::get('/palns/$id', new SubscriptionController('list_plan'));
        // Router::post('/subscriptions', new SubscriptionController('create'));
    });
    /// Transaction Routes
    Router::group('/transactions', function () {
        Router::get('/$id', new TransactionController(''));
        Router::get('', new TransactionController('list'));
        Router::get('/generate/ref', new TransactionController('trans_ref'));
        Router::post('/verify', new TransactionController('create'));
        Router::post('/webhook', new TransactionController('update'));
    });
    /// Strategy Routes
    Router::group('/strategies', function () {
        Router::get('/$id', new StrategyController());
        Router::get('', new StrategyController('list'));
        Router::get('/query/$query', new StrategyController('list'));
        Router::get('/users/$id', new StrategyController('list_user'));
        Router::post('', new StrategyController('create'));
        Router::put('/$id', new StrategyController('update'));
        Router::delete('/$id', new StrategyController('delete'));
    });
    /// TestSessions Routes
    Router::group('/test-sessions', function () {
        Router::get('/$id', new TestSessionController());
        Router::get('', new TestSessionController('list'));
        Router::get('/query/$query', new TestSessionController('list'));
        Router::post('', new TestSessionController('create'));
        Router::put('/$id', new TestSessionController('update'));
        Router::delete('/$id', new TestSessionController('delete'));
    });
    /// Pairs Routes
    Router::group('/pairs', function () {
        Router::get('/$id', new PairController());
        Router::get('', new PairController('list'));
        Router::get('/list/onlypair', new PairController('listonlypair'));
        Router::get('/list/pairorsym/$id/query/$query', new PairController('query'));
        Router::post('', new PairController('create'));
        Router::put('/$id', new PairController('update'));
        Router::delete('/$id', new PairController('delete'));
    });
    Router::group('/positions', function () {
        /// Positions Routes
        Router::get('/$id', new PositionController());
        Router::get('', new PositionController('list'));
        Router::get('/query/$query', new PositionController('list'));
        Router::get('/users/$id', new PositionController('list_user'));
        Router::post('', new PositionController('create'));
        Router::put('/$id', new PositionController('update'));
        Router::put('/$id/unset/$tporsl', new PositionController('unsetslortp'));
        Router::delete('/$id', new PositionController('delete'));
    });
    /// Feedbacks Routes
    Router::group('/feedbacks', function () {
        Router::get('/$id', new FeedbackController());
        Router::get('', new FeedbackController('list'));
        Router::get('/query/$query', new FeedbackController('list'));
        Router::get('/users/$id', new FeedbackController('list_user'));
        Router::post('', new FeedbackController('create'));
        Router::put('/$id', new FeedbackController('update'));
        Router::delete('/$id', new FeedbackController('delete'));
    });
    /// Admin Routes
    Router::group('/admin', function () {
        Router::group('/logs', function () {
            Router::get('/$id', new VisitController());
            Router::get('/all/count', new VisitController('count'));
            Router::get('/all/count/$query', new VisitController('count'));
            Router::get('', new VisitController('list'));
            Router::get('/query/$query', new VisitController('list'));
        });
        Router::group('/blog', function () {
            Router::post('/posts', new BlogController('create'));
            Router::put('/posts/$id', new BlogController('update'));
            Router::delete('/posts/$id', new BlogController('delete'));
        });
        Router::group('/cheapcountries', function () {
            Router::get('/$id', new CheapCountryController());
            Router::get('', new CheapCountryController('list'));
            Router::post('', new CheapCountryController('create'));
            Router::put('/$id', new CheapCountryController('update'));
            Router::delete('/$id', new CheapCountryController('delete'));
        });
        Router::post('/landing/data', new MiscellaneousController('update_landing'));
    });
    Router::group('/devops', function () {
        Router::get('/migrate', new MigrateController);
    });
    Router::group('/blog', function () {
        Router::group('/posts', function () {
            Router::get('', new BlogController('list'));
            Router::get('/$id', new BlogController());
            Router::get('/$id/comments', new PostCommentController('list'));
            Router::get('/$id/comments/$id', new PostCommentController());
            Router::get('/$id/comments/query/$query', new PostCommentController('list'));
            Router::post('/$id/comments', new PostCommentController('create'));
            Router::put('/$id/comments/$id', new PostCommentController('update'));
            Router::delete('/$id/comments/$id', new PostCommentController('delete'));
        });
        Router::get('/comments', new PostCommentController('listall'));
    });
    /// Historical Data Routes
    Router::group('/fx', function () {
        Router::get('/download/min/ticker/$ticker/offerside/$offerside/period/$period/yr/$year/mn/$month/wk/$week', new FxDataController());
        Router::get('/currency/conversiondata/ticker/$ticker/year/$year', new FxDataController('currency_conversion_data'));
        Router::get('/tickers/query/$query', new MiscellaneousController('search_ticker'));
        Router::get('/tickers/query', new MiscellaneousController('search_ticker'));
    });
    /// Others
    Router::post('/misc/contact-us', new MiscellaneousController());
    Router::get('/landing/data', new MiscellaneousController('fetch_landing'));
    Router::get('/referrals', new ReferralController('list'));
    Router::get('/referrals', new ReferralController('list'));

    Router::any('/404', new NotFoundController);
});

$request = Request::capture();
Router::dispatch($request);



// Example usage
// class AuthMiddleware implements MiddlewareInterface
// {
//     public function handle(array $parameters): bool
//     {
//         // Perform authentication check
//         return true;
//     }
// }

/**
 * Example usage
 */

// Router::group('/api', function () {
//     Router::middleware(AuthMiddleware::class, function () {
//         Router::get('/users', function () {
//             echo 'Users API';
//         });

//         Router::group('/v1', function () {
//             Router::name('v1.users', function () {
//                 Router::get('/users', function () {
//                     echo 'Users API Version 1';
//                 });
//             });

//             Router::post('/users', function () {
//                 echo 'Create User API Version 1';
//             });
//         });
//     });
// });

// $router = new Router();
// $router::post('/example', function () {
//     echo 'Hello, POST world!';
// });

// Router::dispatch();

// // Get URL of named route
// echo Router::route('v1.users'); // Output: /api/v1/users


// Static GET
// In the URL -> http://localhost
// The output -> Index
// get('/', 'views/index.php');

// // Dynamic GET. Example with 1 variable
// // The $id will be available in user.php
// get('/user/$id', 'views/user');

// // Dynamic GET. Example with 2 variables
// // The $name will be available in full_name.php
// // The $last_name will be available in full_name.php
// // In the browser point to: localhost/user/X/Y
// get('/user/$name/$last_name', 'views/full_name.php');

// // Dynamic GET. Example with 2 variables with static
// // In the URL -> http://localhost/product/shoes/color/blue
// // The $type will be available in product.php
// // The $color will be available in product.php
// get('/product/$type/color/$color', 'product.php');

// // A route with a callback
// get('/callback', function(){
//   out('<script>alert()</script>');
// });

// // A route with a callback passing a variable
// // To run this route, in the browser type:
// // http://localhost/user/A
// get('/callback/$name', function($name){
//   echo "<script>alert(`$name`)</script>";
// });

// // A route with a callback passing 2 variables
// // To run this route, in the browser type:
// // http://localhost/callback/A/B
// get('/callback/$name/$last_name', function($name, $last_name){
//   echo "Callback executed. The full name is $name $last_name";
// });

// // ##################################################
// // ##################################################
// // ##################################################
// // any can be used for GETs or POSTs

// // For GET or POST
// // The 404.php which is inside the views folder will be called
// // The 404.php has access to $_GET and $_POST
// any('/404','views/404.php');