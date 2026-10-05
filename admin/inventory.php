<?php
/* =========================================================
   THE KNOT HOUSE - INVENTORY MANAGEMENT
   File: inventory.php
   ========================================================= */

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
   HELPER FUNCTION
   ========================================================= */

function clean($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   CHECK PRODUCTS TABLE
   ========================================================= */

$productsTableExists = false;

$tableCheck = $conn->query(
    "SHOW TABLES LIKE 'products'"
);

if ($tableCheck && $tableCheck->num_rows > 0) {
    $productsTableExists = true;
}


/* =========================================================
   MESSAGES
   ========================================================= */

$successMessage = "";
$errorMessage = "";


/* =========================================================
   UPDATE STOCK
   ========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["update_stock"])
) {

    $productId = isset($_POST["product_id"])
        ? (int)$_POST["product_id"]
        : 0;

    $newStock = isset($_POST["stock"])
        ? (int)$_POST["stock"]
        : -1;


    if ($productId <= 0) {

        $errorMessage = "Invalid product selected.";

    } elseif ($newStock < 0) {

        $errorMessage =
            "Stock quantity cannot be negative.";

    } elseif (!$productsTableExists) {

        $errorMessage =
            "Products table was not found.";

    } else {

        $stmt = $conn->prepare(
            "UPDATE products SET stock = ? WHERE id = ?"
        );

        if ($stmt) {

            $stmt->bind_param(
                "ii",
                $newStock,
                $productId
            );

            if ($stmt->execute()) {

                $successMessage =
                    "Stock updated successfully.";

            } else {

                $errorMessage =
                    "Unable to update stock.";
            }

            $stmt->close();

        } else {

            $errorMessage =
                "Unable to prepare stock update.";
        }
    }
}


/* =========================================================
   SEARCH & FILTER
   ========================================================= */

$search = trim(
    $_GET["search"] ?? ""
);

$categoryFilter = trim(
    $_GET["category"] ?? ""
);

$stockFilter = trim(
    $_GET["stock_status"] ?? ""
);


/* =========================================================
   CATEGORIES
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
   INVENTORY STATISTICS
   ========================================================= */

$totalProducts = 0;
$inStockProducts = 0;
$lowStockProducts = 0;
$outOfStockProducts = 0;

if ($productsTableExists) {

    /* Total */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM products
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $totalProducts =
            (int)($row["total"] ?? 0);
    }


    /* In Stock */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM products
        WHERE stock > 5
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $inStockProducts =
            (int)($row["total"] ?? 0);
    }


    /* Low Stock */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM products
        WHERE stock BETWEEN 1 AND 5
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $lowStockProducts =
            (int)($row["total"] ?? 0);
    }


    /* Out of Stock */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM products
        WHERE stock <= 0
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        $outOfStockProducts =
            (int)($row["total"] ?? 0);
    }
}


/* =========================================================
   GET INVENTORY PRODUCTS
   ========================================================= */

$products = [];

if ($productsTableExists) {

    $conditions = [];
    $params = [];
    $types = "";


    /* SEARCH */

    if ($search !== "") {

        $conditions[] = "
            (
                product_name LIKE ?
                OR category LIKE ?
            )
        ";

        $searchValue =
            "%" . $search . "%";

        $params[] = $searchValue;
        $params[] = $searchValue;

        $types .= "ss";
    }


    /* CATEGORY */

    if (
        $categoryFilter !== ""
        && in_array(
            $categoryFilter,
            $categories,
            true
        )
    ) {

        $conditions[] =
            "category = ?";

        $params[] =
            $categoryFilter;

        $types .= "s";
    }


    /* STOCK STATUS */

    if ($stockFilter === "in_stock") {

        $conditions[] =
            "stock > 5";

    } elseif ($stockFilter === "low_stock") {

        $conditions[] =
            "stock BETWEEN 1 AND 5";

    } elseif ($stockFilter === "out_of_stock") {

        $conditions[] =
            "stock <= 0";
    }


    $sql = "
        SELECT
            id,
            product_name,
            category,
            price,
            image,
            stock,
            status,
            created_at
        FROM products
    ";


    if (!empty($conditions)) {

        $sql .=
            " WHERE "
            . implode(
                " AND ",
                $conditions
            );
    }


    /*
       Low stock and out of stock first,
       then newest products.
    */

    $sql .= "
        ORDER BY
            CASE
                WHEN stock <= 0 THEN 1
                WHEN stock <= 5 THEN 2
                ELSE 3
            END,
            id DESC
    ";


    $stmt = $conn->prepare($sql);

    if ($stmt) {

        if (!empty($params)) {

            $stmt->bind_param(
                $types,
                ...$params
            );
        }

        $stmt->execute();

        $result =
            $stmt->get_result();

        while (
            $row =
            $result->fetch_assoc()
        ) {

            $products[] = $row;
        }

        $stmt->close();
    }
}


