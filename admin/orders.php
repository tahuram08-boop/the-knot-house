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
   CHECK ORDERS TABLE
   ========================================================= */
$tableExists = false;

$tableCheck = $conn->query(
    "SHOW TABLES LIKE 'orders'"
);

if ($tableCheck && $tableCheck->num_rows > 0) {
    $tableExists = true;
}


/* =========================================================
   SEARCH / FILTER VALUES
   ========================================================= */
$search = trim($_GET["search"] ?? "");
$status = trim($_GET["status"] ?? "");


/* =========================================================
   ORDER STATUS LIST
   ========================================================= */
$orderStatuses = [
    "Pending",
    "Confirmed",
    "Processing",
    "Shipped",
    "Delivered",
    "Cancelled"
];


/* =========================================================
   STATISTICS
   ========================================================= */
$totalOrders = 0;
$pendingOrders = 0;
$processingOrders = 0;
$deliveredOrders = 0;

$orders = [];


/* =========================================================
   FETCH ORDER DATA
   ========================================================= */
if ($tableExists) {


    /* -----------------------------------------------------
       TOTAL ORDERS
       ----------------------------------------------------- */
    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM orders
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $totalOrders = (int)$row["total"];
    }


    /* -----------------------------------------------------
       PENDING ORDERS
       ----------------------------------------------------- */
    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM orders
        WHERE LOWER(order_status) = 'pending'
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $pendingOrders = (int)$row["total"];
    }


    /* -----------------------------------------------------
       PROCESSING ORDERS
       ----------------------------------------------------- */
    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM orders
        WHERE LOWER(order_status) IN
        ('confirmed', 'processing', 'shipped')
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $processingOrders = (int)$row["total"];
    }


    /* -----------------------------------------------------
       DELIVERED ORDERS
       ----------------------------------------------------- */
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
       FETCH ORDERS
       ===================================================== */

    $sql = "
        SELECT *
        FROM orders
        WHERE 1=1
    ";

    $types = "";
    $params = [];


    /* -----------------------------------------------------
       SEARCH
       ----------------------------------------------------- */
    if ($search !== "") {

        $sql .= "
            AND (
                customer_name LIKE ?
                OR customer_email LIKE ?
                OR customer_phone LIKE ?
                OR CAST(id AS CHAR) LIKE ?
            )
        ";

        $searchValue = "%" . $search . "%";

        $types .= "ssss";

        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
    }


    /* -----------------------------------------------------
       STATUS FILTER
       ----------------------------------------------------- */
    if ($status !== "") {

        $sql .= "
            AND order_status = ?
        ";

        $types .= "s";

        $params[] = $status;
    }


    /* -----------------------------------------------------
       ORDER BY
       ----------------------------------------------------- */
    $sql .= "
        ORDER BY id DESC
    ";


    /* -----------------------------------------------------
       PREPARE QUERY
       ----------------------------------------------------- */
    $stmt = $conn->prepare($sql);

    if ($stmt) {

        if (!empty($params)) {

            $stmt->bind_param(
                $types,
                ...$params
            );
        }

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            $orders[] = $row;
        }

        $stmt->close();
    }
}


