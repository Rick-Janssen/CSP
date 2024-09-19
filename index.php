<?php
$controllers = glob('Controllers/*.php');

foreach ($controllers as $controller) {
    require_once $controller;
}

$routes = array(
    "GET" => array(
        "/CSP-Backend/products" => ["ProductController", "index"],
        "/CSP-Backend/product/(\d+)" => ["ProductController", "show"],

    ),
    "POST" => array(
        "/CSP-Backend/products" => ["ProductController", "store"],
    ),
    "PUT" => array(
        "/CSP-Backend/product/(\d+)" => ["ProductController", "update"],
    ),
    "DELETE" => array(
        "/CSP-Backend/product/(\d+)" => ["ProductController", "destroy"],
    ),



);

$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

if (isset($routes[$request_method])) {
    foreach ($routes[$request_method] as $route => $function) {
        if (preg_match("~^$route$~", $request_uri, $matches)) {
            array_shift($matches);

            $controllerName = $function[0];
            $methodName = $function[1];
            $controllerInstance = new $controllerName();
            call_user_func_array([$controllerInstance, $methodName], $matches);
            exit();
        }
    }
}

http_response_code(404);
echo "404 Not Found";
