<?php
session_start();

// Check customer login
if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "knot_house");

if ($conn->connect_error) {
    die("Database connection failed. Please check XAMPP.");
}

$conn->set_charset("utf8mb4");

// Get logged-in customer ID
$customerId = (int) $_SESSION["customer_id"];

// Fetch customer information
$stmt = $conn->prepare(
    "SELECT id, full_name, email, phone, created_at
     FROM customers
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to load your account. Please try again.");
}

$stmt->bind_param("i", $customerId);
$stmt->execute();

$result = $stmt->get_result();
$customer = $result->fetch_assoc();

$stmt->close();

// Check whether customer exists
if (!$customer) {
    $conn->close();

    $_SESSION = [];
    session_destroy();

    header("Location: login.php");
    exit();
}

// Update session information
$_SESSION["customer_id"] = (int) $customer["id"];
$_SESSION["customer_name"] = $customer["full_name"];
$_SESSION["customer_email"] = $customer["email"];

$conn->close();

// Customer information
$fullName = $customer["full_name"];
$email = $customer["email"];

$phone = !empty($customer["phone"])
    ? $customer["phone"]
    : "Not provided";

// Format registration date
$memberSince = "Not available";

if (!empty($customer["created_at"])) {
    $timestamp = strtotime($customer["created_at"]);

    if ($timestamp !== false) {
        $memberSince = date("d M Y", $timestamp);
    }
}

// Get customer initials
$initials = "";

$nameParts = preg_split('/\s+/', trim($fullName));

foreach ($nameParts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    if (strlen($initials) >= 2) {
        break;
    }
}

