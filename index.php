<?php
$controllers = glob('Controllers/*.php');

foreach ($controllers as $controller) {
    require_once $controller;
}

$routes = array(
    "GET" => array(
        "/csp-backend/products" => ["ProductController", "index"],
        "/csp-backend/product/(\d+)" => ["ProductController", "show"],

    ),
    "POST" => array(
        "/CSP-Backend/products" => ["ProductController", "store"],

        "/CSP-Backend/product/(\d+)/review" => ["ReviewController", "store"],

        "/CSP-Backend/login" => ["UserController", "login"],
        "/CSP-Backend/register" => ["UserController", "register"],
        "/CSP-Backend/logout" => ["UserController", "logout"]
    ),
    "PUT" => array(
        "/csp-backend/product/(\d+)" => ["ProductController", "update"],
    ),
    "DELETE" => array(
        "/csp-backend/product/(\d+)" => ["ProductController", "destroy"],
        "/csp-backend/product/(\d+)" => ["ProductController", "destroy"],

        "/csp-backend/product/(\d+)/review/(\d+)" => ["ReviewController", "destroy"]
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
