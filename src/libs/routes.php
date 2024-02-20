<?php

require_once __DIR__.'/router.php';

require_once __DIR__."/../Controllers/Api/Auth/AuthController.php";
require_once __DIR__.'/../TickController.php';
require_once __DIR__.'/../MinuteController.php';

use Basttyy\FxDataServer\Controllers\Api\Auth\AuthController;
use Basttyy\FxDataServer\Controllers\Api\Auth\CaptchaController;
use Basttyy\FxDataServer\Controllers\Api\Auth\TwoFaController;
use Basttyy\FxDataServer\Controllers\Api\BlogController;
use Basttyy\FxDataServer\Controllers\Api\FeedbackController;
use Basttyy\FxDataServer\Controllers\Api\FxHistoryController;
use Basttyy\FxDataServer\Controllers\Api\MigrateController;
use Basttyy\FxDataServer\Controllers\Api\MiscellaneousController;
use Basttyy\FxDataServer\Controllers\Api\PairController;
use Basttyy\FxDataServer\Controllers\Api\PlanController;
use Basttyy\FxDataServer\Controllers\Api\PositionController;
use Basttyy\FxDataServer\Controllers\Api\PostCommentController;
use Basttyy\FxDataServer\Controllers\Api\RequestLogController;
use Basttyy\FxDataServer\Controllers\Api\StrategyController;
use Basttyy\FxDataServer\Controllers\Api\SubscriptionController;
use Basttyy\FxDataServer\Controllers\Api\TestSessionController;
use Basttyy\FxDataServer\Controllers\Api\UserController;
use Basttyy\FxDataServer\Controllers\Api\UserExplicitController;
use Basttyy\FxDataServer\Controllers\NotFoundController;

// ##################################################
// ##################################################
// ##################################################

call_user_func(new RequestLogController('create'));

/// Auth routes
post('/auth/login', new AuthController());
get('/auth/login', new AuthController('login_oauth'));
get('/auth/refresh-token', new AuthController('refresh_token'));
get('/auth/captcha', new CaptchaController());
post('/auth/captcha', new CaptchaController('validate'));
get('/auth/twofa/mode/$mode', new TwoFaController());
post('/auth/twofa/mode/$mode', new TwoFaController('validate'));
get('/auth/twofa/mode/$mode/status/$status', new TwoFaController('twofaonoff'));

/// User Routes
get('/users/$id', new UserController());
get('/users', new UserController('list'));
get('/users/query/$query', new UserController('list'));
post('/users', new UserController('create'));
put('/users/$id', new UserController('update'));
delete('/users/$id', new UserController('delete'));
/// User Special Routes
post('/users/method/$method', new UserExplicitController());

/// Plan Routes
get('/plans/$id', new PlanController());
get('/plans', new PlanController('list'));
get('/plans/query/$query', new PlanController('list'));
post('/plans', new PlanController('create'));
put('/plans/$id', new PlanController('update'));
delete('/plans/$id', new PlanController('delete'));

/// Subscription Routes
get('/subscriptions/$id', new SubscriptionController());
get('/subscriptions', new SubscriptionController('list'));
get('/subscriptions/all/count', new SubscriptionController('count'));
get('/subscriptions/all/count/$query', new SubscriptionController('count'));
get('/subscriptions/query/$query', new SubscriptionController('list'));
get('/subscriptions/palns/$id', new SubscriptionController('list_plan'));
post('/subscriptions', new SubscriptionController('create'));

/// Strategy Routes
get('/strategies/$id', new StrategyController());
get('/strategies', new StrategyController('list'));
get('/strategies/query/$query', new StrategyController('list'));
get('/strategies/users/$id', new StrategyController('list_user'));
post('/strategies', new StrategyController('create'));
put('/strategies/$id', new StrategyController('update'));
delete('/strategies/$id', new StrategyController('delete'));

/// TestSessions Routes
get('/test-sessions/$id', new TestSessionController());
get('/test-sessions', new TestSessionController('list'));
get('/test-sessions/query/$query', new TestSessionController('list'));
post('/test-sessions', new TestSessionController('create'));
put('/test-sessions/$id', new TestSessionController('update'));
delete('/test-sessions/$id', new TestSessionController('delete'));

/// Pairs Routes
get('/pairs/$id', new PairController());
get('/pairs', new PairController('list'));
get('/pairs/list/onlypair', new PairController('listonlypair'));
get('/pairs/list/pairorsym/$id/query/$query', new PairController('query'));
post('/pairs', new PairController('create'));
put('/pairs/$id', new PairController('update'));
delete('/pairs/$id', new PairController('delete'));

/// Positions Routes
get('/positions/$id', new PositionController());
get('/positions', new PositionController('list'));
get('/positions/query/$query', new PositionController('list'));
get('/positions/users/$id', new PositionController('list_user'));
post('/positions', new PositionController('create'));
put('/positions/$id', new PositionController('update'));
put('/positions/$id/unset/$tporsl', new PositionController('unsetslortp'));
delete('/positions/$id', new PositionController('delete'));

/// Feedbacks Routes
get('/feedbacks/$id', new FeedbackController());
get('/feedbacks', new FeedbackController('list'));
get('/feedbacks/query/$query', new FeedbackController('list'));
get('/feedbacks/users/$id', new FeedbackController('list_user'));
post('/feedbacks', new FeedbackController('create'));
put('/feedbacks/$id', new FeedbackController('update'));
delete('/feedbacks/$id', new FeedbackController('delete'));

/// Admin Routes
get('/migrate', new MigrateController);
get('/admin/logs/$id', new RequestLogController());
get('/admin/logs/all/count', new RequestLogController('count'));
get('/admin/logs/all/count/$query', new RequestLogController('count'));
get('/admin/logs', new RequestLogController('list'));
get('/admin/logs/query/$query', new RequestLogController('list'));
post('/admin/landing/data', new MiscellaneousController('update_landing'));

post('/admin/blog/posts', new BlogController('create'));
put('/admin/blog/posts/$id', new BlogController('update'));
delete('/admin/blog/posts/$id', new BlogController('delete'));

get('/blog/posts', new BlogController('list'));
get('/blog/posts/$id', new BlogController());

get('/blog/comments', new PostCommentController('listall'));
get('/blog/posts/$id/comments', new PostCommentController('list'));
get('/blog/posts/$id/comments/$id', new PostCommentController());
get('/blog/posts/$id/comments/query/$query', new PostCommentController('list'));
post('/blog/posts/$id/comments', new PostCommentController('create'));
put('/blog/posts/$id/comments/$id', new PostCommentController('update'));
delete('/blog/posts/$id/comments/$id', new PostCommentController('delete'));

// group('/admin', function() {
//     get('/blogs', new BlogController('list'));
//     get('/blogs/$id', new BlogController());
// });

/// Historical Data Routes
get('/fx/download/ticker/$ticker/from/$from/nums/$nums/faster/$faster', $downloadTickData);
get('/fx/candles/ticker/$ticker/from/$fro/nums/$num/timeframe/$timefram', $getTimeframeCandles);
// get('/fx/download/min/ticker/$ticker/period/$period/from/$from/incr/$incr/nums/$nums', new FxController());
get('/fx/download/min/ticker/$ticker/offerside/$offerside/period/$period/yr/$year/mn/$month/wk/$week', new FxHistoryController());
get('/fx/tickers/query/$query', new MiscellaneousController('search_ticker'));
get('/fx/tickers/query', new MiscellaneousController('search_ticker'));

/// Others
post('/misc/contact-us', new MiscellaneousController());
get('/landing/data', new MiscellaneousController('fetch_landing'));

any('/404', new NotFoundController);

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