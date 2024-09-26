<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Send the headers that indicate that the POST request will be accepted
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200); // Send OK status for preflight
    exit(); // Terminate further execution as this is a preflight request
}
// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'DBconn.php';

class ReviewController
{

    public function store($product_id)
    {
        function verifyToken($token)
        {
            // Example token validation logic (replace this with actual implementation)
            $conn = connectToDatabase();
            $sql = "SELECT id, name FROM users WHERE token = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                return $result->fetch_assoc(); // Return user data
            }

            return null; // Token is invalid or user not found
        }

        $conn = connectToDatabase();

        $data = json_decode(file_get_contents('php://input'), true);

        // Check for Authorization header with Bearer token
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

        // Default to NULL for user_id and user_name (anonymous review)
        $user_id = null;
        $user_name = null;

        // If a token is provided, attempt to verify it and get user info
        if ($token) {
            $user = verifyToken($token); // Verify token and fetch user details

            if ($user) {
                // Assign user_id and user_name from the verified token
                $user_id = $user['id'];
                $user_name = $user['name'];
            }
        }

        // Ensure the content and rating fields are present in the request
        if (isset($data['content'], $data['rating'])) {
            $content = $data['content'];
            $rating = $data['rating'];

            // Prepare SQL query, allowing user_id and user_name to be NULL (for anonymous users)
            $sql = "
        INSERT INTO reviews (product_id, user_id, user_name, content, rating)
        VALUES (?, ?, ?, ?, ?)
        ";

            $stmt = $conn->prepare($sql);

            // Bind parameters, with user_id and user_name potentially being NULL
            $stmt->bind_param("iissd", $product_id, $user_id, $user_name, $content, $rating);

            if ($stmt->execute()) {
                echo json_encode(["message" => "Review added successfully"]);
            } else {
                echo json_encode(["message" => "Failed to add review"]);
            }

            $stmt->close();
        } else {
            echo json_encode(["message" => "Invalid input, content, or rating"]);
        }

        $conn->close();
    }


    public function destroy($product_id, $review_id)
    {
        $conn = connectToDatabase();

        $sql = "
        DELETE FROM reviews 
        WHERE id = ? AND product_id = ?
    ";

        $stmt = $conn->prepare($sql);


        $stmt->bind_param("ii", $review_id, $product_id);


        if ($stmt->execute()) {

            if ($stmt->affected_rows > 0) {
                echo json_encode(["message" => "Review deleted successfully"]);
            } else {
                echo json_encode(["message" => "No review found to delete"]);
            }
        } else {
            echo json_encode(["message" => "Failed to delete review"]);
        }

        $stmt->close();
        $conn->close();
    }
}
