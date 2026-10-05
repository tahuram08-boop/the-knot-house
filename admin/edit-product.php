<?php
session_start();

/* =========================================================
   ADMIN ACCESS CHECK
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
   CHECK PRODUCT ID
   ========================================================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: products.php");
    exit();
}

$product_id = (int)$_GET["id"];


/* =========================================================
   GET PRODUCT DATA
   ========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        product_name,
        category,
        price,
        description,
        image,
        stock,
        status
    FROM products
    WHERE id = ?
");

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    $conn->close();

    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();

$stmt->close();


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
   DEFAULT VALUES
   ========================================================= */

$product_name = $product["product_name"];
$category = $product["category"];
$price = $product["price"];
$description = $product["description"];
$stock = $product["stock"];
$status = $product["status"];
$current_image = $product["image"];

$error = "";
$success = "";


/* =========================================================
   UPDATE PRODUCT
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name = trim($_POST["product_name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $stock = trim($_POST["stock"] ?? "");
    $status = trim($_POST["status"] ?? "");


    /* -----------------------------------------------------
       VALIDATION
       ----------------------------------------------------- */

    if ($product_name === "") {

        $error = "Please enter the product name.";

    } elseif ($category === "") {

        $error = "Please select a category.";

    } elseif (!is_numeric($price) || $price < 0) {

        $error = "Please enter a valid price.";

    } elseif (!is_numeric($stock) || $stock < 0) {

        $error = "Please enter a valid stock quantity.";

    } elseif (!in_array($category, $categories)) {

        $error = "Invalid category selected.";

    } elseif (!in_array($status, ["Active", "Inactive"])) {

        $error = "Invalid product status.";

    } else {

        $new_image_path = $current_image;
        $upload_error = false;


        /* =================================================
           IMAGE UPLOAD
           ================================================= */

        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

                $error = "There was a problem uploading the image.";
                $upload_error = true;

            } else {

                $image = $_FILES["image"];

                /* Maximum file size = 5 MB */
                if ($image["size"] > 5 * 1024 * 1024) {

                    $error = "Image size must be less than 5 MB.";
                    $upload_error = true;

                } else {

                    /* -----------------------------------------
                       CHECK IMAGE TYPE
                       ----------------------------------------- */

                    $allowed_types = [
                        "image/jpeg",
                        "image/png",
                        "image/webp"
                    ];

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file(
                        $finfo,
                        $image["tmp_name"]
                    );
                    finfo_close($finfo);


                    if (!in_array($mime_type, $allowed_types)) {

                        $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";
                        $upload_error = true;

                    } else {

                        /* -------------------------------------
                           CREATE IMAGES FOLDER
                           ------------------------------------- */

                        $upload_directory = "../images/";

                        if (!is_dir($upload_directory)) {

                            mkdir(
                                $upload_directory,
                                0777,
                                true
                            );
                        }


                        /* -------------------------------------
                           GET FILE EXTENSION
                           ------------------------------------- */

                        $extension = strtolower(
                            pathinfo(
                                $image["name"],
                                PATHINFO_EXTENSION
                            )
                        );


                        /* -------------------------------------
                           CREATE SAFE FILE NAME
                           ------------------------------------- */

                        $safe_name = preg_replace(
                            "/[^a-zA-Z0-9_-]/",
                            "-",
                            $product_name
                        );

                        $safe_name = strtolower(
                            trim($safe_name, "-")
                        );

                        $new_file_name =
                            $safe_name .
                            "-" .
                            time() .
                            "-" .
                            bin2hex(random_bytes(4)) .
                            "." .
                            $extension;


                        $new_file_path =
                            $upload_directory .
                            $new_file_name;


                        /* -------------------------------------
                           MOVE IMAGE
                           ------------------------------------- */

                        if (
                            move_uploaded_file(
                                $image["tmp_name"],
                                $new_file_path
                            )
                        ) {

                            $new_image_path =
                                "../images/" .
                                $new_file_name;

                        } else {

                            $error =
                                "Unable to save the new image.";

                            $upload_error = true;
                        }
                    }
                }
            }
        }


        /* =================================================
           UPDATE DATABASE
           ================================================= */

        if (!$upload_error && $error === "") {

            $stmt = $conn->prepare("
                UPDATE products
                SET
                    product_name = ?,
                    category = ?,
                    price = ?,
                    description = ?,
                    image = ?,
                    stock = ?,
                    status = ?
                WHERE id = ?
            ");

            $price_value = (float)$price;
            $stock_value = (int)$stock;

            $stmt->bind_param(
                "ssdssisi",
                $product_name,
                $category,
                $price_value,
                $description,
                $new_image_path,
                $stock_value,
                $status,
                $product_id
            );


            if ($stmt->execute()) {

                $stmt->close();
                $conn->close();

                header(
                    "Location: products.php?updated=1"
                );

                exit();

            } else {

                $error =
                    "Unable to update product: " .
                    $stmt->error;

                $stmt->close();
            }
        }
    }
}


