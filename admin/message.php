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

    die(
        "Database connection failed: " .
        $conn->connect_error
    );

}


$conn->set_charset("utf8mb4");


/* =========================================================
   ADMIN INFORMATION
   ========================================================= */

$adminName =
    $_SESSION["admin_name"] ?? "Admin";


$adminEmail =
    $_SESSION["admin_email"] ?? "";


$adminInitial = strtoupper(
    substr(
        trim($adminName),
        0,
        1
    )
);


if ($adminInitial === "") {

    $adminInitial = "A";

}


/* =========================================================
   DELETE MESSAGE
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        isset($_POST["delete_message"]) &&
        isset($_POST["message_id"])
    ) {

        $messageId =
            (int)$_POST["message_id"];


        if ($messageId > 0) {

            $deleteStmt = $conn->prepare(
                "DELETE FROM contact_messages WHERE id = ?"
            );


            if ($deleteStmt) {

                $deleteStmt->bind_param(
                    "i",
                    $messageId
                );

                $deleteStmt->execute();

                $deleteStmt->close();

            }

        }


        header(
            "Location: message.php"
        );

        exit();

    }

}


/* =========================================================
   DEFAULT VALUES
   ========================================================= */

$totalMessages = 0;

$unreadMessages = 0;

$readMessages = 0;

$repliedMessages = 0;

$messages = [];


/* =========================================================
   CHECK CONTACT MESSAGES TABLE
   ========================================================= */

$contactTableExists = false;


$tableCheck = $conn->query(
    "SHOW TABLES LIKE 'contact_messages'"
);


if (
    $tableCheck &&
    $tableCheck->num_rows > 0
) {

    $contactTableExists = true;

}


/* =========================================================
   MESSAGE STATISTICS
   ========================================================= */