$displayedProducts =
    count($products);

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
        Inventory | The Knot House Admin
    </title>


    <!-- GOOGLE FONTS -->

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        /* =====================================================
           ROOT
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

            --shadow:
                0 8px 25px
                rgba(81, 61, 61, 0.07);

            --sidebar-width: 255px;
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

            font-family:
                "DM Sans",
                sans-serif;

            background:
                var(--cream);

            color:
                var(--brown);

            min-height:
                100vh;
        }


        button,
        input,
        select {
            font-family: inherit;
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

            top: 0;
            left: 0;

            width:
                var(--sidebar-width);

            height: 100vh;

            background:
                var(--white);

            border-right:
                1px solid var(--border);

            padding:
                25px 16px 18px;

            z-index: 1000;

            display:
                flex;

            flex-direction:
                column;

            box-shadow:
                5px 0 25px
                rgba(81, 61, 61, 0.04);

            overflow-y:
                auto;
        }


        /* =====================================================
           BRAND
           ===================================================== */

        .brand {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

            padding:
                4px 10px 26px;

            border-bottom:
                1px solid var(--border);

            margin-bottom:
                23px;
        }


        .brand-icon {

            width:
                43px;

            height:
                43px;

            border-radius:
                13px;

            background:
                linear-gradient(
                    135deg,
                    var(--pink),
                    var(--dark-pink)
                );

            color:
                white;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                21px;

            box-shadow:
                0 7px 18px
                rgba(169, 103, 113, 0.22);
        }


        .brand-text h1 {

            font-family:
                "Playfair Display",
                serif;

            font-size:
                18px;

            color:
                var(--brown);

            line-height:
                1.2;
        }


        .brand-text span {

            display:
                block;

            margin-top:
                3px;

            font-size:
                10px;

            font-weight:
                700;

            letter-spacing:
                1.4px;

            color:
                var(--pink);
        }


        /* =====================================================
           MENU
           ===================================================== */

        .menu-section {
            margin-bottom:
                25px;
        }


        .menu-title {

            padding:
                0 11px;

            margin-bottom:
                9px;

            font-size:
                10px;

            font-weight:
                700;

            letter-spacing:
                1.5px;

            color:
                #a69292;

            text-transform:
                uppercase;
        }


        .menu-link {

            position:
                relative;

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

            width:
                100%;

            min-height:
                45px;

            padding:
                10px 12px;

            margin-bottom:
                4px;

            border-radius:
                12px;

            color:
                var(--brown-light);

            font-size:
                13px;

            font-weight:
                600;

            transition:
                all 0.25s ease;
        }


        .menu-link:hover {

            background:
                var(--light-pink);

            color:
                var(--dark-pink);

            transform:
                translateX(2px);
        }


        .menu-link.active {

            background:
                var(--light-pink);

            color:
                var(--dark-pink);
        }


        .menu-link.active::before {

            content:
                "";

            position:
                absolute;

            left:
                0;

            top:
                9px;

            bottom:
                9px;

            width:
                3px;

            border-radius:
                10px;

            background:
                var(--pink);
        }


        .menu-icon {

            width:
                27px;

            height:
                27px;

            border-radius:
                8px;

            background:
                #faf5f3;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                15px;

            flex-shrink:
                0;
        }


        .menu-link.active .menu-icon {

            background:
                white;
        }


        /* =====================================================
           SIDEBAR BOTTOM
           ===================================================== */

        .sidebar-bottom {

            margin-top:
                auto;

            padding-top:
                15px;
        }


        .admin-profile {

            padding:
                13px;

            background:
                var(--cream);

            border:
                1px solid var(--border);

            border-radius:
                15px;

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            margin-bottom:
                10px;
        }


        .admin-avatar {

            width:
                38px;

            height:
                38px;

            flex-shrink:
                0;

            border-radius:
                50%;

            background:
                var(--pink);

            color:
                white;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-weight:
                700;

            font-size:
                14px;
        }


        .admin-details {
            min-width:
                0;
        }


        .admin-details strong {

            display:
                block;

            font-size:
                12px;

            color:
                var(--brown);

            white-space:
                nowrap;

            overflow:
                hidden;

            text-overflow:
                ellipsis;
        }


        .admin-details span {

            display:
                block;

            font-size:
                10px;

            color:
                var(--brown-light);

            margin-top:
                2px;
        }


        .logout-link {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

            padding:
                10px 12px;

            border-radius:
                10px;

            color:
                var(--red);

            font-size:
                12px;

            font-weight:
                600;

            transition:
                0.2s;
        }


        .logout-link:hover {

            background:
                var(--red-light);
        }


        /* =====================================================
           MAIN
           ===================================================== */

        .main {

            margin-left:
                var(--sidebar-width);

            min-height:
                100vh;
        }


        /* =====================================================
           TOPBAR
           ===================================================== */

        .topbar {

            position:
                sticky;

            top:
                0;

            z-index:
                500;

            min-height:
                72px;

            padding:
                14px 32px;

            background:
                rgba(255, 250, 247, 0.93);

            backdrop-filter:
                blur(14px);

            border-bottom:
                1px solid
                rgba(234, 219, 216, 0.8);

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;
        }


        .page-heading h2 {

            font-family:
                "Playfair Display",
                serif;

            font-size:
                24px;

            color:
                var(--brown);
        }


        .page-heading p {

            font-size:
                12px;

            color:
                var(--brown-light);

            margin-top:
                3px;
        }


        .topbar-right {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;
        }


        .store-btn {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                7px;

            padding:
                10px 14px;

            background:
                white;

            border:
                1px solid var(--border);

            border-radius:
                10px;

            color:
                var(--brown);

            font-size:
                12px;

            font-weight:
                700;

            transition:
                0.25s;
        }


        .store-btn:hover {

            border-color:
                var(--pink);

            color:
                var(--pink);

            transform:
                translateY(-1px);
        }


        .mobile-menu-btn {

            display:
                none;

            border:
                1px solid var(--border);

            background:
                white;

            width:
                40px;

            height:
                40px;

            border-radius:
                10px;

            color:
                var(--brown);

            font-size:
                20px;

            cursor:
                pointer;
        }


        /* =====================================================
           CONTENT
           ===================================================== */

        .content {

            padding:
                30px 32px 45px;

            max-width:
                1600px;

            margin:
                0 auto;
        }


        /* =====================================================
           ALERT
           ===================================================== */

        .alert {

            padding:
                13px 16px;

            border-radius:
                12px;

            margin-bottom:
                20px;

            font-size:
                13px;

            font-weight:
                600;

            display:
                flex;

            align-items:
                center;

            gap:
                9px;
        }


        .alert-success {

            background:
                var(--green-light);

            color:
                #36714e;

            border:
                1px solid #cfe8d9;
        }


        .alert-error {

            background:
                var(--red-light);

            color:
                #984949;

            border:
                1px solid #efd0d0;
        }


        .database-warning {

            padding:
                17px;

            background:
                var(--orange-light);

            color:
                #805923;

            border:
                1px solid #f0d6ae;

            border-radius:
                13px;

            margin-bottom:
                22px;

            font-size:
                13px;

            line-height:
                1.6;
        }


        /* =====================================================
           INTRO
           ===================================================== */

        .intro {

            display:
                flex;

            align-items:
                flex-end;

            justify-content:
                space-between;

            gap:
                20px;

            margin-bottom:
                23px;
        }


        .intro h3 {

            font-family:
                "Playfair Display",
                serif;

            font-size:
                27px;

            color:
                var(--brown);

            margin-bottom:
                5px;
        }


        .intro p {

            font-size:
                13px;

            color:
                var(--brown-light);
        }


        .inventory-count {

            padding:
                9px 14px;

            border-radius:
                30px;

            background:
                var(--light-pink);

            color:
                var(--dark-pink);

            font-size:
                12px;

            font-weight:
                700;

            white-space:
                nowrap;
        }


        /* =====================================================
           STAT CARDS
           ===================================================== */

        .stats-grid {

            display:
                grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap:
                17px;

            margin-bottom:
                25px;
        }


        .stat-card {

            background:
                white;

            border:
                1px solid var(--border);

            border-radius:
                17px;

            padding:
                19px;

            box-shadow:
                var(--shadow);

            transition:
                0.25s;
        }


        .stat-card:hover {

            transform:
                translateY(-3px);

            box-shadow:
                0 13px 30px
                rgba(81, 61, 61, 0.09);
        }


        .stat-top {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            margin-bottom:
                15px;
        }


        .stat-icon {

            width:
                43px;

            height:
                43px;

            border-radius:
                12px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                19px;
        }


        .stat-icon.pink {
            background:
                var(--light-pink);
        }


        .stat-icon.green {
            background:
                var(--green-light);
        }


        .stat-icon.orange {
            background:
                var(--orange-light);
        }


        .stat-icon.red {
            background:
                var(--red-light);
        }


        .stat-label {

            font-size:
                11px;

            color:
                var(--brown-light);

            font-weight:
                600;

            text-transform:
                uppercase;

            letter-spacing:
                0.5px;
        }


        .stat-number {

            font-family:
                "Playfair Display",
                serif;

            font-size:
                28px;

            font-weight:
                700;

            color:
                var(--brown);

            line-height:
                1.1;
        }


        .stat-note {

            margin-top:
                6px;

            font-size:
                11px;

            color:
                var(--brown-light);
        }


        /* =====================================================
           FILTER CARD
           ===================================================== */

        .filter-card {

            background:
                white;

            border:
                1px solid var(--border);

            border-radius:
                17px;

            padding:
                19px;

            box-shadow:
                var(--shadow);

            margin-bottom:
                22px;
        }


        .filter-title {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                10px;

            margin-bottom:
                14px;
        }


        .filter-title h3 {

            font-size:
                14px;

            color:
                var(--brown);
        }


        .clear-filter {

            font-size:
                11px;

            font-weight:
                700;

            color:
                var(--pink);
        }


        .clear-filter:hover {
            text-decoration:
                underline;
        }


        .filter-form {

            display:
                grid;

            grid-template-columns:
                minmax(200px, 1.6fr)
                minmax(160px, 1fr)
                minmax(160px, 1fr)
                auto;

            gap:
                10px;
        }


        .input-wrap {
            position:
                relative;
        }


        .input-icon {

            position:
                absolute;

            left:
                13px;

            top:
                50%;

            transform:
                translateY(-50%);

            font-size:
                14px;

            color:
                #aa9999;

            pointer-events:
                none;
        }


        .filter-input,
        .filter-select {

            width:
                100%;

            height:
                44px;

            padding:
                0 13px;

            border:
                1px solid var(--border);

            border-radius:
                10px;

            background:
                #fffdfc;

            color:
                var(--brown);

            font-size:
                12px;

            outline:
                none;

            transition:
                0.2s;
        }


        .filter-input {
            padding-left:
                38px;
        }


        .filter-input:focus,
        .filter-select:focus {

            border-color:
                var(--pink);

            box-shadow:
                0 0 0 3px
                rgba(169, 103, 113, 0.08);
        }


        .filter-btn {

            height:
                44px;

            padding:
                0 19px;

            border:
                0;

            border-radius:
                10px;

            background:
                var(--pink);

            color:
                white;

            font-size:
                12px;

            font-weight:
                700;

            cursor:
                pointer;

            transition:
                0.25s;

            white-space:
                nowrap;
        }


        .filter-btn:hover {

            background:
                var(--dark-pink);

            transform:
                translateY(-1px);
        }


        /* =====================================================
           INVENTORY CARD
           ===================================================== */

        .inventory-card {

            background:
                white;

            border:
                1px solid var(--border);

            border-radius:
                17px;

            box-shadow:
                var(--shadow);

            overflow:
                hidden;
        }


        .inventory-header {

            padding:
                19px 20px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;

            border-bottom:
                1px solid var(--border);
        }


        .inventory-header h3 {

            font-family:
                "Playfair Display",
                serif;

            font-size:
                20px;

            color:
                var(--brown);
        }


        .inventory-header span {

            font-size:
                11px;

            color:
                var(--brown-light);
        }


        /* =====================================================
           TABLE
           ===================================================== */

        .table-wrapper {

            width:
                100%;

            overflow-x:
                auto;
        }


        table {

            width:
                100%;

            min-width:
                1050px;

            border-collapse:
                collapse;
        }


        thead {
            background:
                #fdf8f6;
        }


        th {

            padding:
                13px 17px;

            text-align:
                left;

            color:
                #8e7b7b;

            font-size:
                10px;

            font-weight:
                700;

            letter-spacing:
                0.7px;

            text-transform:
                uppercase;

            white-space:
                nowrap;

            border-bottom:
                1px solid var(--border);
        }


        td {

            padding:
                15px 17px;

            border-bottom:
                1px solid #f1e8e5;

            vertical-align:
                middle;

            font-size:
                12px;

            color:
                var(--brown);
        }


        tbody tr {
            transition:
                0.2s;
        }


        tbody tr:hover {
            background:
                #fffcfb;
        }


        tbody tr:last-child td {
            border-bottom:
                0;
        }


        /* =====================================================
           PRODUCT
           ===================================================== */

        .product-info {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

            min-width:
                230px;
        }


        .product-image {

            width:
                52px;

            height:
                52px;

            border-radius:
                12px;

            overflow:
                hidden;

            background:
                var(--light-pink);

            border:
                1px solid var(--border);

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                22px;
        }


        .product-image img {

            width:
                100%;

            height:
                100%;

            object-fit:
                cover;

            display:
                block;
        }


        .product-details strong {

            display:
                block;

            font-size:
                12px;

            color:
                var(--brown);

            margin-bottom:
                4px;
        }


        .product-details span {

            display:
                block;

            font-size:
                10px;

            color:
                var(--brown-light);
        }


        /* =====================================================
           CATEGORY
           ===================================================== */

        .category-badge {

            display:
                inline-flex;

            padding:
                6px 9px;

            border-radius:
                30px;

            background:
                var(--light-pink);

            color:
                var(--dark-pink);

            font-size:
                10px;

            font-weight:
                700;

            white-space:
                nowrap;
        }


        /* =====================================================
           PRICE
           ===================================================== */

        .price {

            font-weight:
                700;

            font-size:
                13px;

            white-space:
                nowrap;
        }


        /* =====================================================
           STOCK
           ===================================================== */

        .stock-box {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

            min-width:
                150px;
        }


        .stock-number {

            min-width:
                35px;

            font-size:
                15px;

            font-weight:
                700;
        }


        .stock-label {

            display:
                block;

            font-size:
                10px;

            margin-top:
                2px;
        }


        .stock-good {
            color:
                var(--green);
        }


        .stock-low {
            color:
                var(--orange);
        }


        .stock-out {
            color:
                var(--red);
        }


        .stock-indicator {

            width:
                8px;

            height:
                8px;

            border-radius:
                50%;

            flex-shrink:
                0;
        }


        .stock-indicator.good {
            background:
                var(--green);
        }


        .stock-indicator.low {
            background:
                var(--orange);
        }


        .stock-indicator.out {
            background:
                var(--red);
        }


        /* =====================================================
           STOCK UPDATE
           ===================================================== */

        .stock-form {

            display:
                flex;

            align-items:
                center;

            gap:
                6px;

            min-width:
                155px;
        }


        .stock-input {

            width:
                75px;

            height:
                35px;

            padding:
                0 9px;

            border:
                1px solid var(--border);

            border-radius:
                8px;

            background:
                #fffdfc;

            color:
                var(--brown);

            font-size:
                11px;

            outline:
                none;

            text-align:
                center;
        }


        .stock-input:focus {

            border-color:
                var(--pink);

            box-shadow:
                0 0 0 3px
                rgba(169, 103, 113, 0.08);
        }


        .update-stock-btn {

            width:
                35px;

            height:
                35px;

            border:
                0;

            border-radius:
                8px;

            background:
                var(--light-pink);

            color:
                var(--dark-pink);

            cursor:
                pointer;

            font-size:
                14px;

            transition:
                0.2s;
        }


        .update-stock-btn:hover {

            background:
                var(--pink);

            color:
                white;
        }


        /* =====================================================
           PRODUCT STATUS
           ===================================================== */

        .status-badge {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                5px;

            padding:
                6px 9px;

            border-radius:
                30px;

            font-size:
                10px;

            font-weight:
                700;

            white-space:
                nowrap;
        }


        .status-active {

            background:
                var(--green-light);

            color:
                var(--green);
        }


        .status-inactive {

            background:
                var(--red-light);

            color:
                var(--red);
        }


        .status-badge::before {

            content:
                "";

            width:
                5px;

            height:
                5px;

            border-radius:
                50%;

            background:
                currentColor;
        }


        /* =====================================================
           ACTIONS
           ===================================================== */

        .actions {

            display:
                flex;

            align-items:
                center;

            gap:
                6px;
        }


        .action-btn {

            width:
                34px;

            height:
                34px;

            border-radius:
                8px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            border:
                1px solid var(--border);

            background:
                white;

            color:
                var(--brown);

            font-size:
                13px;

            transition:
                0.2s;
        }


        .action-btn:hover {

            border-color:
                var(--pink);

            background:
                var(--light-pink);

            color:
                var(--pink);
        }


        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .empty-state {

            padding:
                65px 20px;

            text-align:
                center;
        }


        .empty-icon {

            width:
                66px;

            height:
                66px;

            margin:
                0 auto 15px;

            border-radius:
                20px;

            background:
                var(--light-pink);

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                29px;
        }


        .empty-state h3 {

            font-family:
                "Playfair Display",
                serif;

            font-size:
                21px;

            color:
                var(--brown);

            margin-bottom:
                6px;
        }


        .empty-state p {

            font-size:
                12px;

            color:
                var(--brown-light);

            max-width:
                420px;

            margin:
                auto;

            line-height:
                1.6;
        }


        .empty-state a {

            display:
                inline-flex;

            margin-top:
                17px;

            padding:
                10px 16px;

            background:
                var(--pink);

            color:
                white;

            border-radius:
                9px;

            font-size:
                11px;

            font-weight:
                700;
        }


        /* =====================================================
           FOOTER
           ===================================================== */

        .page-footer {

            text-align:
                center;

            padding-top:
                27px;

            font-size:
                10px;

            color:
                #a28e8e;
        }


        /* =====================================================
           SIDEBAR OVERLAY
           ===================================================== */

        .sidebar-overlay {

            display:
                none;

            position:
                fixed;

            inset:
                0;

            background:
                rgba(45, 31, 31, 0.35);

            z-index:
                900;
        }


        /* =====================================================
           RESPONSIVE - 1200px
           ===================================================== */

        @media (max-width: 1200px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .filter-form {

                grid-template-columns:
                    1fr 1fr;
            }


            .filter-btn {

                width:
                    100%;
            }
        }


        /* =====================================================
           RESPONSIVE - 900px
           ===================================================== */

        @media (max-width: 900px) {

            :root {
                --sidebar-width: 0px;
            }


            .sidebar {

                width:
                    255px;

                transform:
                    translateX(-100%);

                transition:
                    transform 0.3s ease;

                box-shadow:
                    10px 0 30px
                    rgba(81, 61, 61, 0.15);
            }


            .sidebar.open {

                transform:
                    translateX(0);
            }


            .sidebar-overlay.active {

                display:
                    block;
            }


            .main {

                margin-left:
                    0;
            }


            .mobile-menu-btn {

                display:
                    block;
            }


            .topbar {

                padding:
                    12px 20px;
            }


            .content {

                padding:
                    25px 20px 40px;
            }
        }


        /* =====================================================
           RESPONSIVE - 650px
           ===================================================== */

        @media (max-width: 650px) {

            .topbar {

                min-height:
                    65px;
            }


            .page-heading h2 {

                font-size:
                    20px;
            }


            .page-heading p {

                display:
                    none;
            }


            .store-btn {

                display:
                    none;
            }


            .content {

                padding:
                    20px 14px 35px;
            }


            .stats-grid {

                grid-template-columns:
                    1fr 1fr;

                gap:
                    10px;
            }


            .stat-card {

                padding:
                    14px;
            }


            .stat-icon {

                width:
                    37px;

                height:
                    37px;

                font-size:
                    16px;
            }


            .stat-number {

                font-size:
                    22px;
            }


            .intro {

                align-items:
                    flex-start;

                flex-direction:
                    column;

                margin-bottom:
                    17px;
            }


            .intro h3 {

                font-size:
                    22px;
            }


            .filter-form {

                grid-template-columns:
                    1fr;
            }


            .filter-card {

                padding:
                    14px;
            }


            .inventory-header {

                padding:
                    15px;
            }


            .inventory-header h3 {

                font-size:
                    18px;
            }
        }


        /* =====================================================
           RESPONSIVE - 420px
           ===================================================== */

        @media (max-width: 420px) {

            .stats-grid {

                grid-template-columns:
                    1fr;
            }


            .topbar {

                padding:
                    11px 14px;
            }


            .mobile-menu-btn {

                width:
                    37px;

                height:
                    37px;
            }


            .page-heading h2 {

                font-size:
                    18px;
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

    <div class="brand">

        <div class="brand-icon">
            🧶
        </div>


        <div class="brand-text">

            <h1>
                The Knot House
            </h1>

            <span>
                ADMIN PANEL
            </span>

        </div>

    </div>


    <!-- MAIN MENU -->

    <div class="menu-section">

        <div class="menu-title">
            Main Menu
        </div>


        <a
            href="dashboard.php"
            class="menu-link"
        >

            <span class="menu-icon">
                ⌂
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <a
            href="products.php"
            class="menu-link"
        >

            <span class="menu-icon">
                🛍️
            </span>

            <span>
                Products
            </span>

        </a>


        <a
            href="orders.php"
            class="menu-link"
        >

            <span class="menu-icon">
                📦
            </span>

            <span>
                Orders
            </span>

        </a>

    </div>


    <!-- MANAGEMENT -->

    <div class="menu-section">

        <div class="menu-title">
            Management
        </div>


        <a
            href="inventory.php"
            class="menu-link active"
        >

            <span class="menu-icon">
                📊
            </span>

            <span>
                Inventory
            </span>

        </a>


        <a
            href="../index.html"
            target="_blank"
            class="menu-link"
        >

            <span class="menu-icon">
                🌐
            </span>

            <span>
                View Store
            </span>

        </a>


        <a
            href="add-product.php"
            class="menu-link"
        >

            <span class="menu-icon">
                ＋
            </span>

            <span>
                Add Product
            </span>

        </a>

    </div>


    <!-- SIDEBAR BOTTOM -->

    <div class="sidebar-bottom">


        <div class="admin-profile">

            <div class="admin-avatar">

                <?php
                echo clean($adminInitial);
                ?>

            </div>


            <div class="admin-details">

                <strong>

                    <?php
                    echo clean($adminName);
                    ?>

                </strong>


                <span>
                    Administrator
                </span>

            </div>

        </div>


        <a
            href="logout.php"
            class="logout-link"
        >

            <span>
                ↪
            </span>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>


<!-- =========================================================
     MOBILE OVERLAY
     ========================================================= -->

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


        <div
            style="
                display:flex;
                align-items:center;
                gap:12px;
            "
        >


            <button
                class="mobile-menu-btn"
                onclick="toggleSidebar()"
                aria-label="Open menu"
            >
                ☰
            </button>


            <div class="page-heading">

                <h2>
                    Inventory
                </h2>

                <p>
                    Monitor and manage your product stock
                </p>

            </div>

        </div>


        <div class="topbar-right">

            <a
                href="../index.html"
                target="_blank"
                class="store-btn"
            >
                🌐 View Store
            </a>

        </div>

    </header>


    <!-- =====================================================
         CONTENT
         ===================================================== -->

    <section class="content">


        <!-- DATABASE WARNING -->

        <?php if (!$productsTableExists): ?>

            <div class="database-warning">

                ⚠️

                <strong>
                    Products table not found.
                </strong>

                Please create the
                <b>products</b>
                table in your
                <b>knot_house</b>
                database before using Inventory Management.

            </div>

        <?php endif; ?>


        <!-- SUCCESS -->

        <?php if ($successMessage !== ""): ?>

            <div class="alert alert-success">

                ✓

                <span>
                    <?php
                    echo clean($successMessage);
                    ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- ERROR -->

        <?php if ($errorMessage !== ""): ?>

            <div class="alert alert-error">

                ⚠️

                <span>
                    <?php
                    echo clean($errorMessage);
                    ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- =================================================
             INTRO
             ================================================= -->

        <div class="intro">


            <div>

                <h3>
                    Inventory Management
                </h3>

                <p>
                    Keep track of crochet product stock and
                    update quantities quickly.
                </p>

            </div>


            <div class="inventory-count">

                <?php
                echo number_format(
                    $displayedProducts
                );
                ?>

                <?php
                echo
                    $displayedProducts === 1
                    ? " Product"
                    : " Products";
                ?>

            </div>

        </div>


        <!-- =================================================
             STATISTICS
             ================================================= -->

        <div class="stats-grid">


            <!-- TOTAL -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-icon pink">
                        📦
                    </div>

                    <span class="stat-label">
                        Total Products
                    </span>

                </div>


                <div class="stat-number">

                    <?php
                    echo number_format(
                        $totalProducts
                    );
                    ?>

                </div>


                <div class="stat-note">
                    Products in inventory
                </div>

            </div>


            <!-- IN STOCK -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-icon green">
                        ✓
                    </div>

                    <span class="stat-label">
                        In Stock
                    </span>

                </div>


                <div class="stat-number">

                    <?php
                    echo number_format(
                        $inStockProducts
                    );
                    ?>

                </div>


                <div class="stat-note">
                    More than 5 available
                </div>

            </div>


            <!-- LOW STOCK -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-icon orange">
                        ⚠
                    </div>

                    <span class="stat-label">
                        Low Stock
                    </span>

                </div>


                <div class="stat-number">

                    <?php
                    echo number_format(
                        $lowStockProducts
                    );
                    ?>

                </div>


                <div class="stat-note">
                    1 to 5 items remaining
                </div>

            </div>


            <!-- OUT OF STOCK -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-icon red">
                        ×
                    </div>

                    <span class="stat-label">
                        Out of Stock
                    </span>

                </div>


                <div class="stat-number">

                    <?php
                    echo number_format(
                        $outOfStockProducts
                    );
                    ?>

                </div>


                <div class="stat-note">
                    Products unavailable
                </div>

            </div>

        </div>


        <!-- =================================================
             FILTERS
             ================================================= -->

        <div class="filter-card">


            <div class="filter-title">

                <h3>
                    🔎 Search & Filter Inventory
                </h3>


                <?php if (
                    $search !== ""
                    || $categoryFilter !== ""
                    || $stockFilter !== ""
                ): ?>

                    <a
                        href="inventory.php"
                        class="clear-filter"
                    >
                        Clear Filters
                    </a>

                <?php endif; ?>

            </div>


            <form
                method="GET"
                action="inventory.php"
                class="filter-form"
            >


                <!-- SEARCH -->

                <div class="input-wrap">

                    <span class="input-icon">
                        🔍
                    </span>


                    <input
                        type="text"
                        name="search"
                        class="filter-input"
                        placeholder="Search product or category..."
                        value="<?php
                            echo clean($search);
                        ?>"
                    >

                </div>


                <!-- CATEGORY -->

                <select
                    name="category"
                    class="filter-select"
                >

                    <option value="">
                        All Categories
                    </option>


                    <?php foreach (
                        $categories
                        as $category
                    ): ?>

                        <option
                            value="<?php
                                echo clean($category);
                            ?>"
                            <?php
                            echo
                                $categoryFilter ===
                                $category
                                ? "selected"
                                : "";
                            ?>
                        >

                            <?php
                            echo clean($category);
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <!-- STOCK STATUS -->

                <select
                    name="stock_status"
                    class="filter-select"
                >

                    <option value="">
                        All Stock Status
                    </option>

                    <option
                        value="in_stock"
                        <?php
                        echo
                            $stockFilter ===
                            "in_stock"
                            ? "selected"
                            : "";
                        ?>
                    >
                        In Stock
                    </option>

                    <option
                        value="low_stock"
                        <?php
                        echo
                            $stockFilter ===
                            "low_stock"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Low Stock
                    </option>

                    <option
                        value="out_of_stock"
                        <?php
                        echo
                            $stockFilter ===
                            "out_of_stock"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Out of Stock
                    </option>

                </select>


                <!-- FILTER -->

                <button
                    type="submit"
                    class="filter-btn"
                >
                    Apply Filters
                </button>

            </form>

        </div>


        <!-- =================================================
             INVENTORY TABLE
             ================================================= -->

        <div class="inventory-card">


            <div class="inventory-header">

                <h3>
                    Product Stock
                </h3>


                <span>

                    Showing
                    <?php
                    echo number_format(
                        $displayedProducts
                    );
                    ?>

                    result<?php
                    echo
                        $displayedProducts === 1
                        ? ""
                        : "s";
                    ?>

                </span>

            </div>


            <?php if (!empty($products)): ?>


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
                                    Current Stock
                                </th>

                                <th>
                                    Update Stock
                                </th>

                                <th>
                                    Product Status
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $products
                            as $product
                        ): ?>


                            <?php

                            $stock =
                                (int)$product["stock"];


                            /*
                             * Determine stock status
                             */

                            if ($stock <= 0) {

                                $stockClass = "stock-out";

                                $indicatorClass = "out";

                                $stockText =
                                    "Out of Stock";

                            } elseif ($stock <= 5) {

                                $stockClass = "stock-low";

                                $indicatorClass = "low";

                                $stockText =
                                    "Low Stock";

                            } else {

                                $stockClass = "stock-good";

                                $indicatorClass = "good";

                                $stockText =
                                    "In Stock";
                            }


                            /*
                             * Product status
                             */

                            $productStatus =
                                $product["status"]
                                ?? "Active";


                            $statusClass =
                                strtolower(
                                    $productStatus
                                );


                            /*
                             * Image
                             */

                            $image =
                                trim(
                                    $product["image"]
                                    ?? ""
                                );

                            ?>


                            <tr>


                                <!-- PRODUCT -->

                                <td>

                                    <div class="product-info">


                                        <div class="product-image">


                                            <?php if (
                                                $image !== ""
                                            ): ?>

                                                <img
                                                    src="<?php
                                                        echo clean(
                                                            $image
                                                        );
                                                    ?>"
                                                    alt="<?php
                                                        echo clean(
                                                            $product[
                                                                "product_name"
                                                            ]
                                                        );
                                                    ?>"
                                                    onerror="
                                                        this.style.display='none';
                                                        this.parentElement.innerHTML='🧶';
                                                    "
                                                >

                                            <?php else: ?>

                                                🧶

                                            <?php endif; ?>


                                        </div>


                                        <div class="product-details">

                                            <strong>

                                                <?php
                                                echo clean(
                                                    $product[
                                                        "product_name"
                                                    ]
                                                );
                                                ?>

                                            </strong>


                                            <span>

                                                Product ID:
                                                #
                                                <?php
                                                echo (int)
                                                    $product["id"];
                                                ?>

                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <!-- CATEGORY -->

                                <td>

                                    <span
                                        class="category-badge"
                                    >

                                        <?php
                                        echo clean(
                                            $product[
                                                "category"
                                            ]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- PRICE -->

                                <td>

                                    <span class="price">

                                        ₹<?php
                                        echo number_format(
                                            (float)
                                            $product["price"],
                                            2
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- CURRENT STOCK -->

                                <td>

                                    <div
                                        class="
                                            stock-box
                                            <?php
                                            echo
                                                clean(
                                                    $stockClass
                                                );
                                            ?>
                                        "
                                    >

                                        <span
                                            class="
                                                stock-indicator
                                                <?php
                                                echo
                                                    clean(
                                                        $indicatorClass
                                                    );
                                                ?>
                                            "
                                        ></span>


                                        <div>

                                            <div
                                                class="stock-number"
                                            >

                                                <?php
                                                echo $stock;
                                                ?>

                                            </div>


                                            <span
                                                class="stock-label"
                                            >

                                                <?php
                                                echo clean(
                                                    $stockText
                                                );
                                                ?>

                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <!-- UPDATE STOCK -->

                                <td>

                                    <form
                                        method="POST"
                                        action="inventory.php"
                                        class="stock-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?php
                                                echo (int)
                                                    $product["id"];
                                            ?>"
                                        >


                                        <input
                                            type="number"
                                            name="stock"
                                            class="stock-input"
                                            min="0"
                                            max="999999"
                                            value="<?php
                                                echo $stock;
                                            ?>"
                                            required
                                        >


                                        <button
                                            type="submit"
                                            name="update_stock"
                                            class="update-stock-btn"
                                            title="Update Stock"
                                            aria-label="Update Stock"
                                        >
                                            ✓
                                        </button>

                                    </form>

                                </td>


                                <!-- PRODUCT STATUS -->

                                <td>

                                    <?php if (
                                        strtolower(
                                            $productStatus
                                        ) === "active"
                                    ): ?>

                                        <span
                                            class="
                                                status-badge
                                                status-active
                                            "
                                        >

                                            Active

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="
                                                status-badge
                                                status-inactive
                                            "
                                        >

                                            <?php
                                            echo clean(
                                                $productStatus
                                            );
                                            ?>

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="actions">


                                        <a
                                            href="edit-product.php?id=<?php
                                                echo (int)
                                                    $product["id"];
                                            ?>"
                                            class="action-btn"
                                            title="Edit Product"
                                            aria-label="Edit Product"
                                        >
                                            ✏️
                                        </a>


                                        <a
                                            href="products.php"
                                            class="action-btn"
                                            title="View Products"
                                            aria-label="View Products"
                                        >
                                            👁
                                        </a>

                                    </div>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <!-- EMPTY STATE -->

                <div class="empty-state">


                    <div class="empty-icon">
                        📦
                    </div>


                    <?php if (
                        $search !== ""
                        || $categoryFilter !== ""
                        || $stockFilter !== ""
                    ): ?>


                        <h3>
                            No Products Found
                        </h3>


                        <p>
                            No products match your current
                            search or filter settings.
                            Try changing the filters and
                            search again.
                        </p>


                        <a
                            href="inventory.php"
                        >
                            Clear Filters
                        </a>


                    <?php elseif (
                        !$productsTableExists
                    ): ?>


                        <h3>
                            Products Table Not Available
                        </h3>


                        <p>
                            The products table has not been
                            created in your database yet.
                        </p>


                    <?php else: ?>


                        <h3>
                            No Products Yet
                        </h3>


                        <p>
                            Add your first crochet product
                            to start managing inventory.
                        </p>


                        <a
                            href="add-product.php"
                        >
                            Add Product
                        </a>


                    <?php endif; ?>


                </div>

            <?php endif; ?>


        </div>


        <!-- FOOTER -->

        <div class="page-footer">

            The Knot House Admin Panel
            &nbsp;•&nbsp;
            Handmade with 🧶 and ❤️

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
        document.getElementById("sidebar");

    const overlay =
        document.getElementById(
            "sidebarOverlay"
        );


    function toggleSidebar() {

        sidebar.classList.toggle("open");

        overlay.classList.toggle(
            "active"
        );
    }


    function closeSidebar() {

        sidebar.classList.remove(
            "open"
        );

        overlay.classList.remove(
            "active"
        );
    }


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
       AUTO HIDE ALERT
       ===================================================== */

    setTimeout(
        function() {

            const alerts =
                document.querySelectorAll(
                    ".alert"
                );


            alerts.forEach(
                function(alert) {

                    alert.style.transition =
                        "opacity 0.4s ease";

                    alert.style.opacity =
                        "0";


                    setTimeout(
                        function() {

                            alert.remove();

                        },
                        400
                    );

                }
            );

        },
        4500
    );

</script>


</body>

</html>

<?php

$conn->close();

?>