<?php
require_once 'DBconn.php';

class UserController
{
    public function register()
    {
        $conn = connectToDatabase();

        $input = json_decode(file_get_contents('php://input'), true);

        if (isset($input['name'], $input['email'], $input['password'])) {
            $name = $input['name'];
            $email = $input['email'];
            $rawPassword = $input['password'];
            $password = password_hash($rawPassword, PASSWORD_DEFAULT);

            $sql = "INSERT INTO `users` (name, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $name, $email, $password);

            if ($stmt->execute()) {
                // User successfully registered, now auto-login
                $userId = $stmt->insert_id;

                // Generate a token for automatic login
                $token = bin2hex(random_bytes(32));

                // Store the token in the database
                $updateTokenSql = "UPDATE users SET token = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateTokenSql);
                $updateStmt->bind_param("si", $token, $userId);
                $updateStmt->execute();

                // Return the token so the frontend can log the user in
                echo json_encode(["message" => "User registered and logged in successfully", "token" => $token]);

                $updateStmt->close();
            } else {
                echo json_encode(["message" => "Failed to register user"]);
            }

            $stmt->close();
        } else {
            echo json_encode(["message" => "Missing values"]);
        }

        $conn->close();
    }

    public function login()
    {
        $conn = connectToDatabase();

        $input = json_decode(file_get_contents('php://input'), true);

        if (isset($input['email'], $input['password'])) {
            $email = $input['email'];
            $rawPassword = $input['password'];


            $sql = "SELECT id, password FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();


                if (password_verify($rawPassword, $user['password'])) {

                    $token = bin2hex(random_bytes(32));


                    $updateTokenSql = "UPDATE users SET token = ? WHERE id = ?";
                    $updateStmt = $conn->prepare($updateTokenSql);
                    $updateStmt->bind_param("si", $token, $user['id']);
                    $updateStmt->execute();


                    echo json_encode(["message" => "Login successful", "token" => $token]);
                } else {
                    echo json_encode(["message" => "Invalid email or password"]);
                }
            } else {
                echo json_encode(["message" => "Invalid email or password"]);
            }

            $stmt->close();
        } else {
            echo json_encode(["message" => "Email and password are required"]);
        }

        $conn->close();
    }

    public function authenticate()
    {
        $conn = connectToDatabase();
        $headers = apache_request_headers();

        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);

            $sql = "SELECT id FROM users WHERE token = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Token is valid, return success
                echo json_encode(["message" => null]); // Indicate that the token is valid
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Invalid token"]);
            }

            $stmt->close();
        } else {
            http_response_code(401);
            echo json_encode(["message" => "No token provided"]);
        }

        $conn->close();
    }
    public function logout()
    {
        $conn = connectToDatabase();

        $headers = apache_request_headers();

        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);

            $sql = "SELECT id FROM users WHERE token = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $user = $result->fetch_assoc();
                $updateSql = "UPDATE users SET token = NULL WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("i", $user['id']);
                $updateStmt->execute();

                echo json_encode(["message" => "Logged out successfully"]);
            } else {

                http_response_code(401);
                echo json_encode(["message" => "Invalid token"]);
            }

            $stmt->close();
        } else {

            http_response_code(401);
            echo json_encode(["message" => "No token provided"]);
        }

        $conn->close();
    }
}
