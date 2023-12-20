<?php
use Basttyy\FxDataServer\libs\MysqlSessionHandler;
use Basttyy\FxDataServer\libs\Str;

if (strtolower($_SERVER["REQUEST_METHOD"]) !== "options") {
  // session_destroy();
  //if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_save_handler(new MysqlSessionHandler, true);
    session_start();
  //}
}

// function group(string $route, callable $method) {
//   call_user_func ($method);
// }

function get(string $route, callable|string ...$paths_to_include){
  if( $_SERVER['REQUEST_METHOD'] == 'GET' ){ route($route, ...$paths_to_include); }
}
function post(string $route, callable|string ...$paths_to_include){
  if( $_SERVER['REQUEST_METHOD'] == 'POST' ){ route($route, ...$paths_to_include); }   
}
function put(string $route, callable|string ...$paths_to_include){
  if( $_SERVER['REQUEST_METHOD'] == 'PUT' ){ route($route, ...$paths_to_include); }
}
function patch(string $route, callable|string ...$paths_to_include){
  if( $_SERVER['REQUEST_METHOD'] == 'PATCH' ){ route($route, ...$paths_to_include); }
}
function delete(string $route, callable|string ...$paths_to_include){
  if( $_SERVER['REQUEST_METHOD'] == 'DELETE' ){ route($route, ...$paths_to_include); }
}
function any(string $route, callable|string ...$paths_to_include){ route($route, ...$paths_to_include); }
function route(string $route, callable|string ...$paths_to_include){
  $len = count($paths_to_include);
  foreach ($paths_to_include as $path_to_include) {
    $len--;
    $callback = $path_to_include;
    if( !is_callable($callback) ){
      if(!strpos( "$path_to_include", '.php')){
        $path_to_include.='.php';
      }
    }    
    if($route == "/404"){
      if (is_callable($callback)) {
        call_user_func($callback, []);
        exit();
      }
      include_once __DIR__."/$path_to_include";
      exit();
    }
    $request_url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
    $request_url = rtrim($request_url, '/');
    $request_url = strtok($request_url, '?');
    $route_parts = explode('/', $route);
    $request_url_parts = explode('/', $request_url);
    array_shift($route_parts);
    array_shift($request_url_parts);
    if( $route_parts[0] == '' && count($request_url_parts) == 0 ){
      // Callback function
      if( is_callable($callback) ){
        call_user_func_array($callback, []);
        if ($len < 1)
          exit();
        else
          continue;
      }
      include_once __DIR__."/$path_to_include";
      if ($len < 1)
        exit();
      else
        continue;
    }
    if( count($route_parts) != count($request_url_parts) ){ return; }  
    $parameters = [];
    for( $__i__ = 0; $__i__ < count($route_parts); $__i__++ ){
      $route_part = $route_parts[$__i__];
      if( preg_match("/^[$]/", $route_part) ){
        $route_part = ltrim($route_part, '$');
        array_push($parameters, $request_url_parts[$__i__]);
        $$route_part=$request_url_parts[$__i__];
      }
      else if( $route_parts[$__i__] != $request_url_parts[$__i__] ){
        return;
      } 
    }
    // Callback function
    if( is_callable($callback) ){
      call_user_func_array($callback, $parameters);
      if ($len < 1)
        exit();
      else
        continue;
    }    
    include_once __DIR__."/$path_to_include";
  }

  exit();
}
function out($text, bool $strip_tags = false){
    if ($strip_tags)
        echo htmlspecialchars(strip_tags($text));
    else
        echo htmlspecialchars($text);
}
function set_csrf(){
  if( ! isset($_SESSION["csrf"]) ){ $_SESSION["csrf"] = bin2hex(random_bytes(50)); }
  echo '<input type="hidden" name="csrf" value="'.$_SESSION["csrf"].'">';
}
function is_csrf_valid(){
  if( ! isset($_SESSION['csrf']) || ! isset($_POST['csrf'])){ return false; }
  if( $_SESSION['csrf'] != $_POST['csrf']){ return false; }
  return true;
}