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
    public function show($id)
    {
        $conn = connectToDatabase();

        $sql = "SELECT * FROM `products` WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode($row);
        } else {
            echo json_encode(array("message" => "No products found with ID $id"));
        }

        $stmt->close();
        $conn->close();
    }
    public function store()
    {
        $conn = connectToDatabase();

        $input = json_decode(file_get_contents('php://input'), true);


        if (isset($input['name']) && !empty($input['name'])) {
            $name = $input['name'];

            $sql = "INSERT INTO `products` (name) VALUES (?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $name);

            if ($stmt->execute()) {
                echo json_encode(array("message" => "Product added successfully"));
            } else {
                echo json_encode(array("message" => "Failed to add product"));
            }

            $stmt->close();
        } else {
            echo json_encode(array("message" => "Invalid input: 'name' is required"));
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
