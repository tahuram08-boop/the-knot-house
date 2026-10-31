<?php

/* =========================================================
   THE KNOT HOUSE
   TRACK ORDER BACKEND
   ========================================================= */


/* ---------------------------------------------------------
   DATABASE CONNECTION
   --------------------------------------------------------- */

$host = "localhost";
$username = "root";
$password = "";
$database = "knot_house";


$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);


/* Check connection */

if ($conn->connect_error) {

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $conn->connect_error
    ]);

    exit;

}


/* Set character encoding */

$conn->set_charset("utf8mb4");


/* ---------------------------------------------------------
   GET ORDER ID AND EMAIL
   --------------------------------------------------------- */

$order_id = isset($_GET["order_id"])
    ? trim($_GET["order_id"])
    : "";

$email = isset($_GET["email"])
    ? trim($_GET["email"])
    : "";


/* ---------------------------------------------------------
   VALIDATE INPUT
   --------------------------------------------------------- */

if ($order_id === "" || $email === "") {

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "Please enter your Order ID and Email Address."
    ]);

    exit;

}


/* Make sure Order ID is numeric */

if (!is_numeric($order_id)) {

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "Invalid Order ID."
    ]);

    exit;

}


$order_id = (int)$order_id;


/* ---------------------------------------------------------
   FIND ORDER
   --------------------------------------------------------- */

$sql = "
    SELECT
        id,
        customer_name,
        customer_email,
        customer_phone,
        address,
        city,
        state,
        pincode,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        created_at
    FROM orders
    WHERE id = ?
    AND LOWER(customer_email) = LOWER(?)
    LIMIT 1
";


$stmt = $conn->prepare($sql);


/* Check prepared statement */

if (!$stmt) {

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare order search."
    ]);

    exit;

}


$stmt->bind_param(
    "is",
    $order_id,
    $email
);


$stmt->execute();


$result = $stmt->get_result();


/* ---------------------------------------------------------
   ORDER NOT FOUND
   --------------------------------------------------------- */

if ($result->num_rows === 0) {

    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "No order was found with this Order ID and Email Address."
    ]);

    $stmt->close();
    $conn->close();

    exit;

}


/* ---------------------------------------------------------
   GET ORDER
   --------------------------------------------------------- */

$order = $result->fetch_assoc();


$stmt->close();


/* ---------------------------------------------------------
   GET ORDER ITEMS
   --------------------------------------------------------- */

$items = [];


/*
   Your order_items table should contain:

   order_id
   product_name
   quantity
   price
   image

   If your table does not have image,
   the code below automatically handles it.
*/


$itemSql = "
    SELECT *
    FROM order_items
    WHERE order_id = ?
";


$itemStmt = $conn->prepare($itemSql);


if ($itemStmt) {

    $itemStmt->bind_param(
        "i",
        $order_id
    );

    $itemStmt->execute();

    $itemResult =
        $itemStmt->get_result();


    while (
        $item = $itemResult->fetch_assoc()
    ) {

        $items[] = [

            "product_name" =>
                isset($item["product_name"])
                ? $item["product_name"]
                : "Crochet Product",

            "quantity" =>
                isset($item["quantity"])
                ? $item["quantity"]
                : 1,

            "price" =>
                isset($item["price"])
                ? $item["price"]
                : 0,

            "image" =>
                isset($item["image"])
                ? $item["image"]
                : ""

        ];

    }


    $itemStmt->close();

}


/* ---------------------------------------------------------
   ADD ITEMS TO ORDER
   --------------------------------------------------------- */

$order["items"] = $items;


/* ---------------------------------------------------------
   RETURN JSON
   --------------------------------------------------------- */

header("Content-Type: application/json");


echo json_encode([
    "success" => true,
    "message" => "Order found successfully.",
    "order" => $order
]);


/* ---------------------------------------------------------
   CLOSE DATABASE
   --------------------------------------------------------- */

$conn->close();

?>