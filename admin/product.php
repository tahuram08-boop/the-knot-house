<?php
session_start();

/* =========================================================
   ADMIN SESSION CHECK
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


/* =========================================================
   CHECK WHETHER PRODUCTS TABLE EXISTS
   ========================================================= */

$tableExists = false;

$tableCheck = $conn->query("SHOW TABLES LIKE 'products'");

if ($tableCheck && $tableCheck->num_rows > 0) {
    $tableExists = true;
}


/* =========================================================
   SEARCH AND FILTER
   ========================================================= */

$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");


/* =========================================================
   GET PRODUCTS
   ========================================================= */

$products = [];

if ($tableExists) {

    $sql = "SELECT * FROM products WHERE 1=1";

    $params = [];
    $types = "";

    /* Search */
    if ($search !== "") {
        $sql .= " AND (
                    product_name LIKE ?
                    OR description LIKE ?
                    OR category LIKE ?
                  )";

        $searchValue = "%" . $search . "%";

        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;

        $types .= "sss";
    }


    /* Category */
    if ($category !== "") {
        $sql .= " AND category = ?";

        $params[] = $category;
        $types .= "s";
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


/* =========================================================
   PRODUCT COUNT
   ========================================================= */

$totalProducts = count($products);

if ($tableExists) {

    $countResult = $conn->query("SELECT COUNT(*) AS total FROM products");

    if ($countResult) {
        $countRow = $countResult->fetch_assoc();
        $totalProducts = (int)$countRow["total"];
    }
}


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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Products | The Knot House Admin</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        /* =====================================================
           ROOT VARIABLES
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

            --shadow: 0 10px 30px rgba(81, 61, 61, 0.08);

        }


        /* =====================================================
           RESET
           ===================================================== */

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


        button,
        input,
        select {
            font-family: inherit;
        }


        /* =====================================================
           LAYOUT
           ===================================================== */

        .admin-layout {

            display: flex;

            min-height: 100vh;
        }


        /* =====================================================
           SIDEBAR
           ===================================================== */

        .sidebar {

            width: 260px;

            background: var(--white);

            border-right: 1px solid var(--border);

            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            z-index: 1000;

            display: flex;

            flex-direction: column;

            transition: 0.3s ease;
        }


        .brand {

            padding: 26px 22px;

            border-bottom: 1px solid var(--border);
        }


        .brand a {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .brand-logo {

            width: 46px;

            height: 46px;

            border-radius: 12px;

            background: var(--light-pink);

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--pink);

            font-size: 24px;
        }


        .brand-text h2 {

            font-family: "Playfair Display", serif;

            font-size: 19px;

            color: var(--brown);
        }


        .brand-text span {

            font-size: 11px;

            color: var(--pink);

            text-transform: uppercase;

            letter-spacing: 1.3px;
        }


        /* =====================================================
           NAVIGATION
           ===================================================== */

        .sidebar-nav {

            padding: 22px 14px;

            flex: 1;

            overflow-y: auto;
        }


        .nav-title {

            font-size: 10px;

            font-weight: 700;

            color: #aa9999;

            letter-spacing: 1.4px;

            text-transform: uppercase;

            padding: 0 12px 10px;
        }


        .nav-link {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 12px 14px;

            margin-bottom: 5px;

            border-radius: 10px;

            color: var(--brown-light);

            font-size: 14px;

            font-weight: 500;

            transition: 0.25s ease;
        }


        .nav-link:hover {

            background: var(--light-pink);

            color: var(--pink);

            transform: translateX(2px);
        }


        .nav-link.active {

            background: var(--light-pink);

            color: var(--pink);

            font-weight: 700;
        }


        .nav-icon {

            width: 22px;

            text-align: center;

            font-size: 17px;
        }


        /* =====================================================
           SIDEBAR BOTTOM
           ===================================================== */

        .sidebar-bottom {

            padding: 16px;

            border-top: 1px solid var(--border);
        }


        .admin-mini {

            background: var(--cream);

            border: 1px solid var(--border);

            padding: 12px;

            border-radius: 12px;

            display: flex;

            gap: 10px;

            align-items: center;

            margin-bottom: 10px;
        }


        .admin-avatar {

            width: 38px;

            height: 38px;

            background: var(--pink);

            color: white;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: 700;

            flex-shrink: 0;
        }


        .admin-mini-info {

            min-width: 0;
        }


        .admin-mini-info strong {

            display: block;

            font-size: 12px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .admin-mini-info span {

            display: block;

            font-size: 10px;

            color: var(--brown-light);

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .logout-link {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding: 10px;

            border-radius: 9px;

            background: var(--red-light);

            color: var(--red);

            font-size: 13px;

            font-weight: 600;

            transition: 0.2s ease;
        }


        .logout-link:hover {

            background: #f6dcdc;
        }


        /* =====================================================
           MAIN CONTENT
           ===================================================== */

        .main-content {

            margin-left: 260px;

            width: calc(100% - 260px);

            min-height: 100vh;
        }


        /* =====================================================
           TOPBAR
           ===================================================== */

        .topbar {

            height: 76px;

            background: rgba(255, 250, 247, 0.94);

            backdrop-filter: blur(12px);

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 32px;

            position: sticky;

            top: 0;

            z-index: 500;
        }


        .topbar-left {

            display: flex;

            align-items: center;

            gap: 14px;
        }


        .menu-btn {

            display: none;

            border: none;

            background: var(--white);

            width: 40px;

            height: 40px;

            border-radius: 9px;

            cursor: pointer;

            font-size: 21px;

            color: var(--brown);
        }


        .page-title {

            font-family: "Playfair Display", serif;

            font-size: 24px;

            color: var(--brown);
        }


        .topbar-right {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .view-store {

            padding: 9px 14px;

            border: 1px solid var(--border);

            border-radius: 9px;

            background: white;

            color: var(--brown);

            font-size: 12px;

            font-weight: 600;

            transition: 0.2s ease;
        }


        .view-store:hover {

            border-color: var(--pink);

            color: var(--pink);
        }


        .top-admin {

            display: flex;

            align-items: center;

            gap: 8px;

            padding-left: 12px;

            border-left: 1px solid var(--border);

            font-size: 13px;

            color: var(--brown-light);
        }


        .top-avatar {

            width: 34px;

            height: 34px;

            background: var(--pink);

            color: white;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            font-weight: 700;
        }


        /* =====================================================
           CONTENT WRAPPER
           ===================================================== */

        .content {

            padding: 32px;
        }


        /* =====================================================
           PAGE HEADER
           ===================================================== */

        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 25px;
        }


        .page-heading h1 {

            font-family: "Playfair Display", serif;

            font-size: 31px;

            margin-bottom: 6px;
        }


        .page-heading p {

            color: var(--brown-light);

            font-size: 14px;
        }


        .add-product-btn {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 12px 19px;

            background: var(--pink);

            color: white;

            border-radius: 10px;

            font-size: 13px;

            font-weight: 700;

            box-shadow: 0 7px 18px rgba(169, 103, 113, 0.20);

            transition: 0.25s ease;
        }


        .add-product-btn:hover {

            background: var(--dark-pink);

            transform: translateY(-2px);
        }


        /* =====================================================
           SUMMARY CARDS
           ===================================================== */

        .summary-grid {

            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 17px;

            margin-bottom: 24px;
        }


        .summary-card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 14px;

            padding: 19px;

            box-shadow: var(--shadow);

            display: flex;

            align-items: center;

            gap: 14px;
        }


        .summary-icon {

            width: 48px;

            height: 48px;

            border-radius: 12px;

            background: var(--light-pink);

            color: var(--pink);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;
        }


        .summary-card strong {

            display: block;

            font-size: 23px;

            margin-bottom: 2px;
        }


        .summary-card span {

            font-size: 12px;

            color: var(--brown-light);
        }


        /* =====================================================
           FILTER BOX
           ===================================================== */

        .filter-box {

            background: white;

            border: 1px solid var(--border);

            border-radius: 14px;

            padding: 18px;

            margin-bottom: 20px;

            box-shadow: var(--shadow);
        }


        .filter-form {

            display: flex;

            gap: 11px;

            align-items: center;
        }


        .search-wrapper {

            position: relative;

            flex: 1;
        }


        .search-icon {

            position: absolute;

            left: 14px;

            top: 50%;

            transform: translateY(-50%);

            color: #aa9999;

            font-size: 15px;
        }


        .search-input {

            width: 100%;

            height: 44px;

            border: 1px solid var(--border);

            border-radius: 9px;

            outline: none;

            padding: 0 15px 0 40px;

            color: var(--brown);

            background: var(--cream);

            font-size: 13px;

            transition: 0.2s ease;
        }


        .search-input:focus {

            border-color: var(--pink);

            background: white;

            box-shadow: 0 0 0 3px rgba(169, 103, 113, 0.08);
        }


        .category-select {

            height: 44px;

            min-width: 170px;

            padding: 0 35px 0 13px;

            border: 1px solid var(--border);

            border-radius: 9px;

            outline: none;

            color: var(--brown);

            background: var(--cream);

            font-size: 13px;

            cursor: pointer;
        }


        .category-select:focus {

            border-color: var(--pink);
        }


        .filter-btn {

            height: 44px;

            padding: 0 18px;

            border: none;

            border-radius: 9px;

            background: var(--brown);

            color: white;

            font-weight: 600;

            font-size: 13px;

            cursor: pointer;

            transition: 0.2s ease;
        }


        .filter-btn:hover {

            background: var(--pink);
        }


        .reset-btn {

            height: 44px;

            padding: 0 15px;

            display: flex;

            align-items: center;

            border: 1px solid var(--border);

            border-radius: 9px;

            color: var(--brown-light);

            font-size: 13px;

            background: white;
        }


        .reset-btn:hover {

            color: var(--pink);

            border-color: var(--pink);
        }


        /* =====================================================
           TABLE
           ===================================================== */

        .products-box {

            background: white;

            border: 1px solid var(--border);

            border-radius: 15px;

            box-shadow: var(--shadow);

            overflow: hidden;
        }


        .products-box-header {

            padding: 18px 20px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            border-bottom: 1px solid var(--border);
        }


        .products-box-header h2 {

            font-size: 16px;
        }


        .results-count {

            font-size: 12px;

            color: var(--brown-light);
        }


        .table-container {

            width: 100%;

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 900px;
        }


        th {

            background: #fdf7f5;

            color: #806969;

            text-transform: uppercase;

            letter-spacing: 0.6px;

            font-size: 10px;

            font-weight: 700;

            padding: 13px 16px;

            text-align: left;

            white-space: nowrap;

            border-bottom: 1px solid var(--border);
        }


        td {

            padding: 14px 16px;

            border-bottom: 1px solid #f1e7e4;

            font-size: 13px;

            vertical-align: middle;
        }


        tbody tr {

            transition: 0.2s ease;
        }


        tbody tr:hover {

            background: #fffbf9;
        }


        tbody tr:last-child td {

            border-bottom: none;
        }


        /* =====================================================
           PRODUCT IMAGE
           ===================================================== */

        .product-image {

            width: 58px;

            height: 58px;

            border-radius: 10px;

            object-fit: cover;

            display: block;

            border: 1px solid var(--border);

            background: var(--light-pink);
        }


        .image-placeholder {

            width: 58px;

            height: 58px;

            border-radius: 10px;

            background: var(--light-pink);

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--pink);

            font-size: 23px;

            border: 1px solid var(--border);
        }


        /* =====================================================
           PRODUCT NAME
           ===================================================== */

        .product-name {

            font-weight: 700;

            color: var(--brown);

            margin-bottom: 3px;
        }


        .product-description {

            color: #9a8888;

            font-size: 11px;

            max-width: 230px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        /* =====================================================
           CATEGORY
           ===================================================== */

        .category-badge {

            display: inline-flex;

            padding: 5px 9px;

            background: var(--light-pink);

            color: var(--dark-pink);

            border-radius: 20px;

            font-size: 10px;

            font-weight: 700;
        }


        /* =====================================================
           PRICE
           ===================================================== */

        .price {

            font-weight: 700;

            color: var(--brown);

            white-space: nowrap;
        }


        /* =====================================================
           STOCK
           ===================================================== */

        .stock-number {

            font-weight: 700;
        }


        .stock-low {

            color: var(--orange);
        }


        .stock-out {

            color: var(--red);
        }


        .stock-good {

            color: var(--green);
        }


        /* =====================================================
           STATUS
           ===================================================== */

        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 700;
        }


        .status-badge::before {

            content: "";

            width: 6px;

            height: 6px;

            border-radius: 50%;

            background: currentColor;
        }


        .status-active {

            color: var(--green);

            background: var(--green-light);
        }


        .status-inactive {

            color: var(--red);

            background: var(--red-light);
        }


        /* =====================================================
           ACTION BUTTONS
           ===================================================== */

        .actions {

            display: flex;

            gap: 7px;
        }


        .action-btn {

            width: 34px;

            height: 34px;

            border-radius: 8px;

            display: flex;

            align-items: center;

            justify-content: center;

            border: 1px solid var(--border);

            background: white;

            font-size: 14px;

            transition: 0.2s ease;
        }


        .edit-btn {

            color: var(--pink);
        }


        .edit-btn:hover {

            background: var(--light-pink);

            border-color: var(--pink);
        }


        .delete-btn {

            color: var(--red);
        }


        .delete-btn:hover {

            background: var(--red-light);

            border-color: var(--red);
        }


        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .empty-state {

            text-align: center;

            padding: 70px 25px;
        }


        .empty-icon {

            width: 76px;

            height: 76px;

            margin: 0 auto 17px;

            border-radius: 50%;

            background: var(--light-pink);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 31px;

            color: var(--pink);
        }


        .empty-state h3 {

            font-family: "Playfair Display", serif;

            font-size: 22px;

            margin-bottom: 7px;
        }


        .empty-state p {

            color: var(--brown-light);

            font-size: 13px;

            max-width: 460px;

            margin: 0 auto 20px;

            line-height: 1.6;
        }


        .empty-state .add-product-btn {

            display: inline-flex;
        }


        /* =====================================================
           DATABASE MESSAGE
           ===================================================== */

        .database-message {

            background: var(--orange-light);

            border: 1px solid #f0d7ae;

            color: #916a37;

            border-radius: 11px;

            padding: 13px 15px;

            margin-bottom: 20px;

            font-size: 13px;

            line-height: 1.5;
        }


        .database-message strong {

            display: block;

            margin-bottom: 3px;
        }


        /* =====================================================
           MOBILE OVERLAY
           ===================================================== */

        .sidebar-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background: rgba(50, 35, 35, 0.35);

            z-index: 900;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1100px) {

            .summary-grid {

                grid-template-columns: repeat(3, 1fr);
            }

            .content {

                padding: 25px;
            }

        }


        @media (max-width: 850px) {

            .sidebar {

                transform: translateX(-100%);
            }


            .sidebar.open {

                transform: translateX(0);
            }


            .sidebar-overlay.show {

                display: block;
            }


            .main-content {

                margin-left: 0;

                width: 100%;
            }


            .menu-btn {

                display: block;
            }


            .topbar {

                padding: 0 18px;
            }


            .top-admin {

                display: none;
            }


            .summary-grid {

                grid-template-columns: 1fr 1fr;
            }

        }


        @media (max-width: 650px) {

            .content {

                padding: 18px;
            }


            .page-header {

                flex-direction: column;
            }


            .add-product-btn {

                width: 100%;

                justify-content: center;
            }


            .summary-grid {

                grid-template-columns: 1fr;
            }


            .filter-form {

                flex-direction: column;

                align-items: stretch;
            }


            .category-select,
            .filter-btn,
            .reset-btn {

                width: 100%;
            }


            .page-title {

                font-size: 20px;
            }


            .page-heading h1 {

                font-size: 26px;
            }


            .view-store {

                display: none;
            }

        }


        @media (max-width: 430px) {

            .topbar {

                height: 68px;
            }


            .content {

                padding: 14px;
            }


            .filter-box {

                padding: 13px;
            }


            .products-box-header {

                padding: 15px;
            }

        }

    </style>

