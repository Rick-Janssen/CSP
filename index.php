<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Send the headers that indicate that the POST request will be accepted
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200); // Send OK status for preflight
    exit(); // Terminate further execution as this is a preflight request
}
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
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
        "/csp-backend/authenticate" => ["UserController", "authenticate"],
        "/csp-backend/login" => ["UserController", "login"],
        "/csp-backend/register" => ["UserController", "register"],
        "/csp-backend/logout" => ["UserController", "logout"]
    ),
    "PUT" => array(
        "/csp-backend/product/(\d+)" => [
            "ProductController",
            "update",
            "AuthMiddleware",
            "checkAdmin"
        ],
    ),
    "DELETE" => array(
        "/csp-backend/product/(\d+)" => [
            "ProductController",
            "destroy",
            "AuthMiddleware",
            "checkAdmin"
        ],


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
