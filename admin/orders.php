
<?php
session_start();

/* =========================================================
   1. ADMIN LOGIN CHECK
========================================================= */

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

/* =========================================================
   2. DATABASE CONNECTION
========================================================= */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = null;
$dbError = "";
$tableExists = false;

try {
    $conn = new mysqli(
        "localhost",
        "root",
        "",
        "knot_house"
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    $dbError = "Unable to connect to the database. Please check your XAMPP settings.";
}

/* =========================================================
   3. HELPER FUNCTION
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* =========================================================
   4. ADMIN INFORMATION
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
   5. ORDER STATUS OPTIONS
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
   6. SEARCH AND FILTER
========================================================= */

$search = trim($_GET["search"] ?? "");
$status = trim($_GET["status"] ?? "");

/* Only allow valid order statuses */

if (!in_array($status, $orderStatuses, true)) {
    $status = "";
}

/* =========================================================
   7. PAGINATION SETTINGS
========================================================= */

$recordsPerPage = 10;

$currentPage = filter_input(
    INPUT_GET,
    "page",
    FILTER_VALIDATE_INT
);

$currentPage = max(1, (int)($currentPage ?: 1));

$totalRecords = 0;
$totalPages = 1;
$offset = 0;

/* =========================================================
   8. ORDER STATISTICS
========================================================= */

$totalOrders = 0;
$pendingOrders = 0;
$processingOrders = 0;
$deliveredOrders = 0;

$orders = [];

/* =========================================================
   9. CHECK ORDERS TABLE
========================================================= */

if ($conn && $dbError === "") {

    try {

        $checkTable = $conn->query("
            SELECT COUNT(*) AS total
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = 'orders'
        ");

        $tableRow = $checkTable->fetch_assoc();

        $tableExists = ((int)$tableRow["total"] > 0);

        /* =================================================
           10. FETCH ORDER INFORMATION
        ================================================= */

        if ($tableExists) {

            /*
             * Check required columns.
             * This helps identify an incompatible table.
             */

            $requiredColumns = [
                "id",
                "customer_name",
                "customer_email",
                "customer_phone",
                "total_amount",
                "payment_status",
                "order_status",
                "created_at"
            ];

            $columnResult = $conn->query("
                SHOW COLUMNS FROM orders
            ");

            $availableColumns = [];

            while ($column = $columnResult->fetch_assoc()) {
                $availableColumns[] = $column["Field"];
            }

            $missingColumns = array_diff(
                $requiredColumns,
                $availableColumns
            );

            if (!empty($missingColumns)) {

                $dbError = "Your orders table is missing required columns: "
                    . implode(", ", $missingColumns);

                $tableExists = false;

            } else {

                /* =========================================
                   11. GLOBAL ORDER STATISTICS
                ========================================= */

                $statsQuery = $conn->query("
                    SELECT
                        COUNT(*) AS total_orders,

                        SUM(
                            CASE
                                WHEN LOWER(order_status) = 'pending'
                                THEN 1 ELSE 0
                            END
                        ) AS pending_orders,

                        SUM(
                            CASE
                                WHEN LOWER(order_status) IN (
                                    'confirmed',
                                    'processing',
                                    'shipped'
                                )
                                THEN 1 ELSE 0
                            END
                        ) AS processing_orders,

                        SUM(
                            CASE
                                WHEN LOWER(order_status) = 'delivered'
                                THEN 1 ELSE 0
                            END
                        ) AS delivered_orders

                    FROM orders
                ");

                $stats = $statsQuery->fetch_assoc();

                $totalOrders = (int)($stats["total_orders"] ?? 0);
                $pendingOrders = (int)($stats["pending_orders"] ?? 0);
                $processingOrders = (int)($stats["processing_orders"] ?? 0);
                $deliveredOrders = (int)($stats["delivered_orders"] ?? 0);

                /* =========================================
                   12. BUILD SEARCH QUERY
                ========================================= */

                $where = " WHERE 1=1 ";

                $types = "";
                $params = [];

                if ($search !== "") {

                    $where .= "
                        AND (
                            CAST(id AS CHAR) LIKE ?
                            OR customer_name LIKE ?
                            OR customer_email LIKE ?
                            OR customer_phone LIKE ?
                        )
                    ";

                    $searchValue = "%" . $search . "%";

                    $types .= "ssss";

                    $params[] = $searchValue;
                    $params[] = $searchValue;
                    $params[] = $searchValue;
                    $params[] = $searchValue;
                }

                /* =========================================
                   13. STATUS FILTER
                ========================================= */

                if ($status !== "") {

                    $where .= "
                        AND LOWER(order_status) = LOWER(?)
                    ";

                    $types .= "s";
                    $params[] = $status;
                }

                /* =========================================
                   14. TOTAL FILTERED RECORDS
                ========================================= */

                $countSql = "
                    SELECT COUNT(*) AS total
                    FROM orders
                    $where
                ";

                $countStmt = $conn->prepare($countSql);

                if (!empty($params)) {
                    $countStmt->bind_param($types, ...$params);
                }

                $countStmt->execute();

                $countResult = $countStmt->get_result();

                $countRow = $countResult->fetch_assoc();

                $totalRecords = (int)$countRow["total"];

                $countStmt->close();

                /* =========================================
                   15. CALCULATE PAGINATION
                ========================================= */

                $totalPages = max(
                    1,
                    (int)ceil($totalRecords / $recordsPerPage)
                );

                $currentPage = min(
                    $currentPage,
                    $totalPages
                );

                $offset = ($currentPage - 1) * $recordsPerPage;

                /* =========================================
                   16. FETCH ORDERS
                ========================================= */

                $sql = "
                    SELECT
                        id,
                        customer_name,
                        customer_email,
                        customer_phone,
                        total_amount,
                        payment_status,
                        order_status,
                        created_at

                    FROM orders

                    $where

                    ORDER BY id DESC

                    LIMIT ? OFFSET ?
                ";

                $stmt = $conn->prepare($sql);

                $queryTypes = $types . "ii";

                $queryParams = $params;

                $queryParams[] = $recordsPerPage;
                $queryParams[] = $offset;

                $stmt->bind_param(
                    $queryTypes,
                    ...$queryParams
                );

                $stmt->execute();

                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $orders[] = $row;
                }

                $stmt->close();
            }
        }

    } catch (mysqli_sql_exception $e) {

        $dbError = "Something went wrong while loading orders. Please check your database table and columns.";
    }
}

/* =========================================================
   17. CLOSE DATABASE CONNECTION
========================================================= */

if ($conn) {
    $conn->close();
}

/* =========================================================
   18. PAGINATION URL
========================================================= */

function pageUrl($page, $search, $status)
{
    $params = [
        "page" => $page
    ];

    if ($search !== "") {
        $params["search"] = $search;
    }

    if ($status !== "") {
        $params["status"] = $status;
    }

    return "orders.php?" . http_build_query($params);
}

/* =========================================================
   19. STATUS CSS CLASS
========================================================= */

function getStatusClass($status)
{
    switch (strtolower(trim($status))) {

        case "confirmed":
            return "confirmed";

        case "processing":
            return "processing";

        case "shipped":
            return "shipped";

        case "delivered":
            return "delivered";

        case "cancelled":
            return "cancelled";

        default:
            return "pending";
    }
}

/* =========================================================
   20. PAYMENT CSS CLASS
========================================================= */

function getPaymentClass($status)
{
    switch (strtolower(trim($status))) {

        case "paid":
        case "completed":
            return "paid";

        case "failed":
            return "failed";

        default:
            return "pending";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Orders | The Knot House Admin</title>

    <!-- GOOGLE FONTS -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

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

            --sidebar-width: 245px;

            --shadow: 0 5px 22px rgba(81,61,61,.055);
            --shadow-hover: 0 9px 28px rgba(81,61,61,.09);
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

        button {
            cursor: pointer;
        }

        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;

            width: var(--sidebar-width);

            background: var(--white);
            border-right: 1px solid var(--border);

            display: flex;
            flex-direction: column;

            z-index: 1000;

            transition: transform .3s ease;

            box-shadow: 4px 0 20px rgba(81,61,61,.025);
        }

        /* BRAND */

        .sidebar-brand {
            min-height: 78px;

            display: flex;
            align-items: center;

            padding: 0 18px;

            border-bottom: 1px solid var(--border);
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;

            border-radius: 12px;

            background: linear-gradient(
                135deg,
                var(--pink),
                var(--dark-pink)
            );

            display: flex;
            align-items: center;
            justify-content: center;

            color: white;
            font-size: 22px;

            margin-right: 10px;

            box-shadow: 0 5px 13px rgba(169,103,113,.18);
        }

        .brand-text {
            min-width: 0;
            line-height: 1.1;
        }

        .brand-title {
            font-family: "Playfair Display", serif;
            font-size: 19px;
            font-weight: 700;
            color: var(--brown);

            white-space: nowrap;
        }

        .brand-subtitle {
            margin-top: 5px;

            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1.7px;

            color: var(--pink);
        }

        /* SIDEBAR CONTENT */

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px 12px;
        }

        .nav-section {
            margin-bottom: 25px;
        }

        .nav-title {
            padding: 0 11px;
            margin-bottom: 9px;

            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;

            color: #a38e8e;
        }

        .nav-link {
            min-height: 43px;
            width: 100%;

            display: flex;
            align-items: center;
            gap: 11px;

            padding: 9px 12px;
            margin-bottom: 4px;

            border-radius: 9px;

            color: var(--brown-light);

            font-size: 12px;
            font-weight: 600;

            transition: .2s ease;
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

            box-shadow: inset 3px 0 0 var(--pink);
        }

        .nav-icon {
            width: 23px;
            min-width: 23px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 16px;
        }

        /* SIDEBAR PROFILE */

        .sidebar-bottom {
            padding: 12px;
            border-top: 1px solid var(--border);
        }

        .admin-mini {
            display: flex;
            align-items: center;

            padding: 10px;

            border-radius: 11px;

            background: var(--cream);
            border: 1px solid var(--border);
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            min-width: 36px;

            border-radius: 50%;

            background: var(--pink);
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 13px;
            font-weight: 700;

            margin-right: 9px;
        }

        .admin-info {
            flex: 1;
            min-width: 0;
        }

        .admin-name {
            font-size: 11px;
            font-weight: 700;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .admin-role {
            margin-top: 3px;
            font-size: 10px;
            color: var(--brown-light);
        }

        .logout-link {
            width: 30px;
            height: 30px;
            min-width: 30px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;

            color: var(--red);
            font-size: 16px;

            transition: .2s ease;
        }

        .logout-link:hover {
            background: var(--red-light);
        }

        /* =====================================================
           MAIN
        ===================================================== */

        .main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* TOPBAR */

        .topbar {
            height: 72px;

            position: sticky;
            top: 0;
            z-index: 100;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 28px;

            background: rgba(255,250,247,.96);

            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);

            border-bottom: 1px solid var(--border);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mobile-menu-btn {
            display: none;

            width: 39px;
            height: 39px;

            border: 1px solid var(--border);
            background: white;
            border-radius: 9px;

            color: var(--brown);
            font-size: 19px;
        }

        .topbar-title h2 {
            font-family: "Playfair Display", serif;
            font-size: 24px;
            color: var(--brown);
        }

        .topbar-title p {
            margin-top: 2px;
            font-size: 10px;
            color: var(--brown-light);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .view-store-btn {
            height: 37px;

            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 0 13px;

            border: 1px solid var(--border);
            border-radius: 8px;

            background: white;
            color: var(--brown);

            font-size: 11px;
            font-weight: 700;

            transition: .2s ease;
        }

        .view-store-btn:hover {
            background: var(--light-pink);
            color: var(--dark-pink);
        }

        .top-admin {
            display: flex;
            align-items: center;
            gap: 9px;

            padding-left: 13px;
            border-left: 1px solid var(--border);
        }

        .top-admin-avatar {
            width: 35px;
            height: 35px;

            border-radius: 50%;

            background: var(--pink);
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 12px;
            font-weight: 700;
        }

        .top-admin-text strong {
            display: block;
            font-size: 11px;
        }

        .top-admin-text span {
            display: block;
            margin-top: 2px;

            font-size: 10px;
            color: var(--brown-light);
        }

        /* =====================================================
           CONTENT
        ===================================================== */

        .content {
            padding: 27px 28px 40px;
            max-width: 1600px;
            margin: auto;
        }

        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;

            margin-bottom: 22px;
        }

        .page-heading h1 {
            font-family: "Playfair Display", serif;
            font-size: 28px;
            line-height: 1.2;

            margin-bottom: 6px;
        }

        .page-heading p {
            font-size: 12px;
            line-height: 1.6;
            color: var(--brown-light);
        }

        /* =====================================================
           STATISTICS
        ===================================================== */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;

            margin-bottom: 21px;
        }

        .stat-card {
            min-width: 0;

            display: flex;
            align-items: center;
            gap: 12px;

            padding: 17px;

            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;

            box-shadow: var(--shadow);

            transition: .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;
            font-size: 19px;
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
            margin-bottom: 5px;

            font-size: 10px;
            font-weight: 600;
            color: var(--brown-light);
        }

        .stat-number {
            font-size: 22px;
            line-height: 1;
            font-weight: 800;
            color: var(--brown);
        }

        /* =====================================================
           FILTER
        ===================================================== */

        .filter-card {
            padding: 15px;
            margin-bottom: 19px;

            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;

            box-shadow: var(--shadow);
        }

        .filter-form {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                175px
                100px
                75px;

            gap: 9px;
            align-items: center;
        }

        .search-wrapper {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;

            transform: translateY(-50%);

            font-size: 13px;
            color: #a99494;

            pointer-events: none;
        }

        .form-input,
        .form-select {
            width: 100%;
            height: 41px;

            padding: 0 12px;

            border: 1px solid var(--border);
            border-radius: 8px;

            background: var(--cream);
            color: var(--brown);

            outline: none;
            font-size: 11px;

            transition: .2s ease;
        }

        .search-input {
            padding-left: 35px;
        }

        .form-input::placeholder {
            color: #ad9999;
        }

        .form-input:focus,
        .form-select:focus {
            background: white;
            border-color: var(--pink);

            box-shadow: 0 0 0 3px rgba(169,103,113,.08);
        }

        .filter-btn {
            height: 41px;
            padding: 0 14px;

            border: 0;
            border-radius: 8px;

            background: var(--pink);
            color: white;

            font-size: 11px;
            font-weight: 700;

            transition: .2s ease;
        }

        .filter-btn:hover {
            background: var(--dark-pink);
            transform: translateY(-1px);
        }

        .clear-btn {
            height: 41px;

            display: flex;
            align-items: center;
            justify-content: center;

            border: 1px solid var(--border);
            border-radius: 8px;

            background: white;
            color: var(--brown-light);

            font-size: 11px;
            font-weight: 700;

            transition: .2s ease;
        }

        .clear-btn:hover {
            background: var(--light-pink);
            color: var(--dark-pink);
        }

        /* =====================================================
           TABLE
        ===================================================== */

        .table-card {
            overflow: hidden;

            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;

            box-shadow: var(--shadow);
        }

        .table-header {
            min-height: 66px;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            padding: 14px 18px;

            border-bottom: 1px solid var(--border);
        }

        .table-heading h3 {
            font-family: "Playfair Display", serif;
            font-size: 19px;
        }

        .table-heading p {
            margin-top: 3px;
            font-size: 10px;
            color: var(--brown-light);
        }

        .result-count {
            padding: 7px 11px;

            border-radius: 7px;

            background: var(--light-pink);
            color: var(--dark-pink);

            font-size: 10px;
            font-weight: 700;

            white-space: nowrap;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;

            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            min-width: 950px;

            border-collapse: collapse;
        }

        thead th {
            padding: 13px;

            background: var(--cream);

            color: var(--brown-light);
            border-bottom: 1px solid var(--border);

            text-align: left;

            font-size: 10px;
            font-weight: 800;
            letter-spacing: .6px;
            text-transform: uppercase;

            white-space: nowrap;
        }

        tbody td {
            padding: 14px 13px;

            border-bottom: 1px solid #f0e5e2;

            color: var(--brown);
            font-size: 11px;

            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr {
            transition: background .2s ease;
        }

        tbody tr:hover {
            background: #fffdfc;
        }

        /* ORDER ID */

        .order-id {
            color: var(--pink);
            font-weight: 800;
            white-space: nowrap;
        }

        /* CUSTOMER */

        .customer-cell {
            min-width: 180px;
        }

        .customer-name {
            margin-bottom: 4px;

            font-size: 11px;
            font-weight: 700;
        }

        .customer-email {
            max-width: 180px;

            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;

            color: var(--brown-light);
            font-size: 9px;
        }

        .phone {
            color: var(--brown-light);
            white-space: nowrap;
            font-size: 10px;
        }

        /* AMOUNT */

        .amount {
            color: var(--brown);
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        /* PAYMENT */

        .payment-badge {
            display: inline-flex;
            align-items: center;

            padding: 6px 9px;

            border-radius: 6px;

            font-size: 9px;
            font-weight: 800;

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

        /* ORDER STATUS */

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            padding: 6px 9px;

            border-radius: 6px;

            font-size: 9px;
            font-weight: 800;

            white-space: nowrap;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
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

        /* DATE */

        .order-date {
            color: var(--brown-light);
            font-size: 10px;
            white-space: nowrap;
        }

        /* VIEW BUTTON */

        .view-order-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;

            padding: 7px 10px;

            border: 1px solid var(--border);
            border-radius: 7px;

            background: white;
            color: var(--pink);

            font-size: 10px;
            font-weight: 800;

            white-space: nowrap;

            transition: .2s ease;
        }

        .view-order-btn:hover {
            background: var(--light-pink);
            color: var(--dark-pink);
            border-color: var(--soft-pink);
        }

        /* =====================================================
           EMPTY STATE AND ERRORS
        ===================================================== */

        .empty-state {
            padding: 55px 20px;
            text-align: center;
        }

        .empty-icon {
            width: 64px;
            height: 64px;

            margin: 0 auto 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 18px;

            background: var(--light-pink);
            color: var(--pink);

            font-size: 27px;
        }

        .empty-state h3 {
            margin-bottom: 7px;

            font-family: "Playfair Display", serif;
            font-size: 20px;
        }

        .empty-state p {
            margin-bottom: 17px;

            color: var(--brown-light);
            font-size: 11px;
            line-height: 1.6;
        }

        .empty-add-btn {
            display: inline-flex;

            padding: 10px 15px;

            border-radius: 8px;

            background: var(--pink);
            color: white;

            font-size: 10px;
            font-weight: 700;
        }

        .empty-add-btn:hover {
            background: var(--dark-pink);
        }

        .error-message {
            padding: 17px;

            margin-bottom: 18px;

            border: 1px solid #efcaca;
            border-radius: 10px;

            background: var(--red-light);
            color: var(--red);

            font-size: 12px;
            line-height: 1.6;
        }

        /* =====================================================
           PAGINATION
        ===================================================== */

        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            padding: 16px 18px;

            border-top: 1px solid var(--border);
        }

        .pagination-info {
            color: var(--brown-light);
            font-size: 10px;
        }

        .pagination-links {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .page-link {
            min-width: 31px;
            height: 31px;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 0 8px;

            border: 1px solid var(--border);
            border-radius: 7px;

            background: white;
            color: var(--brown-light);

            font-size: 10px;
            font-weight: 700;

            transition: .2s ease;
        }

        .page-link:hover {
            background: var(--light-pink);
            color: var(--dark-pink);
        }

        .page-link.active {
            background: var(--pink);
            border-color: var(--pink);
            color: white;
        }

        .page-link.disabled {
            opacity: .4;
            pointer-events: none;
        }

        /* =====================================================
           OVERLAY
        ===================================================== */

        .sidebar-overlay {
            display: none;

            position: fixed;
            inset: 0;

            background: rgba(81,61,61,.4);

            z-index: 900;
        }

        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 1100px) {

            :root {
                --sidebar-width: 225px;
            }

            .content {
                padding: 23px 20px 35px;
            }

            .topbar {
                padding: 0 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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

            .main {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .topbar {
                height: 66px;
                padding: 0 17px;
            }

            .topbar-title h2 {
                font-size: 22px;
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
                padding: 21px 17px 30px;
            }

            .filter-form {
                grid-template-columns: 1fr 150px;
            }

            .filter-btn,
            .clear-btn {
                width: 100%;
            }
        }

        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .topbar {
                padding: 0 13px;
            }

            .topbar-title h2 {
                font-size: 20px;
            }

            .content {
                padding: 18px 13px 25px;
            }

            .page-heading h1 {
                font-size: 24px;
            }

            .page-heading p {
                font-size: 11px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .stat-card {
                padding: 14px;
            }

            .filter-card {
                padding: 11px;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .table-header {
                align-items: flex-start;
                flex-direction: column;
                padding: 14px;
            }

            .pagination {
                align-items: flex-start;
                flex-direction: column;
            }

            .pagination-links {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 380px) {

            .brand-title {
                font-size: 17px;
            }

            .topbar-title h2 {
                font-size: 18px;
            }

            .content {
                padding-left: 10px;
                padding-right: 10px;
            }

            .page-heading h1 {
                font-size: 22px;
            }

            .stat-number {
                font-size: 20px;
            }
        }

    </style>

</head>

<body>

<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar" id="sidebar">

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

        <div class="nav-section">

            <div class="nav-title">
                Main Menu
            </div>

            <a href="dashboard.php" class="nav-link">

                <span class="nav-icon">🏠</span>
                <span>Dashboard</span>

            </a>

            <a href="products.php" class="nav-link">

                <span class="nav-icon">🛍️</span>
                <span>Products</span>

            </a>

            <a href="orders.php" class="nav-link active" aria-current="page">

                <span class="nav-icon">📦</span>
                <span>Orders</span>

            </a>

            <a href="message.php" class="nav-link">

                <span class="nav-icon">💌</span>
                <span>Messages</span>

            </a>

        </div>

        <div class="nav-section">

            <div class="nav-title">
                Management
            </div>

            <a href="products.php" class="nav-link">

                <span class="nav-icon">📊</span>
                <span>Inventory</span>

            </a>

            <a href="../index.html" class="nav-link" target="_blank" rel="noopener noreferrer">

                <span class="nav-icon">👁️</span>
                <span>View Store</span>

            </a>

            <a href="add-product.php" class="nav-link">

                <span class="nav-icon">➕</span>
                <span>Add Product</span>

            </a>

        </div>

    </div>

    <!-- ADMIN PROFILE -->

    <div class="sidebar-bottom">

        <div class="admin-mini">

            <div class="admin-avatar">
                <?php echo e($adminInitial); ?>
            </div>

            <div class="admin-info">

                <div class="admin-name">
                    <?php echo e($adminName); ?>
                </div>

                <div class="admin-role">
                    Administrator
                </div>

            </div>

            <a
                href="logout.php"
                class="logout-link"
                title="Logout"
                aria-label="Logout"
                onclick="return confirm('Are you sure you want to logout?');"
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
     MAIN CONTENT
========================================================= -->

<main class="main">

    <!-- TOPBAR -->

    <header class="topbar">

        <div class="topbar-left">

            <button
                type="button"
                class="mobile-menu-btn"
                onclick="toggleSidebar()"
                aria-label="Open navigation menu"
                aria-expanded="false"
                id="menuButton"
            >
                ☰
            </button>

            <div class="topbar-title">

                <h2>Orders</h2>

                <p>Manage customer orders</p>

            </div>

        </div>

        <div class="topbar-right">

            <a
                href="../index.html"
                class="view-store-btn"
                target="_blank"
                rel="noopener noreferrer"
            >
                👁️ View Store
            </a>

            <div class="top-admin">

                <div class="top-admin-avatar">
                    <?php echo e($adminInitial); ?>
                </div>

                <div class="top-admin-text">

                    <strong>
                        <?php echo e($adminName); ?>
                    </strong>

                    <span>Administrator</span>

                </div>

            </div>

        </div>

    </header>

    <!-- PAGE CONTENT -->

    <section class="content">

        <!-- PAGE HEADING -->

        <div class="page-header">

            <div class="page-heading">

                <h1>Order Management</h1>

                <p>
                    View, search and manage all customer orders from your crochet store.
                </p>

            </div>

        </div>

        <!-- DATABASE ERROR -->

        <?php if ($dbError !== ""): ?>

            <div class="error-message">
                ⚠️ <?php echo e($dbError); ?>
            </div>

        <?php endif; ?>

        <?php if ($dbError === "" && !$tableExists): ?>

            <div class="error-message">
                ⚠️ Orders table was not found in the knot_house database.
                Please check your database.
            </div>

        <?php endif; ?>

        <?php if ($tableExists && $dbError === ""): ?>

        <!-- =============================================
             ORDER STATISTICS
        ============================================== -->

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
                        <?php echo number_format($totalOrders); ?>
                    </div>

                </div>

            </div>

            <!-- PENDING ORDERS -->

            <div class="stat-card">

                <div class="stat-icon orange">
                    ⏳
                </div>

                <div>

                    <div class="stat-label">
                        Pending Orders
                    </div>

                    <div class="stat-number">
                        <?php echo number_format($pendingOrders); ?>
                    </div>

                </div>

            </div>

            <!-- PROCESSING ORDERS -->

            <div class="stat-card">

                <div class="stat-icon blue">
                    🔄
                </div>

                <div>

                    <div class="stat-label">
                        Processing
                    </div>

                    <div class="stat-number">
                        <?php echo number_format($processingOrders); ?>
                    </div>

                </div>

            </div>

            <!-- DELIVERED ORDERS -->

            <div class="stat-card">

                <div class="stat-icon green">
                    ✓
                </div>

                <div>

                    <div class="stat-label">
                        Delivered
                    </div>

                    <div class="stat-number">
                        <?php echo number_format($deliveredOrders); ?>
                    </div>

                </div>

            </div>

        </div>

        <!-- =============================================
             SEARCH AND FILTER
        ============================================== -->

        <div class="filter-card">

            <form
                method="GET"
                action="orders.php"
                class="filter-form"
            >

                <!-- SEARCH -->

                <div class="search-wrapper">

                    <span class="search-icon">
                        🔍
                    </span>

                    <input
                        type="text"
                        name="search"
                        class="form-input search-input"
                        placeholder="Search order ID, customer, email or phone..."
                        value="<?php echo e($search); ?>"
                        maxlength="100"
                    >

                </div>

                <!-- STATUS FILTER -->

                <select
                    name="status"
                    class="form-select"
                    aria-label="Filter by order status"
                >

                    <option value="">
                        All Statuses
                    </option>

                    <?php foreach ($orderStatuses as $orderStatus): ?>

                        <option
                            value="<?php echo e($orderStatus); ?>"
                            <?php echo ($status === $orderStatus) ? "selected" : ""; ?>
                        >
                            <?php echo e($orderStatus); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <!-- FILTER BUTTON -->

                <button
                    type="submit"
                    class="filter-btn"
                >
                    🔍 Filter
                </button>

                <!-- CLEAR BUTTON -->

                <?php if ($search !== "" || $status !== ""): ?>

                    <a
                        href="orders.php"
                        class="clear-btn"
                    >
                        Clear
                    </a>

                <?php else: ?>

                    <span></span>

                <?php endif; ?>

            </form>

        </div>

        <!-- =============================================
             ORDERS TABLE
        ============================================== -->

        <div class="table-card">

            <div class="table-header">

                <div class="table-heading">

                    <h3>All Orders</h3>

                    <p>
                        Customer orders and payment details
                    </p>

                </div>

                <div class="result-count">

                    <?php echo number_format($totalRecords); ?>

                    <?php echo ($totalRecords === 1) ? "Order" : "Orders"; ?>

                </div>

            </div>

            <!-- EMPTY STATE -->

            <?php if (empty($orders)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        📦
                    </div>

                    <h3>No Orders Found</h3>

                    <p>

                        <?php if ($search !== "" || $status !== ""): ?>

                            No orders match your current search or filter.

                        <?php else: ?>

                            There are no customer orders yet.

                        <?php endif; ?>

                    </p>

                    <?php if ($search !== "" || $status !== ""): ?>

                        <a
                            href="orders.php"
                            class="empty-add-btn"
                        >
                            View All Orders
                        </a>

                    <?php endif; ?>

                </div>

            <?php else: ?>

                <!-- ORDERS TABLE -->

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>Order ID</th>

                                <th>Customer</th>

                                <th>Phone</th>

                                <th>Date</th>

                                <th>Total</th>

                                <th>Payment</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($orders as $order): ?>

                                <?php

                                $orderId = (int)$order["id"];

                                $customerName = $order["customer_name"] ?? "Customer";

                                $customerEmail = $order["customer_email"] ?? "";

                                $customerPhone = $order["customer_phone"] ?? "";

                                $totalAmount = (float)($order["total_amount"] ?? 0);

                                $paymentStatus = $order["payment_status"] ?? "Pending";

                                $orderStatus = $order["order_status"] ?? "Pending";

                                $createdAt = $order["created_at"] ?? "";

                                /* FORMAT DATE */

                                $formattedDate = "—";

                                if ($createdAt !== "") {

                                    $timestamp = strtotime($createdAt);

                                    if ($timestamp !== false) {

                                        $formattedDate = date(
                                            "d M Y",
                                            $timestamp
                                        );

                                    }
                                }

                                /* STATUS CLASS */

                                $statusClass = getStatusClass($orderStatus);

                                /* PAYMENT CLASS */

                                $paymentClass = getPaymentClass($paymentStatus);

                                ?>

                                <tr>

                                    <!-- ORDER ID -->

                                    <td>

                                        <span class="order-id">
                                            #<?php echo $orderId; ?>
                                        </span>

                                    </td>

                                    <!-- CUSTOMER -->

                                    <td>

                                        <div class="customer-cell">

                                            <div class="customer-name">
                                                <?php echo e($customerName); ?>
                                            </div>

                                            <div
                                                class="customer-email"
                                                title="<?php echo e($customerEmail); ?>"
                                            >
                                                <?php echo e($customerEmail); ?>
                                            </div>

                                        </div>

                                    </td>

                                    <!-- PHONE -->

                                    <td>

                                        <span class="phone">
                                            <?php echo e($customerPhone !== "" ? $customerPhone : "—"); ?>
                                        </span>

                                    </td>

                                    <!-- DATE -->

                                    <td>

                                        <span class="order-date">
                                            <?php echo e($formattedDate); ?>
                                        </span>

                                    </td>

                                    <!-- TOTAL -->

                                    <td>

                                        <span class="amount">
                                            ₹<?php echo number_format($totalAmount, 2); ?>
                                        </span>

                                    </td>

                                    <!-- PAYMENT -->

                                    <td>

                                        <span class="payment-badge payment-<?php echo e($paymentClass); ?>">

                                            <?php echo e($paymentStatus); ?>

                                        </span>

                                    </td>

                                    <!-- STATUS -->

                                    <td>

                                        <span class="status-badge status-<?php echo e($statusClass); ?>">

                                            <span class="status-dot"></span>

                                            <?php echo e($orderStatus); ?>

                                        </span>

                                    </td>

                                    <!-- ACTION -->

                                    <td>

                                        <a
                                            href="order.php?id=<?php echo $orderId; ?>"
                                            class="view-order-btn"
                                        >
                                            👁️ View Order
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <!-- =====================================
                     PAGINATION
                ====================================== -->

                <?php if ($totalPages > 1): ?>

                    <div class="pagination">

                        <div class="pagination-info">

                            Showing

                            <?php echo number_format($offset + 1); ?>

                            to

                            <?php echo number_format(min($offset + count($orders), $totalRecords)); ?>

                            of

                            <?php echo number_format($totalRecords); ?>

                            orders

                        </div>

                        <div class="pagination-links">

                            <!-- PREVIOUS -->

                            <a
                                href="<?php echo e(pageUrl($currentPage - 1, $search, $status)); ?>"
                                class="page-link <?php echo ($currentPage <= 1) ? "disabled" : ""; ?>"
                                aria-label="Previous page"
                            >
                                ‹
                            </a>

                            <!-- PAGE NUMBERS -->

                            <?php

                            $startPage = max(1, $currentPage - 2);

                            $endPage = min(
                                $totalPages,
                                $currentPage + 2
                            );

                            ?>

                            <?php if ($startPage > 1): ?>

                                <a
                                    href="<?php echo e(pageUrl(1, $search, $status)); ?>"
                                    class="page-link"
                                >
                                    1
                                </a>

                                <?php if ($startPage > 2): ?>

                                    <span>...</span>

                                <?php endif; ?>

                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>

                                <a
                                    href="<?php echo e(pageUrl($i, $search, $status)); ?>"
                                    class="page-link <?php echo ($currentPage === $i) ? "active" : ""; ?>"
                                    <?php echo ($currentPage === $i) ? 'aria-current="page"' : ""; ?>
                                >
                                    <?php echo $i; ?>
                                </a>

                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>

                                <?php if ($endPage < $totalPages - 1): ?>

                                    <span>...</span>

                                <?php endif; ?>

                                <a
                                    href="<?php echo e(pageUrl($totalPages, $search, $status)); ?>"
                                    class="page-link"
                                >
                                    <?php echo $totalPages; ?>
                                </a>

                            <?php endif; ?>

                            <!-- NEXT -->

                            <a
                                href="<?php echo e(pageUrl($currentPage + 1, $search, $status)); ?>"
                                class="page-link <?php echo ($currentPage >= $totalPages) ? "disabled" : ""; ?>"
                                aria-label="Next page"
                            >
                                ›
                            </a>

                        </div>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>

        <?php endif; ?>

    </section>

</main>

<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

    const sidebar = document.getElementById("sidebar");

    const overlay = document.getElementById("sidebarOverlay");

    const menuButton = document.getElementById("menuButton");


    /* OPEN SIDEBAR */

    function toggleSidebar() {

        const isOpen = sidebar.classList.toggle("open");

        overlay.classList.toggle("show", isOpen);

        menuButton.setAttribute(
            "aria-expanded",
            isOpen ? "true" : "false"
        );

    }


    /* CLOSE SIDEBAR */

    function closeSidebar() {

        sidebar.classList.remove("open");

        overlay.classList.remove("show");

        menuButton.setAttribute(
            "aria-expanded",
            "false"
        );

    }


    /* CLOSE WHEN CLICKING NAVIGATION */

    document.querySelectorAll(".nav-link").forEach(function(link) {

        link.addEventListener("click", function() {

            if (window.innerWidth <= 850) {

                closeSidebar();

            }

        });

    });


    /* CLOSE WITH ESCAPE KEY */

    document.addEventListener("keydown", function(event) {

        if (event.key === "Escape") {

            closeSidebar();

        }

    });


    /* CLOSE SIDEBAR ON DESKTOP RESIZE */

    window.addEventListener("resize", function() {

        if (window.innerWidth > 850) {

            closeSidebar();

        }

    });

</script>

</body>

</html>
