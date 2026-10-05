<?php

/* =========================================================
   THE KNOT HOUSE - PLACE ORDER
   Saves checkout order into MySQL database
   ========================================================= */

header("Content-Type: application/json; charset=UTF-8");


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

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit;

}


$conn->set_charset("utf8mb4");


/* =========================================================
   ONLY POST REQUEST ALLOWED
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    $conn->close();

    exit;
}


/* =========================================================
   GET JSON DATA FROM CHECKOUT
   ========================================================= */

$input = file_get_contents("php://input");


if (!$input) {

    echo json_encode([
        "success" => false,
        "message" => "No order data received."
    ]);

    $conn->close();

    exit;
}


$data = json_decode($input, true);


if (!is_array($data)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid order data."
    ]);

    $conn->close();

    exit;
}


/* =========================================================
   CUSTOMER INFORMATION
   ========================================================= */

$customer = $data["customer"] ?? [];

$customerName =
    trim($customer["name"] ?? "");

$customerEmail =
    trim($customer["email"] ?? "");

$customerPhone =
    trim($customer["phone"] ?? "");

$address =
    trim($customer["address"] ?? "");

$city =
    trim($customer["city"] ?? "");

$state =
    trim($customer["state"] ?? "");

$pincode =
    trim($customer["pincode"] ?? "");


/* =========================================================
   ORDER INFORMATION
   ========================================================= */

$orderInfo = $data["orderInfo"] ?? [];

$paymentMethod =
    trim(
        $orderInfo["paymentMethod"]
        ?? "Cash on Delivery"
    );


/* =========================================================
   ITEMS
   ========================================================= */

$items = $data["items"] ?? [];


/* =========================================================
   BASIC VALIDATION
   ========================================================= */

if (
    $customerName === "" ||
    $customerEmail === "" ||
    $customerPhone === "" ||
    $address === "" ||
    $city === "" ||
    $state === "" ||
    $pincode === ""
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Please provide all required customer and address details."
    ]);

    $conn->close();

    exit;
}


if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email address."
    ]);

    $conn->close();

    exit;
}


if (!preg_match("/^[6-9][0-9]{9}$/", $customerPhone)) {

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid 10-digit phone number."
    ]);

    $conn->close();

    exit;
}


if (!preg_match("/^[0-9]{6}$/", $pincode)) {

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid 6-digit pincode."
    ]);

    $conn->close();

    exit;
}


if (!is_array($items) || count($items) === 0) {

    echo json_encode([
        "success" => false,
        "message" => "Your cart is empty."
    ]);

    $conn->close();

    exit;
}


/* =========================================================
   START DATABASE TRANSACTION
   ========================================================= */

$conn->begin_transaction();


