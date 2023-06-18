<?php

require_once __DIR__.'/router.php';

require_once __DIR__."/../Controllers/Api/Auth/AuthController.php";
require_once __DIR__.'/../TickController.php';
require_once __DIR__.'/../MinuteController.php';

use Basttyy\FxDataServer\Controllers\Api\Auth\AuthController;
use Basttyy\FxDataServer\Controllers\Api\Auth\CaptchaController;
use Basttyy\FxDataServer\Controllers\Api\Auth\TwoFaController;
use Basttyy\FxDataServer\Controllers\Api\MigrateController;
use Basttyy\FxDataServer\Controllers\Api\MiscellaneousController;
use Basttyy\FxDataServer\Controllers\Api\PairController;
use Basttyy\FxDataServer\Controllers\Api\PlanController;
use Basttyy\FxDataServer\Controllers\Api\PositionController;
use Basttyy\FxDataServer\Controllers\Api\RequestLogController;
use Basttyy\FxDataServer\Controllers\Api\StrategyController;
use Basttyy\FxDataServer\Controllers\Api\TestSessionController;
use Basttyy\FxDataServer\Controllers\Api\UserController;
use Basttyy\FxDataServer\Controllers\Api\UserExplicitController;
use Basttyy\FxDataServer\Controllers\NotFoundController;
use Basttyy\FxDataServer\libs\MysqlSessionHandler;

// ##################################################
// ##################################################
// ##################################################

if (strtolower($_SERVER['REQUEST_METHOD']) !== "options") {
  session_destroy();
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_save_handler(new MysqlSessionHandler, true);
    session_start();
  }
}

call_user_func(new RequestLogController('create'));

/// frontend route
get('/', function () {
  header('Content-Type: text/html', true, 200);
  echo file_get_contents($_SERVER["DOCUMENT_ROOT"]."/public/index.html");
  return true;
});

/// Auth routes
post('/api/login', new AuthController());
get('/api/login', new AuthController('login_oauth'));
get('/api/refresh-token', new AuthController('refresh_token'));
get('/api/auth/captcha', new CaptchaController());
post('/api/auth/captcha', new CaptchaController('validate'));
get('/api/auth/twofa/$mode', new TwoFaController());
post('/api/auth/twofa/$mode', new TwoFaController('validate'));

/// User Routes
get('/api/users/$id', new UserController());
get('/api/users', new UserController('list'));
get('/api/users/query/$query', new UserController('list'));
post('/api/users', new UserController('create'));
put('/api/users/$id', new UserController('update'));
delete('/api/users/$id', new UserController('delete'));
/// User Special Routes
post('/api/users/method/$method', new UserExplicitController());

/// Plan Routes
get('/api/plans/$id', new PlanController());
get('/api/plans', new PlanController('list'));
get('/api/plans/query/$query', new PlanController('list'));
post('/api/plans', new PlanController('create'));
put('/api/plans/$id', new PlanController('update'));
delete('/api/plans/$id', new PlanController('delete'));

/// Strategy Routes
get('/api/strategies/$id', new StrategyController());
get('/api/strategies', new StrategyController('list'));
get('/api/strategies/query/$query', new StrategyController('list'));
get('/api/strategies/users/$id', new StrategyController('list_user'));
post('/api/strategies', new StrategyController('create'));
put('/api/strategies/$id', new StrategyController('update'));
delete('/api/strategies/$id', new StrategyController('delete'));

/// TestSessions Routes
get('/api/test-sessions/$id', new TestSessionController());
get('/api/test-sessions', new TestSessionController('list'));
get('/api/test-sessions/query/$query', new TestSessionController('list'));
post('/api/test-sessions', new TestSessionController('create'));
put('/api/test-sessions/$id', new TestSessionController('update'));
delete('/api/test-sessions/$id', new TestSessionController('delete'));

/// Pairs Routes
get('/api/pairs/$id', new PairController());
get('/api/pairs', new PairController('list'));
get('/api/pairs/query', new PairController('list'));
post('/api/pairs', new PairController('create'));
put('/api/pairs/$id', new PairController('update'));
delete('/api/pairs/$id', new PairController('delete'));

/// Positions Routes
get('/api/positions/$id', new PositionController());
get('/api/positions', new PositionController('list'));
get('/api/positions/query', new PositionController('list'));
get('/api/positions/users/$id', new PositionController('list_user'));
post('/api/positions', new PositionController('create'));
put('/api/positions/$id', new PositionController('update'));
delete('/api/positions/$id', new PositionController('delete'));

/// Admin Routes
get('/api/migrate', new MigrateController);
get('/api/admin/logs/$id', new RequestLogController());
get('/api/admin/logs', new RequestLogController('list'));

/// Historical Data Routes
get('/api/download/ticker/$ticker/from/$from/nums/$nums/faster/$faster', $downloadTickData);
get('/api/candles/ticker/$ticker/from/$fro/nums/$num/timeframe/$timefram', $getTimeframeCandles);
get('/api/download/min/ticker/$ticker/from/$from/incr/$incr/nums/$nums', $downloadMinuteData);
get('/api/tickers/query/$query', $searchTicker);
get('/api/tickers/query', $searchTicker);

/// Others
post('/api/misc/contact-us', new MiscellaneousController('contact-us'));

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