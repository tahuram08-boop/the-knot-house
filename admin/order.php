<?php
session_start();

/* =========================================================
   ADMIN LOGIN CHECK
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
   ADMIN INFORMATION
   ========================================================= */

$adminName = $_SESSION["admin_name"] ?? "Admin";

$adminInitial = strtoupper(
    substr(trim($adminName), 0, 1)
);

if ($adminInitial === "") {
    $adminInitial = "A";
}


/* =========================================================
   GET ORDER ID
   ========================================================= */

$orderId = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;


/* =========================================================
   CHECK ORDER ID
   ========================================================= */

if ($orderId <= 0) {
    die("Invalid order ID.");
}


/* =========================================================
   FETCH ORDER
   ========================================================= */

$order = null;

$stmt = $conn->prepare(
    "SELECT * FROM orders WHERE id = ? LIMIT 1"
);

if (!$stmt) {
    die(
        "Database query error: " .
        $conn->error
    );
}

$stmt->bind_param(
    "i",
    $orderId
);

$stmt->execute();

$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {

    $order = $result->fetch_assoc();

} else {

    $stmt->close();
    $conn->close();

    die("Order #" . $orderId . " was not found.");
}

$stmt->close();


/* =========================================================
   FETCH ORDERED PRODUCTS
   ========================================================= */

$orderItems = [];

$itemStmt = $conn->prepare(
    "SELECT 
        id,
        product_id,
        product_name,
        price,
        quantity,
        subtotal
     FROM order_items
     WHERE order_id = ?
     ORDER BY id ASC"
);

if ($itemStmt) {

    $itemStmt->bind_param(
        "i",
        $orderId
    );

    $itemStmt->execute();

    $itemsResult = $itemStmt->get_result();

    if ($itemsResult) {

        while ($item = $itemsResult->fetch_assoc()) {

            $orderItems[] = $item;
        }
    }

    $itemStmt->close();
}


/* =========================================================
   CLOSE DATABASE
   ========================================================= */

$conn->close();


/* =========================================================
   ORDER VALUES
   ========================================================= */

$customerName =
    $order["customer_name"]
    ?? "Customer";

$customerEmail =
    $order["customer_email"]
    ?? "";

$customerPhone =
    $order["customer_phone"]
    ?? "";

$customerAddress =
    $order["customer_address"]
    ?? "";

$totalAmount =
    (float)(
        $order["total_amount"]
        ?? 0
    );

$paymentMethod =
    $order["payment_method"]
    ?? "Cash on Delivery";

$paymentStatus =
    $order["payment_status"]
    ?? "Pending";

$orderStatus =
    $order["order_status"]
    ?? "Pending";

$createdAt =
    $order["created_at"]
    ?? "";


/* =========================================================
   DATE FORMAT
   ========================================================= */

$formattedDate = "";

if ($createdAt !== "") {

    $time = strtotime($createdAt);

    if ($time !== false) {

        $formattedDate = date(
            "d M Y, h:i A",
            $time
        );
    }
}


/* =========================================================
   STATUS CLASS
   ========================================================= */

$statusClass = "pending";

switch (strtolower($orderStatus)) {

    case "confirmed":
        $statusClass = "confirmed";
        break;

    case "processing":
        $statusClass = "processing";
        break;

    case "shipped":
        $statusClass = "shipped";
        break;

    case "delivered":
        $statusClass = "delivered";
        break;

    case "cancelled":
        $statusClass = "cancelled";
        break;

    default:
        $statusClass = "pending";
}


/* =========================================================
   PAYMENT CLASS
   ========================================================= */

$paymentClass = "pending";

$paymentLower =
    strtolower($paymentStatus);

