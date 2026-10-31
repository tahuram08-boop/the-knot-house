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
$adminEmail = $_SESSION["admin_email"] ?? "";

$adminInitial = strtoupper(
    substr(trim($adminName), 0, 1)
);

if ($adminInitial === "") {
    $adminInitial = "A";
}


/* =========================================================
   DEFAULT DASHBOARD VALUES
   ========================================================= */

$totalProducts = 0;
$activeProducts = 0;
$lowStockProducts = 0;

$totalOrders = 0;
$pendingOrders = 0;
$processingOrders = 0;
$deliveredOrders = 0;

$totalRevenue = 0;

$recentOrders = [];
$lowStockItems = [];

$productsTableExists = false;
$ordersTableExists = false;


/* =========================================================
   CHECK PRODUCTS TABLE
   ========================================================= */

$tableCheck = $conn->query(
    "SHOW TABLES LIKE 'products'"
);

if ($tableCheck && $tableCheck->num_rows > 0) {
    $productsTableExists = true;
}


/* =========================================================
   CHECK ORDERS TABLE
   ========================================================= */

$tableCheck = $conn->query(
    "SHOW TABLES LIKE 'orders'"
);

if ($tableCheck && $tableCheck->num_rows > 0) {
    $ordersTableExists = true;
}


/* =========================================================
   PRODUCT STATISTICS
   ========================================================= */

if ($productsTableExists) {

    /* TOTAL PRODUCTS */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM products
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $totalProducts = (int)$row["total"];
    }


    /* ACTIVE PRODUCTS */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM products
        WHERE LOWER(status) = 'active'
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $activeProducts = (int)$row["total"];
    }


    /* LOW STOCK */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM products
        WHERE stock <= 5
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $lowStockProducts = (int)$row["total"];
    }


    /* LOW STOCK PRODUCTS */

    $result = $conn->query("
        SELECT
            id,
            product_name,
            category,
            price,
            stock,
            image
        FROM products
        WHERE stock <= 5
        ORDER BY stock ASC, id DESC
        LIMIT 5
    ");

    if ($result) {

        while ($row = $result->fetch_assoc()) {

            $lowStockItems[] = $row;
        }
    }
}


/* =========================================================
   ORDER STATISTICS
   ========================================================= */

if ($ordersTableExists) {

    /* TOTAL ORDERS */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM orders
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $totalOrders = (int)$row["total"];
    }


    /* PENDING ORDERS */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM orders
        WHERE LOWER(order_status) = 'pending'
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $pendingOrders = (int)$row["total"];
    }


    /* PROCESSING ORDERS */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM orders
        WHERE LOWER(order_status)
        IN ('confirmed', 'processing', 'shipped')
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $processingOrders = (int)$row["total"];
    }


    /* DELIVERED ORDERS */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM orders
        WHERE LOWER(order_status) = 'delivered'
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $deliveredOrders = (int)$row["total"];
    }


    /* =====================================================
       TOTAL REVENUE

       Cancelled orders are excluded.
       ===================================================== */

    $result = $conn->query("
        SELECT COALESCE(
            SUM(total_amount),
            0
        ) AS revenue
        FROM orders
        WHERE LOWER(order_status) != 'cancelled'
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $totalRevenue = (float)$row["revenue"];
    }


    /* =====================================================
       RECENT ORDERS
       ===================================================== */

    $result = $conn->query("
        SELECT
            id,
            customer_name,
            customer_email,
            total_amount,
            payment_status,
            order_status,
            created_at
        FROM orders
        ORDER BY id DESC
        LIMIT 6
    ");

    if ($result) {

        while ($row = $result->fetch_assoc()) {

            $recentOrders[] = $row;
        }
    }
}


/* =========================================================
   CLOSE DATABASE
   ========================================================= */

