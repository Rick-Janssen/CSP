<?php
function connectToDatabase()
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "products";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

function executeQuery($conn, $sql)
{
    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(array("error" => $conn->error));
        return null;
    }

    return $result;
}
