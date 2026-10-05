<?php

session_start();

/* =========================================================
   ADMIN ACCESS CHECK
   ========================================================= */

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}


/* =========================================================
   DATABASE CONNECTION
   ========================================================= */

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

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


/* =========================================================
   CHECK PRODUCT ID
   ========================================================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: products.php");
    exit();
}

$product_id = (int) $_GET["id"];


/* =========================================================
   GET PRODUCT INFORMATION
   ========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        product_name,
        image
    FROM products
    WHERE id = ?
");

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();
    $conn->close();

    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   DELETE PRODUCT
   ========================================================= */

$stmt = $conn->prepare("
    DELETE FROM products
    WHERE id = ?
");

$stmt->bind_param(
    "i",
    $product_id
);


if ($stmt->execute()) {

    $stmt->close();

    /* =====================================================
       DELETE PRODUCT IMAGE FROM SERVER
       ===================================================== */

    if (!empty($product["image"])) {

        $image_path = $product["image"];

        /*
         * Database image path normally looks like:
         *
         * ../images/crochet-gajra-12345.jpg
         *
         * Since this PHP file is inside /admin,
         * the path can be used directly.
         */

        if (strpos($image_path, "../") === 0) {

            $full_image_path =
                __DIR__ . "/" . $image_path;

            if (file_exists($full_image_path)) {
                unlink($full_image_path);
            }

        }
    }


    $conn->close();


    /* =====================================================
       SUCCESS REDIRECT
       ===================================================== */

    header(
        "Location: products.php?deleted=1"
    );

    exit();

}


/* =========================================================
   DELETE FAILED
   ========================================================= */

$error_message = $stmt->error;

$stmt->close();
$conn->close();

echo "
<!DOCTYPE html>
<html lang='en'>
<head>

<meta charset='UTF-8'>

<meta
    name='viewport'
    content='width=device-width, initial-scale=1.0'
>

<title>Delete Product | The Knot House</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f8f1ef;

    font-family: Arial, sans-serif;

    color: #513d3d;

    padding: 20px;
}

.box {
    width: 100%;
    max-width: 500px;

    background: #fffaf7;

    border: 1px solid #eadbd8;

    border-radius: 18px;

    padding: 35px;

    text-align: center;

    box-shadow:
        0 10px 30px rgba(81, 61, 61, 0.08);
}

.icon {
    font-size: 50px;

    margin-bottom: 15px;
}

h1 {
    color: #8d555e;

    margin-bottom: 10px;
}

p {
    color: #806969;

    line-height: 1.6;

    margin-bottom: 25px;
}

a {
    display: inline-block;

    padding: 12px 22px;

    background: #a96771;

    color: white;

    text-decoration: none;

    border-radius: 10px;

    font-weight: bold;
}

a:hover {
    background: #8d555e;
}

</style>

</head>

<body>

<div class='box'>

    <div class='icon'>⚠️</div>

    <h1>Unable to Delete Product</h1>

    <p>
        Something went wrong while deleting the product.
        Please try again.
    </p>

    <a href='products.php'>
        ← Back to Products
    </a>

</div>

</body>
</html>
";

?>