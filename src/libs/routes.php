<?php

require_once __DIR__.'\\router.php';

require_once __DIR__."\\..\\Controllers\\Api\\Auth\\AuthController.php";
require_once __DIR__.'\\..\\TickController.php';
require_once __DIR__.'\\..\\MinuteController.php';

use Basttyy\FxDataServer\Controllers\Api\Auth\AuthController;
use Basttyy\FxDataServer\Controllers\Api\Auth\CaptchaController;
use Basttyy\FxDataServer\Controllers\Api\Auth\TwoFaController;

// ##################################################
// ##################################################
// ##################################################

/// Auth routes
post('/api/login', new AuthController());
get('/api/refresh-token', new AuthController('refresh_token'));
get('/api/auth/captcha', new CaptchaController());
post('/api/auth/captcha', new CaptchaController('validate'));
get('/api/auth/twofa/$mode', new TwoFaController());
post('/api/auth/twofa/$mode', new TwoFaController('validate'));

get('/download/ticker/$ticker/from/$from/nums/$nums/faster/$faster', $downloadTickData);
get('/candles/ticker/$ticker/from/$fro/nums/$num/timeframe/$timefram', $getTimeframeCandles);
get('/download/min/ticker/$ticker/from/$from/incr/$incr/nums/$nums', $downloadMinuteData);
get('/tickers/query/$query', $searchTicker);
get('/tickers/query', $searchTicker);

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