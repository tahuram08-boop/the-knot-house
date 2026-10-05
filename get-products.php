<?php

header("Content-Type: application/json; charset=UTF-8");

$host = "localhost";
$username = "root";
$password = "";
$database = "knot_house";


/* =========================================================
   CONNECT TO DATABASE
========================================================= */

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);


/* =========================================================
   CHECK CONNECTION
========================================================= */

if ($conn->connect_error) {

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit();

}


/* =========================================================
   SET UTF-8
========================================================= */

$conn->set_charset("utf8mb4");


/* =========================================================
   CHECK PRODUCTS TABLE
========================================================= */

$tableCheck = $conn->query(
    "SHOW TABLES LIKE 'products'"
);


if (!$tableCheck || $tableCheck->num_rows === 0) {

    echo json_encode([
        "success" => false,
        "message" => "Products table does not exist."
    ]);

    $conn->close();

    exit();

}


/* =========================================================
   GET ACTIVE PRODUCTS
========================================================= */

$sql = "
    SELECT
        id,
        product_name,
        category,
        price,
        description,
        image,
        stock,
        status,
        created_at
    FROM products
    WHERE status = 'Active'
    ORDER BY id DESC
";


$result = $conn->query($sql);


if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to fetch products."
    ]);

    $conn->close();

    exit();

}


/* =========================================================
   CREATE PRODUCT ARRAY
========================================================= */

$products = [];


while ($row = $result->fetch_assoc()) {

    $products[] = [

        "id" => (int)$row["id"],

        "product_name" =>
            $row["product_name"],

        "category" =>
            $row["category"],

        "price" =>
            (float)$row["price"],

        "description" =>
            $row["description"] ?? "",

        "image" =>
            $row["image"] ?? "",

        "stock" =>
            (int)$row["stock"],

        "status" =>
            $row["status"]

    ];

}


/* =========================================================
   SEND RESPONSE
========================================================= */

echo json_encode([

    "success" => true,

    "count" => count($products),

    "products" => $products

], JSON_UNESCAPED_UNICODE);


$conn->close();

?>