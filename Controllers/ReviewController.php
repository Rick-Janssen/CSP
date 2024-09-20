<?php
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