// Secure output function
function escapeOutput($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Account | The Knot House</title>

    <meta name="theme-color" content="#fffaf7">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        :root {
            --pink: #a96771;
            --pink-dark: #8d555e;
            --pink-light: #f8e8e7;
            --pink-soft: #f3d8d9;

            --cream: #fffaf7;
            --white: #ffffff;

            --brown: #513d3d;
            --brown-light: #806969;

            --border: #ead9d6;

            --shadow: 0 10px 35px rgba(141, 85, 94, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            font-family: "DM Sans", sans-serif;
            background: var(--cream);
            color: var(--brown);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button {
            font-family: inherit;
        }

        /* ==================================
           TOP ANNOUNCEMENT BAR
        ================================== */

        .top-bar {
            background: var(--brown);
            color: var(--white);
            text-align: center;
            padding: 9px 15px;
            font-size: 11px;
            letter-spacing: 1.2px;
            font-weight: 500;
        }

        .top-bar i {
            color: #e8b9bd;
            margin: 0 6px;
        }

        /* ==================================
           MAIN HEADER
        ================================== */

        .main-header {
            position: sticky;
            top: 0;
            z-index: 1000;

            background: rgba(255, 250, 247, 0.94);

            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);

            border-bottom: 1px solid rgba(169, 103, 113, 0.15);

            box-shadow: 0 4px 20px rgba(81, 61, 61, 0.035);

            transition: box-shadow 0.3s ease;
        }

        .main-header.scrolled {
            box-shadow: 0 6px 25px rgba(81, 61, 61, 0.09);
        }

        .header-container {
            max-width: 1400px;
            min-height: 88px;

            margin: auto;
            padding: 12px 45px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 25px;
        }

        /* ==================================
           LOGO
        ================================== */

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;

            flex-shrink: 0;
        }

        .brand-logo {
            width: 55px;
            height: 55px;

            object-fit: contain;
            border-radius: 50%;

            transition: transform 0.4s ease;
        }

        .brand:hover .brand-logo {
            transform: rotate(-8deg) scale(1.05);
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .brand-name {
            font-family: "Playfair Display", serif;
            font-size: 25px;
            font-weight: 700;

            color: var(--brown);
            letter-spacing: -0.5px;
        }

        .brand-tagline {
            font-size: 9px;
            letter-spacing: 2.2px;
            text-transform: uppercase;

            color: var(--pink-dark);
            font-weight: 700;
        }

        /* ==================================
           DESKTOP NAVIGATION
        ================================== */

        .navigation {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 29px;
        }

        .navigation a {
            position: relative;

            font-size: 13px;
            font-weight: 700;

            color: var(--brown);

            padding: 12px 0;

            transition: color 0.3s ease;
        }

        .navigation a::after {
            content: "";

            position: absolute;
            bottom: 4px;
            left: 0;

            width: 0;
            height: 2px;

            background: var(--pink);

            border-radius: 5px;

            transition: width 0.3s ease;
        }

        .navigation a:hover,
        .navigation a.active {
            color: var(--pink-dark);
        }

        .navigation a:hover::after,
        .navigation a.active::after {
            width: 100%;
        }

        /* ==================================
           HEADER ACTIONS
        ================================== */

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .account-button {
            display: flex;
            align-items: center;
            gap: 9px;

            padding: 11px 17px;

            border: 1px solid var(--border);
            border-radius: 30px;

            background: var(--white);

            color: var(--pink-dark);

            font-size: 12px;
            font-weight: 700;

            transition: all 0.3s ease;
        }

        .account-button:hover {
            background: var(--pink-light);
            border-color: var(--pink);
            transform: translateY(-2px);
        }

        .account-button i {
            font-size: 14px;
        }

        .logout-button {
            display: flex;
            align-items: center;
            gap: 8px;

            padding: 12px 19px;

            background: var(--pink);
            color: var(--white);

            border-radius: 30px;

            font-size: 12px;
            font-weight: 700;

            transition: all 0.3s ease;
        }

        .logout-button:hover {
            background: var(--pink-dark);
            transform: translateY(-2px);

            box-shadow: 0 5px 15px rgba(141, 85, 94, 0.2);
        }

        /* ==================================
           MOBILE MENU BUTTON
        ================================== */

        .menu-toggle {
            display: none;

            width: 43px;
            height: 43px;

            border: 1px solid var(--border);
            border-radius: 50%;

            background: var(--white);
            color: var(--pink-dark);

            font-size: 18px;

            cursor: pointer;
        }

        /* ==================================
           PAGE
        ================================== */

        .page {
            max-width: 1150px;

            margin: 55px auto;
            padding: 0 25px;
        }

        .welcome {
            margin-bottom: 35px;
        }

        .eyebrow {
            color: var(--pink-dark);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: 2.5px;
            text-transform: uppercase;

            margin-bottom: 12px;
        }

        h1, h2 {
            font-family: "Playfair Display", serif;
        }

        h1 {
            font-size: clamp(30px, 5vw, 43px);
            font-weight: 600;

            margin-bottom: 12px;
        }

        .welcome p {
            color: var(--brown-light);
            font-size: 14px;
            line-height: 1.8;
        }

        /* ==================================
           ACCOUNT LAYOUT
        ================================== */

        .account-layout {
            display: grid;
            grid-template-columns: 260px 1fr;

            gap: 28px;
            align-items: start;
        }

        .sidebar,
        .profile-card {
            background: var(--white);

            border: 1px solid var(--border);
            border-radius: 17px;

            box-shadow: var(--shadow);
        }

        /* ==================================
           SIDEBAR
        ================================== */

        .sidebar {
            padding: 24px 19px;
        }

        .sidebar-heading {
            font-size: 21px;
            margin-bottom: 7px;
        }

        .sidebar-subtitle {
            font-size: 11px;
            color: var(--brown-light);

            margin-bottom: 22px;
        }

        .sidebar-divider {
            border: none;
            border-top: 1px solid var(--border);

            margin-bottom: 13px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 13px 12px;
            margin: 5px 0;

            border-radius: 9px;

            color: var(--brown-light);

            font-size: 13px;
            font-weight: 600;

            transition: all 0.25s ease;
        }

        .sidebar a i {
            width: 18px;
            text-align: center;
            font-size: 14px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: var(--pink-light);
            color: var(--pink-dark);

            transform: translateX(3px);
        }

        .sidebar a.logout-side {
            color: #b34c4c;
            margin-top: 15px;
        }

        .sidebar a.logout-side:hover {
            background: #fff0f0;
        }

        /* ==================================
           PROFILE CARD
        ================================== */

        .profile-card {
            padding: 32px;
        }

        .profile-heading {
            display: flex;
            align-items: center;
            gap: 20px;

            padding-bottom: 27px;

            border-bottom: 1px solid var(--border);
        }

        .avatar {
            width: 76px;
            height: 76px;

            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: var(--pink-light);
            color: var(--pink-dark);

            border: 3px solid #f3d8d9;

            font-family: "Playfair Display", serif;
            font-size: 27px;
            font-weight: 700;
        }

        .profile-heading h2 {
            font-size: 25px;
            margin-bottom: 7px;

            overflow-wrap: anywhere;
        }

        .profile-heading p {
            font-size: 13px;
            color: var(--brown-light);

            overflow-wrap: anywhere;
        }

        .member-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            margin-top: 10px;
            padding: 6px 11px;

            background: var(--pink-light);
            color: var(--pink-dark);

            border-radius: 20px;

            font-size: 10px;
            font-weight: 700;
        }

        /* ==================================
           PERSONAL INFORMATION
        ================================== */

        .details-title {
            margin: 27px 0 19px;
            font-size: 22px;
        }

        .details {
            display: grid;

            grid-template-columns: repeat(2, minmax(0, 1fr));

            gap: 18px;
        }

        .detail {
            min-width: 0;

            padding: 19px;

            background: var(--cream);

            border: 1px solid var(--border);
            border-radius: 12px;

            transition: all 0.3s ease;
        }

        .detail:hover {
            border-color: var(--pink);
            transform: translateY(-3px);

            box-shadow: 0 5px 15px rgba(141, 85, 94, 0.06);
        }

        .detail-label {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 10px;

            color: var(--brown-light);

            font-size: 10px;
            font-weight: 800;

            letter-spacing: 1.2px;
        }

        .detail-label i {
            color: var(--pink);
            font-size: 13px;
        }

        .detail-value {
            font-size: 14px;
            font-weight: 700;

            overflow-wrap: anywhere;
        }

        /* ==================================
           EDIT BUTTON
        ================================== */

        .edit-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;

            margin-top: 27px;
            padding: 13px 23px;

            background: var(--pink);
            color: var(--white);

            border-radius: 8px;

            font-size: 13px;
            font-weight: 700;

            transition: all 0.3s ease;
        }

        .edit-link:hover {
            background: var(--pink-dark);
            transform: translateY(-2px);

            box-shadow: 0 6px 15px rgba(141, 85, 94, 0.2);
        }

        /* ==================================
           FOOTER
        ================================== */

        footer {
            margin-top: 65px;
            padding: 25px 15px;

            border-top: 1px solid var(--border);

            text-align: center;

            color: var(--brown-light);
            font-size: 12px;
        }

        footer i {
            color: var(--pink);
            margin: 0 4px;
        }

        /* ==================================
           RESPONSIVE DESIGN
        ================================== */

        @media (max-width: 1100px) {

            .header-container {
                padding: 12px 25px;
            }

            .navigation {
                gap: 18px;
            }

            .navigation a {
                font-size: 12px;
            }

            .brand-name {
                font-size: 22px;
            }
        }

        @media (max-width: 850px) {

            .header-container {
                min-height: 76px;
            }

            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .header-container {
                flex-wrap: wrap;
            }

            .navigation {
                display: none;

                width: 100%;

                flex-direction: column;
                align-items: stretch;

                gap: 0;

                padding: 10px 0;

                border-top: 1px solid var(--border);
            }

            .navigation.show {
                display: flex;
            }

            .navigation a {
                padding: 13px 10px;
            }

            .navigation a::after {
                display: none;
            }

            .navigation a:hover,
            .navigation a.active {
                background: var(--pink-light);
                border-radius: 7px;
            }

            .header-actions {
                margin-left: auto;
            }

            .account-button {
                display: none;
            }

            .account-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 5px;
            }

            .sidebar h2,
            .sidebar-subtitle,
            .sidebar-divider {
                grid-column: 1 / -1;
            }

            .sidebar a {
                margin: 0;
            }

            .sidebar a:hover,
            .sidebar a.active {
                transform: none;
            }
        }

        @media (max-width: 550px) {

            .top-bar {
                font-size: 9px;
                letter-spacing: 0.7px;
            }

            .header-container {
                padding: 10px 16px;
                gap: 10px;
            }

            .brand-logo {
                width: 43px;
                height: 43px;
            }

            .brand-name {
                font-size: 19px;
            }

            .brand-tagline {
                font-size: 7px;
                letter-spacing: 1.5px;
            }

            .logout-button {
                padding: 10px 13px;
                font-size: 11px;
            }

            .logout-button span {
                display: none;
            }

            .page {
                margin: 35px auto;
                padding: 0 15px;
            }

            .welcome {
                margin-bottom: 25px;
            }

            .sidebar {
                padding: 17px;
            }

            .sidebar a {
                padding: 12px 8px;
                font-size: 11px;
                gap: 7px;
            }

            .profile-card {
                padding: 20px;
            }

            .profile-heading {
                gap: 13px;
            }

            .avatar {
                width: 61px;
                height: 61px;
                font-size: 23px;
            }

            .profile-heading h2 {
                font-size: 20px;
            }

            .details {
                grid-template-columns: 1fr;
                gap: 13px;
            }

            .detail {
                padding: 16px;
            }

            footer {
                margin-top: 40px;
            }
        }

    </style>

