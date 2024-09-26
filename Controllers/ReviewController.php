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

        // If no token is provided, return an error message
        if (!$token) {
            echo json_encode(["message" => "Authorization token missing. You must be logged in to post a review."]);
            http_response_code(401);
            return;
        }

        // Verify the provided token and get user info
        $user = verifyToken($token);

        // If the token is invalid or the user is not found, return an error message
        if (!$user) {
            echo json_encode(["message" => "Invalid or expired token. Please log in again."]);
            http_response_code(401);
            return;
        }

        // Ensure the content and rating fields are present in the request
        if (isset($data['content'], $data['rating'])) {
            $content = $data['content'];
            $rating = $data['rating'];
            $user_id = $user['id']; // Use the verified user ID

            // Prepare SQL query to insert the review
            $sql = "
        INSERT INTO reviews (product_id, user_id, content, rating)
        VALUES (?, ?, ?, ?)
        ";

            $stmt = $conn->prepare($sql);

            // Bind parameters
            $stmt->bind_param("iisd", $product_id, $user_id, $content, $rating);

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