</head>


<body>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="sidebar" id="sidebar">

        <div class="brand">

            <a href="dashboard.php">

                <div class="brand-logo">
                    🧶
                </div>

                <div class="brand-text">

                    <h2>The Knot House</h2>

                    <span>Admin Panel</span>

                </div>

            </a>

        </div>


        <nav class="sidebar-nav">

            <div class="nav-title">
                Main Menu
            </div>


            <a href="dashboard.php" class="nav-link">

                <span class="nav-icon">📊</span>

                <span>Dashboard</span>

            </a>


            <a href="products.php" class="nav-link active">

                <span class="nav-icon">🛍️</span>

                <span>Products</span>

            </a>


            <a href="orders.php" class="nav-link">

                <span class="nav-icon">📦</span>

                <span>Orders</span>

            </a>


            <a href="customers.php" class="nav-link">

                <span class="nav-icon">👥</span>

                <span>Customers</span>

            </a>


            <a href="inventory.php" class="nav-link">

                <span class="nav-icon">📋</span>

                <span>Inventory</span>

            </a>


            <div class="nav-title" style="margin-top: 20px;">
                Manage
            </div>


            <a href="feedback.php" class="nav-link">

                <span class="nav-icon">💬</span>

                <span>Feedback</span>

            </a>


            <a href="profile.php" class="nav-link">

                <span class="nav-icon">⚙️</span>

                <span>Profile</span>

            </a>

        </nav>


        <div class="sidebar-bottom">

            <div class="admin-mini">

                <div class="admin-avatar">

                    <?php
                    echo strtoupper(substr($adminName, 0, 1));
                    ?>

                </div>

                <div class="admin-mini-info">

                    <strong>
                        <?php echo htmlspecialchars($adminName); ?>
                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

            </div>


            <a href="logout.php" class="logout-link">

                <span>↪</span>

                Logout

            </a>

        </div>

    </aside>


    <!-- MOBILE OVERLAY -->

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
        onclick="closeSidebar()">
    </div>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
             ================================================= -->

        <header class="topbar">

            <div class="topbar-left">

                <button
                    class="menu-btn"
                    onclick="toggleSidebar()"
                    aria-label="Open menu">

                    ☰

                </button>


                <div class="page-title">
                    Products
                </div>

            </div>


            <div class="topbar-right">

                <a
                    href="../index.html"
                    target="_blank"
                    class="view-store">

                    🌐 View Store

                </a>


                <div class="top-admin">

                    <div class="top-avatar">

                        <?php
                        echo strtoupper(substr($adminName, 0, 1));
                        ?>

                    </div>

                    <span>
                        <?php echo htmlspecialchars($adminName); ?>
                    </span>

                </div>

            </div>

        </header>


        <!-- =================================================
             PAGE CONTENT
             ================================================= -->

        <section class="content">


            <!-- PAGE HEADER -->

            <div class="page-header">

                <div class="page-heading">

                    <h1>
                        Product Management
                    </h1>

                    <p>
                        Add, manage and organize your crochet products.
                    </p>

                </div>


                <a
                    href="add-product.php"
                    class="add-product-btn">

                    <span>＋</span>

                    Add New Product

                </a>

            </div>


            <?php if (!$tableExists): ?>

                <div class="database-message">

                    <strong>⚠️ Products table not found</strong>

                    The product management page is ready, but the
                    <b>products</b> table has not been created in your
                    <b>knot_house</b> database yet. Create the table first,
                    then refresh this page.

                </div>

            <?php endif; ?>


            <!-- SUMMARY -->

            <div class="summary-grid">


                <div class="summary-card">

                    <div class="summary-icon">
                        🛍️
                    </div>

                    <div>

                        <strong>
                            <?php echo $totalProducts; ?>
                        </strong>

                        <span>
                            Total Products
                        </span>

                    </div>

                </div>


                <div class="summary-card">

                    <div class="summary-icon">
                        🧶
                    </div>

                    <div>

                        <strong>
                            <?php echo count($categories); ?>
                        </strong>

                        <span>
                            Categories
                        </span>

                    </div>

                </div>


                <div class="summary-card">

                    <div class="summary-icon">
                        ✨
                    </div>

                    <div>

                        <strong>
                            Handmade
                        </strong>

                        <span>
                            Product Collection
                        </span>

                    </div>

                </div>


            </div>


            <!-- FILTER -->

            <div class="filter-box">

                <form
                    method="GET"
                    action="products.php"
                    class="filter-form">


                    <div class="search-wrapper">

                        <span class="search-icon">
                            🔍
                        </span>

                        <input
                            type="text"
                            name="search"
                            class="search-input"
                            placeholder="Search products by name, category..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                    </div>


                    <select
                        name="category"
                        class="category-select">

                        <option value="">
                            All Categories
                        </option>


                        <?php foreach ($categories as $cat): ?>

                            <option
                                value="<?php echo htmlspecialchars($cat); ?>"
                                <?php
                                if ($category === $cat) {
                                    echo "selected";
                                }
                                ?>>

                                <?php echo htmlspecialchars($cat); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <button
                        type="submit"
                        class="filter-btn">

                        Filter

                    </button>


                    <?php if ($search !== "" || $category !== ""): ?>

                        <a
                            href="products.php"
                            class="reset-btn">

                            Reset

                        </a>

                    <?php endif; ?>


                </form>

            </div>


            <!-- =================================================
                 PRODUCTS TABLE
                 ================================================= -->

            <div class="products-box">


                <div class="products-box-header">

                    <h2>
                        All Products
                    </h2>

                    <span class="results-count">

                        <?php

                        if ($search !== "" || $category !== "") {

                            echo count($products) . " result(s) found";

                        } else {

                            echo $totalProducts . " product(s)";

                        }

                        ?>

                    </span>

                </div>


                <?php if (!$tableExists): ?>


                    <div class="empty-state">

                        <div class="empty-icon">
                            🛍️
                        </div>

                        <h3>
                            Product Table Not Ready
                        </h3>

                        <p>
                            Your admin product page is ready.
                            Create the <strong>products</strong> table
                            in phpMyAdmin and then you can start adding
                            your crochet products.
                        </p>

                        <a
                            href="add-product.php"
                            class="add-product-btn">

                            ＋ Add Product

                        </a>

                    </div>


                <?php elseif (empty($products)): ?>


                    <div class="empty-state">

                        <div class="empty-icon">
                            🧶
                        </div>

                        <h3>
                            No Products Found
                        </h3>

                        <p>

                            <?php if ($search !== "" || $category !== ""): ?>

                                No products match your current search
                                or category filter.

                            <?php else: ?>

                                You haven't added any products yet.
                                Start building your crochet collection.

                            <?php endif; ?>

                        </p>


                        <?php if ($search !== "" || $category !== ""): ?>

                            <a
                                href="products.php"
                                class="add-product-btn">

                                View All Products

                            </a>

                        <?php else: ?>

                            <a
                                href="add-product.php"
                                class="add-product-btn">

                                ＋ Add First Product

                            </a>

                        <?php endif; ?>

                    </div>


                <?php else: ?>


                    <div class="table-container">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Image
                                    </th>

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

                                $productId =
                                    $product["id"] ?? "";

                                $productName =
                                    $product["product_name"]
                                    ?? $product["name"]
                                    ?? "Unnamed Product";

                                $productCategory =
                                    $product["category"]
                                    ?? "Other";

                                $productPrice =
                                    $product["price"]
                                    ?? 0;

                                $productDescription =
                                    $product["description"]
                                    ?? "";

                                $productImage =
                                    $product["image"]
                                    ?? "";

                                $productStock =
                                    $product["stock"]
                                    ?? $product["stock_quantity"]
                                    ?? 0;

                                $productStatus =
                                    $product["status"]
                                    ?? "Active";


                                /* Stock class */

                                if ((int)$productStock <= 0) {

                                    $stockClass = "stock-out";

                                } elseif ((int)$productStock <= 5) {

                                    $stockClass = "stock-low";

                                } else {

                                    $stockClass = "stock-good";

                                }


                                /* Status class */

                                $isActive =
                                    strtolower($productStatus) === "active";

                                $statusClass =
                                    $isActive
                                    ? "status-active"
                                    : "status-inactive";


                                ?>


                                <tr>


                                    <!-- IMAGE -->

                                    <td>

                                        <?php if ($productImage !== ""): ?>

                                            <img
                                                src="<?php echo htmlspecialchars($productImage); ?>"
                                                alt="<?php echo htmlspecialchars($productName); ?>"
                                                class="product-image"
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

                                    </td>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div class="product-name">

                                            <?php
                                            echo htmlspecialchars($productName);
                                            ?>

                                        </div>


                                        <?php if ($productDescription !== ""): ?>

                                            <div class="product-description">

                                                <?php
                                                echo htmlspecialchars(
                                                    $productDescription
                                                );
                                                ?>

                                            </div>

                                        <?php endif; ?>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <span class="category-badge">

                                            <?php
                                            echo htmlspecialchars(
                                                $productCategory
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- PRICE -->

                                    <td>

                                        <span class="price">

                                            ₹<?php
                                            echo number_format(
                                                (float)$productPrice,
                                                2
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- STOCK -->

                                    <td>

                                        <span
                                            class="stock-number <?php echo $stockClass; ?>">

                                            <?php
                                            echo (int)$productStock;
                                            ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="status-badge <?php echo $statusClass; ?>">

                                            <?php
                                            echo htmlspecialchars(
                                                $productStatus
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="actions">


                                            <a
                                                href="edit-product.php?id=<?php echo (int)$productId; ?>"
                                                class="action-btn edit-btn"
                                                title="Edit Product">

                                                ✏️

                                            </a>


                                            <a
                                                href="delete-product.php?id=<?php echo (int)$productId; ?>"
                                                class="action-btn delete-btn"
                                                title="Delete Product"
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


</div>


<script>

    /* =========================================================
       SIDEBAR
       ========================================================= */

    function toggleSidebar() {

        const sidebar =
            document.getElementById("sidebar");

        const overlay =
            document.getElementById("sidebarOverlay");

        sidebar.classList.toggle("open");

        overlay.classList.toggle("show");
    }


    function closeSidebar() {

        const sidebar =
            document.getElementById("sidebar");

        const overlay =
            document.getElementById("sidebarOverlay");

        sidebar.classList.remove("open");

        overlay.classList.remove("show");
    }


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
       CLOSE SIDEBAR AFTER CLICKING LINK ON MOBILE
       ========================================================= */

    document.querySelectorAll(".nav-link").forEach(function(link) {

        link.addEventListener("click", function() {

            if (window.innerWidth <= 850) {

                closeSidebar();

            }

        });

    });

</script>


</body>

</html>


<?php

$conn->close();

?>