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
        $conn = connectToDatabase();

        $data = json_decode(file_get_contents('php://input'), true);


        if (isset($data['title'], $data['content'], $data['rating'])) {

            $title = $data['title'];
            $content = $data['content'];
            $rating = $data['rating'];


            $sql = "
            INSERT INTO reviews (product_id, title, content, rating)
            VALUES (?, ?, ?, ?)
        ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issi", $product_id, $title, $content, $rating);

            if ($stmt->execute()) {
                echo json_encode(["message" => "Review added successfully"]);
            } else {
                echo json_encode(["message" => "Failed to add review"]);
            }

            $stmt->close();
        } else {

            echo json_encode(["message" => "Invalid input, missing title, content, or rating"]);
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