if ($contactTableExists) {


    /* =====================================================
       TOTAL MESSAGES
       ===================================================== */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM contact_messages
    ");


    if ($result) {

        $row = $result->fetch_assoc();

        $totalMessages =
            (int)$row["total"];

    }


    /* =====================================================
       UNREAD MESSAGES
       ===================================================== */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM contact_messages
        WHERE LOWER(status) = 'unread'
    ");


    if ($result) {

        $row = $result->fetch_assoc();

        $unreadMessages =
            (int)$row["total"];

    }


    /* =====================================================
       READ MESSAGES
       ===================================================== */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM contact_messages
        WHERE LOWER(status) = 'read'
    ");


    if ($result) {

        $row = $result->fetch_assoc();

        $readMessages =
            (int)$row["total"];

    }


    /* =====================================================
       REPLIED MESSAGES
       ===================================================== */

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM contact_messages
        WHERE LOWER(status) = 'replied'
    ");


    if ($result) {

        $row = $result->fetch_assoc();

        $repliedMessages =
            (int)$row["total"];

    }


    /* =====================================================
       GET ALL MESSAGES
       ===================================================== */

    $result = $conn->query("
        SELECT
            id,
            name,
            email,
            phone,
            subject,
            message,
            admin_reply,
            status,
            replied_at,
            created_at
        FROM contact_messages
        ORDER BY created_at DESC
    ");


    if ($result) {

        while (
            $row = $result->fetch_assoc()
        ) {

            $messages[] = $row;

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
        Messages | The Knot House Admin
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
        href="https://fonts.googleapis.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>


        /* =====================================================
           ROOT COLORS
           SAME AS DASHBOARD
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
                0 8px 30px rgba(
                    81,
                    61,
                    61,
                    0.06
                );

            --shadow-hover:
                0 12px 35px rgba(
                    81,
                    61,
                    61,
                    0.10
                );

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

            width:
                var(--sidebar-width);

            background:
                var(--white);

            border-right:
                1px solid var(--border);

            display: flex;

            flex-direction: column;

            z-index: 1000;

            transition:
                transform 0.3s ease;

            box-shadow:
                5px 0 25px
                rgba(
                    81,
                    61,
                    61,
                    0.03
                );

        }


        /* =====================================================
           SIDEBAR BRAND
           ===================================================== */

        .sidebar-brand {

            height: 82px;

            display: flex;

            align-items: center;

            padding:
                0 22px;

            border-bottom:
                1px solid var(--border);

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
                0 6px 15px
                rgba(
                    169,
                    103,
                    113,
                    0.22
                );

        }


        .brand-text {

            line-height: 1.1;

        }


        .brand-title {

            font-family:
                "Playfair Display",
                serif;

            font-size: 18px;

            font-weight: 700;

            color:
                var(--brown);

        }


        .brand-subtitle {

            font-size: 10px;

            letter-spacing: 1.5px;

            font-weight: 700;

            color:
                var(--pink);

            margin-top: 5px;

        }


        /* =====================================================
           SIDEBAR CONTENT
           ===================================================== */

        .sidebar-content {

            flex: 1;

            overflow-y: auto;

            padding:
                22px 14px;

        }


        .nav-section {

            margin-bottom: 25px;

        }


        .nav-title {

            padding:
                0 12px;

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

            padding:
                10px 12px;

            margin-bottom: 4px;

            border-radius: 11px;

            color:
                var(--brown-light);

            font-size: 14px;

            font-weight: 600;

            transition:
                background 0.25s ease,
                color 0.25s ease,
                transform 0.25s ease;

        }


        .nav-link:hover {

            background:
                var(--light-pink);

            color:
                var(--dark-pink);

            transform:
                translateX(2px);

        }


        .nav-link.active {

            background:
                var(--light-pink);

            color:
                var(--dark-pink);

            font-weight: 700;

            box-shadow:
                inset 3px 0 0
                var(--pink);

        }


        .nav-icon {

            width: 25px;

            display: inline-flex;

            justify-content: center;

            align-items: center;

            font-size: 17px;

        }


        /* =====================================================
           MESSAGE COUNT IN SIDEBAR
           ===================================================== */

        .nav-count {

            margin-left: auto;

            min-width: 22px;

            height: 22px;

            padding:
                0 6px;

            border-radius: 7px;

            background:
                var(--pink);

            color:
                var(--white);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 9px;

            font-weight: 700;

        }


        /* =====================================================
           SIDEBAR BOTTOM
           ===================================================== */

        .sidebar-bottom {

            padding: 15px;

            border-top:
                1px solid var(--border);

        }


        .admin-mini {

            display: flex;

            align-items: center;

            padding: 11px;

            border-radius: 13px;

            background:
                var(--cream);

            border:
                1px solid var(--border);

        }


        .admin-avatar {

            width: 39px;

            height: 39px;

            border-radius: 50%;

            background:
                var(--pink);

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

            color:
                var(--brown);

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        .admin-role {

            font-size: 11px;

            color:
                var(--brown-light);

            margin-top: 2px;

        }


        .logout-link {

            width: 32px;

            height: 32px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            color:
                var(--red);

            font-size: 16px;

            transition:
                0.25s ease;

        }


        .logout-link:hover {

            background:
                var(--red-light);

            transform:
                scale(1.05);

        }


        /* =====================================================
           MAIN
           ===================================================== */

        .main {

            margin-left:
                var(--sidebar-width);

            min-height: 100vh;

        }


        /* =====================================================
           TOPBAR
           ===================================================== */

        .topbar {

            height: 76px;

            background:
                rgba(
                    255,
                    250,
                    247,
                    0.94
                );

            backdrop-filter:
                blur(14px);

            -webkit-backdrop-filter:
                blur(14px);

            border-bottom:
                1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0 30px;

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

            border:
                1px solid var(--border);

            background:
                var(--white);

            border-radius: 10px;

            color:
                var(--brown);

            font-size: 20px;

            cursor: pointer;

        }


        .topbar-title h2 {

            font-family:
                "Playfair Display",
                serif;

            font-size: 23px;

            color:
                var(--brown);

            font-weight: 700;

        }


        .topbar-title p {

            color:
                var(--brown-light);

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

            padding:
                9px 14px;

            border-radius: 9px;

            border:
                1px solid var(--border);

            background:
                var(--white);

            color:
                var(--brown);

            font-size: 12px;

            font-weight: 700;

            transition:
                0.25s ease;

        }


        .view-store-btn:hover {

            background:
                var(--light-pink);

            border-color:
                var(--soft-pink);

            color:
                var(--dark-pink);

            transform:
                translateY(-1px);

        }


        .top-admin {

            display: flex;

            align-items: center;

            gap: 9px;

            padding-left: 14px;

            border-left:
                1px solid var(--border);

        }


        .top-admin-avatar {

            width: 37px;

            height: 37px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                var(--pink);

            color: white;

            font-size: 13px;

            font-weight: 700;

        }


        .top-admin-text strong {

            display: block;

            color:
                var(--brown);

            font-size: 12px;

        }


        .top-admin-text span {

            display: block;

            color:
                var(--brown-light);

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
           PAGE HERO
           ===================================================== */

        .page-hero {

            position: relative;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    var(--white) 0%,
                    var(--light-pink) 100%
                );

            border:
                1px solid var(--border);

            border-radius: 18px;

            padding:
                27px 28px;

            margin-bottom: 24px;

            box-shadow:
                var(--shadow);

        }


        .page-hero-content {

            position: relative;

            z-index: 2;

            max-width: 700px;

        }


        .hero-small {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            color:
                var(--pink);

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 0.5px;

            margin-bottom: 8px;

        }


        .page-hero h1 {

            font-family:
                "Playfair Display",
                serif;

            font-size: 30px;

            line-height: 1.2;

            color:
                var(--brown);

            margin-bottom: 8px;

        }


        .page-hero h1 span {

            color:
                var(--pink);

        }


        .page-hero p {

            color:
                var(--brown-light);

            font-size: 13px;

            line-height: 1.6;

        }


        .hero-decoration {

            position: absolute;

            right: 30px;

            top: 50%;

            transform:
                translateY(-50%);

            width: 135px;

            height: 135px;

            border-radius: 50%;

            background:
                rgba(
                    169,
                    103,
                    113,
                    0.08
                );

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 62px;

        }


        /* =====================================================
           STATISTICS
           ===================================================== */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 17px;

            margin-bottom: 24px;

        }


        .stat-card {

            background:
                var(--white);

            border:
                1px solid var(--border);

            border-radius: 15px;

            padding: 18px;

            box-shadow:
                var(--shadow);

            display: flex;

            align-items: center;

            gap: 13px;

            transition:
                0.25s ease;

        }


        .stat-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                var(--shadow-hover);

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

            background:
                var(--light-pink);

            color:
                var(--pink);

        }


        .stat-icon.orange {

            background:
                var(--orange-light);

            color:
                var(--orange);

        }


        .stat-icon.blue {

            background:
                var(--blue-light);

            color:
                var(--blue);

        }


        .stat-icon.green {

            background:
                var(--green-light);

            color:
                var(--green);

        }


        .stat-label {

            font-size: 10px;

            color:
                var(--brown-light);

            font-weight: 600;

            margin-bottom: 5px;

        }


        .stat-number {

            font-size: 22px;

            font-weight: 700;

            color:
                var(--brown);

            line-height: 1;

        }


        .stat-note {

            font-size: 9px;

            color:
                var(--brown-light);

            margin-top: 5px;

        }


        /* =====================================================
           MESSAGE PANEL
           ===================================================== */

        .panel {

            background:
                var(--white);

            border:
                1px solid var(--border);

            border-radius: 15px;

            box-shadow:
                var(--shadow);

            overflow: hidden;

        }


        .panel-header {

            min-height: 65px;

            padding:
                14px 18px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            border-bottom:
                1px solid var(--border);

        }


        .panel-header h3 {

            font-family:
                "Playfair Display",
                serif;

            font-size: 18px;

            color:
                var(--brown);

        }


        .panel-header p {

            font-size: 10px;

            color:
                var(--brown-light);

            margin-top: 3px;

        }


        .panel-count {

            background:
                var(--light-pink);

            color:
                var(--dark-pink);

            border-radius: 8px;

            padding:
                6px 10px;

            font-size: 10px;

            font-weight: 700;

        }


        /* =====================================================
           TABLE WRAPPER
           ===================================================== */

        .table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .message-table {

            width: 100%;

            min-width: 1000px;

            border-collapse:
                collapse;

        }


        .message-table th {

            background:
                #fffdfc;

            color:
                var(--brown-light);

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.6px;

            text-align: left;

            padding:
                13px 16px;

            border-bottom:
                1px solid var(--border);

            white-space: nowrap;

        }


        .message-table td {

            padding:
                15px 16px;

            border-bottom:
                1px solid #f0e5e2;

            vertical-align: middle;

        }


        .message-table tbody tr {

            transition:
                background 0.2s ease;

        }


        .message-table tbody tr:hover {

            background:
                #fffdfc;

        }


        .message-table tbody tr:last-child td {

            border-bottom: none;

        }


        /* =====================================================
           MESSAGE ID
           ===================================================== */

        .message-id {

            color:
                var(--pink);

            font-size: 11px;

            font-weight: 700;

        }


        /* =====================================================
           CUSTOMER
           ===================================================== */

        .customer-cell {

            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 180px;

        }


        .customer-avatar {

            width: 37px;

            height: 37px;

            border-radius: 11px;

            background:
                var(--light-pink);

            color:
                var(--dark-pink);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 13px;

            font-weight: 700;

            flex-shrink: 0;

        }


        .customer-info {

            min-width: 0;

        }


        .customer-name {

            font-size: 12px;

            font-weight: 700;

            color:
                var(--brown);

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

            max-width: 160px;

        }


        .customer-email {

            font-size: 9px;

            color:
                var(--brown-light);

            margin-top: 3px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

            max-width: 180px;

        }


        /* =====================================================
           PHONE
           ===================================================== */

        .phone-text {

            font-size: 11px;

            color:
                var(--brown-light);

            white-space: nowrap;

        }


        /* =====================================================
           SUBJECT
           ===================================================== */

        .subject-text {

            max-width: 190px;

            font-size: 11px;

            font-weight: 700;

            color:
                var(--brown);

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        /* =====================================================
           DATE
           ===================================================== */

        .date-text {

            font-size: 10px;

            color:
                var(--brown-light);

            white-space: nowrap;

        }


        /* =====================================================
           STATUS
           ===================================================== */

        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding:
                6px 9px;

            border-radius: 7px;

            font-size: 9px;

            font-weight: 700;

            white-space: nowrap;

        }


        .status-dot {

            width: 6px;

            height: 6px;

            border-radius: 50%;

            display: inline-block;

        }


        .status-unread {

            background:
                var(--orange-light);

            color:
                var(--orange);

        }


        .status-unread
        .status-dot {

            background:
                var(--orange);

        }


        .status-read {

            background:
                var(--blue-light);

            color:
                var(--blue);

        }


        .status-read
        .status-dot {

            background:
                var(--blue);

        }


        .status-replied {

            background:
                var(--green-light);

            color:
                var(--green);

        }


        .status-replied
        .status-dot {

            background:
                var(--green);

        }


        /* =====================================================
           ACTION BUTTONS
           ===================================================== */

        .action-buttons {

            display: flex;

            align-items: center;

            gap: 7px;

        }


        .view-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            padding:
                8px 11px;

            border-radius: 8px;

            background:
                var(--light-pink);

            color:
                var(--dark-pink);

            font-size: 10px;

            font-weight: 700;

            border:
                1px solid transparent;

            transition:
                0.25s ease;

            white-space: nowrap;

        }


        .view-btn:hover {

            background:
                var(--pink);

            color:
                var(--white);

            transform:
                translateY(-1px);

        }


        .delete-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            padding:
                8px 11px;

            border-radius: 8px;

            background:
                var(--red-light);

            color:
                var(--red);

            font-size: 10px;

            font-weight: 700;

            border:
                1px solid transparent;

            cursor: pointer;

            transition:
                0.25s ease;

            white-space: nowrap;

        }


        .delete-btn:hover {

            background:
                var(--red);

            color:
                var(--white);

            transform:
                translateY(-1px);

        }


        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .empty-state {

            padding:
                65px 25px;

            text-align: center;

        }


        .empty-icon {

            width: 65px;

            height: 65px;

            margin:
                0 auto 14px;

            border-radius: 18px;

            background:
                var(--light-pink);

            color:
                var(--pink);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;

        }


        .empty-state h3 {

            font-family:
                "Playfair Display",
                serif;

            font-size: 20px;

            color:
                var(--brown);

            margin-bottom: 6px;

        }


        .empty-state p {

            max-width: 420px;

            margin:
                0 auto;

            font-size: 11px;

            line-height: 1.6;

            color:
                var(--brown-light);

        }


        /* =====================================================
           DATABASE WARNING
           ===================================================== */

        .database-warning {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            background:
                var(--orange-light);

            border:
                1px solid #efd8b7;

            color:
                #8c612f;

            border-radius: 12px;

            padding:
                13px 15px;

            margin-bottom: 20px;

            font-size: 11px;

            line-height: 1.5;

        }


        /* =====================================================
           SIDEBAR OVERLAY
           ===================================================== */

        .sidebar-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(
                    81,
                    61,
                    61,
                    0.35
                );

            z-index: 900;

        }


        /* =====================================================
           TABLET
           ===================================================== */

        @media (max-width: 1100px) {

            :root {

                --sidebar-width:
                    235px;

            }


            .content {

                padding: 24px;

            }


            .stats-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );

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

                padding:
                    0 18px;

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

                padding:
                    20px 16px 30px;

            }


            .page-hero {

                padding:
                    23px 20px;

            }


            .page-hero h1 {

                font-size: 26px;

                padding-right: 60px;

            }


            .hero-decoration {

                width: 95px;

                height: 95px;

                right: -20px;

                font-size: 45px;

            }


            .stats-grid {

                grid-template-columns: 1fr;

                gap: 10px;

            }


            .panel-header {

                padding:
                    13px 14px;

            }

        }


        /* =====================================================
           SMALL MOBILE
           ===================================================== */

        @media (max-width: 480px) {

            .topbar {

                padding:
                    0 13px;

            }


            .topbar-title h2 {

                font-size: 18px;

            }


            .content {

                padding:
                    17px 12px 25px;

            }


            .page-hero {

                padding:
                    20px 17px;

            }


            .page-hero h1 {

                font-size: 23px;

                padding-right: 35px;

            }


            .page-hero p {

                font-size: 11px;

            }


            .hero-decoration {

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


            .action-buttons {

                flex-direction: column;

                align-items: stretch;

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


        <!-- =================================================
             MAIN MENU
             ================================================= -->

        <div class="nav-section">

            <div class="nav-title">
                Main Menu
            </div>


            <!-- DASHBOARD -->

            <a
                href="dashboard.php"
                class="nav-link"
            >

                <span class="nav-icon">
                    🏠
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <!-- PRODUCTS -->

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


            <!-- ORDERS -->

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


            <!-- MESSAGES -->

            <a
                href="messages.php"
                class="nav-link active"
            >

                <span class="nav-icon">
                    💬
                </span>

                <span>
                    Messages
                </span>


                <?php if (
                    $unreadMessages > 0
                ): ?>

                    <span class="nav-count">

                        <?php
                        echo
                            $unreadMessages;
                        ?>

                    </span>

                <?php endif; ?>

            </a>

        </div>


        <!-- =================================================
             MANAGEMENT
             ================================================= -->

        <div class="nav-section">

            <div class="nav-title">
                Management
            </div>


            <!-- INVENTORY -->

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


            <!-- VIEW STORE -->

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


            <!-- ADD PRODUCT -->

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


    <!-- =====================================================
         ADMIN BOTTOM
         ===================================================== -->

    <div class="sidebar-bottom">

        <div class="admin-mini">


            <div class="admin-avatar">

                <?php

                echo htmlspecialchars(
                    $adminInitial
                );

                ?>

            </div>


            <div class="admin-info">

                <div class="admin-name">

                    <?php

                    echo htmlspecialchars(
                        $adminName
                    );

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


<!-- =========================================================
     SIDEBAR OVERLAY
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
                    Messages
                </h2>

                <p>
                    Manage customer messages and replies
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

                    echo htmlspecialchars(
                        $adminInitial
                    );

                    ?>

                </div>


                <div class="top-admin-text">

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $adminName
                        );

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
            !$contactTableExists
        ): ?>

            <div class="database-warning">

                <span>
                    ⚠️
                </span>

                <div>

                    <strong>
                        Database setup notice:
                    </strong>

                    The
                    <strong>
                        contact_messages
                    </strong>
                    table was not found.

                    Please check your
                    <strong>
                        knot_house
                    </strong>
                    database.

                </div>

            </div>

        <?php endif; ?>



        <!-- =================================================
             PAGE HERO
             ================================================= -->

        <div class="page-hero">


            <div class="page-hero-content">


                <div class="hero-small">

                    💌

                    <span>
                        CUSTOMER COMMUNICATION
                    </span>

                </div>


                <h1>

                    Customer
                    <span>
                        Messages
                    </span>
                    💗

                </h1>


                <p>

                    View customer enquiries, read their
                    messages and manage your replies
                    from one simple place.

                </p>

            </div>


            <div class="hero-decoration">

                💌

            </div>

        </div>



        <!-- =================================================
             MESSAGE STATISTICS
             ================================================= -->

        <div class="stats-grid">


            <!-- TOTAL -->

            <div class="stat-card">


                <div class="stat-icon pink">
                    💬
                </div>


                <div>

                    <div class="stat-label">
                        Total Messages
                    </div>


                    <div class="stat-number">

                        <?php

                        echo number_format(
                            $totalMessages
                        );

                        ?>

                    </div>


                    <div class="stat-note">
                        All customer messages
                    </div>

                </div>

            </div>


            <!-- UNREAD -->

            <div class="stat-card">


                <div class="stat-icon orange">
                    🔔
                </div>


                <div>

                    <div class="stat-label">
                        Unread Messages
                    </div>


                    <div class="stat-number">

                        <?php

                        echo number_format(
                            $unreadMessages
                        );

                        ?>

                    </div>


                    <div class="stat-note">
                        Need your attention
                    </div>

                </div>

            </div>


            <!-- READ -->

            <div class="stat-card">


                <div class="stat-icon blue">
                    👁️
                </div>


                <div>

                    <div class="stat-label">
                        Read Messages
                    </div>


                    <div class="stat-number">

                        <?php

                        echo number_format(
                            $readMessages
                        );

                        ?>

                    </div>


                    <div class="stat-note">
                        Already viewed
                    </div>

                </div>

            </div>


            <!-- REPLIED -->

            <div class="stat-card">


                <div class="stat-icon green">
                    💚
                </div>


                <div>

                    <div class="stat-label">
                        Replied Messages
                    </div>


                    <div class="stat-number">

                        <?php

                        echo number_format(
                            $repliedMessages
                        );

                        ?>

                    </div>


                    <div class="stat-note">
                        Successfully answered
                    </div>

                </div>

            </div>

        </div>



        <!-- =================================================
             MESSAGE PANEL
             ================================================= -->

        <div class="panel">


            <!-- PANEL HEADER -->

            <div class="panel-header">


                <div>

                    <h3>
                        All Customer Messages
                    </h3>

                    <p>
                        Latest messages appear first
                    </p>

                </div>


                <div class="panel-count">

                    <?php

                    echo number_format(
                        $totalMessages
                    );

                    ?>

                    Messages

                </div>

            </div>



            <!-- =================================================
                 EMPTY STATE
                 ================================================= -->

            <?php if (
                !$contactTableExists ||
                empty($messages)
            ): ?>


                <div class="empty-state">


                    <div class="empty-icon">
                        💌
                    </div>


                    <h3>
                        No Messages Yet
                    </h3>


                    <p>

                        Customer messages submitted through
                        the Contact Us page will appear here.

                        You can view each message and reply
                        directly from the admin panel.

                    </p>

                </div>


            <?php else: ?>


                <!-- =================================================
                     MESSAGE TABLE
                     ================================================= -->

                <div class="table-wrapper">


                    <table
                        class="message-table"
                    >


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Subject
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $messages
                                as $message
                            ): ?>


                                <?php

                                /* =================================
                                   MESSAGE DATA
                                   ================================= */

                                $messageId =
                                    (int)(
                                        $message["id"]
                                        ?? 0
                                    );


                                $customerName =
                                    $message["name"]
                                    ?? "Customer";


                                $customerEmail =
                                    $message["email"]
                                    ?? "";


                                $customerPhone =
                                    $message["phone"]
                                    ?? "";


                                $subject =
                                    $message["subject"]
                                    ?? "No subject";


                                $status =
                                    strtolower(
                                        trim(
                                            $message["status"]
                                            ?? "unread"
                                        )
                                    );


                                $createdAt =
                                    $message["created_at"]
                                    ?? "";


                                /* =================================
                                   CUSTOMER INITIAL
                                   ================================= */

                                $customerInitial =
                                    strtoupper(
                                        substr(
                                            trim(
                                                $customerName
                                            ),
                                            0,
                                            1
                                        )
                                    );


                                if (
                                    $customerInitial === ""
                                ) {

                                    $customerInitial =
                                        "C";

                                }


                                /* =================================
                                   STATUS CLASS
                                   ================================= */

                                $statusClass =
                                    "status-unread";


                                $statusText =
                                    "Unread";


                                if (
                                    $status === "read"
                                ) {

                                    $statusClass =
                                        "status-read";

                                    $statusText =
                                        "Read";

                                }


                                if (
                                    $status === "replied"
                                ) {

                                    $statusClass =
                                        "status-replied";

                                    $statusText =
                                        "Replied";

                                }


                                /* =================================
                                   DATE FORMAT
                                   ================================= */

                                $formattedDate =
                                    "—";


                                if (
                                    !empty(
                                        $createdAt
                                    )
                                ) {

                                    $timestamp =
                                        strtotime(
                                            $createdAt
                                        );


                                    if (
                                        $timestamp !== false
                                    ) {

                                        $formattedDate =
                                            date(
                                                "d M Y",
                                                $timestamp
                                            );

                                    }

                                }

                                ?>


                                <tr>


                                    <!-- =========================
                                         ID
                                         ========================= -->

                                    <td>

                                        <span
                                            class="message-id"
                                        >

                                            #<?php

                                            echo
                                                $messageId;

                                            ?>

                                        </span>

                                    </td>



                                    <!-- =========================
                                         CUSTOMER
                                         ========================= -->

                                    <td>


                                        <div
                                            class="customer-cell"
                                        >


                                            <div
                                                class="customer-avatar"
                                            >

                                                <?php

                                                echo
                                                    htmlspecialchars(
                                                        $customerInitial
                                                    );

                                                ?>

                                            </div>


                                            <div
                                                class="customer-info"
                                            >


                                                <div
                                                    class="customer-name"
                                                    title="<?php
                                                        echo htmlspecialchars(
                                                            $customerName
                                                        );
                                                    ?>"
                                                >

                                                    <?php

                                                    echo
                                                        htmlspecialchars(
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
                                                    ?>"
                                                >

                                                    <?php

                                                    echo
                                                        htmlspecialchars(
                                                            $customerEmail
                                                        );

                                                    ?>

                                                </div>


                                            </div>


                                        </div>

                                    </td>



                                    <!-- =========================
                                         PHONE
                                         ========================= -->

                                    <td>

                                        <span
                                            class="phone-text"
                                        >

                                            <?php

                                            if (
                                                trim(
                                                    $customerPhone
                                                ) !== ""
                                            ) {

                                                echo
                                                    htmlspecialchars(
                                                        $customerPhone
                                                    );

                                            } else {

                                                echo
                                                    "Not provided";

                                            }

                                            ?>

                                        </span>

                                    </td>



                                    <!-- =========================
                                         SUBJECT
                                         ========================= -->

                                    <td>

                                        <div
                                            class="subject-text"
                                            title="<?php
                                                echo htmlspecialchars(
                                                    $subject
                                                );
                                            ?>"
                                        >

                                            <?php

                                            echo
                                                htmlspecialchars(
                                                    $subject
                                                );

                                            ?>

                                        </div>

                                    </td>



                                    <!-- =========================
                                         STATUS
                                         ========================= -->

                                    <td>

                                        <span
                                            class="status-badge
                                            <?php
                                            echo
                                                $statusClass;
                                            ?>"
                                        >

                                            <span
                                                class="status-dot"
                                            ></span>


                                            <?php

                                            echo
                                                $statusText;

                                            ?>

                                        </span>

                                    </td>



                                    <!-- =========================
                                         DATE
                                         ========================= -->

                                    <td>

                                        <span
                                            class="date-text"
                                        >

                                            <?php

                                            echo
                                                htmlspecialchars(
                                                    $formattedDate
                                                );

                                            ?>

                                        </span>

                                    </td>



                                    <!-- =========================
                                         ACTION
                                         ========================= -->

                                    <td>

                                        <div class="action-buttons">

                                            <a
                                                href="view-message.php?id=<?php echo $messageId; ?>"
                                                class="view-btn"
                                            >

                                                👁️

                                                <span>
                                                    View
                                                </span>

                                            </a>


                                            <form
                                                method="POST"
                                                action="message.php"
                                                onsubmit="return confirm('Are you sure you want to delete this message? This action cannot be undone.');"
                                                style="margin: 0;"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="message_id"
                                                    value="<?php echo $messageId; ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    name="delete_message"
                                                    class="delete-btn"
                                                    title="Delete Message"
                                                >

                                                    🗑️

                                                    <span>
                                                        Delete
                                                    </span>

                                                </button>

                                            </form>

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
       WINDOW RESIZE
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