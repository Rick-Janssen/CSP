<?php
require_once 'DBconn.php';

class ProductController
{
    public function index()
    {
        $conn = connectToDatabase();

        $sql = "SELECT * FROM `products`";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($rows);
        } else {
            echo json_encode(array("message" => "No products found with ratings"));
        }

        $stmt->close();
        $conn->close();
    }
}