$conn->close();

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
        Dashboard | The Knot House Admin
    </title>


    <!-- =====================================================
         GOOGLE FONTS
         ===================================================== -->

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

        /* =====================================================
           ROOT COLORS
           SAME AS PRODUCTS + ORDERS
           ===================================================== */

        :root {

            --pink: #a96771;
            --dark-pink: #8d555e;

            --light-pink: #f8e8e7;
            --soft-pink: #f3d8d9;

            --cream: #fffaf7;
            --cream-dark: #f6eee9;

            --brown: #513d3d;
            --brown-light: #806969;

            --white: #ffffff;

            --border: #eadbd8;

            --green: #4d9569;
            --green-light: #e8f5ed;

            --orange: #c88942;
            --orange-light: #fff3df;

            --red: #b85c5c;
            --red-light: #fbeaea;

            --blue: #6387a9;
            --blue-light: #edf4fa;

            --sidebar-width: 255px;

            --shadow:
                0 8px 30px rgba(81, 61, 61, 0.06);

            --shadow-hover:
                0 12px 35px rgba(81, 61, 61, 0.10);
        }


        /* =====================================================
           RESET
           ===================================================== */

        * {

            margin: 0;
            padding: 0;

            box-sizing: border-box;
        }


        html {

            scroll-behavior: smooth;
        }


        body {

            font-family: "DM Sans", sans-serif;

            background: var(--cream);

            color: var(--brown);

            min-height: 100vh;

            overflow-x: hidden;
        }


        a {

            text-decoration: none;

            color: inherit;
        }


        button {

            font-family: inherit;
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

            display: flex;

            flex-direction: column;

            z-index: 1000;

            transition:
                transform 0.3s ease;

            box-shadow:
                5px 0 25px rgba(81, 61, 61, 0.03);
        }


        /* =====================================================
           BRAND
           ===================================================== */

        .sidebar-brand {

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

            font-size: 30px;

            color: white;

            margin-right: 12px;

            box-shadow:
                0 6px 15px rgba(169, 103, 113, 0.22);
        }


        .brand-text {

            line-height: 1.1;
        }


        .brand-title {

            font-family:
                "Playfair Display", serif;

            font-size: 25px;

            font-weight: 700;

            color: var(--brown);
        }


        .brand-subtitle {

            font-size: 10px;

            letter-spacing: 1.5px;

            font-weight: 700;

            color: var(--pink);

            margin-top: 5px;
        }


        /* =====================================================
           SIDEBAR CONTENT
           ===================================================== */

        .sidebar-content {

            flex: 1;

            overflow-y: auto;

            padding: 22px 14px;
        }


        .nav-section {

            margin-bottom: 25px;
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

            width: 100%;

            min-height: 45px;

            padding: 10px 12px;

            margin-bottom: 4px;

            border-radius: 11px;

            color: var(--brown-light);

            font-size: 14px;

            font-weight: 600;

            transition:
                background 0.25s ease,
                color 0.25s ease,
                transform 0.25s ease;
        }


        .nav-link:hover {

            background: var(--light-pink);

            color: var(--dark-pink);

            transform: translateX(2px);
        }


        .nav-link.active {

            background: var(--light-pink);

            color: var(--dark-pink);

            font-weight: 700;

            box-shadow:
                inset 3px 0 0 var(--pink);
        }


        .nav-icon {

            width: 25px;

            display: inline-flex;

            justify-content: center;

            align-items: center;

            font-size: 17px;
        }


        /* =====================================================
           SIDEBAR BOTTOM
           ===================================================== */

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

            font-size: 14px;

            font-weight: 700;

            margin-right: 10px;

            flex-shrink: 0;
        }


        .admin-info {

            min-width: 0;

            flex: 1;
        }


        .admin-name {

            font-size: 13px;

            font-weight: 700;

            color: var(--brown);

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .admin-role {

            font-size: 11px;

            color: var(--brown-light);

            margin-top: 2px;
        }


        .logout-link {

            width: 32px;
            height: 32px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--red);

            font-size: 16px;

            transition: 0.25s ease;
        }


        .logout-link:hover {

            background: var(--red-light);

            transform: scale(1.05);
        }


        /* =====================================================
           MAIN
           ===================================================== */

        .main {

            margin-left: var(--sidebar-width);

            min-height: 100vh;
        }


        /* =====================================================
           TOPBAR
           ===================================================== */

        .topbar {

            height: 76px;

            background:
                rgba(255, 250, 247, 0.94);

            backdrop-filter: blur(14px);

            -webkit-backdrop-filter: blur(14px);

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 30px;

            position: sticky;

            top: 0;

            z-index: 100;
        }


        .topbar-left {

            display: flex;

            align-items: center;

            gap: 14px;
        }


        .mobile-menu-btn {

            display: none;

            width: 40px;
            height: 40px;

            border: 1px solid var(--border);

            background: var(--white);

            border-radius: 10px;

            color: var(--brown);

            font-size: 20px;

            cursor: pointer;
        }


        .topbar-title h2 {

            font-family:
                "Playfair Display", serif;

            font-size: 30px;

            color: var(--brown);

            font-weight: 700;
        }


        .topbar-title p {

            color: var(--brown-light);

            font-size: 14px;

            margin-top: 2px;
        }


        .topbar-right {

            display: flex;

            align-items: center;

            gap: 15px;
        }


        .view-store-btn {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 9px 14px;

            border-radius: 9px;

            border: 1px solid var(--border);

            background: var(--white);

            color: var(--brown);

            font-size: 15px;

            font-weight: 700;

            transition: 0.25s ease;
        }


        .view-store-btn:hover {

            background: var(--light-pink);

            border-color: var(--soft-pink);

            color: var(--dark-pink);

            transform: translateY(-1px);
        }


        .top-admin {

            display: flex;

            align-items: center;

            gap: 9px;

            padding-left: 14px;

            border-left: 1px solid var(--border);
        }


        .top-admin-avatar {

            width: 37px;
            height: 37px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: var(--pink);

            color: white;

            font-size: 13px;

            font-weight: 700;
        }


        .top-admin-text strong {

            display: block;

            color: var(--brown);

            font-size: 15px;
        }


        .top-admin-text span {

            display: block;

            color: var(--brown-light);

            font-size: 13px;

            margin-top: 2px;
        }


        /* =====================================================
           CONTENT
           ===================================================== */

        .content {

            padding: 30px;
        }


        /* =====================================================
           WELCOME SECTION
           ===================================================== */

        .welcome-card {

            position: relative;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    var(--white) 0%,
                    var(--light-pink) 100%
                );

            border: 1px solid var(--border);

            border-radius: 18px;

            padding: 27px 28px;

            margin-bottom: 24px;

            box-shadow: var(--shadow);
        }


        .welcome-content {

            position: relative;

            z-index: 2;

            max-width: 650px;
        }


        .welcome-small {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            color: var(--pink);

            font-size: 15px;

            font-weight: 700;

            letter-spacing: 0.5px;

            margin-bottom: 8px;
        }


        .welcome-card h1 {

            font-family:
                "Playfair Display", serif;

            font-size: 33px;

            line-height: 1.2;

            color: var(--brown);

            margin-bottom: 8px;
        }


        .welcome-card h1 span {

            color: var(--pink);
        }


        .welcome-card p {

            color: var(--brown-light);

            font-size: 13px;

            line-height: 1.6;
        }


        .welcome-decoration {

            position: absolute;

            right: 25px;

            top: 50%;

            transform:
                translateY(-50%);

            width: 145px;
            height: 145px;

            border-radius: 50%;

            background:
                rgba(169, 103, 113, 0.08);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 68px;

            opacity: 0.9;
        }


        /* =====================================================
           STATISTICS
           ===================================================== */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 17px;

            margin-bottom: 24px;
        }


        .stat-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 15px;

            padding: 18px;

            box-shadow: var(--shadow);

            display: flex;

            align-items: center;

            gap: 13px;

            transition: 0.25s ease;
        }


        .stat-card:hover {

            transform: translateY(-2px);

            box-shadow: var(--shadow-hover);
        }


        .stat-icon {

            width: 48px;
            height: 48px;

            border-radius: 13px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            flex-shrink: 0;
        }


        .stat-icon.pink {

            background: var(--light-pink);

            color: var(--pink);
        }


        .stat-icon.orange {

            background: var(--orange-light);

            color: var(--orange);
        }


        .stat-icon.blue {

            background: var(--blue-light);

            color: var(--blue);
        }


        .stat-icon.green {

            background: var(--green-light);

            color: var(--green);
        }


        .stat-label {

            font-size: 13px;

            color: var(--brown-light);

            font-weight: 600;

            margin-bottom: 5px;
        }


        .stat-number {

            font-size: 22px;

            font-weight: 700;

            color: var(--brown);

            line-height: 1;
        }


        .stat-note {

            font-size: 11px;

            color: var(--brown-light);

            margin-top: 5px;
        }


        /* =====================================================
           QUICK ACTIONS
           ===================================================== */

        .section-heading {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 13px;
        }


        .section-heading h3 {

            font-family:
                "Playfair Display", serif;

            font-size: 25px;

            color: var(--brown);
        }


        .section-heading p {

            color: var(--brown-light);

            font-size: 13px;
        }


        .quick-actions {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 13px;

            margin-bottom: 25px;
        }


        .quick-action {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 13px;

            padding: 16px;

            display: flex;

            align-items: center;

            gap: 12px;

            transition: 0.25s ease;

            box-shadow: var(--shadow);
        }


        .quick-action:hover {

            transform: translateY(-2px);

            border-color: var(--soft-pink);

            box-shadow: var(--shadow-hover);
        }


        .quick-icon {

            width: 42px;
            height: 42px;

            border-radius: 11px;

            background: var(--light-pink);

            color: var(--pink);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

            flex-shrink: 0;
        }


        .quick-action h4 {

            font-size: 15px;

            color: var(--brown);

            margin-bottom: 3px;
        }


        .quick-action p {

            font-size: 11px;

            color: var(--brown-light);

            line-height: 1.4;
        }


        /* =====================================================
           TWO COLUMN AREA
           ===================================================== */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1.6fr)
                minmax(280px, 1fr);

            gap: 20px;

            align-items: start;
        }


        .panel {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 15px;

            box-shadow: var(--shadow);

            overflow: hidden;
        }


        .panel-header {

            min-height: 65px;

            padding: 14px 18px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            border-bottom: 1px solid var(--border);
        }


        .panel-header h3 {

            font-family:
                "Playfair Display", serif;

            font-size: 25px;

            color: var(--brown);
        }


        .panel-header p {

            font-size: 13px;

            color: var(--brown-light);

            margin-top: 3px;
        }


        .panel-link {

            font-size: 13px;

            font-weight: 700;

            color: var(--pink);

            padding: 6px 9px;

            border-radius: 7px;

            white-space: nowrap;
        }


        .panel-link:hover {

            background: var(--light-pink);

            color: var(--dark-pink);
        }


        /* =====================================================
           RECENT ORDERS
           ===================================================== */

        .orders-list {

            width: 100%;
        }


        .order-row {

            display: grid;

            grid-template-columns:
                65px
                minmax(140px, 1fr)
                95px
                105px;

            align-items: center;

            gap: 10px;

            padding: 14px 18px;

            border-bottom: 1px solid #f0e5e2;

            transition: 0.2s ease;
        }


        .order-row:last-child {

            border-bottom: none;
        }


        .order-row:hover {

            background: #fffdfc;
        }


        .order-id {

            font-size: 15px;

            font-weight: 700;

            color: var(--pink);
        }


        .customer-name {

            font-size: 15px;

            font-weight: 700;

            color: var(--brown);

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .customer-email {

            font-size: 11px;

            color: var(--brown-light);

            margin-top: 2px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .order-amount {

            font-size: 15px;

            font-weight: 700;

            color: var(--brown);

            white-space: nowrap;
        }


        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 8px;

            border-radius: 7px;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;
        }


        .status-dot {

            width: 6px;
            height: 6px;

            border-radius: 50%;

            display: inline-block;
        }


        .status-pending {

            background: var(--orange-light);

            color: var(--orange);
        }


        .status-pending .status-dot {

            background: var(--orange);
        }


        .status-confirmed,
        .status-processing {

            background: var(--blue-light);

            color: var(--blue);
        }


        .status-confirmed .status-dot,
        .status-processing .status-dot {

            background: var(--blue);
        }


        .status-shipped {

            background: var(--light-pink);

            color: var(--dark-pink);
        }


        .status-shipped .status-dot {

            background: var(--pink);
        }


        .status-delivered {

            background: var(--green-light);

            color: var(--green);
        }


        .status-delivered .status-dot {

            background: var(--green);
        }


        .status-cancelled {

            background: var(--red-light);

            color: var(--red);
        }


        .status-cancelled .status-dot {

            background: var(--red);
        }


        /* =====================================================
           LOW STOCK
           ===================================================== */

        .stock-list {

            padding: 4px 0;
        }


        .stock-item {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 13px 18px;

            border-bottom: 1px solid #f0e5e2;
        }


        .stock-item:last-child {

            border-bottom: none;
        }


        .stock-image {

            width: 42px;
            height: 42px;

            border-radius: 10px;

            background: var(--light-pink);

            border: 1px solid var(--border);

            overflow: hidden;

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--pink);

            font-size: 18px;

            flex-shrink: 0;
        }


        .stock-image img {

            width: 100%;
            height: 100%;

            object-fit: cover;
        }


        .stock-info {

            min-width: 0;

            flex: 1;
        }


        .stock-name {

            font-size: 11px;

            font-weight: 700;

            color: var(--brown);

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .stock-category {

            font-size: 9px;

            color: var(--brown-light);

            margin-top: 2px;
        }


        .stock-number {

            padding: 5px 7px;

            border-radius: 7px;

            font-size: 9px;

            font-weight: 700;

            white-space: nowrap;
        }


        .stock-out {

            background: var(--red-light);

            color: var(--red);
        }


        .stock-low {

            background: var(--orange-light);

            color: var(--orange);
        }


        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .empty-small {

            padding: 35px 18px;

            text-align: center;
        }


        .empty-small-icon {

            width: 45px;
            height: 45px;

            margin: 0 auto 10px;

            border-radius: 13px;

            background: var(--light-pink);

            color: var(--pink);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 26px;
        }


        .empty-small h4 {

            font-family:
                "Playfair Display", serif;

            font-size: 20px;

            color: var(--brown);

            margin-bottom: 4px;
        }


        .empty-small p {

            font-size: 15px;

            color: var(--brown-light);

            line-height: 1.5;
        }


        /* =====================================================
           DATABASE WARNING
           ===================================================== */

        .database-warning {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            background: var(--orange-light);

            border: 1px solid #efd8b7;

            color: #8c612f;

            border-radius: 12px;

            padding: 13px 15px;

            margin-bottom: 20px;

            font-size: 11px;

            line-height: 1.5;
        }


        /* =====================================================
           MOBILE OVERLAY
           ===================================================== */

        .sidebar-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(81, 61, 61, 0.35);

            z-index: 900;
        }


        /* =====================================================
           TABLET
           ===================================================== */

        @media (max-width: 1100px) {

            :root {

                --sidebar-width: 235px;
            }


            .content {

                padding: 24px;
            }


            .stats-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .dashboard-grid {

                grid-template-columns: 1fr;
            }
        }


        /* =====================================================
           MOBILE
           ===================================================== */

        @media (max-width: 800px) {

            .sidebar {

                transform:
                    translateX(-100%);
            }


            .sidebar.open {

                transform:
                    translateX(0);
            }


            .sidebar-overlay.show {

                display: block;
            }


            .main {

                margin-left: 0;
            }


            .mobile-menu-btn {

                display: flex;

                align-items: center;

                justify-content: center;
            }


            .topbar {

                height: 70px;

                padding: 0 18px;
            }


            .topbar-title h2 {

                font-size: 20px;
            }


            .topbar-title p {

                display: none;
            }


            .view-store-btn {

                display: none;
            }


            .top-admin {

                border-left: none;

                padding-left: 0;
            }


            .top-admin-text {

                display: none;
            }


            .content {

                padding: 20px 16px 30px;
            }


            .welcome-card {

                padding: 23px 20px;
            }


            .welcome-card h1 {

                font-size: 26px;

                padding-right: 60px;
            }


            .welcome-decoration {

                width: 95px;
                height: 95px;

                right: -20px;

                font-size: 45px;
            }


            .stats-grid {

                grid-template-columns: 1fr;

                gap: 10px;
            }


            .quick-actions {

                grid-template-columns: 1fr;

                gap: 10px;
            }


            .dashboard-grid {

                grid-template-columns: 1fr;

                gap: 15px;
            }


            .order-row {

                grid-template-columns:
                    55px
                    minmax(120px, 1fr)
                    80px;

                gap: 8px;
            }


            .order-status-column {

                display: none;
            }
        }


        /* =====================================================
           SMALL MOBILE
           ===================================================== */

        @media (max-width: 480px) {

            .topbar {

                padding: 0 13px;
            }


            .topbar-title h2 {

                font-size: 18px;
            }


            .content {

                padding: 17px 12px 25px;
            }


            .welcome-card {

                padding: 20px 17px;
            }


            .welcome-card h1 {

                font-size: 23px;

                padding-right: 35px;
            }


            .welcome-card p {

                font-size: 11px;
            }


            .welcome-decoration {

                width: 70px;
                height: 70px;

                right: -20px;

                font-size: 32px;
            }


            .stat-card {

                padding: 14px;
            }


            .stat-icon {

                width: 44px;
                height: 44px;
            }


            .stat-number {

                font-size: 21px;
            }


            .panel-header {

                padding: 13px 14px;
            }


            .order-row {

                padding: 12px 14px;
            }


            .section-heading h3 {

                font-size: 18px;
            }
        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<aside
    class="sidebar"
    id="sidebar"
>


    <!-- BRAND -->

    <div class="sidebar-brand">

        <div class="brand-icon">
            🧶
        </div>


        <div class="brand-text">

            <div class="brand-title">
                The Knot House
            </div>

            <div class="brand-subtitle">
                ADMIN PANEL
            </div>

        </div>

    </div>


    <!-- SIDEBAR CONTENT -->

    <div class="sidebar-content">


        <!-- MAIN MENU -->

        <div class="nav-section">

            <div class="nav-title">
                Main Menu
            </div>


            <a
                href="dashboard.php"
                class="nav-link active"
            >

                <span class="nav-icon">
                    🏠
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="products.php"
                class="nav-link"
            >

                <span class="nav-icon">
                    🛍️
                </span>

                <span>
                    Products
                </span>

            </a>


            <a
                href="orders.php"
                class="nav-link"
            >

                <span class="nav-icon">
                    📦
                </span>

                <span>
                    Orders
                </span>

            </a>
			<a
                    href="message.php"
                    class="nav-link"
                >

                    <span class="nav-icon">
                        💌
                    </span>

                    <span>
                        Messages
                    </span>

                </a>

        </div>


        <!-- MANAGEMENT -->

        <div class="nav-section">

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

                <span>
                    Inventory
                </span>

            </a>


            <a
                href="../index.html"
                class="nav-link"
                target="_blank"
            >

                <span class="nav-icon">
                    👁️
                </span>

                <span>
                    View Store
                </span>

            </a>


            <a
                href="add-product.php"
                class="nav-link"
            >

                <span class="nav-icon">
                    ➕
                </span>

                <span>
                    Add Product
                </span>

            </a>

        </div>

    </div>


    <!-- ADMIN BOTTOM -->

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
                class="logout-link"
                title="Logout"
                onclick="
                    return confirm(
                        'Are you sure you want to logout?'
                    );
                "
            >

                ↪

            </a>

        </div>

    </div>

</aside>


<!-- SIDEBAR OVERLAY -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
    onclick="closeSidebar()"
></div>



<!-- =========================================================
     MAIN
     ========================================================= -->

<main class="main">


    <!-- =====================================================
         TOPBAR
         ===================================================== -->

    <header class="topbar">


        <div class="topbar-left">


            <button
                class="mobile-menu-btn"
                onclick="toggleSidebar()"
                aria-label="Open menu"
            >

                ☰

            </button>


            <div class="topbar-title">

                <h2>
                    Dashboard
                </h2>

                <p>
                    Welcome back to your admin panel
                </p>

            </div>

        </div>


        <div class="topbar-right">


            <a
                href="../index.html"
                class="view-store-btn"
                target="_blank"
            >

                👁️

                <span>
                    View Store
                </span>

            </a>


            <div class="top-admin">


                <div class="top-admin-avatar">

                    <?php
                    echo htmlspecialchars($adminInitial);
                    ?>

                </div>


                <div class="top-admin-text">

                    <strong>

                        <?php
                        echo htmlspecialchars($adminName);
                        ?>

                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

            </div>

        </div>

    </header>



    <!-- =====================================================
         CONTENT
         ===================================================== -->

    <section class="content">


        <!-- =================================================
             DATABASE WARNING
             ================================================= -->

        <?php if (
            !$productsTableExists ||
            !$ordersTableExists
        ): ?>

            <div class="database-warning">

                <span>
                    ⚠️
                </span>

                <div>

                    <strong>
                        Database setup notice:
                    </strong>

                    <?php if (!$productsTableExists): ?>

                        The
                        <strong>products</strong>
                        table is missing.

                    <?php endif; ?>


                    <?php if (
                        !$productsTableExists &&
                        !$ordersTableExists
                    ): ?>

                        &nbsp;

                    <?php endif; ?>


                    <?php if (!$ordersTableExists): ?>

                        The
                        <strong>orders</strong>
                        table is missing.

                    <?php endif; ?>

                    Please check your
                    <strong>knot_house</strong>
                    database.

                </div>

            </div>

        <?php endif; ?>



        <!-- =================================================
             WELCOME CARD
             ================================================= -->

        <div class="welcome-card">


            <div class="welcome-content">


                <div class="welcome-small">

                    🧶

                    <span>
                        THE KNOT HOUSE ADMIN
                    </span>

                </div>


                <h1>

                    Welcome back,
                    <span>
                        <?php
                        echo htmlspecialchars(
                            $adminName
                        );
                        ?>
                    </span>
                    ✨

                </h1>


                <p>

                    Manage your handmade crochet products,
                    customer orders and store activity
                    from one beautiful dashboard.

                </p>

            </div>


            <div class="welcome-decoration">

                🧶

            </div>

        </div>



        <!-- =================================================
             STATISTICS
             ================================================= -->

        <div class="stats-grid">


            <!-- PRODUCTS -->

            <div class="stat-card">

                <div class="stat-icon pink">
                    🛍️
                </div>


                <div>

                    <div class="stat-label">
                        Total Products
                    </div>


                    <div class="stat-number">

                        <?php
                        echo number_format(
                            $totalProducts
                        );
                        ?>

                    </div>


                    <div class="stat-note">

                        <?php
                        echo number_format(
                            $activeProducts
                        );
                        ?>
                        active products

                    </div>

                </div>

            </div>


            <!-- ORDERS -->

            <div class="stat-card">

                <div class="stat-icon blue">
                    📦
                </div>


                <div>

                    <div class="stat-label">
                        Total Orders
                    </div>


                    <div class="stat-number">

                        <?php
                        echo number_format(
                            $totalOrders
                        );
                        ?>

                    </div>


                    <div class="stat-note">

                        <?php
                        echo number_format(
                            $pendingOrders
                        );
                        ?>
                        pending orders

                    </div>

                </div>

            </div>


            <!-- REVENUE -->

            <div class="stat-card">

                <div class="stat-icon green">
                    ₹
                </div>


                <div>

                    <div class="stat-label">
                        Total Revenue
                    </div>


                    <div class="stat-number"
                         style="font-size:19px;">

                        ₹<?php
                        echo number_format(
                            $totalRevenue,
                            2
                        );
                        ?>

                    </div>


                    <div class="stat-note">

                        From non-cancelled orders

                    </div>

                </div>

            </div>


            <!-- LOW STOCK -->

            <div class="stat-card">

                <div class="stat-icon orange">
                    ⚠️
                </div>


                <div>

                    <div class="stat-label">
                        Low / Out of Stock
                    </div>


                    <div class="stat-number">

                        <?php
                        echo number_format(
                            $lowStockProducts
                        );
                        ?>

                    </div>


                    <div class="stat-note">

                        Products need attention

                    </div>

                </div>

            </div>

        </div>



        <!-- =================================================
             QUICK ACTIONS
             ================================================= -->

        <div class="section-heading">

            <div>

                <h3>
                    Quick Actions
                </h3>

                <p>
                    Frequently used admin actions
                </p>

            </div>

        </div>


        <div class="quick-actions">


            <!-- ADD PRODUCT -->

            <a
                href="add-product.php"
                class="quick-action"
            >

                <div class="quick-icon">
                    ➕
                </div>


                <div>

                    <h4>
                        Add Product
                    </h4>

                    <p>
                        Add a new crochet product
                        to your store.
                    </p>

                </div>

            </a>


            <!-- VIEW ORDERS -->

            <a
                href="orders.php"
                class="quick-action"
            >

                <div class="quick-icon">
                    📦
                </div>


                <div>

                    <h4>
                        Manage Orders
                    </h4>

                    <p>
                        View and manage customer
                        orders.
                    </p>

                </div>

            </a>


            <!-- VIEW STORE -->

            <a
                href="../index.html"
                class="quick-action"
                target="_blank"
            >

                <div class="quick-icon">
                    👁️
                </div>


                <div>

                    <h4>
                        View Store
                    </h4>

                    <p>
                        Open The Knot House
                        customer website.
                    </p>

                </div>

            </a>

        </div>



        <!-- =================================================
             DASHBOARD TWO COLUMNS
             ================================================= -->

        <div class="dashboard-grid">


            <!-- =================================================
                 RECENT ORDERS
                 ================================================= -->

            <div class="panel">


                <div class="panel-header">

                    <div>

                        <h3>
                            Recent Orders
                        </h3>

                        <p>
                            Latest customer orders
                        </p>

                    </div>


                    <a
                        href="orders.php"
                        class="panel-link"
                    >

                        View All →

                    </a>

                </div>


                <?php if (
                    !$ordersTableExists ||
                    empty($recentOrders)
                ): ?>


                    <div class="empty-small">

                        <div class="empty-small-icon">
                            📦
                        </div>


                        <h4>
                            No Recent Orders
                        </h4>


                        <p>

                            Customer orders will appear
                            here when they are placed.

                        </p>

                    </div>


                <?php else: ?>


                    <div class="orders-list">


                        <?php foreach (
                            $recentOrders
                            as $order
                        ): ?>


                            <?php

                            $recentOrderId =
                                (int)(
                                    $order["id"]
                                    ?? 0
                                );

                            $recentCustomer =
                                $order[
                                    "customer_name"
                                ]
                                ?? "Customer";

                            $recentEmail =
                                $order[
                                    "customer_email"
                                ]
                                ?? "";

                            $recentAmount =
                                (float)(
                                    $order[
                                        "total_amount"
                                    ]
                                    ?? 0
                                );

                            $recentStatus =
                                $order[
                                    "order_status"
                                ]
                                ?? "Pending";


                            $recentStatusClass =
                                "status-pending";


                            switch (
                                strtolower(
                                    $recentStatus
                                )
                            ) {

                                case "confirmed":

                                    $recentStatusClass =
                                        "status-confirmed";

                                    break;

                                case "processing":

                                    $recentStatusClass =
                                        "status-processing";

                                    break;

                                case "shipped":

                                    $recentStatusClass =
                                        "status-shipped";

                                    break;

                                case "delivered":

                                    $recentStatusClass =
                                        "status-delivered";

                                    break;

                                case "cancelled":

                                    $recentStatusClass =
                                        "status-cancelled";

                                    break;
                            }

                            ?>


                            <div class="order-row">


                                <!-- ORDER ID -->

                                <div>

                                    <div class="order-id">

                                        #<?php
                                        echo $recentOrderId;
                                        ?>

                                    </div>

                                </div>


                                <!-- CUSTOMER -->

                                <div>

                                    <div
                                        class="customer-name"
                                        title="<?php
                                            echo htmlspecialchars(
                                                $recentCustomer
                                            );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $recentCustomer
                                        );
                                        ?>

                                    </div>


                                    <div
                                        class="customer-email"
                                        title="<?php
                                            echo htmlspecialchars(
                                                $recentEmail
                                            );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $recentEmail
                                        );
                                        ?>

                                    </div>

                                </div>


                                <!-- AMOUNT -->

                                <div>

                                    <div class="order-amount">

                                        ₹<?php
                                        echo number_format(
                                            $recentAmount,
                                            2
                                        );
                                        ?>

                                    </div>

                                </div>


                                <!-- STATUS -->

                                <div class="order-status-column">

                                    <span
                                        class="status-badge
                                        <?php
                                        echo $recentStatusClass;
                                        ?>"
                                    >

                                        <span
                                            class="status-dot">
                                        </span>

                                        <?php
                                        echo htmlspecialchars(
                                            $recentStatus
                                        );
                                        ?>

                                    </span>

                                </div>

                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </div>



            <!-- =================================================
                 LOW STOCK
                 ================================================= -->

            <div class="panel">


                <div class="panel-header">

                    <div>

                        <h3>
                            Low Stock
                        </h3>

                        <p>
                            Products that need attention
                        </p>

                    </div>


                    <a
                        href="products.php"
                        class="panel-link"
                    >

                        Inventory →

                    </a>

                </div>


                <?php if (
                    !$productsTableExists ||
                    empty($lowStockItems)
                ): ?>


                    <div class="empty-small">

                        <div class="empty-small-icon">
                            ✓
                        </div>


                        <h4>
                            Stock Looks Good
                        </h4>


                        <p>
                            No products are currently
                            low or out of stock.
                        </p>

                    </div>


                <?php else: ?>


                    <div class="stock-list">


                        <?php foreach (
                            $lowStockItems
                            as $item
                        ): ?>


                            <?php

                            $itemName =
                                $item[
                                    "product_name"
                                ]
                                ?? "Product";

                            $itemCategory =
                                $item[
                                    "category"
                                ]
                                ?? "Other";

                            $itemStock =
                                (int)(
                                    $item[
                                        "stock"
                                    ]
                                    ?? 0
                                );

                            $itemImage =
                                trim(
                                    $item[
                                        "image"
                                    ]
                                    ?? ""
                                );


                            if (
                                $itemStock <= 0
                            ) {

                                $itemStockClass =
                                    "stock-out";

                                $itemStockText =
                                    "Out of stock";

                            } else {

                                $itemStockClass =
                                    "stock-low";

                                $itemStockText =
                                    $itemStock .
                                    " left";
                            }

                            ?>


                            <div class="stock-item">


                                <!-- IMAGE -->

                                <div class="stock-image">


                                    <?php if (
                                        $itemImage !== ""
                                    ): ?>

                                        <img
                                            src="<?php
                                                echo htmlspecialchars(
                                                    $itemImage
                                                );
                                            ?>"
                                            alt="<?php
                                                echo htmlspecialchars(
                                                    $itemName
                                                );
                                            ?>"
                                            onerror="
                                                this.style.display='none';
                                                this.nextElementSibling.style.display='flex';
                                            "
                                        >

                                        <span
                                            style="
                                                display:none;
                                            "
                                        >
                                            🧶
                                        </span>

                                    <?php else: ?>

                                        🧶

                                    <?php endif; ?>


                                </div>


                                <!-- PRODUCT INFO -->

                                <div class="stock-info">

                                    <div
                                        class="stock-name"
                                        title="<?php
                                            echo htmlspecialchars(
                                                $itemName
                                            );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $itemName
                                        );
                                        ?>

                                    </div>


                                    <div class="stock-category">

                                        <?php
                                        echo htmlspecialchars(
                                            $itemCategory
                                        );
                                        ?>

                                    </div>

                                </div>


                                <!-- STOCK -->

                                <div
                                    class="stock-number
                                    <?php
                                    echo $itemStockClass;
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $itemStockText
                                    );
                                    ?>

                                </div>

                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </div>


        </div>


    </section>

