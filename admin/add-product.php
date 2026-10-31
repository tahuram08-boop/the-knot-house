<?php

/* =========================================================
   THE KNOT HOUSE - ADMIN ADD PRODUCT
   ========================================================= */

/* =========================================================
   START SESSION SAFELY
   This prevents:
   "session_start(): Ignoring session_start() because a
   session is already active"
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


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
   FORM VARIABLES
   ========================================================= */

$productName = "";
$category = "";
$price = "";
$description = "";
$stock = "";
$status = "Active";

$error = "";
$success = "";


/* =========================================================
   PRODUCT CATEGORIES
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
   IMAGE UPLOAD DIRECTORY
   ========================================================= */

$uploadDirectory = "../images/";

if (!is_dir($uploadDirectory)) {

    mkdir(
        $uploadDirectory,
        0777,
        true
    );
}


/* =========================================================
   FORM SUBMISSION
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* -----------------------------------------------------
       GET FORM VALUES
       ----------------------------------------------------- */

    $productName = trim(
        $_POST["product_name"] ?? ""
    );

    $category = trim(
        $_POST["category"] ?? ""
    );

    $price = trim(
        $_POST["price"] ?? ""
    );

    $description = trim(
        $_POST["description"] ?? ""
    );

    $stock = trim(
        $_POST["stock"] ?? ""
    );

    $status = trim(
        $_POST["status"] ?? "Active"
    );


    /* -----------------------------------------------------
       VALIDATION
       ----------------------------------------------------- */

    if ($productName === "") {

        $error = "Please enter the product name.";

    } elseif (strlen($productName) < 2) {

        $error =
            "Product name must contain at least 2 characters.";

    } elseif ($category === "") {

        $error =
            "Please select a product category.";

    } elseif (!in_array($category, $categories, true)) {

        $error =
            "Please select a valid product category.";

    } elseif ($price === "") {

        $error =
            "Please enter the product price.";

    } elseif (!is_numeric($price)) {

        $error =
            "Product price must be a valid number.";

    } elseif ((float)$price < 0) {

        $error =
            "Product price cannot be negative.";

    } elseif ($stock === "") {

        $error =
            "Please enter the stock quantity.";

    } elseif (!is_numeric($stock)) {

        $error =
            "Stock quantity must be a valid number.";

    } elseif ((int)$stock < 0) {

        $error =
            "Stock quantity cannot be negative.";

    } elseif (
        !in_array(
            $status,
            ["Active", "Inactive"],
            true
        )
    ) {

        $error =
            "Please select a valid product status.";
    }


    /* -----------------------------------------------------
       IMAGE UPLOAD
       ----------------------------------------------------- */

    $imagePath = "";

    if ($error === "") {

        if (
            !isset($_FILES["product_image"]) ||
            $_FILES["product_image"]["error"]
            === UPLOAD_ERR_NO_FILE
        ) {

            $error =
                "Please select a product image.";

        } else {

            $image = $_FILES["product_image"];

            $maxFileSize =
                5 * 1024 * 1024;


            /* ---------------------------------------------
               UPLOAD ERROR
               --------------------------------------------- */

            if (
                $image["error"]
                !== UPLOAD_ERR_OK
            ) {

                $error =
                    "There was a problem uploading the image.";

            } elseif (
                $image["size"]
                > $maxFileSize
            ) {

                $error =
                    "Image size must be less than 5 MB.";
            }


            /* ---------------------------------------------
               CHECK IMAGE TYPE
               --------------------------------------------- */

            if ($error === "") {

                $allowedTypes = [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];

                $fileInfo =
                    finfo_open(
                        FILEINFO_MIME_TYPE
                    );

                $mimeType =
                    finfo_file(
                        $fileInfo,
                        $image["tmp_name"]
                    );

                finfo_close($fileInfo);


                if (
                    !in_array(
                        $mimeType,
                        $allowedTypes,
                        true
                    )
                ) {

                    $error =
                        "Only JPG, JPEG, PNG and WEBP images are allowed.";
                }
            }


            /* ---------------------------------------------
               CREATE SAFE FILE NAME
               --------------------------------------------- */

            if ($error === "") {

                $extension =
                    strtolower(
                        pathinfo(
                            $image["name"],
                            PATHINFO_EXTENSION
                        )
                    );


                $safeName =
                    preg_replace(
                        "/[^a-zA-Z0-9-_]/",
                        "-",
                        $productName
                    );


                $safeName =
                    strtolower(
                        trim(
                            $safeName,
                            "-"
                        )
                    );


                if ($safeName === "") {
                    $safeName = "product";
                }


                $uniqueName =
                    $safeName .
                    "-" .
                    time() .
                    "-" .
                    uniqid() .
                    "." .
                    $extension;


                $destination =
                    $uploadDirectory .
                    $uniqueName;


                /* -----------------------------------------
                   MOVE IMAGE
                   ----------------------------------------- */

                if (
                    move_uploaded_file(
                        $image["tmp_name"],
                        $destination
                    )
                ) {

                    $imagePath =
                        "../images/" .
                        $uniqueName;

                } else {

                    $error =
                        "Unable to save the uploaded image.";
                }
            }
        }
    }


    /* =====================================================
       INSERT PRODUCT INTO DATABASE
       ===================================================== */

    if ($error === "") {

        $priceValue =
            (float)$price;

        $stockValue =
            (int)$stock;


        $sql = "
            INSERT INTO products
            (
                product_name,
                category,
                price,
                description,
                image,
                stock,
                status
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?)
        ";


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {

            $error =
                "Unable to prepare database query.";


            if (
                $imagePath !== "" &&
                file_exists($imagePath)
            ) {

                unlink($imagePath);
            }

        } else {

            $stmt->bind_param(
                "ssdssis",
                $productName,
                $category,
                $priceValue,
                $description,
                $imagePath,
                $stockValue,
                $status
            );


            if ($stmt->execute()) {

                /*
                 * Product successfully added.
                 */

                $success =
                    "Product added successfully!";

                /*
                 * Redirect after a short delay.
                 * This prevents duplicate form submission.
                 */

                header(
                    "Refresh: 1.5; URL=products.php"
                );


            } else {

                $error =
                    "Unable to add product: " .
                    $stmt->error;


                if (
                    $imagePath !== "" &&
                    file_exists($imagePath)
                ) {

                    unlink($imagePath);
                }
            }


            $stmt->close();
        }
    }
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
        Add Product | The Knot House Admin
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

            --red: #b85c5c;
            --red-light: #fbeaea;

            --shadow:
                0 10px 30px
                rgba(81, 61, 61, 0.08);

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


        a {
            text-decoration: none;
            color: inherit;
        }


        button,
        input,
        textarea,
        select {
            font-family: inherit;
        }


        /* =====================================================
           ADMIN LAYOUT
           ===================================================== */

        .admin-layout {

            display: flex;

            min-height: 100vh;
        }


        /* =====================================================
           SIDEBAR
           ===================================================== */

        .sidebar {

            width: 255px;

            background:
                var(--white);

            border-right:
                1px solid var(--border);

            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            z-index: 1000;

            display: flex;

            flex-direction: column;

            transition:
                transform 0.3s ease;
        }


        /* =====================================================
           BRAND
           ===================================================== */

        .brand {

            padding:
                25px 21px;

            border-bottom:
                1px solid var(--border);
        }


        .brand-link {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .brand-logo {

            width: 45px;
            height: 45px;

            border-radius: 12px;

            background:
                var(--light-pink);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;

            flex-shrink: 0;
        }


        .brand-text h2 {

            font-family:
                "Playfair Display",
                serif;

            font-size: 25px;

            line-height: 1.2;
        }


        .brand-text span {

            display: block;

            margin-top: 3px;

            color:
                var(--pink);

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 1.4px;
        }


        /* =====================================================
           SIDEBAR NAVIGATION
           ===================================================== */

        .sidebar-nav {

            padding:
                21px 13px;

            flex: 1;

            overflow-y: auto;
        }


        .nav-section-title {

            padding:
                0 11px 9px;

            margin-top: 4px;

            color:
                #aa9999;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1.4px;

            text-transform: uppercase;
        }


        .nav-section-title:not(:first-child) {

            margin-top: 22px;
        }


        .nav-link {

            display: flex;

            align-items: center;

            gap: 11px;

            padding:
                11px 13px;

            margin-bottom: 4px;

            border-radius: 9px;

            color:
                var(--brown-light);

            font-size: 13px;

            font-weight: 500;

            transition:
                all 0.22s ease;
        }


        .nav-link:hover {

            background:
                var(--light-pink);

            color:
                var(--pink);

            transform:
                translateX(2px);
        }


        .nav-link.active {

            background:
                var(--light-pink);

            color:
                var(--pink);

            font-weight: 700;
        }


        .nav-icon {

            width: 23px;

            text-align: center;

            font-size: 16px;

            flex-shrink: 0;
        }


        /* =====================================================
           SIDEBAR BOTTOM
           ===================================================== */

        .sidebar-bottom {

            padding:
                15px;

            border-top:
                1px solid var(--border);
        }


        .admin-mini {

            display: flex;

            align-items: center;

            gap: 10px;

            padding:
                11px;

            margin-bottom: 9px;

            border:
                1px solid var(--border);

            border-radius: 11px;

            background:
                var(--cream);
        }


        .admin-avatar {

            width: 37px;
            height: 37px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background:
                var(--pink);

            color:
                var(--white);

            font-size: 12px;

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

            margin-top: 2px;

            color:
                var(--brown-light);

            font-size: 10px;
        }


        .logout-link {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding:
                10px;

            border-radius: 9px;

            background:
                var(--red-light);

            color:
                var(--red);

            font-size: 12px;

            font-weight: 700;

            transition:
                0.2s ease;
        }


        .logout-link:hover {

            background:
                #f7dede;
        }


        /* =====================================================
           MAIN CONTENT
           ===================================================== */

        .main-content {

            margin-left: 255px;

            width:
                calc(100% - 255px);

            min-height: 100vh;
        }


        /* =====================================================
           TOPBAR
           ===================================================== */

        .topbar {

            height: 75px;

            background:
                rgba(255, 250, 247, 0.95);

            border-bottom:
                1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0 30px;

            position: sticky;

            top: 0;

            z-index: 500;

            backdrop-filter:
                blur(12px);
        }


        .topbar-left {

            display: flex;

            align-items: center;

            gap: 15px;
        }


        .page-title {

            font-family:
                "Playfair Display",
                serif;

            font-size: 30px;

            font-weight: 600;

            color:
                var(--brown);
        }


        .menu-btn {

            display: none;

            width: 40px;
            height: 40px;

            border:
                1px solid var(--border);

            border-radius: 9px;

            background:
                var(--white);

            color:
                var(--brown);

            font-size: 20px;

            cursor: pointer;
        }


        .topbar-right {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .view-store {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding:
                9px 14px;

            border:
                1px solid var(--border);

            border-radius: 9px;

            background:
                var(--white);

            color:
                var(--brown);

            font-size: 15px;

            font-weight: 600;

            transition:
                0.2s ease;
        }


        .view-store:hover {

            background:
                var(--light-pink);

            color:
                var(--pink);
        }


        .top-admin {

            display: flex;

            align-items: center;

            gap: 9px;
        }


        .top-admin-avatar {

            width: 36px;
            height: 36px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background:
                var(--pink);

            color:
                var(--white);

            font-size: 12px;

            font-weight: 700;
        }


        .top-admin-name {

            font-size: 15px;

            font-weight: 700;
        }


        .top-admin-role {

            color:
                var(--brown-light);

            font-size: 13px;

            margin-top: 1px;
        }


        /* =====================================================
           PAGE CONTENT
           ===================================================== */

        .page-content {

            padding:
                30px;
        }


        .page-intro {

            margin-bottom:
                24px;
        }


        .page-intro h1 {

            font-family:
                "Playfair Display",
                serif;

            font-size: 30px;

            margin-bottom:
                7px;
        }


        .page-intro p {

            color:
                var(--brown-light);

            font-size: 13px;

            line-height: 1.7;
        }


        /* =====================================================
           ALERTS
           ===================================================== */

        .alert {

            padding:
                14px 16px;

            border-radius: 10px;

            margin-bottom:
                20px;

            display: flex;

            align-items: flex-start;

            gap: 10px;

            font-size: 13px;

            line-height: 1.5;
        }


        .alert-error {

            background:
                var(--red-light);

            border:
                1px solid #f0cccc;

            color:
                #963f3f;
        }


        .alert-success {

            background:
                var(--green-light);

            border:
                1px solid #cbe6d6;

            color:
                #397550;
        }


        .alert-icon {

            font-size: 18px;

            flex-shrink: 0;
        }


        /* =====================================================
           FORM LAYOUT
           ===================================================== */

        .form-layout {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                320px;

            gap: 24px;

            align-items: start;
        }


        /* =====================================================
           FORM CARD
           ===================================================== */

        .form-card {

            background:
                var(--white);

            border:
                1px solid var(--border);

            border-radius: 16px;

            box-shadow:
                var(--shadow);

            overflow: hidden;
        }


        .card-header {

            padding:
                20px 22px;

            border-bottom:
                1px solid var(--border);
        }


        .card-header h2 {

            font-family:
                "Playfair Display",
                serif;

            font-size: 20px;

            margin-bottom: 5px;
        }


        .card-header p {

            color:
                var(--brown-light);

            font-size: 15px;
        }


        .form-body {

            padding:
                24px 22px;
        }


        /* =====================================================
           FORM GROUP
           ===================================================== */

        .form-group {

            margin-bottom:
                20px;
        }


        .form-group:last-child {

            margin-bottom: 0;
        }


        .form-label {

            display: block;

            margin-bottom:
                8px;

            color:
                var(--brown);

            font-size: 14px;

            font-weight: 700;
        }


        .required {

            color:
                var(--red);
        }


        .form-control {

            width: 100%;

            padding:
                12px 13px;

            border:
                1px solid var(--border);

            border-radius: 9px;

            background:
                var(--cream);

            color:
                var(--brown);

            font-size: 13px;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .form-control:focus {

            border-color:
                var(--pink);

            background:
                var(--white);

            box-shadow:
                0 0 0 3px
                rgba(169, 103, 113, 0.10);
        }


        textarea.form-control {

            min-height:
                130px;

            resize:
                vertical;

            line-height:
                1.6;
        }


        select.form-control {

            cursor:
                pointer;
        }


        .input-help {

            display: block;

            margin-top:
                6px;

            color:
                #9a8989;

            font-size: 13px;

            line-height: 1.5;
        }


        /* =====================================================
           TWO COLUMN FORM
           ===================================================== */

        .form-row {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 16px;
        }


        /* =====================================================
           IMAGE UPLOAD
           ===================================================== */

        .image-upload {

            border:
                1.5px dashed
                var(--border);

            border-radius:
                12px;

            background:
                var(--cream);

            padding:
                22px;

            text-align:
                center;

            transition:
                0.2s ease;
        }


        .image-upload:hover {

            border-color:
                var(--pink);

            background:
                var(--light-pink);
        }


        .upload-icon {

            width: 54px;
            height: 54px;

            margin:
                0 auto 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background:
                var(--light-pink);

            font-size: 24px;
        }


        .image-upload h3 {

            font-size: 14px;

            margin-bottom:
                5px;
        }


        .image-upload p {

            color:
                var(--brown-light);

            font-size: 11px;

            line-height: 1.5;

            margin-bottom:
                13px;
        }


        .file-input {

            width: 100%;

            font-size: 11px;

            color:
                var(--brown-light);
        }


        .file-input::file-selector-button {

            padding:
                9px 12px;

            margin-right:
                8px;

            border:
                none;

            border-radius:
                7px;

            background:
                var(--pink);

            color:
                var(--white);

            font-family:
                inherit;

            font-size: 11px;

            font-weight: 700;

            cursor:
                pointer;
        }


        /* =====================================================
           IMAGE PREVIEW
           ===================================================== */

        .image-preview {

            display:
                none;

            margin-top:
                15px;
        }


        .image-preview img {

            width: 100%;

            max-height:
                220px;

            object-fit:
                cover;

            border-radius:
                10px;

            border:
                1px solid var(--border);
        }


        /* =====================================================
           ACTION BUTTONS
           ===================================================== */

        .form-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            padding-top:
                22px;

            margin-top:
                22px;

            border-top:
                1px solid var(--border);
        }


        .btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            min-height:
                42px;

            padding:
                0 18px;

            border-radius:
                9px;

            border:
                none;

            font-size: 12px;

            font-weight: 700;

            cursor:
                pointer;

            transition:
                all 0.2s ease;
        }


        .btn-cancel {

            background:
                var(--cream-dark);

            color:
                var(--brown);
        }


        .btn-cancel:hover {

            background:
                var(--border);
        }


        .btn-primary {

            background:
                var(--pink);

            color:
                var(--white);

            box-shadow:
                0 5px 15px
                rgba(169, 103, 113, 0.20);
        }


        .btn-primary:hover {

            background:
                var(--dark-pink);

            transform:
                translateY(-1px);
        }


        /* =====================================================
           SIDE INFORMATION CARDS
           ===================================================== */

        .info-card {

            background:
                var(--white);

            border:
                1px solid var(--border);

            border-radius:
                16px;

            box-shadow:
                var(--shadow);

            overflow:
                hidden;

            margin-bottom:
                20px;
        }


        .info-card:last-child {

            margin-bottom:
                0;
        }


        .info-card-header {

            padding:
                18px;

            border-bottom:
                1px solid var(--border);
        }


        .info-card-header h3 {

            font-family:
                "Playfair Display",
                serif;

            font-size:
                20px;
        }


        .info-card-body {

            padding:
                18px;
        }


        .tip {

            display:
                flex;

            align-items:
                flex-start;

            gap:
                10px;

            margin-bottom:
                15px;
        }


        .tip:last-child {

            margin-bottom:
                0;
        }


        .tip-icon {

            width:
                30px;

            height:
                30px;

            flex-shrink:
                0;

            border-radius:
                8px;

            background:
                var(--light-pink);

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                14px;
        }


        .tip-text strong {

            display:
                block;

            margin-bottom:
                3px;

            font-size:
                15px;
        }


        .tip-text span {

            display:
                block;

            color:
                var(--brown-light);

            font-size:
                13px;

            line-height:
                1.5;
        }


        /* =====================================================
           PRODUCT PREVIEW NOTE
           ===================================================== */

        .preview-note {

            padding:
                14px;

            border-radius:
                10px;

            background:
                var(--light-pink);

            color:
                var(--dark-pink);

            font-size:
                15px;

            line-height:
                1.6;
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
                rgba(81, 61, 61, 0.40);

            z-index:
                999;
        }


        /* =====================================================
           RESPONSIVE - TABLET
           ===================================================== */

        @media (max-width: 1050px) {

            .form-layout {

                grid-template-columns:
                    1fr;
            }

            .form-side {

                display:
                    grid;

                grid-template-columns:
                    1fr 1fr;

                gap:
                    20px;
            }

            .info-card {

                margin-bottom:
                    0;
            }

        }


        /* =====================================================
           RESPONSIVE - MOBILE
           ===================================================== */

        @media (max-width: 768px) {

            .sidebar {

                transform:
                    translateX(-100%);
            }


            .sidebar.open {

                transform:
                    translateX(0);
            }


            .sidebar-overlay.active {

                display:
                    block;
            }


            .main-content {

                margin-left:
                    0;

                width:
                    100%;
            }


            .menu-btn {

                display:
                    flex;

                align-items:
                    center;

                justify-content:
                    center;
            }


            .topbar {

                padding:
                    0 17px;

                height:
                    65px;
            }


            .page-title {

                font-size:
                    21px;
            }


            .top-admin {

                display:
                    none;
            }


            .view-store {

                padding:
                    8px 10px;
            }


            .page-content {

                padding:
                    22px 16px;
            }


            .page-intro h1 {

                font-size:
                    25px;
            }


            .form-row {

                grid-template-columns:
                    1fr;
            }


            .form-side {

                grid-template-columns:
                    1fr;
            }

        }


        /* =====================================================
           SMALL MOBILE
           ===================================================== */

        @media (max-width: 480px) {

            .topbar-right {

                gap:
                    5px;
            }


            .view-store span {

                display:
                    none;
            }


            .page-content {

                padding:
                    18px 12px;
            }


            .form-body {

                padding:
                    18px 15px;
            }


            .card-header {

                padding:
                    17px 15px;
            }


            .form-actions {

                flex-direction:
                    column-reverse;
            }


            .btn {

                width:
                    100%;
            }

        }

    </style>

</head>


<body>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >


        <!-- BRAND -->

        <div class="brand">

            <a
                href="dashboard.php"
                class="brand-link"
            >

                <div class="brand-logo">
                    🧶
                </div>

                <div class="brand-text">

                    <h2>
                        The Knot House
                    </h2>

                    <span>
                        Admin Panel
                    </span>

                </div>

            </a>

        </div>


        <!-- NAVIGATION -->

        <nav class="sidebar-nav">


            <!-- MAIN MENU -->

            <div class="nav-section-title">
                Main Menu
            </div>


            <a
                href="dashboard.php"
                class="nav-link"
            >

                <span class="nav-icon">
                    📊
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


            <!-- MANAGEMENT -->

            <div class="nav-section-title">
                Management
            </div>


            <a
                href="inventory.php"
                class="nav-link"
            >

                <span class="nav-icon">
                    📋
                </span>

                <span>
                    Inventory
                </span>

            </a>


            <a
                href="../index.html"
                target="_blank"
                class="nav-link"
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
                class="nav-link active"
            >

                <span class="nav-icon">
                    ➕
                </span>

                <span>
                    Add Product
                </span>

            </a>


        </nav>


        <!-- SIDEBAR BOTTOM -->

        <div class="sidebar-bottom">


            <div class="admin-mini">

                <div class="admin-avatar">

                    <?php
                    echo htmlspecialchars(
                        $adminInitial
                    );
                    ?>

                </div>


                <div class="admin-mini-info">

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


            <a
                href="logout.php"
                class="logout-link"
            >

                ↪ Logout

            </a>


        </div>


    </aside>


    <!-- =====================================================
         MOBILE OVERLAY
         ===================================================== -->

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
        onclick="closeSidebar()"
    ></div>


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
                    type="button"
                    class="menu-btn"
                    onclick="toggleSidebar()"
                    aria-label="Open menu"
                >
                    ☰
                </button>


                <div class="page-title">
                    Add Product
                </div>


            </div>


            <div class="topbar-right">


                <a
                    href="../index.html"
                    target="_blank"
                    class="view-store"
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


                    <div>

                        <div class="top-admin-name">

                            <?php
                            echo htmlspecialchars(
                                $adminName
                            );
                            ?>

                        </div>

                        <div class="top-admin-role">
                            Administrator
                        </div>

                    </div>

                </div>


            </div>


        </header>


        <!-- =================================================
             PAGE CONTENT
             ================================================= -->

        <section class="page-content">


            <!-- PAGE INTRO -->

            <div class="page-intro">

                <h1>
                    Add New Product
                </h1>

                <p>
                    Add a new handmade crochet product
                    to The Knot House store.
                </p>

            </div>


            <!-- =================================================
                 ALERTS
                 ================================================= -->

            <?php if ($error !== ""): ?>

                <div class="alert alert-error">

                    <span class="alert-icon">
                        ⚠️
                    </span>

                    <div>

                        <?php
                        echo htmlspecialchars(
                            $error
                        );
                        ?>

                    </div>

                </div>

            <?php endif; ?>


            <?php if ($success !== ""): ?>

                <div class="alert alert-success">

                    <span class="alert-icon">
                        ✓
                    </span>

                    <div>

                        <?php
                        echo htmlspecialchars(
                            $success
                        );
                        ?>

                        <br>

                        <small>
                            Redirecting to products...
                        </small>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORM LAYOUT
                 ================================================= -->

            <div class="form-layout">


                <!-- =================================================
                     MAIN FORM
                     ================================================= -->

                <div class="form-card">


                    <div class="card-header">

                        <h2>
                            Product Information
                        </h2>

                        <p>
                            Enter the details of your crochet product.
                        </p>

                    </div>


                    <div class="form-body">


                        <form
                            method="POST"
                            enctype="multipart/form-data"
                            id="productForm"
                        >


                            <!-- PRODUCT NAME -->

                            <div class="form-group">

                                <label
                                    class="form-label"
                                    for="product_name"
                                >

                                    Product Name
                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    id="product_name"
                                    name="product_name"
                                    class="form-control"
                                    placeholder="Example: Crochet Flower Bouquet"
                                    value="<?php
                                        echo htmlspecialchars(
                                            $productName
                                        );
                                    ?>"
                                    required
                                >


                                <span class="input-help">
                                    Give your product a clear and attractive name.
                                </span>

                            </div>


                            <!-- CATEGORY + PRICE -->

                            <div class="form-row">


                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="category"
                                    >

                                        Category
                                        <span class="required">
                                            *
                                        </span>

                                    </label>


                                    <select
                                        id="category"
                                        name="category"
                                        class="form-control"
                                        required
                                    >

                                        <option value="">
                                            Select Category
                                        </option>


                                        <?php foreach (
                                            $categories
                                            as $item
                                        ): ?>

                                            <option
                                                value="<?php
                                                    echo htmlspecialchars(
                                                        $item
                                                    );
                                                ?>"
                                                <?php
                                                if (
                                                    $category ===
                                                    $item
                                                ) {
                                                    echo "selected";
                                                }
                                                ?>
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $item
                                                );
                                                ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>


                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="price"
                                    >

                                        Price (₹)
                                        <span class="required">
                                            *
                                        </span>

                                    </label>


                                    <input
                                        type="number"
                                        id="price"
                                        name="price"
                                        class="form-control"
                                        placeholder="Example: 499"
                                        min="0"
                                        step="0.01"
                                        value="<?php
                                            echo htmlspecialchars(
                                                $price
                                            );
                                        ?>"
                                        required
                                    >

                                </div>


                            </div>


                            <!-- STOCK + STATUS -->

                            <div class="form-row">


                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="stock"
                                    >

                                        Stock Quantity
                                        <span class="required">
                                            *
                                        </span>

                                    </label>


                                    <input
                                        type="number"
                                        id="stock"
                                        name="stock"
                                        class="form-control"
                                        placeholder="Example: 10"
                                        min="0"
                                        step="1"
                                        value="<?php
                                            echo htmlspecialchars(
                                                $stock
                                            );
                                        ?>"
                                        required
                                    >

                                    <span class="input-help">
                                        Enter the number of items currently available.
                                    </span>

                                </div>


                                <div class="form-group">

                                    <label
                                        class="form-label"
                                        for="status"
                                    >

                                        Product Status
                                        <span class="required">
                                            *
                                        </span>

                                    </label>


                                    <select
                                        id="status"
                                        name="status"
                                        class="form-control"
                                    >

                                        <option
                                            value="Active"
                                            <?php
                                            if (
                                                $status ===
                                                "Active"
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Active
                                        </option>


                                        <option
                                            value="Inactive"
                                            <?php
                                            if (
                                                $status ===
                                                "Inactive"
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Inactive
                                        </option>

                                    </select>

                                    <span class="input-help">
                                        Active products can be shown in the store.
                                    </span>

                                </div>


                            </div>


                            <!-- DESCRIPTION -->

                            <div class="form-group">

                                <label
                                    class="form-label"
                                    for="description"
                                >

                                    Product Description

                                </label>


                                <textarea
                                    id="description"
                                    name="description"
                                    class="form-control"
                                    placeholder="Write a short description of the crochet product..."
                                ><?php
                                    echo htmlspecialchars(
                                        $description
                                    );
                                ?></textarea>


                                <span class="input-help">
                                    Describe the material, design,
                                    size, colour or other useful details.
                                </span>

                            </div>


                            <!-- IMAGE -->

                            <div class="form-group">

                                <label
                                    class="form-label"
                                    for="product_image"
                                >

                                    Product Image
                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <div class="image-upload">


                                    <div class="upload-icon">
                                        🖼️
                                    </div>


                                    <h3>
                                        Upload Product Image
                                    </h3>


                                    <p>
                                        JPG, JPEG, PNG or WEBP
                                        <br>
                                        Maximum size: 5 MB
                                    </p>


                                    <input
                                        type="file"
                                        id="product_image"
                                        name="product_image"
                                        class="file-input"
                                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                        required
                                    >


                                    <div
                                        class="image-preview"
                                        id="imagePreview"
                                    >

                                        <img
                                            id="previewImage"
                                            src=""
                                            alt="Image Preview"
                                        >

                                    </div>


                                </div>

                            </div>


                            <!-- ACTIONS -->

                            <div class="form-actions">


                                <a
                                    href="products.php"
                                    class="btn btn-cancel"
                                >
                                    Cancel
                                </a>


                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >

                                    ➕
                                    Add Product

                                </button>


                            </div>


                        </form>


                    </div>

                </div>


                <!-- =================================================
                     RIGHT SIDE INFORMATION
                     ================================================= -->

                <div class="form-side">


                    <!-- TIPS CARD -->

                    <div class="info-card">


                        <div class="info-card-header">

                            <h3>
                                Product Tips
                            </h3>

                        </div>


                        <div class="info-card-body">


                            <div class="tip">

                                <div class="tip-icon">
                                    🏷️
                                </div>

                                <div class="tip-text">

                                    <strong>
                                        Product Name
                                    </strong>

                                    <span>
                                        Use a simple and attractive
                                        name that customers can easily understand.
                                    </span>

                                </div>

                            </div>


                            <div class="tip">

                                <div class="tip-icon">
                                    💰
                                </div>

                                <div class="tip-text">

                                    <strong>
                                        Set the Price
                                    </strong>

                                    <span>
                                        Enter the selling price in Indian Rupees.
                                    </span>

                                </div>

                            </div>


                            <div class="tip">

                                <div class="tip-icon">
                                    📦
                                </div>

                                <div class="tip-text">

                                    <strong>
                                        Stock
                                    </strong>

                                    <span>
                                        Keep the available quantity updated
                                        for accurate inventory.
                                    </span>

                                </div>

                            </div>


                            <div class="tip">

                                <div class="tip-icon">
                                    📸
                                </div>

                                <div class="tip-text">

                                    <strong>
                                        Good Image
                                    </strong>

                                    <span>
                                        Use a clear crochet product image
                                        with good lighting.
                                    </span>

                                </div>

                            </div>


                        </div>

                    </div>


                    <!-- IMAGE NOTE -->

                    <div class="info-card">


                        <div class="info-card-header">

                            <h3>
                                Image Guidelines
                            </h3>

                        </div>


                        <div class="info-card-body">

                            <div class="preview-note">

                                🧶
                                <strong>
                                    Crochet products look best
                                    with clean, clear images.
                                </strong>

                                <br><br>

                                Recommended formats:
                                JPG, PNG or WEBP.

                                <br>

                                Maximum file size:
                                5 MB.

                            </div>

                        </div>

                    </div>


                    <!-- STORE NOTE -->

                    <div class="info-card">


                        <div class="info-card-header">

                            <h3>
                                Store Categories
                            </h3>

                        </div>


                        <div class="info-card-body">

                            <div class="preview-note">

                                👜 Bags

                                <br>

                                🌸 Flowers

                                <br>

                                🧸 Toys

                                <br>

                                👗 Clothes

                                <br>

                                🎀 Accessories

                                <br>

                                🏠 Decor

                            </div>

                        </div>

                    </div>


                </div>


            </div>


        </section>


    </main>


</div>


<script>

/* =========================================================
   MOBILE SIDEBAR
   ========================================================= */

function toggleSidebar() {

    const sidebar =
        document.getElementById("sidebar");

    const overlay =
        document.getElementById("sidebarOverlay");

    sidebar.classList.toggle("open");

    overlay.classList.toggle("active");
}


function closeSidebar() {

    const sidebar =
        document.getElementById("sidebar");

    const overlay =
        document.getElementById("sidebarOverlay");

    sidebar.classList.remove("open");

    overlay.classList.remove("active");
}


/* =========================================================
   IMAGE PREVIEW
   ========================================================= */

const imageInput =
    document.getElementById("product_image");

const imagePreview =
    document.getElementById("imagePreview");

const previewImage =
    document.getElementById("previewImage");


if (imageInput) {

    imageInput.addEventListener(
        "change",
        function () {

            const file =
                this.files[0];

            if (!file) {

                imagePreview.style.display =
                    "none";

                previewImage.src = "";

                return;
            }


            const allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];


            if (
                !allowedTypes.includes(
                    file.type
                )
            ) {

                alert(
                    "Please select a JPG, PNG or WEBP image."
                );

                this.value = "";

                imagePreview.style.display =
                    "none";

                return;
            }


            if (
                file.size >
                5 * 1024 * 1024
            ) {

                alert(
                    "Image size must be less than 5 MB."
                );

                this.value = "";

                imagePreview.style.display =
                    "none";

                return;
            }


            const reader =
                new FileReader();


            reader.onload =
                function (event) {

                    previewImage.src =
                        event.target.result;

                    imagePreview.style.display =
                        "block";
                };


            reader.readAsDataURL(file);

        }
    );

}


/* =========================================================
   FORM SUBMIT BUTTON
   ========================================================= */

const productForm =
    document.getElementById("productForm");


if (productForm) {

    productForm.addEventListener(
        "submit",
        function () {

            const submitButton =
                productForm.querySelector(
                    "button[type='submit']"
                );


            if (submitButton) {

                submitButton.disabled =
                    true;

                submitButton.innerHTML =
                    "⏳ Adding Product...";

            }

        }
    );

}

</script>


</body>

</html>