$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Orders | The Knot House Admin
    </title>


    <!-- =====================================================
         GOOGLE FONTS
         ===================================================== -->

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet">


    <style>

        /* =====================================================
           ROOT COLORS
           SAME AS DASHBOARD + PRODUCTS
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


        button,
        input,
        select {

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

            font-size: 22px;

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

            font-size: 18px;

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
           SIDEBAR NAV
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

            font-size: 23px;

            color: var(--brown);

            font-weight: 700;
        }


        .topbar-title p {

            color: var(--brown-light);

            font-size: 12px;

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

            font-size: 12px;

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

            font-size: 12px;
        }


        .top-admin-text span {

            display: block;

            color: var(--brown-light);

            font-size: 10px;

            margin-top: 2px;
        }


        /* =====================================================
           CONTENT
           ===================================================== */

        .content {

            padding: 30px;
        }


        /* =====================================================
           PAGE HEADER
           ===================================================== */

        .page-header {

            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 26px;
        }


        .page-heading h1 {

            font-family:
                "Playfair Display", serif;

            font-size: 31px;

            color: var(--brown);

            line-height: 1.15;

            margin-bottom: 7px;
        }


        .page-heading p {

            font-size: 13px;

            color: var(--brown-light);

            max-width: 650px;

            line-height: 1.6;
        }


        /* =====================================================
           NEW ORDER BUTTON
           ===================================================== */

        .new-order-btn {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            background: var(--pink);

            color: white;

            padding: 11px 17px;

            border-radius: 10px;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;

            transition: 0.25s ease;

            box-shadow:
                0 6px 16px rgba(169, 103, 113, 0.18);
        }


        .new-order-btn:hover {

            background: var(--dark-pink);

            transform: translateY(-2px);
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

            width: 47px;
            height: 47px;

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

            font-size: 10px;

            color: var(--brown-light);

            font-weight: 600;

            margin-bottom: 4px;
        }


        .stat-number {

            font-size: 22px;

            font-weight: 700;

            color: var(--brown);

            line-height: 1;
        }


        /* =====================================================
           FILTER CARD
           ===================================================== */

        .filter-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 15px;

            padding: 17px;

            margin-bottom: 20px;

            box-shadow: var(--shadow);
        }


        .filter-form {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                190px
                auto
                auto;

            gap: 10px;

            align-items: center;
        }


        .search-wrapper {

            position: relative;
        }


        .search-icon {

            position: absolute;

            left: 13px;

            top: 50%;

            transform:
                translateY(-50%);

            color: #a99494;

            font-size: 14px;

            pointer-events: none;
        }


        .form-input,
        .form-select {

            width: 100%;

            height: 43px;

            border: 1px solid var(--border);

            background: var(--cream);

            border-radius: 9px;

            padding: 0 13px;

            color: var(--brown);

            outline: none;

            font-size: 12px;

            transition: 0.2s ease;
        }


        .search-input {

            padding-left: 36px;
        }


        .form-input::placeholder {

            color: #ad9999;
        }


        .form-input:focus,
        .form-select:focus {

            border-color: var(--pink);

            background: var(--white);

            box-shadow:
                0 0 0 3px rgba(169, 103, 113, 0.08);
        }


        .filter-btn {

            height: 43px;

            padding: 0 17px;

            border: none;

            border-radius: 9px;

            background: var(--pink);

            color: white;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.25s ease;
        }


        .filter-btn:hover {

            background: var(--dark-pink);

            transform: translateY(-1px);
        }


        .clear-btn {

            height: 43px;

            padding: 0 15px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border: 1px solid var(--border);

            border-radius: 9px;

            background: var(--white);

            color: var(--brown-light);

            font-size: 12px;

            font-weight: 700;

            transition: 0.25s ease;
        }


        .clear-btn:hover {

            background: var(--light-pink);

            color: var(--dark-pink);

            border-color: var(--soft-pink);
        }


        /* =====================================================
           TABLE CARD
           ===================================================== */

        .table-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 15px;

            box-shadow: var(--shadow);

            overflow: hidden;
        }


        .table-header {

            min-height: 66px;

            padding: 15px 20px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            border-bottom: 1px solid var(--border);

            gap: 15px;
        }


        .table-heading h3 {

            font-family:
                "Playfair Display", serif;

            font-size: 18px;

            color: var(--brown);
        }


        .table-heading p {

            font-size: 11px;

            color: var(--brown-light);

            margin-top: 3px;
        }


        .result-count {

            padding: 7px 11px;

            border-radius: 8px;

            background: var(--light-pink);

            color: var(--dark-pink);

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;
        }


        .table-wrapper {

            width: 100%;

            overflow-x: auto;
        }


        table {

            width: 100%;

            min-width: 1050px;

            border-collapse: collapse;
        }


        thead th {

            background: var(--cream);

            color: var(--brown-light);

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 0.7px;

            text-transform: uppercase;

            text-align: left;

            padding: 13px 16px;

            border-bottom: 1px solid var(--border);

            white-space: nowrap;
        }


        tbody td {

            padding: 14px 16px;

            border-bottom: 1px solid #f0e5e2;

            font-size: 12px;

            color: var(--brown);

            vertical-align: middle;
        }


        tbody tr:last-child td {

            border-bottom: none;
        }


        tbody tr {

            transition: 0.2s ease;
        }


        tbody tr:hover {

            background: #fffdfc;
        }


        /* =====================================================
           ORDER ID
           ===================================================== */

        .order-id {

            font-weight: 700;

            color: var(--pink);

            white-space: nowrap;
        }


        /* =====================================================
           CUSTOMER
           ===================================================== */

        .customer-cell {

            min-width: 190px;
        }


        .customer-name {

            font-size: 13px;

            font-weight: 700;

            color: var(--brown);

            margin-bottom: 3px;
        }


        .customer-email {

            font-size: 10px;

            color: var(--brown-light);

            max-width: 190px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        /* =====================================================
           PHONE
           ===================================================== */

        .phone {

            color: var(--brown-light);

            white-space: nowrap;
        }


        /* =====================================================
           AMOUNT
           ===================================================== */

        .amount {

            font-size: 13px;

            font-weight: 700;

            color: var(--brown);

            white-space: nowrap;
        }


        /* =====================================================
           PAYMENT STATUS
           ===================================================== */

        .payment-badge {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            border-radius: 7px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }


        .payment-paid {

            background: var(--green-light);

            color: var(--green);
        }


        .payment-pending {

            background: var(--orange-light);

            color: var(--orange);
        }


        .payment-failed {

            background: var(--red-light);

            color: var(--red);
        }


        /* =====================================================
           ORDER STATUS
           ===================================================== */

        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            border-radius: 7px;

            font-size: 10px;

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


        .status-confirmed {

            background: var(--blue-light);

            color: var(--blue);
        }


        .status-confirmed .status-dot {

            background: var(--blue);
        }


        .status-processing {

            background: var(--blue-light);

            color: var(--blue);
        }


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
           DATE
           ===================================================== */

        .order-date {

            white-space: nowrap;

            color: var(--brown-light);

            font-size: 11px;
        }


        /* =====================================================
           VIEW BUTTON
           ===================================================== */

        .view-order-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            padding: 7px 10px;

            border-radius: 8px;

            border: 1px solid var(--border);

            background: var(--white);

            color: var(--pink);

            font-size: 10px;

            font-weight: 700;

            transition: 0.2s ease;

            white-space: nowrap;
        }


        .view-order-btn:hover {

            background: var(--light-pink);

            border-color: var(--soft-pink);

            color: var(--dark-pink);

            transform: translateY(-1px);
        }


        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .empty-state {

            padding: 65px 25px;

            text-align: center;
        }


        .empty-icon {

            width: 68px;
            height: 68px;

            border-radius: 20px;

            background: var(--light-pink);

            color: var(--pink);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 29px;

            margin: 0 auto 15px;
        }


        .empty-state h3 {

            font-family:
                "Playfair Display", serif;

            font-size: 20px;

            color: var(--brown);

            margin-bottom: 6px;
        }


        .empty-state p {

            font-size: 12px;

            color: var(--brown-light);

            margin-bottom: 18px;
        }


        .empty-add-btn {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 10px 15px;

            border-radius: 9px;

            background: var(--pink);

            color: white;

            font-size: 12px;

            font-weight: 700;
        }


        .empty-add-btn:hover {

            background: var(--dark-pink);
        }


        /* =====================================================
           SIDEBAR OVERLAY
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


            .filter-form {

                grid-template-columns:
                    minmax(0, 1fr)
                    170px
                    auto;
            }


            .clear-btn {

                grid-column: 3;
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


            .page-header {

                align-items: flex-start;

                flex-direction: column;
            }


            .page-heading h1 {

                font-size: 27px;
            }


            .new-order-btn {

                width: 100%;

                justify-content: center;
            }


            .stats-grid {

                grid-template-columns: 1fr;

                gap: 10px;
            }


            .filter-form {

                grid-template-columns: 1fr;
            }


            .clear-btn {

                grid-column: auto;
            }


            .filter-btn,
            .clear-btn {

                width: 100%;
            }


            .table-header {

                align-items: flex-start;

                flex-direction: column;
            }


            .result-count {

                align-self: flex-start;
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


            .page-heading h1 {

                font-size: 25px;
            }


            .page-heading p {

                font-size: 12px;
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
        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<aside class="sidebar"
       id="sidebar">


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


    <!-- NAVIGATION -->

    <div class="sidebar-content">


        <!-- MAIN MENU -->

        <div class="nav-section">

            <div class="nav-title">
                Main Menu
            </div>


            <a href="dashboard.php"
               class="nav-link">

                <span class="nav-icon">
                    🏠
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <a href="products.php"
               class="nav-link">

                <span class="nav-icon">
                    🛍️
                </span>

                <span>
                    Products
                </span>

            </a>


            <a href="orders.php"
               class="nav-link active">

                <span class="nav-icon">
                    📦
                </span>

                <span>
                    Orders
                </span>

            </a>

        </div>


        <!-- MANAGEMENT -->

        <div class="nav-section">

            <div class="nav-title">
                Management
            </div>


            <a href="products.php"
               class="nav-link">

                <span class="nav-icon">
                    📊
                </span>

                <span>
                    Inventory
                </span>

            </a>


            <a href="../index.html"
               class="nav-link"
               target="_blank">

                <span class="nav-icon">
                    👁️
                </span>

                <span>
                    View Store
                </span>

            </a>


            <a href="add-product.php"
               class="nav-link">

                <span class="nav-icon">
                    ➕
                </span>

                <span>
                    Add Product
                </span>

            </a>

        </div>

    </div>


    <!-- ADMIN -->

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


            <a href="logout.php"
               class="logout-link"
               title="Logout"
               onclick="
                   return confirm(
                       'Are you sure you want to logout?'
                   );
               ">

                ↪

            </a>

        </div>

    </div>

</aside>


<!-- OVERLAY -->

<div class="sidebar-overlay"
     id="sidebarOverlay"
     onclick="closeSidebar()">
</div>



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
                aria-label="Open menu">

                ☰

            </button>


            <div class="topbar-title">

                <h2>
                    Orders
                </h2>

                <p>
                    Manage customer orders
                </p>

            </div>

        </div>


        <div class="topbar-right">


            <a href="../index.html"
               class="view-store-btn"
               target="_blank">

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


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div class="page-heading">

                <h1>
                    Order Management
                </h1>

                <p>
                    View and manage all customer orders
                    from your crochet store.
                </p>

            </div>


        </div>



        <!-- =================================================
             STATISTICS
             ================================================= -->

        <div class="stats-grid">


            <!-- TOTAL ORDERS -->

            <div class="stat-card">

                <div class="stat-icon pink">
                    📦
                </div>

                <div>

                    <div class="stat-label">
                        Total Orders
                    </div>

                    <div class="stat-number">

                        <?php
                        echo number_format($totalOrders);
                        ?>

                    </div>

                </div>

            </div>


            <!-- PENDING -->

            <div class="stat-card">

                <div class="stat-icon orange">
                    ⏳
                </div>

                <div>

                    <div class="stat-label">
                        Pending Orders
                    </div>

                    <div class="stat-number">

                        <?php
                        echo number_format($pendingOrders);
                        ?>

                    </div>

                </div>

            </div>


            <!-- PROCESSING -->

            <div class="stat-card">

                <div class="stat-icon blue">
                    🔄
                </div>

                <div>

                    <div class="stat-label">
                        Processing
                    </div>

                    <div class="stat-number">

                        <?php
                        echo number_format($processingOrders);
                        ?>

                    </div>

                </div>

            </div>


            <!-- DELIVERED -->

            <div class="stat-card">

                <div class="stat-icon green">
                    ✓
                </div>

                <div>

                    <div class="stat-label">
                        Delivered
                    </div>

                    <div class="stat-number">

                        <?php
                        echo number_format($deliveredOrders);
                        ?>

                    </div>

                </div>

            </div>

        </div>



        <!-- =================================================
             SEARCH & FILTER
             ================================================= -->

        <div class="filter-card">

            <form
                method="GET"
                action="orders.php"
                class="filter-form">


                <!-- SEARCH -->

                <div class="search-wrapper">

                    <span class="search-icon">
                        🔍
                    </span>

                    <input
                        type="text"
                        name="search"
                        class="form-input search-input"
                        placeholder="Search by order ID, customer, email or phone..."
                        value="<?php
                            echo htmlspecialchars($search);
                        ?>"
                    >

                </div>


                <!-- STATUS -->

                <select
                    name="status"
                    class="form-select">

                    <option value="">
                        All Statuses
                    </option>


                    <?php foreach ($orderStatuses as $orderStatus): ?>

                        <option
                            value="<?php
                                echo htmlspecialchars($orderStatus);
                            ?>"
                            <?php
                                echo (
                                    $status === $orderStatus
                                )
                                ? "selected"
                                : "";
                            ?>>

                            <?php
                            echo htmlspecialchars($orderStatus);
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <!-- FILTER -->

                <button
                    type="submit"
                    class="filter-btn">

                    🔍 Filter

                </button>


                <!-- CLEAR -->

                <?php if (
                    $search !== "" ||
                    $status !== ""
                ): ?>

                    <a
                        href="orders.php"
                        class="clear-btn">

                        Clear

                    </a>

                <?php else: ?>

                    <span></span>

                <?php endif; ?>

            </form>

        </div>



        <!-- =================================================
             ORDERS TABLE
             ================================================= -->

        <div class="table-card">


            <!-- TABLE HEADER -->

            <div class="table-header">

                <div class="table-heading">

                    <h3>
                        All Orders
                    </h3>

                    <p>
                        Customer orders and payment details
                    </p>

                </div>


                <div class="result-count">

                    <?php
                    echo count($orders);
                    ?>

                    <?php
                    echo count($orders) == 1
                        ? "Order"
                        : "Orders";
                    ?>

                </div>

            </div>



            <?php if (!$tableExists): ?>


                <!-- DATABASE EMPTY -->

                <div class="empty-state">

                    <div class="empty-icon">
                        🗄️
                    </div>

                    <h3>
                        Orders Table Not Found
                    </h3>

                    <p>
                        Please create the orders table
                        in the knot_house database.
                    </p>

                </div>


            <?php elseif (empty($orders)): ?>


                <!-- NO ORDERS -->

                <div class="empty-state">

                    <div class="empty-icon">
                        📦
                    </div>

                    <h3>
                        No Orders Found
                    </h3>

                    <p>

                        <?php if (
                            $search !== "" ||
                            $status !== ""
                        ): ?>

                            No orders match your
                            current search or filter.

                        <?php else: ?>

                            There are no customer orders yet.

                        <?php endif; ?>

                    </p>


                    <?php if (
                        $search !== "" ||
                        $status !== ""
                    ): ?>

                        <a
                            href="orders.php"
                            class="empty-add-btn">

                            View All Orders

                        </a>

                    <?php endif; ?>

                </div>


            <?php else: ?>


                <!-- =================================================
                     ORDERS TABLE
                     ================================================= -->

                <div class="table-wrapper">

                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Order ID
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Payment
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $orders as $order
                        ): ?>


                            <?php

                            /* ---------------------------------
                               ORDER VALUES
                               --------------------------------- */

                            $orderId =
                                (int)(
                                    $order["id"] ?? 0
                                );

                            $customerName =
                                $order["customer_name"]
                                ?? "Customer";

                            $customerEmail =
                                $order["customer_email"]
                                ?? "";

                            $customerPhone =
                                $order["customer_phone"]
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


                            /* ---------------------------------
                               DATE FORMAT
                               --------------------------------- */

                            $formattedDate = "";

                            if ($createdAt !== "") {

                                $time =
                                    strtotime($createdAt);

                                if ($time !== false) {

                                    $formattedDate =
                                        date(
                                            "d M Y",
                                            $time
                                        );
                                }
                            }


                            /* ---------------------------------
                               ORDER STATUS CLASS
                               --------------------------------- */

                            $statusClass =
                                "status-pending";

                            switch (
                                strtolower($orderStatus)
                            ) {

                                case "confirmed":

                                    $statusClass =
                                        "status-confirmed";

                                    break;

                                case "processing":

                                    $statusClass =
                                        "status-processing";

                                    break;

                                case "shipped":

                                    $statusClass =
                                        "status-shipped";

                                    break;

                                case "delivered":

                                    $statusClass =
                                        "status-delivered";

                                    break;

                                case "cancelled":

                                    $statusClass =
                                        "status-cancelled";

                                    break;

                                default:

                                    $statusClass =
                                        "status-pending";
                            }


                            /* ---------------------------------
                               PAYMENT CLASS
                               --------------------------------- */

                            $paymentClass =
                                "payment-pending";

                            $paymentLower =
                                strtolower(
                                    $paymentStatus
                                );

                            if (
                                $paymentLower === "paid" ||
                                $paymentLower === "completed"
                            ) {

                                $paymentClass =
                                    "payment-paid";

                            } elseif (
                                $paymentLower === "failed"
                            ) {

                                $paymentClass =
                                    "payment-failed";
                            }

                            ?>


                            <tr>


                                <!-- ORDER ID -->

                                <td>

                                    <span class="order-id">

                                        #<?php
                                        echo $orderId;
                                        ?>

                                    </span>

                                </td>



                                <!-- CUSTOMER -->

                                <td>

                                    <div class="customer-cell">

                                        <div class="customer-name">

                                            <?php
                                            echo htmlspecialchars(
                                                $customerName
                                            );
                                            ?>

                                        </div>


                                        <div
                                            class="customer-email"
                                            title="<?php
                                                echo htmlspecialchars(
                                                    $customerEmail
                                                );
                                            ?>">

                                            <?php
                                            echo htmlspecialchars(
                                                $customerEmail
                                            );
                                            ?>

                                        </div>

                                    </div>

                                </td>



                                <!-- PHONE -->

                                <td>

                                    <span class="phone">

                                        <?php
                                        echo htmlspecialchars(
                                            $customerPhone
                                        );
                                        ?>

                                    </span>

                                </td>



                                <!-- DATE -->

                                <td>

                                    <span class="order-date">

                                        <?php
                                        echo htmlspecialchars(
                                            $formattedDate
                                        );
                                        ?>

                                    </span>

                                </td>



                                <!-- TOTAL -->

                                <td>

                                    <span class="amount">

                                        ₹<?php
                                        echo number_format(
                                            $totalAmount,
                                            2
                                        );
                                        ?>

                                    </span>

                                </td>



                                <!-- PAYMENT -->

                                <td>

                                    <span
                                        class="payment-badge
                                        <?php
                                        echo $paymentClass;
                                        ?>">

                                        <?php
                                        echo htmlspecialchars(
                                            $paymentStatus
                                        );
                                        ?>

                                    </span>

                                </td>



                                <!-- ORDER STATUS -->

                                <td>

                                    <span
                                        class="status-badge
                                        <?php
                                        echo $statusClass;
                                        ?>">

                                        <span
                                            class="status-dot">
                                        </span>

                                        <?php
                                        echo htmlspecialchars(
                                            $orderStatus
                                        );
                                        ?>

                                    </span>

                                </td>



                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="order.php?id=<?php
                                            echo $orderId;
                                        ?>"
                                        class="view-order-btn">

                                        👁️

                                        View Order

                                    </a>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>

            <?php endif; ?>


        </div>


    </section>

</main>



<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->

<script>


    /* =====================================================
       SIDEBAR ELEMENTS
       ===================================================== */

    const sidebar =
        document.getElementById("sidebar");

    const overlay =
        document.getElementById("sidebarOverlay");


    /* =====================================================
       OPEN / CLOSE SIDEBAR
       ===================================================== */

    function toggleSidebar() {

        sidebar.classList.toggle("open");

        overlay.classList.toggle("show");
    }


    function closeSidebar() {

        sidebar.classList.remove("open");

        overlay.classList.remove("show");
    }


    /* =====================================================
       CLOSE SIDEBAR AFTER CLICK
       ===================================================== */

    const navLinks =
        document.querySelectorAll(".nav-link");

    navLinks.forEach(function(link) {

        link.addEventListener(
            "click",
            function() {

                if (window.innerWidth <= 800) {

                    closeSidebar();
                }

            }
        );

    });


    /* =====================================================
       ESCAPE KEY
       ===================================================== */

    document.addEventListener(
        "keydown",
        function(event) {

            if (event.key === "Escape") {

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

            if (window.innerWidth > 800) {

                closeSidebar();
            }

        }
    );

</script>


</body>

</html>