</head>

<body>

<!-- TOP ANNOUNCEMENT -->
<div class="top-bar">
    <i class="fa-solid fa-heart"></i>
    HANDMADE WITH LOVE, ONE STITCH AT A TIME
    <i class="fa-solid fa-heart"></i>
</div>

<!-- ==================================
     HEADER
================================== -->

<header class="main-header" id="mainHeader">

    <div class="header-container">

        <!-- BRAND LOGO -->
        <a href="index.html" class="brand">

            <img src="logo.png"
                 alt="The Knot House Logo"
                 class="brand-logo">

            <div class="brand-text">

                <span class="brand-name">
                    The Knot House
                </span>

                <span class="brand-tagline">
                    Handmade Happiness
                </span>

            </div>

        </a>

        <!-- DESKTOP / MOBILE NAVIGATION -->
        <nav class="navigation" id="navigation">

            <a href="index.html">
                Home
            </a>

            <a href="about.html">
                About Us
            </a>

            <a href="product.html">
                Products
            </a>

            <a href="gallery.html">
                Gallery
            </a>

            <a href="contact.html">
                Contact Us
            </a>

            <a href="my-account.php" class="active">
                My Account
            </a>

        </nav>

        <!-- HEADER ACTIONS -->
        <div class="header-actions">

            <a href="my-account.php" class="account-button">

                <i class="fa-regular fa-user"></i>

                My Account

            </a>

            <a href="logout.php" class="logout-button">

                <i class="fa-solid fa-arrow-right-from-bracket"></i>

                <span>Logout</span>

            </a>

            <!-- MOBILE MENU -->
            <button class="menu-toggle"
                    id="menuToggle"
                    type="button"
                    aria-label="Open navigation menu"
                    aria-expanded="false"
                    aria-controls="navigation">

                <i class="fa-solid fa-bars"></i>

            </button>

        </div>

    </div>

