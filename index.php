<?php
$controllers = glob('Controllers/*.php');

foreach ($controllers as $controller) {
    require_once $controller;
}
require_once 'AuthMiddleware.php';

$routes = array(
    "GET" => array(
        "/csp-backend/products" => ["ProductController", "index"],
        "/csp-backend/product/(\d+)" => ["ProductController", "show"],

    ),
    "POST" => array(
        "/csp-backend/products" => [
            "ProductController",
            "store",
            "AuthMiddleware",
            "checkAdmin"
        ],

        "/csp-backend/product/(\d+)/review" => ["ReviewController", "store"],

        // USERS
        "/csp-backend/login" => ["UserController", "login"],
        "/csp-backend/register" => ["UserController", "register"],
        "/csp-backend/logout" => ["UserController", "logout"]
    ),
    "PUT" => array(
        "/csp-backend/product/(\d+)" => ["ProductController", "update"],
    ),
    "DELETE" => array(
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

            if (isset($function[2]) && isset($function[3])) {

                $middleware = new $function[2]();
                call_user_func([$middleware, $function[3]]);
            }

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