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

class ProductController
{
    public function index()
    {
        $conn = connectToDatabase();

        $sql = "
        SELECT p.id as product_id, p.description, p.image_url, p.origin, p.type ,  p.name product_name, r.id as review_id, r.user_id, r.rating, r.content
        FROM products p
        LEFT JOIN reviews r ON p.id = r.product_id
    ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];

        while ($row = $result->fetch_assoc()) {
            $productId = $row['product_id'];

            if (!isset($products[$productId])) {
                $products[$productId] = [
                    'id' => $row['product_id'],
                    'name' => $row['product_name'],
                    'description' => $row['description'],
                    'image_url' => $row['image_url'],
                    'origin' => $row['origin'],
                    'type' => $row['type'],
                    'reviews' => []
                ];
            }

            if ($row['review_id'] !== null) {
                $products[$productId]['reviews'][] = [
                    'id' => $row['review_id'],
                    'user_id' => $row['user_id'],
                    'content' => $row['content'],
                    'rating' => $row['rating'],
                ];
            }
        }

        $products = array_values($products);

        if (!empty($products)) {
            echo json_encode(value: $products);
        } else {
            echo json_encode(["message" => "No products or reviews found"]);
        }

        $stmt->close();
        $conn->close();
    }

    public function show($product_id)
    {
        $conn = connectToDatabase();

        $sql = "
    SELECT p.id as product_id, p.name, p.description, p.image_url, p.origin, p.type, 
           r.id as review_id, r.rating, r.user_id, r.content, 
           u.name as user_name
    FROM products p
    LEFT JOIN reviews r ON p.id = r.product_id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE p.id = ?
";



        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $product = null;
        $reviews = [];

        while ($row = $result->fetch_assoc()) {
            if (!$product) {
                $product = [
                    'id' => $row['product_id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'image_url' => $row['image_url'],
                    'origin' => $row['origin'],
                    'type' => $row['type'],
                    'reviews' => []
                ];
            }

            if ($row['review_id'] !== null) {
                $reviews[] = [
                    'id' => $row['review_id'],
                    'user_id' => $row['user_id'],
                    'user_name' => $row['user_name'],
                    'content' => $row['content'],
                    'rating' => $row['rating'],
                ];
            }
        }

        if ($product) {
            $product['reviews'] = $reviews;
            echo json_encode($product);
        } else {
            echo json_encode(["message" => "Product not found"]);
        }

        $stmt->close();
        $conn->close();
    }


    public function store()
    {
        $conn = connectToDatabase();


        $input = json_decode(file_get_contents('php://input'), true);


        if (
            isset($input['name'], $input['description'], $input['image_url'], $input['origin'], $input['type']) &&
            !empty($input['name']) &&
            !empty($input['description']) &&
            !empty($input['image_url']) &&
            !empty($input['origin']) &&
            !empty($input['type'])
        ) {


            $name = $input['name'];
            $description = $input['description'];
            $image_url = $input['image_url'];
            $origin = $input['origin'];
            $type = $input['type'];


            $sql = "INSERT INTO `products` (name, description, image_url, origin, type) VALUES (?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $name, $description, $image_url, $origin, $type);


            if ($stmt->execute()) {
                echo json_encode(array("message" => "Product added successfully"));
            } else {
                echo json_encode(array("message" => "Failed to add product"));
            }

            $stmt->close();
        } else {
            echo json_encode(array("message" => "Invalid input: All fields are required"));
        }

        $conn->close();
    }

    public function update($id)
    {
        $conn = connectToDatabase();

        $input = json_decode(file_get_contents('php://input'), true);

        if (isset($input['name']) && !empty($input['name'])) {
            $name = $input['name'];

            $sql = "UPDATE `products` SET name = ? WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $name, $id);

            if ($stmt->execute()) {
                echo json_encode(array("message" => "Product with ID $id updated successfully"));
            } else {
                echo json_encode(array("message" => "Failed to update product with ID $id"));
            }

            $stmt->close();
        } else {
            echo json_encode(array("message" => "Invalid input: 'name' is required"));
        }

        $conn->close();
    }

    public function destroy($id)
    {
        $conn = connectToDatabase();

        $sql = "DELETE FROM `products` WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(array("message" => "Product with ID $id deleted successfully"));
        } else {
            echo json_encode(array("message" => "Failed to delete product with ID $id"));
        }

        $stmt->close();
        $conn->close();
    }
}