</header>

<!-- ==================================
     MAIN CONTENT
================================== -->

<main class="page">

    <!-- WELCOME SECTION -->

    <section class="welcome">

        <div class="eyebrow">
            YOUR PERSONAL SPACE
        </div>

        <h1>
            Welcome, <?= escapeOutput($fullName) ?>! 🩷
        </h1>

        <p>
            Manage your profile, keep track of your orders and
            explore your handmade favourites at The Knot House.
        </p>

    </section>

    <!-- ACCOUNT LAYOUT -->

    <div class="account-layout">

        <!-- SIDEBAR -->

        <aside class="sidebar">

            <h2 class="sidebar-heading">
                My Account
            </h2>

            <p class="sidebar-subtitle">
                Manage your personal account
            </p>

            <hr class="sidebar-divider">

            <a href="my-account.php" class="active">

                <i class="fa-regular fa-user"></i>

                My Profile

            </a>

            <a href="my-orders.php">

                <i class="fa-solid fa-box"></i>

                My Orders

            </a>

            <a href="my-favourites.php">

                <i class="fa-regular fa-heart"></i>

                My Favourites

            </a>

            <a href="my-addresses.php">

                <i class="fa-solid fa-location-dot"></i>

                My Addresses

            </a>

            <a href="my-history.php">

                <i class="fa-solid fa-clock-rotate-left"></i>

                My History

            </a>

            <a href="profile.php">

                <i class="fa-solid fa-pen-to-square"></i>

                Edit Profile

            </a>

            <a href="logout.php" class="logout-side">

                <i class="fa-solid fa-right-from-bracket"></i>

                Logout

            </a>

        </aside>

        <!-- PROFILE CARD -->

        <section class="profile-card">

            <!-- PROFILE HEADING -->

            <div class="profile-heading">

                <div class="avatar">

                    <?= escapeOutput($initials) ?>

                </div>

                <div>

                    <h2>
                        <?= escapeOutput($fullName) ?>
                    </h2>

                    <p>
                        <?= escapeOutput($email) ?>
                    </p>

                    <div class="member-badge">

                        <i class="fa-solid fa-heart"></i>

                        The Knot House Member

                    </div>

                </div>

            </div>

            <!-- PERSONAL INFORMATION -->

            <h2 class="details-title">
                Personal Information
            </h2>

            <div class="details">

                <div class="detail">

                    <span class="detail-label">

                        <i class="fa-regular fa-user"></i>

                        FULL NAME

                    </span>

                    <span class="detail-value">

                        <?= escapeOutput($fullName) ?>

                    </span>

                </div>

                <div class="detail">

                    <span class="detail-label">

                        <i class="fa-regular fa-envelope"></i>

                        EMAIL ADDRESS

                    </span>

                    <span class="detail-value">

                        <?= escapeOutput($email) ?>

                    </span>

                </div>

                <div class="detail">

                    <span class="detail-label">

                        <i class="fa-solid fa-phone"></i>

                        PHONE NUMBER

                    </span>

                    <span class="detail-value">

                        <?= escapeOutput($phone) ?>

                    </span>

                </div>

                <div class="detail">

                    <span class="detail-label">

                        <i class="fa-regular fa-calendar"></i>

                        MEMBER SINCE

                    </span>

                    <span class="detail-value">

                        <?= escapeOutput($memberSince) ?>

                    </span>

                </div>

            </div>

            <!-- EDIT PROFILE BUTTON -->

            <a href="profile.php" class="edit-link">

                <i class="fa-solid fa-pen-to-square"></i>

                Edit My Profile

            </a>

        </section>

    </div>

