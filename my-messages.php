
<?php
/* =========================================================
   THE KNOT HOUSE - MY MESSAGES
   ========================================================= */

$messageResults = [];
$errorMessage = "";
$searchedEmail = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $searchedEmail = trim($_POST["email"] ?? "");

    if ($searchedEmail === "") {
        $errorMessage = "Please enter your email address.";
    } elseif (!filter_var($searchedEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } else {
        $conn = new mysqli("localhost", "root", "", "knot_house");

        if ($conn->connect_error) {
            $errorMessage = "Database connection failed. Please try again later.";
        } else {
            $conn->set_charset("utf8mb4");

            $sql = "SELECT id, name, email, phone, subject, message,
                           admin_reply, status, replied_at, created_at
                    FROM contact_messages
                    WHERE email = ?
                    ORDER BY created_at DESC";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("s", $searchedEmail);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $messageResults[] = $row;
                }

                $stmt->close();
            } else {
                $errorMessage = "Unable to load your messages.";
            }

            $conn->close();
        }
    }
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function formatDate($value) {
    if (empty($value) || strtotime($value) === false) {
        return "Not available";
    }

    return date("d M Y, h:i A", strtotime($value));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Messages | The Knot House</title>
    <meta name="description" content="View your messages and replies from The Knot House.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --pink: #a96771;
            --dark-pink: #8d555e;
            --light-pink: #f8e8e7;
            --soft-pink: #f3d8d9;
            --cream: #fffaf7;
            --brown: #513d3d;
            --text: #6d5d5d;
            --border: #ead9d6;
            --white: #fff;
            --success: #4f8767;
            --success-bg: #edf7f0;
            --warning: #a47743;
            --warning-bg: #fff6e9;
            --shadow: 0 15px 45px rgba(81,61,61,.09);
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
            font-family: "DM Sans", sans-serif;
            background: var(--cream);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input {
            font: inherit;
        }

        /* HEADER */

        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(255,250,247,.96);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 4px 22px rgba(81,61,61,.07);
        }

        .header-container {
            width: 92%;
            max-width: 1400px;
            min-height: 78px;
            margin: auto;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 11px;
            flex-shrink: 0;
        }

        .logo img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 50%;
        }

        .logo-text {
            font-family: "Playfair Display", serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--brown);
            white-space: nowrap;
        }

        .main-nav {
            margin-left: auto;
        }

        .main-nav ul {
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
        }

        .main-nav a {
            position: relative;
            display: inline-block;
            padding: 8px 2px;
            color: var(--brown);
            font-size: 13.5px;
            font-weight: 600;
            white-space: nowrap;
            transition: color .3s ease;
        }

        .main-nav a::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 1px;
            width: 0;
            height: 2px;
            border-radius: 10px;
            background: var(--pink);
            transition: width .3s ease;
        }

        .main-nav a:hover,
        .main-nav a.active {
            color: var(--pink);
        }

        .main-nav a:hover::after,
        .main-nav a.active::after {
            width: 100%;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .profile-btn,
        .cart-btn {
            position: relative;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--light-pink);
            color: var(--brown);
            font-size: 19px;
            transition: .3s ease;
        }

        .profile-btn:hover,
        .cart-btn:hover {
            background: var(--pink);
            color: var(--white);
            transform: translateY(-2px);
        }

        .cart-count {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 19px;
            height: 19px;
            padding: 0 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--dark-pink);
            color: var(--white);
            border-radius: 50%;
            font-size: 10px;
            font-weight: 700;
        }

        .menu-toggle {
            display: none;
            width: 42px;
            height: 42px;
            border: none;
            background: transparent;
            color: var(--brown);
            font-size: 27px;
            cursor: pointer;
        }

        /* PAGE HERO */

        .page-hero {
            padding: 145px 20px 75px;
            text-align: center;
            background:
                radial-gradient(circle at top right, #f5dddd 0, transparent 35%),
                linear-gradient(135deg, #fffaf7, #f8e8e7);
            border-bottom: 1px solid var(--border);
        }

        .hero-icon {
            width: 74px;
            height: 74px;
            margin: 0 auto 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--white);
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(81,61,61,.09);
            font-size: 34px;
        }

        .page-hero small {
            display: block;
            color: var(--pink);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2.5px;
            margin-bottom: 8px;
        }

        .page-hero h1 {
            font-family: "Playfair Display", serif;
            color: var(--brown);
            font-size: clamp(36px, 5vw, 55px);
            line-height: 1.15;
            margin-bottom: 13px;
        }

        .page-hero h1 span {
            color: var(--pink);
        }

        .page-hero p {
            max-width: 650px;
            margin: auto;
            font-size: 15px;
        }

        /* MAIN CONTENT */

        .messages-section {
            width: 92%;
            max-width: 950px;
            margin: 0 auto;
            padding: 70px 0 100px;
        }

        .search-card,
        .empty-message,
        .message-card {
            background: var(--white);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .search-card {
            border-radius: 25px;
            padding: 35px;
            margin-bottom: 40px;
        }

        .search-card-header {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 25px;
        }

        .search-card-icon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 15px;
            background: var(--light-pink);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .search-card h2,
        .result-title h2,
        .empty-message h3,
        .contact-cta h2 {
            font-family: "Playfair Display", serif;
            color: var(--brown);
        }

        .search-card h2 {
            font-size: 27px;
            margin-bottom: 4px;
        }

        .search-card p {
            font-size: 14px;
        }

        .email-form {
            display: flex;
            gap: 12px;
        }

        .email-input-wrapper {
            position: relative;
            flex: 1;
        }

        .email-input-wrapper span {
            position: absolute;
            left: 17px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 17px;
        }

        .email-input {
            width: 100%;
            height: 52px;
            padding: 0 16px 0 48px;
            border: 1px solid var(--border);
            border-radius: 14px;
            outline: none;
            background: #fffdfc;
            color: var(--brown);
            font-size: 14px;
        }

        .email-input:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 4px rgba(169,103,113,.10);
        }

        .email-input::placeholder {
            color: #ad9b9b;
        }

        .search-button {
            min-height: 52px;
            padding: 0 25px;
            border: none;
            border-radius: 14px;
            background: var(--pink);
            color: var(--white);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .3s ease;
            white-space: nowrap;
        }

        .search-button:hover {
            background: var(--dark-pink);
            transform: translateY(-2px);
        }

        .error-box {
            margin-top: 18px;
            padding: 15px 18px;
            border-radius: 13px;
            background: #fff0f0;
            border: 1px solid #f1cccc;
            color: #9a5555;
            font-size: 14px;
        }

        .result-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
        }

        .result-title h2 {
            font-size: 30px;
            margin-bottom: 4px;
        }

        .result-title p {
            font-size: 13px;
        }

        .result-email {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 13px;
            background: var(--light-pink);
            color: var(--dark-pink);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .empty-message {
            border-radius: 22px;
            padding: 55px 25px;
            text-align: center;
        }

        .empty-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: var(--light-pink);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .empty-message h3 {
            font-size: 25px;
            margin-bottom: 8px;
        }

        .empty-message p {
            max-width: 520px;
            margin: auto;
            font-size: 14px;
        }

        /* MESSAGE CARDS */

        .message-card {
            border-radius: 23px;
            margin-bottom: 25px;
            overflow: hidden;
            transition: .3s ease;
        }

        .message-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 50px rgba(81,61,61,.12);
        }

        .message-top {
            padding: 23px 27px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            background: linear-gradient(135deg, #fffaf7, #fff5f4);
            border-bottom: 1px solid var(--border);
        }

        .message-number {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .message-number-icon {
            width: 43px;
            height: 43px;
            border-radius: 13px;
            background: var(--light-pink);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .message-number strong {
            display: block;
            color: var(--brown);
            font-size: 14px;
        }

        .message-number span {
            display: block;
            color: #a18e8e;
            font-size: 11px;
            margin-top: 2px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .status-unread {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .status-read {
            background: #f1f1f1;
            color: #777;
        }

        .status-replied {
            background: var(--success-bg);
            color: var(--success);
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .message-body {
            padding: 28px;
        }

        .subject {
            font-family: "Playfair Display", serif;
            color: var(--brown);
            font-size: 23px;
            margin-bottom: 15px;
            overflow-wrap: anywhere;
        }

        .message-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 22px;
        }

        .meta-item {
            padding: 7px 11px;
            background: #faf5f3;
            border-radius: 9px;
            font-size: 11px;
            overflow-wrap: anywhere;
        }

        .meta-item strong {
            color: var(--brown);
        }

        .message-label {
            color: var(--pink);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 7px;
        }

        .original-message {
            padding: 18px;
            background: #fffdfc;
            border: 1px solid var(--border);
            border-radius: 14px;
            font-size: 14px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .reply-box {
            margin-top: 25px;
            padding: 23px;
            background: linear-gradient(135deg, #fff8f8, #fdf0ef);
            border: 1px solid #efd8d5;
            border-radius: 17px;
            position: relative;
        }

        .reply-box::before {
            content: "";
            position: absolute;
            left: 0;
            top: 17px;
            bottom: 17px;
            width: 4px;
            background: var(--pink);
            border-radius: 0 5px 5px 0;
        }

        .reply-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .reply-icon {
            width: 39px;
            height: 39px;
            border-radius: 12px;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .reply-header strong {
            color: var(--brown);
            font-size: 14px;
        }

        .reply-date {
            display: block;
            color: #a18e8e;
            font-size: 10px;
            margin-top: 1px;
        }

        .reply-text {
            padding-left: 49px;
            font-size: 14px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .waiting-box {
            margin-top: 25px;
            padding: 18px;
            background: var(--warning-bg);
            border: 1px solid #f0dfc2;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 13px;
            color: #80633e;
            font-size: 13px;
        }

        .waiting-icon {
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            border-radius: 11px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .message-footer {
            padding: 14px 27px;
            border-top: 1px solid var(--border);
            background: #fffdfc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .message-footer span {
            font-size: 11px;
            color: #a18e8e;
        }

        .message-id {
            font-weight: 700;
            color: var(--pink) !important;
        }

        /* CONTACT CTA */

        .contact-cta {
            margin-top: 55px;
            padding: 45px 30px;
            background: var(--brown);
            border-radius: 27px;
            text-align: center;
            color: var(--white);
            box-shadow: 0 20px 50px rgba(81,61,61,.14);
        }

        .contact-cta .icon {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .contact-cta h2 {
            color: var(--white);
            font-size: 31px;
            margin-bottom: 9px;
        }

        .contact-cta p {
            max-width: 570px;
            margin: 0 auto 23px;
            color: #eadede;
            font-size: 14px;
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 23px;
            border-radius: 24px;
            background: var(--white);
            color: var(--brown);
            font-size: 13px;
            font-weight: 700;
            transition: .3s ease;
        }

        .cta-button:hover {
            background: var(--light-pink);
            transform: translateY(-2px);
        }

        /* FOOTER - MATCHES THE FOOTER HTML */

        .site-footer {
            background: #3f3030;
            color: #f7eeee;
            padding: 65px 6% 25px;
        }

        .footer-container {
            width: 100%;
            max-width: 1200px;
            margin: auto;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 45px;
        }

        .footer-logo {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 17px;
        }

        .footer-logo img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 50%;
            background: var(--white);
        }

        .footer-logo h3 {
            font-family: "Playfair Display", serif;
            color: var(--white);
            font-size: 24px;
        }

        .footer-about {
            max-width: 330px;
            color: #d9caca;
            font-size: 13px;
            line-height: 1.8;
        }

        .footer-social {
            display: flex;
            gap: 9px;
            margin-top: 20px;
        }

        .footer-social a {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.10);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            transition: .3s ease;
        }

        .footer-social a:hover {
            background: var(--pink);
            transform: translateY(-3px);
        }

        .footer-column h4 {
            color: var(--white);
            font-size: 15px;
            margin-bottom: 18px;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column li {
            margin-bottom: 9px;
        }

        .footer-column a {
            color: #d8c8c8;
            font-size: 13px;
            transition: .3s ease;
            overflow-wrap: anywhere;
        }

        .footer-column a:hover {
            color: var(--white);
            padding-left: 4px;
        }

        .footer-contact-item {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            color: #d8c8c8;
            font-size: 13px;
            margin-bottom: 12px;
            overflow-wrap: anywhere;
        }

        .footer-bottom {
            width: 100%;
            max-width: 1200px;
            margin: 45px auto 0;
            padding-top: 22px;
            border-top: 1px solid rgba(255,255,255,.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .footer-bottom p {
            color: #cdbebe;
            font-size: 11px;
        }

        .footer-bottom .made-with {
            color: #d8c8c8;
        }

        /* RESPONSIVE */

        @media (max-width: 1200px) {
            .header-container {
                gap: 12px;
            }

            .main-nav ul {
                gap: 11px;
            }

            .main-nav a {
                font-size: 12px;
            }

            .logo-text {
                font-size: 22px;
            }

            .footer-container {
                grid-template-columns: 1.5fr 1fr 1fr;
            }
        }

        @media (max-width: 1050px) {
            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .main-nav {
                position: fixed;
                top: 78px;
                right: -100%;
                width: 310px;
                max-width: 100%;
                height: calc(100vh - 78px);
                overflow-y: auto;
                padding: 25px;
                margin: 0;
                background: var(--cream);
                border-left: 1px solid var(--border);
                box-shadow: -12px 15px 35px rgba(81,61,61,.10);
                transition: right .3s ease;
            }

            .main-nav.active {
                right: 0;
            }

            .main-nav ul {
                flex-direction: column;
                align-items: stretch;
                gap: 4px;
            }

            .main-nav a {
                display: block;
                padding: 13px 10px;
                border-bottom: 1px solid var(--border);
                font-size: 14px;
            }

            .main-nav a::after {
                display: none;
            }
        }

        @media (max-width: 800px) {
            .footer-container {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 35px;
            }

            .result-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 600px) {
            .header-container {
                width: 94%;
                min-height: 70px;
                gap: 8px;
            }

            .logo {
                gap: 8px;
            }

            .logo img {
                width: 40px;
                height: 40px;
            }

            .logo-text {
                font-size: 18px;
            }

            .header-right {
                margin-left: auto;
                gap: 6px;
            }

            .profile-btn,
            .cart-btn {
                width: 38px;
                height: 38px;
                font-size: 17px;
            }

            .menu-toggle {
                width: 36px;
                height: 40px;
            }

            .main-nav {
                top: 70px;
                width: 100%;
                height: calc(100vh - 70px);
            }

            .page-hero {
                padding: 120px 18px 55px;
            }

            .hero-icon {
                width: 65px;
                height: 65px;
                font-size: 29px;
            }

            .page-hero h1 {
                font-size: 38px;
            }

            .page-hero p {
                font-size: 14px;
            }

            .messages-section {
                padding: 45px 0 70px;
            }

            .search-card {
                padding: 23px 18px;
                border-radius: 20px;
            }

            .search-card h2 {
                font-size: 23px;
            }

            .email-form {
                flex-direction: column;
            }

            .search-button {
                width: 100%;
            }

            .message-top {
                padding: 18px;
                align-items: flex-start;
                flex-direction: column;
            }

            .message-body {
                padding: 20px 18px;
            }

            .subject {
                font-size: 21px;
            }

            .message-meta {
                flex-direction: column;
                align-items: stretch;
            }

            .meta-item {
                width: 100%;
            }

            .reply-box {
                padding: 20px 17px;
            }

            .reply-text {
                padding-left: 0;
                margin-top: 10px;
            }

            .message-footer {
                padding: 13px 18px;
                align-items: flex-start;
                flex-direction: column;
            }

            .contact-cta {
                padding: 38px 20px;
                border-radius: 22px;
            }

            .contact-cta h2 {
                font-size: 27px;
            }

            /* FOOTER MOBILE LAYOUT */

            .site-footer {
                padding: 48px 6% 22px;
            }

            .footer-container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 35px;
            }

            .footer-logo {
                justify-content: center;
            }

            .footer-about {
                margin: auto;
            }

            .footer-social {
                justify-content: center;
            }

            .footer-contact-item {
                justify-content: center;
                overflow-wrap: anywhere;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
                margin-top: 35px;
            }
        }
    </style>
</head>

<body>

<!-- HEADER -->

<header class="site-header" id="siteHeader">
    <div class="header-container">

        <a href="index.html" class="logo" aria-label="The Knot House Home">
            <img src="./logo.png" alt="The Knot House Logo">
            <span class="logo-text">The Knot House</span>
        </a>

        <nav class="main-nav" id="mainNav" aria-label="Main navigation">
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="about.html">About Us</a></li>
                <li><a href="product.html">Products</a></li>
                <li><a href="gallery.html">Gallery</a></li>
                <li><a href="contact.html">Contact Us</a></li>
                <li><a href="feedback.html">Feedback</a></li>
                <li><a href="faq.html">FAQ</a></li>
                <li><a href="track-order.html">Track Order</a></li>
                <li>
                    <a href="my-messages.php" class="active" aria-current="page">
                        My Messages
                    </a>
                </li>
            </ul>
        </nav>

        <div class="header-right">
            <a href="login.html"
               class="profile-btn"
               aria-label="Customer Profile"
               title="Customer Profile">👤</a>

            <a href="cart.html"
               class="cart-btn"
               aria-label="Shopping Cart"
               title="Shopping Cart">
                🛒
                <span class="cart-count" id="cartCount">0</span>
            </a>

            <button class="menu-toggle"
                    id="menuToggle"
                    type="button"
                    aria-label="Open navigation menu"
                    aria-controls="mainNav"
                    aria-expanded="false">☰</button>
        </div>

    </div>
</header>

<!-- PAGE HERO -->

<section class="page-hero">
    <div class="hero-icon">💌</div>
    <small>CUSTOMER SUPPORT</small>
    <h1>My <span>Messages</span></h1>
    <p>Check your messages, enquiries and replies from The Knot House in one beautiful place.</p>
</section>

<!-- MAIN CONTENT -->

<main id="main-content">
    <section class="messages-section">

        <!-- EMAIL SEARCH -->

        <div class="search-card">
            <div class="search-card-header">
                <div class="search-card-icon">✉️</div>
                <div>
                    <h2>Find Your Messages</h2>
                    <p>Enter the same email address you used when contacting The Knot House.</p>
                </div>
            </div>

            <form method="POST" class="email-form" autocomplete="on">
                <div class="email-input-wrapper">
                    <span>✉️</span>
                    <input
                        type="email"
                        name="email"
                        class="email-input"
                        placeholder="Enter your email address"
                        value="<?php echo e($searchedEmail); ?>"
                        required
                        autocomplete="email">
                </div>

                <button type="submit" class="search-button">
                    🔍 View Messages
                </button>
            </form>

            <?php if ($errorMessage !== ""): ?>
                <div class="error-box" role="alert">
                    ⚠️ <?php echo e($errorMessage); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($_SERVER["REQUEST_METHOD"] === "POST" && $errorMessage === ""): ?>

            <div class="result-header">
                <div class="result-title">
                    <h2>Your Messages</h2>
                    <p><?php echo count($messageResults); ?> message(s) found for your email.</p>
                </div>

                <div class="result-email">
                    ✉️ <?php echo e($searchedEmail); ?>
                </div>
            </div>

            <?php if (count($messageResults) === 0): ?>

                <div class="empty-message">
                    <div class="empty-icon">💌</div>
                    <h3>No Messages Found</h3>
                    <p>
                        We couldn't find any messages connected to this email address.
                        Please make sure you entered the same email address used on the Contact Us page.
                    </p>
                </div>

            <?php else: ?>

                <?php foreach ($messageResults as $message): ?>

                    <?php
                    $status = strtolower(trim($message["status"] ?? "unread"));

                    if (!empty($message["admin_reply"]) || $status === "replied") {
                        $statusClass = "status-replied";
                        $statusText = "Replied";
                    } elseif ($status === "read") {
                        $statusClass = "status-read";
                        $statusText = "Read";
                    } else {
                        $statusClass = "status-unread";
                        $statusText = "Unread";
                    }
                    ?>

                    <article class="message-card">

                        <div class="message-top">
                            <div class="message-number">
                                <div class="message-number-icon">💬</div>
                                <div>
                                    <strong>Message #<?php echo e($message["id"]); ?></strong>
                                    <span>Sent on <?php echo e(formatDate($message["created_at"])); ?></span>
                                </div>
                            </div>

                            <div class="status <?php echo e($statusClass); ?>">
                                <span class="status-dot"></span>
                                <?php echo e($statusText); ?>
                            </div>
                        </div>

                        <div class="message-body">

                            <h2 class="subject">
                                <?php echo e($message["subject"] ?: "General Enquiry"); ?>
                            </h2>

                            <div class="message-meta">
                                <div class="meta-item">
                                    <strong>Name:</strong> <?php echo e($message["name"]); ?>
                                </div>

                                <?php if (!empty($message["phone"])): ?>
                                    <div class="meta-item">
                                        <strong>Phone:</strong> <?php echo e($message["phone"]); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="meta-item">
                                    <strong>Email:</strong> <?php echo e($message["email"]); ?>
                                </div>
                            </div>

                            <div class="message-label">Your Message</div>
                            <div class="original-message"><?php echo e($message["message"]); ?></div>

                            <?php if (!empty($message["admin_reply"])): ?>

                                <div class="reply-box">
                                    <div class="reply-header">
                                        <div class="reply-icon">💗</div>
                                        <div>
                                            <strong>Reply from The Knot House</strong>

                                            <?php if (!empty($message["replied_at"])): ?>
                                                <span class="reply-date">
                                                    Replied on <?php echo e(formatDate($message["replied_at"])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="reply-text"><?php echo e($message["admin_reply"]); ?></div>
                                </div>

                            <?php else: ?>

                                <div class="waiting-box">
                                    <div class="waiting-icon">⏳</div>
                                    <div>
                                        <strong>Your message has been received.</strong>
                                        <br>
                                        Our team has not replied yet. Please check again later.
                                    </div>
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="message-footer">
                            <span class="message-id">
                                💌 Message ID: #<?php echo e($message["id"]); ?>
                            </span>
                            <span>The Knot House • Handmade with love 🧶</span>
                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        <?php else: ?>

            <div class="empty-message">
                <div class="empty-icon">💗</div>
                <h3>Stay Connected With Us</h3>
                <p>
                    Enter your email address above to view your Contact Us messages
                    and any replies from The Knot House team.
                </p>
            </div>

        <?php endif; ?>

        <!-- CONTACT CTA -->

        <div class="contact-cta">
            <div class="icon">🧶</div>
            <h2>Need To Send Us A New Message?</h2>
            <p>
                Have a question about our crochet products, want a custom order,
                or simply want to talk to us? We'd love to hear from you.
            </p>
            <a href="contact.html" class="cta-button">💌 Contact The Knot House</a>
        </div>

    </section>
</main>

<!-- FOOTER -->

<footer class="site-footer">
    <div class="footer-container">

        <!-- BRAND -->

        <div class="footer-brand">
            <a href="index.html" class="footer-logo" aria-label="The Knot House Home">
                <img src="./logo.png" alt="The Knot House Logo" loading="lazy">
                <h3>The Knot House</h3>
            </a>

            <p class="footer-about">
                A little place for handmade happiness, where every loop,
                stitch and creation is made with love.
            </p>

            <div class="footer-social">
                <a href="https://www.instagram.com/crochet__creations_by_amrin/"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="Instagram">◎</a>

                <a href="#"
                   aria-label="Facebook">f</a>

                <a href="#"
                   aria-label="Pinterest">p</a>
            </div>
        </div>

        <!-- QUICK LINKS -->

        <div class="footer-column">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="about.html">About Us</a></li>
                <li><a href="product.html">Products</a></li>
                <li><a href="gallery.html">Gallery</a></li>
            </ul>
        </div>

        <!-- HELP -->

        <div class="footer-column">
            <h4>Help</h4>
            <ul>
                <li><a href="contact.html">Contact Us</a></li>
                <li><a href="feedback.html">Feedback</a></li>
                <li><a href="faq.html">FAQ</a></li>
                <li><a href="track-order.html">Track Order</a></li>
                <li><a href="cart.html">My Cart</a></li>
            </ul>
        </div>

        <!-- CONTACT -->

        <div class="footer-column">
            <h4>Contact</h4>

            <div class="footer-contact-item">
                <span aria-hidden="true">✉</span>
                <a href="mailto:theknothouse@gmail.com">
                    theknothouse@gmail.com
                </a>
            </div>

            <div class="footer-contact-item">
                <span aria-hidden="true">◎</span>
                <a href="https://www.instagram.com/crochet__creations_by_amrin/"
                   target="_blank"
                   rel="noopener noreferrer">
                    crochet__creations_by_amrin
                </a>
            </div>

            <div class="footer-contact-item">
                <span aria-hidden="true">⌖</span>
                <span>Ahmedabad, Gujarat, India</span>
            </div>
        </div>

    </div>

    <!-- COPYRIGHT -->

    <div class="footer-bottom">
        <p>© 2026 The Knot House. All rights reserved.</p>
        <p class="made-with">Made with ♡ and yarn.</p>
    </div>
</footer>

<!-- JAVASCRIPT -->

<script>
    /* MOBILE MENU */

    const mainNav = document.getElementById("mainNav");
    const menuToggle = document.getElementById("menuToggle");
    const siteHeader = document.getElementById("siteHeader");

    function closeMenu() {
        mainNav.classList.remove("active");
        menuToggle.setAttribute("aria-expanded", "false");
        menuToggle.setAttribute("aria-label", "Open navigation menu");
        menuToggle.textContent = "☰";
    }

    menuToggle.addEventListener("click", function () {
        const isOpen = mainNav.classList.toggle("active");

        menuToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        menuToggle.setAttribute(
            "aria-label",
            isOpen ? "Close navigation menu" : "Open navigation menu"
        );
        menuToggle.textContent = isOpen ? "✕" : "☰";
    });

    mainNav.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", closeMenu);
    });

    document.addEventListener("click", function (event) {
        if (!siteHeader.contains(event.target)) {
            closeMenu();
        }
    });

    window.addEventListener("resize", function () {
        if (window.innerWidth > 1050) {
            closeMenu();
        }
    });

    /* CART NAVIGATION */

    function openCart() {
        window.location.href = "cart.html";
    }

    /* CART COUNT */

    function updateCartCount() {
        let cart = [];

        try {
            cart = JSON.parse(localStorage.getItem("cart")) || [];
        } catch (error) {
            cart = [];
        }

        let totalItems = 0;

        if (Array.isArray(cart)) {
            cart.forEach(function (item) {
                if (item && item.quantity !== undefined) {
                    totalItems += Number(item.quantity) || 0;
                } else if (item) {
                    totalItems += 1;
                }
            });
        }

        const cartCount = document.getElementById("cartCount");

        if (cartCount) {
            cartCount.textContent = totalItems;
        }
    }

    document.addEventListener("DOMContentLoaded", updateCartCount);
    window.addEventListener("storage", updateCartCount);
</script>

</body>
</html>
