<?php
require_once 'DBconn.php';

class AuthMiddleware
{
    public static function checkAdmin()
    {

        $headers = getallheaders();
        $authToken = isset($headers['Authorization']) ? trim($headers['Authorization']) : '';

        if (empty($authToken) || !preg_match('/^Bearer\s+(.*)$/', $authToken, $matches)) {
            http_response_code(401);
            echo "Unauthorized. Please provide a valid token.";
            exit();
        }

        $token = $matches[1];


        $user = self::getUserByToken($token);

        if ($user === null) {
            http_response_code(401);
            echo "Unauthorized. Invalid token.";
            exit();
        }

        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo "Forbidden. Admin access required.";
            exit();
        }
    }


    private static function getUserByToken($token)
    {
        // Database connection
        $conn = connectToDatabase();

        // Prepare query to check if the token matches a user
        $sql = "SELECT * FROM users WHERE token = ? LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token); // Bind the token as a string

        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc(); // Fetch a single user as an associative array
    }
}
