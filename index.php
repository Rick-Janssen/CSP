<?php
$controllers = glob('Controllers/*.php');

foreach ($controllers as $controller) {
    require_once $controller;
}

$routes = array(
    "/CSPBackend/products" => ["ProductController", "index"],
    "/CSPBackend/product/(\d+)" => ["ProductController", "show"],


);

$request_uri = $_SERVER['REQUEST_URI'];

foreach ($routes as $route => $function) {
    if (preg_match("~^$route$~", $request_uri, $matches)) {
        array_shift($matches);
        $controllerInstance = new $function[0]();
        call_user_func_array([$controllerInstance, $function[1]], $matches);
        exit();
    }
}

http_response_code(404);
echo "404 Not Found";
