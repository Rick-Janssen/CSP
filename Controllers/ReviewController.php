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

        function callApi($method, $url, $data)
        {

            $env = parse_ini_file('.env');

            $apiKey = $env['API_KEY'];

            $curl = curl_init();
            switch (strtoupper($method)) {
                case "POST":
                    curl_setopt($curl, CURLOPT_POST, 1);
                    if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                case "PUT":
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                    if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                default:
                    if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
            }

            // OPTIONS:
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer " . $apiKey,
                'Content-Type: application/json',
            ));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            // EXECUTE:
            $result = curl_exec($curl);
            if (!$result) {
                die("Connection Failure");
            }
            curl_close($curl);
            return $result;
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

            // Moderate content using OpenAI API
            $data_array = array(
                "input" => $content
            );

            // Call the Moderation API
            $make_call = callApi('POST', 'https://api.openai.com/v1/moderations', json_encode($data_array));
            $response = json_decode($make_call, true);

            // Check if the content is flagged
            $is_flagged = isset($response['results'][0]['flagged']) ? $response['results'][0]['flagged'] : false;

            if ($is_flagged) {
                echo json_encode(["message" => "The content contains inappropriate material and cannot be posted."]);
            } else {
                // Prepare SQL query, allowing user_id and user_name to be NULL (for anonymous users)
                $sql = "
                    INSERT INTO reviews (product_id, user_id, content, rating)
                    VALUES (?, ?, ?, ?)
                ";

                $stmt = $conn->prepare($sql);

                // Bind parameters, with user_id and user_name potentially being NULL
                $stmt->bind_param("iisd", $product_id, $user_id, $content, $rating);

                if ($stmt->execute()) {
                    echo json_encode(["message" => "Review added successfully"]);
                } else {
                    echo json_encode(["message" => "Failed to add review"]);
                }

                $stmt->close();
            }
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