if (
    $paymentLower === "paid" ||
    $paymentLower === "completed"
) {

    $paymentClass = "paid";

} elseif (
    $paymentLower === "failed"
) {

    $paymentClass = "failed";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Order #<?php echo $orderId; ?> | The Knot House
    </title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        :root {

            --pink: #a96771;
            --dark-pink: #8d555e;

            --light-pink: #f8e8e7;
            --soft-pink: #f3d8d9;

            --cream: #fffaf7;
            --white: #ffffff;

            --brown: #513d3d;
            --brown-light: #806969;

            --border: #eadbd8;

            --green: #4d9569;
            --green-light: #e8f5ed;

            --orange: #c88942;
            --orange-light: #fff3df;

            --red: #b85c5c;
            --red-light: #fbeaea;

            --blue: #6387a9;
            --blue-light: #edf4fa;

            --shadow:
                0 8px 30px rgba(81, 61, 61, 0.06);

            --sidebar-width: 255px;
        }


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            font-family: "DM Sans", sans-serif;

            background: var(--cream);

            color: var(--brown);

            min-height: 100vh;
        }


        a {
            text-decoration: none;
            color: inherit;
        }


        /* =====================================================
           SIDEBAR
           ===================================================== */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;
            bottom: 0;

            width: var(--sidebar-width);

            background: var(--white);

            border-right: 1px solid var(--border);

            z-index: 1000;

            display: flex;

            flex-direction: column;
        }


        .brand {

            height: 82px;

            display: flex;

            align-items: center;

            padding: 0 22px;

            border-bottom: 1px solid var(--border);
        }


        .brand-icon {

            width: 43px;
            height: 43px;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    var(--pink),
                    var(--dark-pink)
                );

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 22px;

            color: white;

            margin-right: 12px;
        }


        .brand-title {

            font-family:
                "Playfair Display", serif;

            font-size: 18px;

            font-weight: 700;
        }


        .brand-subtitle {

            font-size: 10px;

            letter-spacing: 1.5px;

            font-weight: 700;

            color: var(--pink);

            margin-top: 5px;
        }


        .sidebar-content {

            flex: 1;

            padding: 22px 14px;
        }


        .nav-title {

            padding: 0 12px;

            margin-bottom: 9px;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1.5px;

            text-transform: uppercase;

            color: #a38e8e;
        }


        .nav-link {

            display: flex;

            align-items: center;

            gap: 12px;

            min-height: 45px;

            padding: 10px 12px;

            margin-bottom: 4px;

            border-radius: 11px;

            color: var(--brown-light);

            font-size: 14px;

            font-weight: 600;

            transition: 0.2s ease;
        }


        .nav-link:hover,
        .nav-link.active {

            background: var(--light-pink);

            color: var(--dark-pink);
        }


        .nav-icon {

            width: 25px;

            display: inline-flex;

            justify-content: center;

            font-size: 17px;
        }


        .sidebar-bottom {

            padding: 15px;

            border-top: 1px solid var(--border);
        }


        .admin-mini {

            display: flex;

            align-items: center;

            padding: 11px;

            border-radius: 13px;

            background: var(--cream);

            border: 1px solid var(--border);
        }


        .admin-avatar {

            width: 39px;
            height: 39px;

            border-radius: 50%;

            background: var(--pink);

            color: white;

            display: flex;

            align-items: center;
            justify-content: center;

            font-weight: 700;

            margin-right: 10px;
        }


        .admin-info {
            flex: 1;
            min-width: 0;
        }


        .admin-name {

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .admin-role {

            font-size: 11px;

            color: var(--brown-light);

            margin-top: 2px;
        }


        .logout {

            color: var(--red);

            font-size: 17px;
        }


        /* =====================================================
           MAIN
           ===================================================== */

        .main {

            margin-left: var(--sidebar-width);

            min-height: 100vh;
        }


        .topbar {

            height: 76px;

            background: rgba(255, 250, 247, 0.95);

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 30px;

            position: sticky;

            top: 0;

            z-index: 100;
        }


        .topbar h2 {

            font-family:
                "Playfair Display", serif;

            font-size: 23px;
        }


        .topbar p {

            color: var(--brown-light);

            font-size: 12px;

            margin-top: 2px;
        }


        .top-admin {

            display: flex;

            align-items: center;

            gap: 9px;
        }


        .top-avatar {

            width: 37px;
            height: 37px;

            border-radius: 50%;

            background: var(--pink);

            color: white;

            display: flex;

            align-items: center;
            justify-content: center;

            font-weight: 700;
        }


        .content {

            padding: 30px;
        }


        /* =====================================================
           BACK BUTTON
           ===================================================== */

        .back-btn {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 9px 14px;

            border: 1px solid var(--border);

            border-radius: 9px;

            background: var(--white);

            color: var(--brown);

            font-size: 12px;

            font-weight: 700;

            margin-bottom: 20px;

            transition: 0.2s ease;
        }


        .back-btn:hover {

            background: var(--light-pink);

            color: var(--dark-pink);
        }


        /* =====================================================
           PAGE TITLE
           ===================================================== */

        .page-title {

            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;
        }


        .page-title h1 {

            font-family:
                "Playfair Display", serif;

            font-size: 31px;
        }


        .page-title p {

            color: var(--brown-light);

            font-size: 13px;

            margin-top: 6px;
        }


        /* =====================================================
           STATUS
           ===================================================== */

        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 8px 13px;

            border-radius: 9px;

            font-size: 11px;

            font-weight: 700;
        }


        .status-badge.pending {

            background: var(--orange-light);

            color: var(--orange);
        }


        .status-badge.confirmed,
        .status-badge.processing {

            background: var(--blue-light);

            color: var(--blue);
        }


        .status-badge.shipped {

            background: var(--light-pink);

            color: var(--dark-pink);
        }


        .status-badge.delivered {

            background: var(--green-light);

            color: var(--green);
        }


        .status-badge.cancelled {

            background: var(--red-light);

            color: var(--red);
        }


        /* =====================================================
           GRID
           ===================================================== */

        .details-grid {

            display: grid;

            grid-template-columns:
                1.4fr
                1fr;

            gap: 20px;
        }


        .card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 15px;

            box-shadow: var(--shadow);

            overflow: hidden;
        }


        .card-header {

            padding: 17px 20px;

            border-bottom: 1px solid var(--border);
        }


        .card-header h3 {

            font-family:
                "Playfair Display", serif;

            font-size: 18px;
        }


        .card-header p {

            color: var(--brown-light);

            font-size: 11px;

            margin-top: 3px;
        }


        .card-body {

            padding: 20px;
        }


        /* =====================================================
           CUSTOMER INFO
           ===================================================== */

        .info-row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 12px 0;

            border-bottom: 1px solid #f0e5e2;
        }


        .info-row:last-child {

            border-bottom: none;
        }


        .info-label {

            color: var(--brown-light);

            font-size: 11px;

            font-weight: 600;
        }


        .info-value {

            text-align: right;

            font-size: 12px;

            font-weight: 700;

            max-width: 65%;

            word-break: break-word;
        }


        /* =====================================================
           PAYMENT
           ===================================================== */

        .payment-badge {

            display: inline-flex;

            padding: 6px 10px;

            border-radius: 7px;

            font-size: 10px;

            font-weight: 700;
        }


        .payment-badge.paid {

            background: var(--green-light);

            color: var(--green);
        }


        .payment-badge.pending {

            background: var(--orange-light);

            color: var(--orange);
        }


        .payment-badge.failed {

            background: var(--red-light);

            color: var(--red);
        }


        /* =====================================================
           TOTAL
           ===================================================== */

        .total-box {

            background: var(--light-pink);

            border-radius: 12px;

            padding: 17px;

            margin-top: 15px;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .total-label {

            font-size: 12px;

            font-weight: 700;
        }


        .total-value {

            font-size: 22px;

            font-weight: 700;

            color: var(--dark-pink);
        }


        /* =====================================================
           ADDRESS
           ===================================================== */

        .address-box {

            background: var(--cream);

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: 13px;

            font-size: 12px;

            line-height: 1.6;

            color: var(--brown-light);

            margin-top: 10px;
        }


        /* =====================================================
           ORDERED PRODUCTS
           ===================================================== */

        .products-card {

            margin-top: 20px;
        }


        .product-list {

            display: flex;

            flex-direction: column;

            gap: 12px;
        }


        .product-item {

            display: grid;

            grid-template-columns:
                1fr
                auto
                auto
                auto;

            align-items: center;

            gap: 20px;

            padding: 16px;

            background: var(--cream);

            border: 1px solid var(--border);

            border-radius: 12px;
        }


        .product-info {

            min-width: 0;
        }


        .product-name {

            font-size: 14px;

            font-weight: 700;

            color: var(--brown);
        }


        .product-id {

            font-size: 10px;

            color: var(--brown-light);

            margin-top: 4px;
        }


        .product-detail {

            text-align: right;

            min-width: 70px;
        }


        .product-detail-label {

            display: block;

            font-size: 10px;

            color: var(--brown-light);

            margin-bottom: 4px;
        }


        .product-detail-value {

            font-size: 12px;

            font-weight: 700;

            color: var(--brown);
        }


        .product-subtotal {

            color: var(--dark-pink);

            font-size: 13px;

            font-weight: 700;
        }


        .no-products {

            padding: 25px;

            text-align: center;

            background: var(--cream);

            border: 1px dashed var(--border);

            border-radius: 10px;

            color: var(--brown-light);

            font-size: 12px;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 900px) {

            :root {
                --sidebar-width: 0px;
            }


            .sidebar {

                position: relative;

                width: 100%;

                height: auto;
            }


            .main {

                margin-left: 0;
            }


            .details-grid {

                grid-template-columns: 1fr;
            }


            .topbar {

                padding: 0 18px;
            }


            .content {

                padding: 20px 16px;
            }


            .product-item {

                grid-template-columns: 1fr 1fr;

                gap: 12px;
            }


            .product-detail {

                text-align: left;
            }
        }


        @media (max-width: 600px) {

            .page-title {

                align-items: flex-start;

                flex-direction: column;
            }


            .info-row {

                flex-direction: column;

                gap: 5px;
            }


            .info-value {

                text-align: left;

                max-width: 100%;
            }


            .product-item {

                grid-template-columns: 1fr;
            }


            .product-detail {

                text-align: left;
            }
        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<aside class="sidebar">


    <div class="brand">

        <div class="brand-icon">
            🧶
        </div>

        <div>

            <div class="brand-title">
                The Knot House
            </div>

            <div class="brand-subtitle">
                ADMIN PANEL
            </div>

        </div>

    </div>


    <div class="sidebar-content">

        <div class="nav-title">
            Main Menu
        </div>


        <a
            href="dashboard.php"
            class="nav-link"
        >

            <span class="nav-icon">
                🏠
            </span>

            Dashboard

        </a>


        <a
            href="products.php"
            class="nav-link"
        >

            <span class="nav-icon">
                🛍️
            </span>

            Products

        </a>


        <a
            href="orders.php"
            class="nav-link active"
        >

            <span class="nav-icon">
                📦
            </span>

            Orders

        </a>


        <a
            href="message.php"
            class="nav-link"
        >

            <span class="nav-icon">
                💌
            </span>

            Messages

        </a>


        <br>


        <div class="nav-title">
            Management
        </div>


        <a
            href="products.php"
            class="nav-link"
        >

            <span class="nav-icon">
                📊
            </span>

            Inventory

        </a>


        <a
            href="../index.html"
            class="nav-link"
            target="_blank"
        >

            <span class="nav-icon">
                👁️
            </span>

            View Store

        </a>


        <a
            href="add-product.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ➕
            </span>

            Add Product

        </a>

    </div>


    <div class="sidebar-bottom">

        <div class="admin-mini">

            <div class="admin-avatar">

                <?php
                echo htmlspecialchars($adminInitial);
                ?>

            </div>


            <div class="admin-info">

                <div class="admin-name">

                    <?php
                    echo htmlspecialchars($adminName);
                    ?>

                </div>

                <div class="admin-role">
                    Administrator
                </div>

            </div>


            <a
                href="logout.php"
                class="logout"
                title="Logout"
            >
                ↪
            </a>

        </div>

    </div>

</aside>



<!-- =========================================================
     MAIN
     ========================================================= -->

<main class="main">


    <header class="topbar">

        <div>

            <h2>
                Order Details
            </h2>

            <p>
                View customer order information
            </p>

        </div>


        <div class="top-admin">

            <div class="top-avatar">

                <?php
                echo htmlspecialchars($adminInitial);
                ?>

            </div>

            <strong>

                <?php
                echo htmlspecialchars($adminName);
                ?>

            </strong>

        </div>

    </header>



    <section class="content">


        <a
            href="orders.php"
            class="back-btn"
        >

            ← Back to Orders

        </a>


        <div class="page-title">

            <div>

                <h1>
                    Order #<?php echo $orderId; ?>
                </h1>

                <p>
                    Placed on
                    <?php
                    echo htmlspecialchars(
                        $formattedDate
                    );
                    ?>
                </p>

            </div>


            <span
                class="status-badge <?php
                    echo $statusClass;
                ?>"
            >

                ●

                <?php
                echo htmlspecialchars(
                    $orderStatus
                );
                ?>

            </span>

        </div>



        <div class="details-grid">


            <!-- =================================================
                 CUSTOMER DETAILS
                 ================================================= -->

            <div class="card">

                <div class="card-header">

                    <h3>
                        Customer Information
                    </h3>

                    <p>
                        Customer contact and delivery details
                    </p>

                </div>


                <div class="card-body">


                    <div class="info-row">

                        <span class="info-label">
                            Customer Name
                        </span>

                        <span class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $customerName
                            );
                            ?>

                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Email
                        </span>

                        <span class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $customerEmail
                            );
                            ?>

                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Phone
                        </span>

                        <span class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $customerPhone
                            );
                            ?>

                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Payment Method
                        </span>

                        <span class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $paymentMethod
                            );
                            ?>

                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Payment Status
                        </span>

                        <span class="info-value">

                            <span
                                class="payment-badge <?php
                                    echo $paymentClass;
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $paymentStatus
                                );
                                ?>

                            </span>

                        </span>

                    </div>


                    <div class="info-label">
                        Delivery Address
                    </div>


                    <div class="address-box">

                        <?php if (
                            $customerAddress !== ""
                        ): ?>

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $customerAddress
                                )
                            );
                            ?>

                        <?php else: ?>

                            Address not available.

                        <?php endif; ?>

                    </div>


                </div>

            </div>



            <!-- =================================================
                 ORDER SUMMARY
                 ================================================= -->

            <div class="card">

                <div class="card-header">

                    <h3>
                        Order Summary
                    </h3>

                    <p>
                        Payment and order total
                    </p>

                </div>


                <div class="card-body">


                    <div class="info-row">

                        <span class="info-label">
                            Order ID
                        </span>

                        <span class="info-value">

                            #<?php
                            echo $orderId;
                            ?>

                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Order Date
                        </span>

                        <span class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $formattedDate
                            );
                            ?>

                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Order Status
                        </span>

                        <span class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $orderStatus
                            );
                            ?>

                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Payment Method
                        </span>

                        <span class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $paymentMethod
                            );
                            ?>

                        </span>

                    </div>


                    <div class="total-box">

                        <span class="total-label">
                            Total Amount
                        </span>

                        <span class="total-value">

                            ₹<?php
                            echo number_format(
                                $totalAmount,
                                2
                            );
                            ?>

                        </span>

                    </div>


                </div>

            </div>


        </div>


        <!-- =====================================================
             ORDERED PRODUCTS
             ===================================================== -->

        <div class="card products-card">

            <div class="card-header">

                <h3>
                    🛍️ Ordered Products
                </h3>

                <p>
                    Products included in this order
                </p>

            </div>


            <div class="card-body">

                <?php if (count($orderItems) > 0): ?>

                    <div class="product-list">

                        <?php foreach (
                            $orderItems
                            as $item
                        ): ?>

                            <div class="product-item">


                                <!-- PRODUCT NAME -->

                                <div class="product-info">

                                    <div class="product-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $item["product_name"]
                                            ?? "Product"
                                        );
                                        ?>

                                    </div>

                                    <div class="product-id">

                                        Product ID:
                                        <?php
                                        echo htmlspecialchars(
                                            $item["product_id"]
                                            ?? "N/A"
                                        );
                                        ?>

                                    </div>

                                </div>


                                <!-- PRICE -->

                                <div class="product-detail">

                                    <span class="product-detail-label">
                                        Price
                                    </span>

                                    <span class="product-detail-value">

                                        ₹<?php
                                        echo number_format(
                                            (float)(
                                                $item["price"]
                                                ?? 0
                                            ),
                                            2
                                        );
                                        ?>

                                    </span>

                                </div>


                                <!-- QUANTITY -->

                                <div class="product-detail">

                                    <span class="product-detail-label">
                                        Quantity
                                    </span>

                                    <span class="product-detail-value">

                                        <?php
                                        echo (int)(
                                            $item["quantity"]
                                            ?? 0
                                        );
                                        ?>

                                    </span>

                                </div>


                                <!-- SUBTOTAL -->

                                <div class="product-detail">

                                    <span class="product-detail-label">
                                        Subtotal
                                    </span>

                                    <span class="product-subtotal">

                                        ₹<?php
                                        echo number_format(
                                            (float)(
                                                $item["subtotal"]
                                                ?? 0
                                            ),
                                            2
                                        );
                                        ?>

                                    </span>

                                </div>


                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="no-products">

                        No products were found for this order.

                    </div>

                <?php endif; ?>


            </div>

        </div>


    </section>

</main>


</body>

</html>