/* =========================================================
   IMAGE PREVIEW PATH
   ========================================================= */

$image_preview = $current_image;

if (strpos($image_preview, "../") === 0) {

    $image_preview = $image_preview;

} elseif ($image_preview !== "") {

    $image_preview = "../" . ltrim(
        $image_preview,
        "./"
    );

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

    <title>Edit Product | The Knot House</title>


    <!-- Google Fonts -->

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        /* =====================================================
           THE KNOT HOUSE - ADMIN EDIT PRODUCT
           ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "DM Sans", sans-serif;
            background: #f8f1ef;
            color: #513d3d;
            min-height: 100vh;
        }


        /* =====================================================
           SIDEBAR
           ===================================================== */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;

            background: #fffaf7;

            border-right: 1px solid #eadbd8;

            padding: 28px 18px;

            z-index: 1000;

            overflow-y: auto;
        }


        .brand {
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 0 10px 28px;

            border-bottom: 1px solid #eadbd8;

            margin-bottom: 22px;
        }


        .brand img {
            width: 48px;
            height: 48px;

            object-fit: contain;

            border-radius: 12px;
        }


        .brand-text h2 {
            font-family: "Playfair Display", serif;

            font-size: 19px;

            color: #8d555e;
        }


        .brand-text span {
            font-size: 11px;

            color: #806969;

            display: block;

            margin-top: 2px;
        }


        .nav-title {
            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 1.5px;

            color: #a08b8b;

            padding: 0 12px;

            margin-bottom: 10px;
        }


        .nav-link {
            display: flex;

            align-items: center;

            gap: 12px;

            padding: 12px 14px;

            margin-bottom: 5px;

            border-radius: 12px;

            text-decoration: none;

            color: #6d5d5d;

            font-size: 14px;

            font-weight: 500;

            transition: 0.25s;
        }


        .nav-link:hover {
            background: #f8e8e7;

            color: #8d555e;

            transform: translateX(3px);
        }


        .nav-link.active {
            background: #a96771;

            color: white;
        }


        .nav-icon {
            width: 25px;

            text-align: center;

            font-size: 17px;
        }


        .logout-link {
            color: #a45d67;
        }


        /* =====================================================
           MAIN CONTENT
           ===================================================== */

        .main {
            margin-left: 250px;

            min-height: 100vh;

            padding: 32px;
        }


        .topbar {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 28px;
        }


        .page-title h1 {
            font-family: "Playfair Display", serif;

            font-size: 32px;

            color: #513d3d;

            margin-bottom: 6px;
        }


        .page-title p {
            color: #806969;

            font-size: 14px;
        }


        .back-btn {
            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 18px;

            border: 1px solid #eadbd8;

            border-radius: 10px;

            background: white;

            color: #6d5d5d;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            transition: 0.25s;
        }


        .back-btn:hover {
            background: #f8e8e7;

            color: #8d555e;
        }


        /* =====================================================
           FORM LAYOUT
           ===================================================== */

        .form-layout {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                340px;

            gap: 24px;

            max-width: 1150px;
        }


        .card {
            background: white;

            border: 1px solid #eadbd8;

            border-radius: 18px;

            padding: 26px;

            box-shadow:
                0 8px 25px rgba(81, 61, 61, 0.05);
        }


        .card-title {
            font-family: "Playfair Display", serif;

            font-size: 21px;

            color: #513d3d;

            margin-bottom: 5px;
        }


        .card-subtitle {
            color: #806969;

            font-size: 13px;

            margin-bottom: 22px;
        }


        /* =====================================================
           FORM ELEMENTS
           ===================================================== */

        .form-group {
            margin-bottom: 20px;
        }


        .form-row {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 16px;
        }


        label {
            display: block;

            font-size: 13px;

            font-weight: 700;

            color: #513d3d;

            margin-bottom: 8px;
        }


        .required {
            color: #a96771;
        }


        input,
        select,
        textarea {
            width: 100%;

            border: 1px solid #eadbd8;

            background: #fffaf7;

            border-radius: 10px;

            padding: 12px 13px;

            font-family: "DM Sans", sans-serif;

            font-size: 14px;

            color: #513d3d;

            outline: none;

            transition: 0.25s;
        }


        input:focus,
        select:focus,
        textarea:focus {
            border-color: #a96771;

            background: white;

            box-shadow:
                0 0 0 3px rgba(169, 103, 113, 0.10);
        }


        textarea {
            min-height: 135px;

            resize: vertical;
        }


        .input-help {
            display: block;

            margin-top: 6px;

            font-size: 11px;

            color: #998787;
        }


        /* =====================================================
           CURRENT IMAGE
           ===================================================== */

        .image-box {
            border: 2px dashed #eadbd8;

            border-radius: 14px;

            padding: 15px;

            background: #fffaf7;

            text-align: center;

            margin-bottom: 15px;
        }


        .image-box img {
            width: 100%;

            height: 220px;

            object-fit: cover;

            border-radius: 10px;

            background: #f8e8e7;
        }


        .no-image {
            height: 220px;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #a08b8b;

            font-size: 14px;
        }


        .file-input {
            padding: 10px;

            cursor: pointer;
        }


        /* =====================================================
           STATUS
           ===================================================== */

        .status-info {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 10px;

            margin-top: 10px;
        }


        .status-option {
            position: relative;
        }


        .status-option input {
            position: absolute;

            opacity: 0;

            pointer-events: none;
        }


        .status-option label {
            border: 1px solid #eadbd8;

            border-radius: 10px;

            padding: 12px;

            text-align: center;

            cursor: pointer;

            margin: 0;

            font-size: 13px;

            background: #fffaf7;

            transition: 0.25s;
        }


        .status-option input:checked + label {
            background: #f8e8e7;

            border-color: #a96771;

            color: #8d555e;
        }


        /* =====================================================
           BUTTONS
           ===================================================== */

        .form-actions {
            display: flex;

            gap: 12px;

            margin-top: 25px;
        }


        .btn {
            border: none;

            border-radius: 10px;

            padding: 13px 20px;

            font-family: "DM Sans", sans-serif;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

            text-decoration: none;

            transition: 0.25s;
        }


        .btn-primary {
            background: #a96771;

            color: white;

            flex: 1;
        }


        .btn-primary:hover {
            background: #8d555e;

            transform: translateY(-1px);
        }


        .btn-secondary {
            background: #f8e8e7;

            color: #8d555e;
        }


        .btn-secondary:hover {
            background: #f3d8d9;
        }


        /* =====================================================
           ALERTS
           ===================================================== */

        .alert {
            max-width: 1150px;

            border-radius: 12px;

            padding: 13px 16px;

            margin-bottom: 20px;

            font-size: 13px;

            font-weight: 600;
        }


        .alert-error {
            background: #fce9e9;

            color: #a34f58;

            border: 1px solid #f1cccc;
        }


        /* =====================================================
           TIPS
           ===================================================== */

        .tips {
            margin-top: 20px;
        }


        .tip {
            display: flex;

            gap: 10px;

            margin-bottom: 13px;

            font-size: 12px;

            color: #806969;

            line-height: 1.5;
        }


        .tip-icon {
            flex-shrink: 0;

            width: 25px;

            height: 25px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f8e8e7;

            border-radius: 50%;
        }


        /* =====================================================
           MOBILE MENU
           ===================================================== */

        .mobile-header {
            display: none;

            background: #fffaf7;

            border-bottom: 1px solid #eadbd8;

            padding: 15px 18px;

            position: sticky;

            top: 0;

            z-index: 1100;

            justify-content: space-between;

            align-items: center;
        }


        .mobile-brand {
            display: flex;

            align-items: center;

            gap: 10px;
        }


        .mobile-brand img {
            width: 40px;

            height: 40px;

            object-fit: contain;
        }


        .mobile-brand strong {
            font-family: "Playfair Display", serif;

            color: #8d555e;
        }


        .menu-btn {
            border: none;

            background: #f8e8e7;

            color: #8d555e;

            width: 40px;

            height: 40px;

            border-radius: 9px;

            font-size: 20px;

            cursor: pointer;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 950px) {

            .sidebar {
                transform: translateX(-100%);

                transition: 0.3s;
            }


            .sidebar.show {
                transform: translateX(0);
            }


            .mobile-header {
                display: flex;
            }


            .main {
                margin-left: 0;

                padding: 24px 18px;
            }


            .form-layout {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 600px) {

            .main {
                padding: 20px 14px;
            }


            .topbar {
                align-items: flex-start;

                flex-direction: column;
            }


            .page-title h1 {
                font-size: 27px;
            }


            .form-row {
                grid-template-columns: 1fr;
            }


            .card {
                padding: 19px;
            }


            .form-actions {
                flex-direction: column;
            }


            .btn {
                width: 100%;

                text-align: center;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     MOBILE HEADER
     ========================================================= -->

<div class="mobile-header">

    <div class="mobile-brand">

        <img
            src="../logo.png"
            alt="The Knot House Logo"
        >

        <strong>
            The Knot House
        </strong>

    </div>


    <button
        class="menu-btn"
        onclick="toggleSidebar()"
        type="button"
    >
        ☰
    </button>

</div>


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<aside class="sidebar" id="sidebar">

    <div class="brand">

        <img
            src="../logo.png"
            alt="The Knot House Logo"
        >

        <div class="brand-text">

            <h2>The Knot House</h2>

            <span>Admin Panel</span>

        </div>

    </div>


    <div class="nav-title">
        Main Menu
    </div>


    <a
        href="dashboard.php"
        class="nav-link"
    >
        <span class="nav-icon">🏠</span>
        Dashboard
    </a>


    <a
        href="products.php"
        class="nav-link active"
    >
        <span class="nav-icon">🧶</span>
        Products
    </a>


    <a
        href="orders.php"
        class="nav-link"
    >
        <span class="nav-icon">📦</span>
        Orders
    </a>


    <a
        href="customers.php"
        class="nav-link"
    >
        <span class="nav-icon">👥</span>
        Customers
    </a>


    <a
        href="inventory.php"
        class="nav-link"
    >
        <span class="nav-icon">📋</span>
        Inventory
    </a>


    <a
        href="feedback.php"
        class="nav-link"
    >
        <span class="nav-icon">💬</span>
        Feedback
    </a>


    <a
        href="profile.php"
        class="nav-link"
    >
        <span class="nav-icon">👤</span>
        Profile
    </a>


    <div
        class="nav-title"
        style="margin-top: 25px;"
    >
        Account
    </div>


    <a
        href="logout.php"
        class="nav-link logout-link"
    >
        <span class="nav-icon">🚪</span>
        Logout
    </a>

</aside>


<!-- =========================================================
     MAIN
     ========================================================= -->

<main class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div class="page-title">

            <h1>
                Edit Product ✏️
            </h1>

            <p>
                Update the details of your crochet product.
            </p>

        </div>


        <a
            href="products.php"
            class="back-btn"
        >
            ← Back to Products
        </a>

    </div>


    <!-- ERROR -->

    <?php if ($error !== ""): ?>

        <div class="alert alert-error">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <!-- FORM -->

    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <div class="form-layout">


            <!-- =================================================
                 LEFT FORM
                 ================================================= -->

            <div class="card">

                <h2 class="card-title">
                    Product Information
                </h2>

                <p class="card-subtitle">
                    Make changes to your product details below.
                </p>


                <!-- PRODUCT NAME -->

                <div class="form-group">

                    <label for="product_name">
                        Product Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="product_name"
                        name="product_name"
                        value="<?php echo htmlspecialchars($product_name); ?>"
                        placeholder="Example: Crochet Gajra"
                        required
                    >

                </div>


                <!-- CATEGORY + PRICE -->

                <div class="form-row">


                    <div class="form-group">

                        <label for="category">
                            Category
                            <span class="required">*</span>
                        </label>

                        <select
                            id="category"
                            name="category"
                            required
                        >

                            <option value="">
                                Select Category
                            </option>

                            <?php foreach ($categories as $cat): ?>

                                <option
                                    value="<?php echo htmlspecialchars($cat); ?>"
                                    <?php
                                    if ($category === $cat) {
                                        echo "selected";
                                    }
                                    ?>
                                >
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="price">
                            Price (₹)
                            <span class="required">*</span>
                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            value="<?php echo htmlspecialchars($price); ?>"
                            min="0"
                            step="0.01"
                            placeholder="899"
                            required
                        >

                    </div>

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group">

                    <label for="description">
                        Product Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Write a short description of your crochet product..."
                    ><?php echo htmlspecialchars($description); ?></textarea>

                    <span class="input-help">
                        Keep the description simple and attractive for customers.
                    </span>

                </div>


                <!-- STOCK + STATUS -->

                <div class="form-row">


                    <div class="form-group">

                        <label for="stock">
                            Stock Quantity
                            <span class="required">*</span>
                        </label>

                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            value="<?php echo htmlspecialchars($stock); ?>"
                            min="0"
                            step="1"
                            placeholder="10"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Product Status
                            <span class="required">*</span>
                        </label>


                        <div class="status-info">

                            <div class="status-option">

                                <input
                                    type="radio"
                                    id="active"
                                    name="status"
                                    value="Active"
                                    <?php
                                    if ($status === "Active") {
                                        echo "checked";
                                    }
                                    ?>
                                >

                                <label for="active">
                                    ✓ Active
                                </label>

                            </div>


                            <div class="status-option">

                                <input
                                    type="radio"
                                    id="inactive"
                                    name="status"
                                    value="Inactive"
                                    <?php
                                    if ($status === "Inactive") {
                                        echo "checked";
                                    }
                                    ?>
                                >

                                <label for="inactive">
                                    ✕ Inactive
                                </label>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="form-actions">

                    <a
                        href="products.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        💾 Update Product
                    </button>

                </div>

            </div>


            <!-- =================================================
                 RIGHT IMAGE CARD
                 ================================================= -->

            <div>


                <div class="card">

                    <h2 class="card-title">
                        Product Image
                    </h2>

                    <p class="card-subtitle">
                        Keep the current image or upload a new one.
                    </p>


                    <div class="image-box">

                        <?php if ($image_preview !== ""): ?>

                            <img
                                id="imagePreview"
                                src="<?php echo htmlspecialchars($image_preview); ?>"
                                alt="Current Product Image"
                                onerror="showNoImage()"
                            >

                            <div
                                id="noImage"
                                class="no-image"
                                style="display:none;"
                            >
                                🧶 Image not available
                            </div>

                        <?php else: ?>

                            <div
                                id="noImage"
                                class="no-image"
                            >
                                🧶 No image available
                            </div>

                            <img
                                id="imagePreview"
                                src=""
                                alt="Image Preview"
                                style="display:none;"
                            >

                        <?php endif; ?>

                    </div>


                    <div class="form-group">

                        <label for="image">
                            Change Product Image
                        </label>

                        <input
                            class="file-input"
                            type="file"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            onchange="previewImage(event)"
                        >

                        <span class="input-help">
                            JPG, JPEG, PNG or WEBP — maximum 5 MB.
                        </span>

                    </div>

                </div>


                <!-- TIPS -->

                <div class="card tips">

                    <h2 class="card-title">
                        💡 Quick Tips
                    </h2>

                    <br>


                    <div class="tip">

                        <span class="tip-icon">
                            🧶
                        </span>

                        <span>
                            Use clear names such as
                            <b>Crochet Gajra</b> or
                            <b>Handmade Crochet Tote</b>.
                        </span>

                    </div>


                    <div class="tip">

                        <span class="tip-icon">
                            📸
                        </span>

                        <span>
                            Use a clear product image with good lighting.
                        </span>

                    </div>


                    <div class="tip">

                        <span class="tip-icon">
                            📦
                        </span>

                        <span>
                            Keep stock quantity updated so customers see correct availability.
                        </span>

                    </div>


                    <div class="tip">

                        <span class="tip-icon">
                            ✓
                        </span>

                        <span>
                            Set the product to <b>Active</b> when you want it visible on the customer Products page.
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </form>

</main>


<script>

/* =========================================================
   MOBILE SIDEBAR
   ========================================================= */

function toggleSidebar() {

    const sidebar =
        document.getElementById("sidebar");

    sidebar.classList.toggle("show");

}


/* =========================================================
   IMAGE PREVIEW
   ========================================================= */

function previewImage(event) {

    const file =
        event.target.files[0];

    const preview =
        document.getElementById("imagePreview");

    const noImage =
        document.getElementById("noImage");


    if (!file) {
        return;
    }


    const reader =
        new FileReader();


    reader.onload = function(e) {

        preview.src =
            e.target.result;

        preview.style.display =
            "block";


        if (noImage) {

            noImage.style.display =
                "none";

        }

    };


    reader.readAsDataURL(file);

}


/* =========================================================
   IMAGE ERROR
   ========================================================= */

function showNoImage() {

    const preview =
        document.getElementById("imagePreview");

    const noImage =
        document.getElementById("noImage");


    if (preview) {

        preview.style.display =
            "none";

    }


    if (noImage) {

        noImage.style.display =
            "flex";

    }

}

</script>


</body>
</html>

<?php
$conn->close();
?>