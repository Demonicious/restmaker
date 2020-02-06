<?php require_once "vendor/autoload.php";
include_once("components/API.php");
$config = json_decode(file_get_contents("app/config/config.json"));
$groups = array();
$gs = array_diff(scandir($config->apis_path), array('.', '..'));
foreach($gs as $group) {
    $groups[$group] = array_diff(scandir($config->apis_path.'/'.$group), array('.', '..'));
}
if(strtolower($config->env) == 'production') error_reporting(0);
else error_reporting(E_ALL);
$GLOBALS['uri_base'] = $_SERVER["PHP_SELF"].'/';
$GLOBALS['routes'] = array();
if($config->hide_script) { $GLOBALS['uri_base'] = str_replace(basename(__FILE__).'/', '', $GLOBALS['uri_base']); }
foreach($groups as $index => $group) {
    foreach($group as $method) {
        $path = $config->apis_path.'/'.$index.'/'.$method;
        include_once($path);
        $classname = str_replace('.php', '', $method);
        $method_obj = new $classname;
        if($method_obj->group != '') $method_obj->group .= '/';
        $GLOBALS['uri_base'] = str_replace($method_obj->group.$method_obj->route.'/', '', $GLOBALS['uri_base']);
        $GLOBALS['routes'][$method_obj->group.$method_obj->route] = array(
            'class' => $method_obj,
            'path' => $path,
        );
    }
}

function dispatch_handler($vars, $headers, $method, $full_uri) {
    $route = $GLOBALS['routes'][$method];
    $api_method = $route['class'];
    return $api_method->handle($vars, $headers, $full_uri);
}

$req_dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $router) {
    foreach($GLOBALS['routes'] as $index => $route) {
        $router->addRoute($route['class']->method, $GLOBALS['uri_base'].$index, 'dispatch_handler');
    }
});
$httpMethod = $_SERVER['REQUEST_METHOD'];
$full_uri = $_SERVER['REQUEST_URI'];
$uri = explode("?", $full_uri)[0];
if($uri[strlen($uri)-1] == '/' || ($uri[strlen($uri)-1] == '\\')) $uri = substr($uri, 0, -1);
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);
$routeInfo = $req_dispatcher->dispatch($httpMethod, $uri);
$reporter = new ErrorReporter();
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $reporter->emitNotFound($httpMethod, $full_uri);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        $reporter->emitBadRequest($httpMethod, $full_uri);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        $handler($vars, getallheaders(), str_replace($GLOBALS['uri_base'], '', $uri), $full_uri);
        break;
}
