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

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/* =========================================================
   ADMIN INFORMATION
   ========================================================= */
$adminName = $_SESSION["admin_name"] ?? "Admin";
$adminEmail = $_SESSION["admin_email"] ?? "";

$adminInitial = strtoupper(substr(trim($adminName), 0, 1));

if ($adminInitial === "") {
    $adminInitial = "A";
}

/* =========================================================
   CHECK PRODUCTS TABLE
   ========================================================= */
$tableExists = false;

$tableCheck = $conn->query("SHOW TABLES LIKE 'products'");

if ($tableCheck && $tableCheck->num_rows > 0) {
    $tableExists = true;
}

/* =========================================================
   SEARCH & FILTER
   ========================================================= */
$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");

$products = [];

$totalProducts = 0;
$activeProducts = 0;
$lowStockProducts = 0;

/* =========================================================
   CATEGORY LIST
   ========================================================= */
$categories = [
    "Bags",
    "Flowers",
    "Toys",
    "Clothes",
    "Accessories",
    "Decor"
];

/* =========================================================
   FETCH PRODUCTS
   ========================================================= */
if ($tableExists) {

    /* -------------------------
       TOTAL PRODUCTS
       ------------------------- */
    $result = $conn->query("SELECT COUNT(*) AS total FROM products");

    if ($result) {
        $row = $result->fetch_assoc();
        $totalProducts = (int)$row["total"];
    }

    /* -------------------------
       ACTIVE PRODUCTS
       ------------------------- */
    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM products
        WHERE LOWER(status) = 'active'
    ");

    if ($result) {
        $row = $result->fetch_assoc();
        $activeProducts = (int)$row["total"];
    }

    /* -------------------------
       LOW / OUT OF STOCK
       ------------------------- */
    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM products
        WHERE stock <= 5
    ");

    if ($result) {
        $row = $result->fetch_assoc();
        $lowStockProducts = (int)$row["total"];
    }

    /* -------------------------
       PRODUCT LIST
       ------------------------- */

    $sql = "
        SELECT *
        FROM products
        WHERE 1=1
    ";

    $types = "";
    $params = [];

    /* Search */
    if ($search !== "") {

        $sql .= "
            AND (
                product_name LIKE ?
                OR description LIKE ?
                OR category LIKE ?
            )
        ";

        $searchValue = "%" . $search . "%";

        $types .= "sss";

        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
    }

    /* Category */
    if ($category !== "") {

        $sql .= " AND category = ? ";

        $types .= "s";
        $params[] = $category;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
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

    <title>Products | The Knot House Admin</title>

    <!-- Google Fonts -->
    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
          rel="stylesheet">

    <style>

        /* =========================================================
           ROOT COLORS
           SAME COLOR SYSTEM AS DASHBOARD
           ========================================================= */

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

            --sidebar-width: 255px;

            --shadow:
                0 8px 30px rgba(81, 61, 61, 0.06);

            --shadow-hover:
                0 12px 35px rgba(81, 61, 61, 0.10);
        }


        /* =========================================================
           RESET
           ========================================================= */

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


        /* =========================================================
           SIDEBAR
           ========================================================= */

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

            transition: transform 0.3s ease;

            box-shadow:
                5px 0 25px rgba(81, 61, 61, 0.03);
        }


        /* =========================================================
           SIDEBAR BRAND
           ========================================================= */

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

            font-size:30px;

            color: white;

            margin-right: 12px;

            box-shadow:
                0 6px 15px rgba(169, 103, 113, 0.22);
        }

        .brand-text {
            line-height: 1.1;
        }

        .brand-title {

            font-family: "Playfair Display", serif;

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


        /* =========================================================
           SIDEBAR NAVIGATION
           ========================================================= */

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


        /* =========================================================
           SIDEBAR BOTTOM
           ========================================================= */

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

            color: var(--red);

            transform: scale(1.05);
        }


        /* =========================================================
           MAIN AREA
           ========================================================= */

        .main {

            margin-left: var(--sidebar-width);

            min-height: 100vh;
        }


        /* =========================================================
           TOPBAR
           ========================================================= */

        .topbar {

            height: 76px;

            background: rgba(255, 250, 247, 0.94);

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

            font-family: "Playfair Display", serif;

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


        /* =========================================================
           CONTENT
           ========================================================= */

        .content {

            padding: 30px;
        }


        /* =========================================================
           PAGE HEADER
           ========================================================= */

        .page-header {

            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 26px;
        }

        .page-heading h1 {

            font-family: "Playfair Display", serif;

            font-size: 31px;

            color: var(--brown);

            line-height: 1.15;

            margin-bottom: 7px;
        }

        .page-heading p {

            font-size: 15px;

            color: var(--brown-light);

            max-width: 650px;

            line-height: 1.6;
        }

        .add-product-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            background: var(--pink);

            color: white;

            padding: 11px 17px;

            border-radius: 10px;

            border: none;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;

            transition: 0.25s ease;

            box-shadow:
                0 6px 16px rgba(169, 103, 113, 0.18);
        }

        .add-product-btn:hover {

            background: var(--dark-pink);

            transform: translateY(-2px);

            box-shadow:
                0 9px 20px rgba(169, 103, 113, 0.23);
        }


        /* =========================================================
           DATABASE WARNING
           ========================================================= */

        .database-warning {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            background: var(--orange-light);

            border: 1px solid #efd8b7;

            color: #8c612f;

            border-radius: 12px;

            padding: 14px 16px;

            margin-bottom: 24px;

            font-size: 13px;

            line-height: 1.5;
        }

        .warning-icon {

            font-size: 18px;

            flex-shrink: 0;
        }


        /* =========================================================
           SUMMARY CARDS
           ========================================================= */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 18px;

            margin-bottom: 24px;
        }

        .stat-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 15px;

            padding: 19px;

            box-shadow: var(--shadow);

            display: flex;

            align-items: center;

            gap: 14px;

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

            font-size: 21px;

            flex-shrink: 0;
        }

        .stat-icon.pink {

            background: var(--light-pink);

            color: var(--pink);
        }

        .stat-icon.green {

            background: var(--green-light);

            color: var(--green);
        }

        .stat-icon.orange {

            background: var(--orange-light);

            color: var(--orange);
        }

        .stat-content {

            min-width: 0;
        }

        .stat-label {

            font-size: 15px;

            color: var(--brown-light);

            font-weight: 600;

            margin-bottom: 4px;
        }

        .stat-number {

            font-size: 23px;

            font-weight: 700;

            color: var(--brown);

            line-height: 1;
        }


        /* =========================================================
           FILTER CARD
           ========================================================= */

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

            transform: translateY(-50%);

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

            font-size: 15px;

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


        /* =========================================================
           TABLE CARD
           ========================================================= */

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

            font-family: "Playfair Display", serif;

            font-size: 22px;

            color: var(--brown);
        }

        .table-heading p {

            font-size: 12px;

            color: var(--brown-light);

            margin-top: 3px;
        }

        .result-count {

            padding: 7px 11px;

            border-radius: 8px;

            background: var(--light-pink);

            color: var(--dark-pink);

            font-size: 13spx;

            font-weight: 700;

            white-space: nowrap;
        }

        .table-wrapper {

            width: 100%;

            overflow-x: auto;
        }

        table {

            width: 100%;

            min-width: 850px;

            border-collapse: collapse;
        }

        thead th {

            background: var(--cream);

            color: var(--brown-light);

            font-size: 12px;

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


        /* =========================================================
           PRODUCT IMAGE
           ========================================================= */

        .product-cell {

            display: flex;

            align-items: center;

            gap: 11px;

            min-width: 230px;
        }

        .product-image {

            width: 50px;
            height: 50px;

            border-radius: 11px;

            overflow: hidden;

            background: var(--light-pink);

            border: 1px solid var(--border);

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;
        }

        .product-image img {

            width: 100%;
            height: 100%;

            object-fit: cover;

            display: block;
        }

        .image-placeholder {

            font-size: 21px;

            color: var(--pink);
        }

        .product-info {

            min-width: 0;
        }

        .product-name {

            font-size: 15px;

            font-weight: 700;

            color: var(--brown);

            max-width: 210px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .product-description {

            font-size: 13px;

            color: var(--brown-light);

            max-width: 210px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

            margin-top: 3px;
        }


        /* =========================================================
           CATEGORY
           ========================================================= */

        .category-badge {

            display: inline-flex;

            align-items: center;

            padding: 5px 9px;

            border-radius: 7px;

            background: var(--light-pink);

            color: var(--dark-pink);

            font-size: 15px;

            font-weight: 700;

            white-space: nowrap;
        }


        /* =========================================================
           PRICE
           ========================================================= */

        .price {

            font-weight: 700;

            font-size: 15px;

            color: var(--brown);

            white-space: nowrap;
        }


        /* =========================================================
           STOCK
           ========================================================= */

        .stock-badge {

            display: inline-flex;

            align-items: center;

            padding: 5px 9px;

            border-radius: 7px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }

        .stock-good {

            color: var(--green);

            background: var(--green-light);
        }

        .stock-low {

            color: var(--orange);

            background: var(--orange-light);
        }

        .stock-out {

            color: var(--red);

            background: var(--red-light);
        }


        /* =========================================================
           STATUS
           ========================================================= */

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

        .status-active {

            background: var(--green-light);

            color: var(--green);
        }

        .status-active .status-dot {

            background: var(--green);
        }

        .status-inactive {

            background: var(--red-light);

            color: var(--red);
        }

        .status-inactive .status-dot {

            background: var(--red);
        }


        /* =========================================================
           ACTIONS
           ========================================================= */

        .actions {

            display: flex;

            align-items: center;

            gap: 6px;
        }

        .action-btn {

            width: 34px;
            height: 34px;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border: 1px solid var(--border);

            background: var(--white);

            font-size: 14px;

            transition: 0.2s ease;
        }

        .edit-btn {

            color: var(--pink);
        }

        .edit-btn:hover {

            background: var(--light-pink);

            border-color: var(--soft-pink);

            transform: translateY(-1px);
        }

        .delete-btn {

            color: var(--red);
        }

        .delete-btn:hover {

            background: var(--red-light);

            border-color: #efcccc;

            transform: translateY(-1px);
        }


        /* =========================================================
           EMPTY STATE
           ========================================================= */

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

            font-family: "Playfair Display", serif;

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


        /* =========================================================
           MOBILE OVERLAY
           ========================================================= */

        .sidebar-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background: rgba(81, 61, 61, 0.35);

            z-index: 900;
        }


        /* =========================================================
           RESPONSIVE - TABLET
           ========================================================= */

        @media (max-width: 1050px) {

            :root {
                --sidebar-width: 235px;
            }

            .content {
                padding: 24px;
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

            .stats-grid {
                gap: 13px;
            }

            .stat-card {
                padding: 15px;
            }
        }


        /* =========================================================
           RESPONSIVE - MOBILE
           ========================================================= */

        @media (max-width: 800px) {

            .sidebar {

                transform: translateX(-100%);
            }

            .sidebar.open {

                transform: translateX(0);
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

            .topbar-right {

                gap: 8px;
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

                margin-bottom: 20px;
            }

            .page-heading h1 {

                font-size: 27px;
            }

            .add-product-btn {

                width: 100%;
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


        /* =========================================================
           RESPONSIVE - SMALL MOBILE
           ========================================================= */

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

                font-size: 19px;
            }

            .stat-number {

                font-size: 21px;
            }

            .table-card {

                border-radius: 12px;
            }

            .table-header {

                padding: 14px;
            }
        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<aside class="sidebar" id="sidebar">


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
               class="nav-link active">

                <span class="nav-icon">
                    🛍️
                </span>

                <span>
                    Products
                </span>

            </a>


            <a href="orders.php"
               class="nav-link">

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


    <!-- SIDEBAR BOTTOM -->

    <div class="sidebar-bottom">

        <div class="admin-mini">

            <div class="admin-avatar">
                <?php echo htmlspecialchars($adminInitial); ?>
            </div>

            <div class="admin-info">

                <div class="admin-name">
                    <?php echo htmlspecialchars($adminName); ?>
                </div>

                <div class="admin-role">
                    Administrator
                </div>

            </div>


            <a href="logout.php"
               class="logout-link"
               title="Logout"
               onclick="return confirm('Are you sure you want to logout?');">

                ↪

            </a>

        </div>

    </div>

</aside>


<!-- SIDEBAR OVERLAY -->

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


            <button class="mobile-menu-btn"
                    id="mobileMenuBtn"
                    onclick="toggleSidebar()"
                    aria-label="Open menu">

                ☰

            </button>


            <div class="topbar-title">

                <h2>
                    Products
                </h2>

                <p>
                    Manage your crochet products
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
                    <?php echo htmlspecialchars($adminInitial); ?>
                </div>

                <div class="top-admin-text">

                    <strong>
                        <?php echo htmlspecialchars($adminName); ?>
                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

            </div>

        </div>

    </header>



    <!-- =====================================================
         PAGE CONTENT
         ===================================================== -->

    <section class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div class="page-heading">

                <h1>
                    Product Management
                </h1>

                <p>
                    Manage your handmade crochet collection
                    from one place.
                </p>

            </div>


            <a href="add-product.php"
               class="add-product-btn">

                <span>＋</span>

                <span>
                    Add New Product
                </span>

            </a>

        </div>



        <!-- DATABASE WARNING -->

        <?php if (!$tableExists): ?>

            <div class="database-warning">

                <div class="warning-icon">
                    ⚠️
                </div>

                <div>

                    <strong>
                        Products table not found.
                    </strong>

                    Please create the
                    <strong>products</strong>
                    table in the
                    <strong>knot_house</strong>
                    database before adding products.

                </div>

            </div>

        <?php endif; ?>



        <!-- =================================================
             STATISTICS
             ================================================= -->

        <div class="stats-grid">


            <!-- TOTAL -->

            <div class="stat-card">

                <div class="stat-icon pink">
                    🛍️
                </div>

                <div class="stat-content">

                    <div class="stat-label">
                        Total Products
                    </div>

                    <div class="stat-number">
                        <?php echo number_format($totalProducts); ?>
                    </div>

                </div>

            </div>


            <!-- ACTIVE -->

            <div class="stat-card">

                <div class="stat-icon green">
                    ✓
                </div>

                <div class="stat-content">

                    <div class="stat-label">
                        Active Products
                    </div>

                    <div class="stat-number">
                        <?php echo number_format($activeProducts); ?>
                    </div>

                </div>

            </div>


            <!-- LOW STOCK -->

            <div class="stat-card">

                <div class="stat-icon orange">
                    ⚠
                </div>

                <div class="stat-content">

                    <div class="stat-label">
                        Low / Out of Stock
                    </div>

                    <div class="stat-number">
                        <?php echo number_format($lowStockProducts); ?>
                    </div>

                </div>

            </div>

        </div>



        <!-- =================================================
             SEARCH / FILTER
             ================================================= -->

        <div class="filter-card">

            <form method="GET"
                  action="products.php"
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
                        placeholder="Search products..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>


                <!-- CATEGORY -->

                <select
                    name="category"
                    class="form-select">

                    <option value="">
                        All Categories
                    </option>

                    <?php foreach ($categories as $cat): ?>

                        <option
                            value="<?php echo htmlspecialchars($cat); ?>"
                            <?php echo ($category === $cat) ? "selected" : ""; ?>>

                            <?php echo htmlspecialchars($cat); ?>

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

                <?php if ($search !== "" || $category !== ""): ?>

                    <a href="products.php"
                       class="clear-btn">

                        Clear

                    </a>

                <?php else: ?>

                    <span></span>

                <?php endif; ?>

            </form>

        </div>



        <!-- =================================================
             PRODUCTS TABLE
             ================================================= -->

        <div class="table-card">


            <!-- TABLE HEADER -->

            <div class="table-header">

                <div class="table-heading">

                    <h3>
                        All Products
                    </h3>

                    <p>
                        Your crochet product collection
                    </p>

                </div>


                <div class="result-count">

                    <?php echo count($products); ?>

                    <?php echo count($products) == 1 ? "Product" : "Products"; ?>

                </div>

            </div>



            <?php if (!$tableExists): ?>


                <!-- TABLE NOT AVAILABLE -->

                <div class="empty-state">

                    <div class="empty-icon">
                        🗄️
                    </div>

                    <h3>
                        Products Table Not Available
                    </h3>

                    <p>
                        Create the products table in your database
                        to start managing products.
                    </p>

                </div>


            <?php elseif (empty($products)): ?>


                <!-- NO PRODUCTS -->

                <div class="empty-state">

                    <div class="empty-icon">
                        🧶
                    </div>

                    <h3>
                        No Products Found
                    </h3>

                    <p>

                        <?php if ($search !== "" || $category !== ""): ?>

                            No products match your current
                            search or filter.

                        <?php else: ?>

                            You haven't added any products yet.

                        <?php endif; ?>

                    </p>


                    <?php if ($search !== "" || $category !== ""): ?>

                        <a href="products.php"
                           class="empty-add-btn">

                            View All Products

                        </a>

                    <?php else: ?>

                        <a href="add-product.php"
                           class="empty-add-btn">

                            ＋ Add Your First Product

                        </a>

                    <?php endif; ?>

                </div>


            <?php else: ?>


                <!-- PRODUCT TABLE -->

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Stock
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($products as $product): ?>


                            <?php

                            /* PRODUCT VALUES */

                            $productId =
                                (int)($product["id"] ?? 0);

                            $productName =
                                $product["product_name"] ?? "Unnamed Product";

                            $productCategory =
                                $product["category"] ?? "Other";

                            $productPrice =
                                (float)($product["price"] ?? 0);

                            $productDescription =
                                trim($product["description"] ?? "");

                            $productImage =
                                trim($product["image"] ?? "");

                            $stock =
                                (int)($product["stock"] ?? 0);

                            $status =
                                $product["status"] ?? "Active";


                            /* STOCK CLASS */

                            if ($stock <= 0) {

                                $stockClass = "stock-out";

                                $stockText = "Out of Stock";

                            } elseif ($stock <= 5) {

                                $stockClass = "stock-low";

                                $stockText =
                                    $stock . " Left";

                            } else {

                                $stockClass = "stock-good";

                                $stockText =
                                    $stock . " In Stock";
                            }


                            /* STATUS CLASS */

                            if (strtolower($status) === "active") {

                                $statusClass = "status-active";

                            } else {

                                $statusClass = "status-inactive";
                            }

                            ?>


                            <tr>


                                <!-- PRODUCT -->

                                <td>

                                    <div class="product-cell">


                                        <div class="product-image">


                                            <?php if ($productImage !== ""): ?>

                                                <img
                                                    src="<?php echo htmlspecialchars($productImage); ?>"
                                                    alt="<?php echo htmlspecialchars($productName); ?>"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                >

                                                <div
                                                    class="image-placeholder"
                                                    style="display:none;">

                                                    🧶

                                                </div>

                                            <?php else: ?>

                                                <div class="image-placeholder">

                                                    🧶

                                                </div>

                                            <?php endif; ?>


                                        </div>


                                        <div class="product-info">

                                            <div class="product-name"
                                                 title="<?php echo htmlspecialchars($productName); ?>">

                                                <?php echo htmlspecialchars($productName); ?>

                                            </div>


                                            <div class="product-description"
                                                 title="<?php echo htmlspecialchars($productDescription); ?>">

                                                <?php

                                                if ($productDescription !== "") {

                                                    echo htmlspecialchars(
                                                        $productDescription
                                                    );

                                                } else {

                                                    echo "Handmade crochet product";
                                                }

                                                ?>

                                            </div>

                                        </div>

                                    </div>

                                </td>



                                <!-- CATEGORY -->

                                <td>

                                    <span class="category-badge">

                                        <?php echo htmlspecialchars($productCategory); ?>

                                    </span>

                                </td>



                                <!-- PRICE -->

                                <td>

                                    <span class="price">

                                        ₹<?php echo number_format($productPrice, 2); ?>

                                    </span>

                                </td>



                                <!-- STOCK -->

                                <td>

                                    <span class="stock-badge <?php echo $stockClass; ?>">

                                        <?php echo htmlspecialchars($stockText); ?>

                                    </span>

                                </td>



                                <!-- STATUS -->

                                <td>

                                    <span class="status-badge <?php echo $statusClass; ?>">

                                        <span class="status-dot"></span>

                                        <?php echo htmlspecialchars($status); ?>

                                    </span>

                                </td>



                                <!-- ACTIONS -->

                                <td>

                                    <div class="actions">


                                        <!-- EDIT -->

                                        <a
                                            href="edit-product.php?id=<?php echo $productId; ?>"
                                            class="action-btn edit-btn"
                                            title="Edit Product"
                                            aria-label="Edit Product">

                                            ✏️

                                        </a>


                                        <!-- DELETE -->

                                        <a
                                            href="delete-product.php?id=<?php echo $productId; ?>"
                                            class="action-btn delete-btn"
                                            title="Delete Product"
                                            aria-label="Delete Product"
                                            onclick="return confirmDelete('<?php echo htmlspecialchars(addslashes($productName)); ?>');">

                                            🗑️

                                        </a>


                                    </div>

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



<script>

    /* =========================================================
       SIDEBAR
       ========================================================= */

    const sidebar =
        document.getElementById("sidebar");

    const overlay =
        document.getElementById("sidebarOverlay");


    function toggleSidebar() {

        sidebar.classList.toggle("open");

        overlay.classList.toggle("show");
    }


    function closeSidebar() {

        sidebar.classList.remove("open");

        overlay.classList.remove("show");
    }


    /* =========================================================
       CLOSE SIDEBAR WHEN LINK IS CLICKED ON MOBILE
       ========================================================= */

    const navLinks =
        document.querySelectorAll(".nav-link");

    navLinks.forEach(function(link) {

        link.addEventListener("click", function() {

            if (window.innerWidth <= 800) {

                closeSidebar();
            }

        });

    });


    /* =========================================================
       ESCAPE KEY
       ========================================================= */

    document.addEventListener("keydown", function(event) {

        if (event.key === "Escape") {

            closeSidebar();
        }

    });


    /* =========================================================
       DELETE CONFIRMATION
       ========================================================= */

    function confirmDelete(productName) {

        return confirm(
            "Are you sure you want to delete \"" +
            productName +
            "\"?\n\nThis action cannot be undone."
        );

    }


    /* =========================================================
       CLOSE MOBILE SIDEBAR WHEN RESIZING
       ========================================================= */

    window.addEventListener("resize", function() {

        if (window.innerWidth > 800) {

            closeSidebar();
        }

    });

</script>


</body>

</html>