try {

    /* =====================================================
       CALCULATE TOTAL FROM DATABASE
       ===================================================== */

    $subtotal = 0;

    $verifiedItems = [];


    /* =====================================================
       PRODUCT QUERY
       ===================================================== */

    $productStmt = $conn->prepare(
        "SELECT
            id,
            product_name,
            price,
            stock,
            status
         FROM products
         WHERE id = ?
         LIMIT 1"
    );


    if (!$productStmt) {

        throw new Exception(
            "Unable to prepare product query."
        );

    }


    /* =====================================================
       CHECK EVERY CART ITEM
       ===================================================== */

    foreach ($items as $item) {

        $productId =
            intval(
                $item["product_id"]
                ?? $item["id"]
                ?? 0
            );


        $quantity =
            intval(
                $item["quantity"]
                ?? 1
            );


        if ($productId <= 0) {

            throw new Exception(
                "Invalid product selected."
            );

        }


        if ($quantity <= 0) {

            throw new Exception(
                "Invalid product quantity."
            );

        }


        /* =================================================
           GET PRODUCT FROM DATABASE
           ================================================= */

        $productStmt->bind_param(
            "i",
            $productId
        );


        $productStmt->execute();


        $result =
            $productStmt->get_result();


        if ($result->num_rows === 0) {

            throw new Exception(
                "One of the products in your cart no longer exists."
            );

        }


        $product =
            $result->fetch_assoc();


        /* =================================================
           CHECK PRODUCT STATUS
           ================================================= */

        if (
            strtolower(
                $product["status"]
            ) !== "active"
        ) {

            throw new Exception(
                $product["product_name"] .
                " is currently unavailable."
            );

        }


        /* =================================================
           CHECK STOCK
           ================================================= */

        $stock =
            intval($product["stock"]);


        if ($quantity > $stock) {

            throw new Exception(
                "Only " .
                $stock .
                " item(s) of " .
                $product["product_name"] .
                " are available."
            );

        }


        /* =================================================
           USE DATABASE PRICE
           ================================================= */

        $price =
            floatval($product["price"]);


        $itemSubtotal =
            $price * $quantity;


        $subtotal +=
            $itemSubtotal;


        $verifiedItems[] = [

            "product_id" =>
                $productId,

            "product_name" =>
                $product["product_name"],

            "price" =>
                $price,

            "quantity" =>
                $quantity,

            "subtotal" =>
                $itemSubtotal

        ];

    }


    $productStmt->close();


    /* =====================================================
       FIXED DELIVERY CHARGE
       ===================================================== */

    $deliveryCharge = 60;


    /* =====================================================
       FINAL TOTAL
       ===================================================== */

    $totalAmount =
        $subtotal + $deliveryCharge;


    /* =====================================================
       ORDER STATUS
       ===================================================== */

    $paymentStatus =
        "Pending";

    $orderStatus =
        "Pending";


    /* =====================================================
       INSERT ORDER
       ===================================================== */

    $orderStmt = $conn->prepare(
        "INSERT INTO orders
        (
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
            order_status
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )"
    );


    if (!$orderStmt) {

        throw new Exception(
            "Unable to prepare order query."
        );

    }


    $orderStmt->bind_param(
        "sssssssdsss",
        $customerName,
        $customerEmail,
        $customerPhone,
        $address,
        $city,
        $state,
        $pincode,
        $totalAmount,
        $paymentMethod,
        $paymentStatus,
        $orderStatus
    );


    if (!$orderStmt->execute()) {

        throw new Exception(
            "Unable to save order."
        );

    }


    /* =====================================================
       GET NEW ORDER ID
       ===================================================== */

    $orderId =
        $conn->insert_id;


    $orderStmt->close();


    /* =====================================================
       INSERT ORDER ITEMS
       ===================================================== */

    $itemStmt = $conn->prepare(
        "INSERT INTO order_items
        (
            order_id,
            product_id,
            product_name,
            price,
            quantity,
            subtotal
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?
        )"
    );


    if (!$itemStmt) {

        throw new Exception(
            "Unable to prepare order item query."
        );

    }


    foreach ($verifiedItems as $verifiedItem) {

        $productId =
            $verifiedItem["product_id"];

        $productName =
            $verifiedItem["product_name"];

        $price =
            $verifiedItem["price"];

        $quantity =
            $verifiedItem["quantity"];

        $itemSubtotal =
            $verifiedItem["subtotal"];


        $itemStmt->bind_param(
            "iisdid",
            $orderId,
            $productId,
            $productName,
            $price,
            $quantity,
            $itemSubtotal
        );


        if (!$itemStmt->execute()) {

            throw new Exception(
                "Unable to save order items."
            );

        }

    }


    $itemStmt->close();


    /* =====================================================
       REDUCE PRODUCT STOCK
       ===================================================== */

    $stockStmt = $conn->prepare(
        "UPDATE products
         SET stock = stock - ?
         WHERE id = ?
         AND stock >= ?"
    );


    if (!$stockStmt) {

        throw new Exception(
            "Unable to prepare stock update."
        );

    }


    foreach ($verifiedItems as $verifiedItem) {

        $productId =
            $verifiedItem["product_id"];

        $quantity =
            $verifiedItem["quantity"];


        $stockStmt->bind_param(
            "iii",
            $quantity,
            $productId,
            $quantity
        );


        if (!$stockStmt->execute()) {

            throw new Exception(
                "Unable to update product stock."
            );

        }


        if ($stockStmt->affected_rows === 0) {

            throw new Exception(
                "Product stock changed. Please try again."
            );

        }

    }


    $stockStmt->close();


    /* =====================================================
       EVERYTHING SUCCESSFUL
       ===================================================== */

    $conn->commit();


    echo json_encode([

        "success" => true,

        "message" =>
            "Order placed successfully.",

        "order_id" =>
            $orderId,

        "subtotal" =>
            number_format(
                $subtotal,
                2,
                ".",
                ""
            ),

        "delivery_charge" =>
            number_format(
                $deliveryCharge,
                2,
                ".",
                ""
            ),

        "total_amount" =>
            number_format(
                $totalAmount,
                2,
                ".",
                ""
            )

    ]);

} catch (Exception $e) {


    /* =====================================================
       ROLLBACK IF ANYTHING FAILS
       ===================================================== */

    $conn->rollback();


    echo json_encode([

        "success" => false,

        "message" =>
            $e->getMessage()

    ]);

}


$conn->close();

?>