</main>



<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->

<script>


    /* =====================================================
       SIDEBAR
       ===================================================== */

    const sidebar =
        document.getElementById(
            "sidebar"
        );

    const overlay =
        document.getElementById(
            "sidebarOverlay"
        );


    function toggleSidebar() {

        sidebar.classList.toggle(
            "open"
        );

        overlay.classList.toggle(
            "show"
        );
    }


    function closeSidebar() {

        sidebar.classList.remove(
            "open"
        );

        overlay.classList.remove(
            "show"
        );
    }


    /* =====================================================
       MOBILE NAVIGATION
       ===================================================== */

    const navLinks =
        document.querySelectorAll(
            ".nav-link"
        );


    navLinks.forEach(
        function(link) {

            link.addEventListener(
                "click",
                function() {

                    if (
                        window.innerWidth <= 800
                    ) {

                        closeSidebar();
                    }

                }
            );

        }
    );


    /* =====================================================
       ESCAPE KEY
       ===================================================== */

    document.addEventListener(
        "keydown",
        function(event) {

            if (
                event.key === "Escape"
            ) {

                closeSidebar();
            }

        }
    );


    /* =====================================================
       RESIZE
       ===================================================== */

    window.addEventListener(
        "resize",
        function() {

            if (
                window.innerWidth > 800
            ) {

                closeSidebar();
            }

        }
    );

</script>


</body>

</html>