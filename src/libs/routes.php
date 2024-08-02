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
use Basttyy\FxDataServer\Middlewares\ThrottleRequestsMiddleware;
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
        Router::post('', [UserController::class, 'create']);
        /// User Special Routes
        Router::post('/method/$method', [UserExplicitController::class, 'index']);
        Router::middleware(AuthMiddleware::class, function () {
            Router::get('/$id', [UserController::class, 'show'])->middleware([ThrottleRequestsMiddleware::class, "3,60"]);
            Router::get('', [UserController::class, 'list'])->name('users.list')->middleware([[ThrottleRequestsMiddleware::class, "3,60"]]);
            Router::get('/query/$query', [UserController::class, 'list']);
            Router::put('/$id', [UserController::class, 'update'])->middleware([ThrottleRequestsMiddleware::class]);
            Router::delete('/$id', [UserController::class, 'delete'])->middleware(ThrottleRequestsMiddleware::class);
            Router::post('/affilliate/withdraw/$points', [UserController::class, 'withrawAffilliateEarnings']);
        });
    });
    /// Plan Routes
    Router::group('/plans', function () {
        Router::get('/$id', [PlanController::class, 'show']);
        Router::get('', [PlanController::class, 'list'])->middleware([[ThrottleRequestsMiddleware::class]]);
        Router::get('/standard/$standard', [PlanController::class, 'list']);
        Router::get('/query/$query', [PlanController::class, 'list']);
        Router::middleware(AuthMiddleware::class, function () {
            Router::post('', [PlanController::class, 'create']);
            Router::put('/$id', [PlanController::class, 'update']);
            Router::delete('/$id', [PlanController::class, 'delete']);
        });
    });
    /// Subscription Routes
    Router::group('/subscriptions', function () {
        Router::get('/$id', [SubscriptionController::class, 'show']);
        // Router::post('/subscriptions', new SubscriptionController('create'));
        Router::middleware(AuthMiddleware::class, function () {
            Router::get('/all/count', [SubscriptionController::class, 'count']);
            Router::get('/all/count/$query', [SubscriptionController::class, 'count']);
            Router::get('', [SubscriptionController::class, 'list']);
            Router::get('/query/$query', [SubscriptionController::class, 'list']);
            Router::get('/user/$id', [SubscriptionController::class, 'listUser']);
            Router::get('/plans/$id', [SubscriptionController::class, 'listPlan']);
            Router::put('/$id/cancel', [SubscriptionController::class, 'cancel']);
        });
    });
    /// Transaction Routes
    Router::group('/transactions', function () {
        Router::get('/$id', [TransactionController::class, 'show']);
        Router::post('/webhook', [TransactionController::class, 'update']);
        Router::middleware(AuthMiddleware::class, function () {
            Router::get('', [TransactionController::class, 'list']);
            Router::post('/verify', [TransactionController::class, 'create']);
            Router::get('/generate/ref', [TransactionController::class, 'trans_ref']);
        });
    });
    /// Strategy Routes
    Router::group('/strategies', function () {
        Router::middleware(AuthMiddleware::class, function () {
            Router::get('/$id', [StrategyController::class, 'show']);
            Router::get('', [StrategyController::class, 'list']);
            Router::get('/query/$query', [StrategyController::class, 'list']);
            Router::get('/users/$id', [StrategyController::class, 'listUser']);
            Router::post('', [StrategyController::class, 'create']);
            Router::put('/$id', [StrategyController::class, 'update']);
            Router::delete('/$id', [StrategyController::class, 'delete']);
        });
    });
    /// TestSessions Routes
    Router::group('/test-sessions', function () {
        Router::middleware(AuthMiddleware::class, function () {
            Router::get('/$id', [TestSessionController::class, 'show']);
            Router::get('', [TestSessionController::class, 'list']);
            Router::get('/query/$query', [TestSessionController::class, 'list']);
            Router::post('', [TestSessionController::class, 'create']);
            Router::put('/$id', [TestSessionController::class, 'update']);
            Router::delete('/$id', [TestSessionController::class, 'delete']);
        });
    });
    /// Pairs Routes
    Router::group('/pairs', function () {
        Router::get('/$id', [PairController::class, 'show']);
        Router::get('', [PairController::class, 'list']);
        Router::get('/list/onlypair', [PairController::class, 'listonlypair']);
        Router::get('/list/pairorsym/$id/query/$query', [PairController::class, 'query']);
        Router::middleware(AuthMiddleware::class, function () {
            Router::post('', [PairController::class, 'create']);
            Router::put('/$id', [PairController::class, 'update']);
            Router::delete('/$id', [PairController::class, 'delete']);
        });
    });
    Router::group('/positions', function () {
        /// Positions Routes
        Router::middleware(AuthMiddleware::class, function () {
            Router::get('/$id', [PositionController::class, 'show']);
            Router::get('', [PositionController::class, 'list']);
            Router::get('/query/$query', [PositionController::class, 'list']);
            Router::get('/users/$id', [PositionController::class, 'list_user']);
            Router::post('', [PositionController::class, 'create']);
            Router::put('/$id', [PositionController::class, 'update']);
            Router::put('/$id/unset/$tporsl', [PositionController::class, 'unsetslortp']);
            Router::delete('/$id', [PositionController::class, 'delete']);
        });
    });
    /// Feedbacks Routes
    Router::group('/feedbacks', function () {
        Router::middleware(AuthMiddleware::class, function () {
            Router::get('/$id', [FeedbackController::class, 'show']);
            Router::get('', [FeedbackController::class, 'list']);
            Router::get('/query/$query', [FeedbackController::class, 'list']);
            Router::get('/users/$id', [FeedbackController::class, 'list_user']);
            Router::post('', [FeedbackController::class, 'create']);
            Router::put('/$id', [FeedbackController::class, 'update']);
            Router::delete('/$id', [FeedbackController::class, 'delete']);
        });
    });
    /// Admin Routes
    Router::group('/admin', function () {
        Router::middleware(AuthMiddleware::class, function () {
            Router::group('/logs', function () {
                Router::get('/$id', [VisitController::class, 'show']);
                Router::get('/all/count', [VisitController::class, 'count']);
                Router::get('/all/count/$query', [VisitController::class, 'count']);
                Router::get('', [VisitController::class, 'list']);
                Router::get('/query/$query', [VisitController::class, 'list']);
            });
            Router::group('/blog', function () {
                Router::post('/posts', [BlogController::class, 'create'])->name('admin.blog.posts');
                Router::put('/posts/$id', [BlogController::class, 'update']);
                Router::delete('/posts/$id', [BlogController::class, 'delete']);
            });
            Router::group('/cheapcountries', function () {
                Router::get('/$id', [CheapCountryController::class, 'show']);
                Router::get('', [CheapCountryController::class, 'list']);
                Router::post('', [CheapCountryController::class, 'create']);
                Router::put('/$id', [CheapCountryController::class, 'update']);
                Router::delete('/$id', [CheapCountryController::class, 'delete']);
            });
            Router::post('/landing/data', [MiscellaneousController::class, 'updateLanding']);
        });
    });
    Router::group('/devops', function () {
        Router::get('/migrate/status', [MigrateController::class, 'status']);
        Router::get('/migrate/migrate', [MigrateController::class, 'migrate']);
        Router::get('/migrate/rollback', [MigrateController::class, 'rollback']);
        Router::get('/migrate/seed', [MigrateController::class, 'seed']);
    });
    Router::group('/blog', function () {
        Router::group('/posts', function () {
            Router::get('', [BlogController::class, 'list']);
            Router::get('/$id', [BlogController::class, 'show']);
            Router::get('/$id/comments/$id', [PostCommentController::class, 'show']);
            Router::get('/$id/comments', [PostCommentController::class, 'list']);
            Router::get('/$id/comments/query/$query', [PostCommentController::class, 'list']);
            Router::middleware(AuthMiddleware::class, function () {
                Router::post('/$id/comments', [PostCommentController::class, 'create']);
                Router::put('/$id/comments/$id', [PostCommentController::class, 'update']);
                Router::delete('/$id/comments/$id', [PostCommentController::class, 'delete']);
            });
        });
        Router::get('/comments', [PostCommentController::class, 'listall']);
    });
    /// Historical Data Routes
    Router::group('/fx', function () {
        Router::get('/download/min/ticker/$ticker/offerside/$offerside/period/$period/yr/$year/mn/$month/wk/$week', [FxDataController::class, 'downloadMinutesData']);
        Router::get('/currency/conversiondata/ticker/$ticker/year/$year', [FxDataController::class, 'currencyConversionData']);
        Router::get('/tickers/query/$query', [MiscellaneousController::class, 'searchTicker']);
        Router::get('/tickers/query', [MiscellaneousController::class, 'searchTicker']);
    });
    /// Others
    Router::post('/misc/contact-us', [MiscellaneousController::class, 'contact_us'])->name('contact-us');
    Router::get('/landing/data', [MiscellaneousController::class, 'fetchLanding']);
    Router::get('/referrals/$id', [ReferralController::class, 'show']);
    Router::get('/referrals', [ReferralController::class, 'list']);

    Router::any('/404', [NotFoundController::class, 'index']);
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