</main>

<!-- ==================================
     FOOTER
================================== -->

<footer>

    © 2026 The Knot House.

    Handmade with

    <i class="fa-solid fa-heart"></i>

    and lots of love.

</footer>

<!-- ==================================
     JAVASCRIPT
================================== -->

<script>

    // Mobile navigation toggle

    const menuToggle = document.getElementById("menuToggle");

    const navigation = document.getElementById("navigation");

    menuToggle.addEventListener("click", function () {

        navigation.classList.toggle("show");

        const isOpen = navigation.classList.contains("show");

        menuToggle.setAttribute("aria-expanded", isOpen);

        menuToggle.innerHTML = isOpen
            ? '<i class="fa-solid fa-xmark"></i>'
            : '<i class="fa-solid fa-bars"></i>';

    });


    // Close mobile menu after clicking a navigation link

    document.querySelectorAll(".navigation a").forEach(function (link) {

        link.addEventListener("click", function () {

            navigation.classList.remove("show");

            menuToggle.setAttribute("aria-expanded", "false");

            menuToggle.innerHTML =
                '<i class="fa-solid fa-bars"></i>';

        });

    });


    // Header shadow effect while scrolling

    const header = document.getElementById("mainHeader");

    window.addEventListener("scroll", function () {

        if (window.scrollY > 30) {

            header.classList.add("scrolled");

        } else {

            header.classList.remove("scrolled");

        }

    });

</script>

</body>
</html>
