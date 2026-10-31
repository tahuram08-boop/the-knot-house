
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

// Get customer details
$stmt = $conn->prepare(
    "SELECT full_name, email
     FROM customers
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to load customer details.");
}

$stmt->bind_param("i", $customerId);
$stmt->execute();

$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

// Check customer record
if (!$customer) {
    $conn->close();

    $_SESSION = [];
    session_destroy();

    header("Location: login.php");
    exit();
}

$fullName = $customer["full_name"];
$email = $customer["email"];

// Safely display values
function escapeOutput($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Fetch this customer's orders
$stmt = $conn->prepare(
    "SELECT id, total_amount, payment_method,
            payment_status, order_status, created_at
     FROM orders
     WHERE LOWER(TRIM(customer_email)) = LOWER(TRIM(?))
     ORDER BY created_at DESC, id DESC"
);

if (!$stmt) {
    die("Unable to load order history.");
}

$stmt->bind_param("s", $email);
$stmt->execute();

$ordersResult = $stmt->get_result();

$orders = [];

while ($order = $ordersResult->fetch_assoc()) {
    $orders[] = $order;
}

$stmt->close();

// Fetch products for each order
$itemStmt = $conn->prepare(
    "SELECT product_name, price, quantity, subtotal
     FROM order_items
     WHERE order_id = ?
     ORDER BY id ASC"
);

if (!$itemStmt) {
    $conn->close();
    die("Unable to load order items.");
}

foreach ($orders as &$order) {
    $orderId = (int) $order["id"];

    $itemStmt->bind_param("i", $orderId);
    $itemStmt->execute();

    $itemsResult = $itemStmt->get_result();

    $order["items"] = [];

    while ($item = $itemsResult->fetch_assoc()) {
        $order["items"][] = $item;
    }
}
unset($order);

$itemStmt->close();
$conn->close();

// Format order date
function formatOrderDate($date) {
    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return "Date unavailable";
    }

    return date("d M Y, h:i A", $timestamp);
}

// Status styling
function statusClass($status) {
    $status = strtolower(trim((string) $status));

    switch ($status) {
        case "delivered":
            return "delivered";

        case "shipped":
            return "shipped";

        case "processing":
            return "processing";

        case "confirmed":
            return "confirmed";

        case "cancelled":
            return "cancelled";

        case "pending":
            return "pending";

        default:
            return "pending";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My History | The Knot House</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    <style>
        :root {
            --pink: #a96771;
            --pink-dark: #8d555e;
            --pink-light: #f8e8e7;
            --cream: #fffaf7;
            --brown: #513d3d;
            --brown-light: #806969;
            --border: #ead9d6;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: "DM Sans", sans-serif;
            background: var(--cream);
            color: var(--brown);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(255, 250, 247, 0.96);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }

        .navbar {
            max-width: 1200px;
            margin: auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: "Playfair Display", serif;
            font-size: 22px;
            font-weight: 600;
        }

        .logo img {
            width: 45px;
            height: 45px;
            object-fit: contain;
            border-radius: 50%;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 22px;
            align-items: center;
        }

        .nav-links a {
            font-size: 14px;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: var(--pink);
        }

        .logout-btn {
            padding: 10px 16px;
            border: 1px solid var(--pink);
            border-radius: 8px;
            color: var(--pink-dark);
        }

        .page {
            max-width: 1100px;
            margin: 45px auto;
            padding: 0 22px;
        }

        .welcome {
            margin-bottom: 30px;
        }

        .eyebrow {
            color: var(--pink-dark);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        h1, h2, h3 {
            font-family: "Playfair Display", serif;
        }

        h1 {
            font-size: clamp(30px, 5vw, 42px);
            margin-bottom: 10px;
        }

        .welcome p {
            color: var(--brown-light);
            line-height: 1.7;
        }

        .account-layout {
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            gap: 25px;
            align-items: start;
        }

        .sidebar,
        .history-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(141, 85, 94, 0.05);
        }

        .sidebar {
            padding: 20px;
        }

        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 18px;
        }

        .sidebar a {
            display: block;
            padding: 12px;
            margin: 4px 0;
            border-radius: 8px;
            font-size: 14px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: var(--pink-light);
            color: var(--pink-dark);
        }

        .history-card {
            padding: 28px;
            min-width: 0;
        }

        .history-heading {
            padding-bottom: 22px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .history-heading h2 {
            font-size: 26px;
            margin-bottom: 8px;
        }

        .history-heading p {
            color: var(--brown-light);
            font-size: 14px;
            line-height: 1.7;
        }

        .order-count {
            display: inline-block;
            margin-top: 14px;
            padding: 7px 12px;
            background: var(--pink-light);
            color: var(--pink-dark);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .order-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 22px;
            overflow: hidden;
        }

        .order-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
            padding: 18px;
            background: var(--cream);
            border-bottom: 1px solid var(--border);
        }

        .order-number {
            font-size: 18px;
            margin-bottom: 6px;
            overflow-wrap: anywhere;
        }

        .order-date {
            color: var(--brown-light);
            font-size: 12px;
        }

        .status {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .status.pending {
            background: #fff0d7;
            color: #8a5b12;
        }

        .status.confirmed {
            background: #e7edff;
            color: #344f9c;
        }

        .status.processing {
            background: #f3e8ff;
            color: #7441a6;
        }

        .status.shipped {
            background: #e2f4ff;
            color: #17658c;
        }

        .status.delivered {
            background: #e3f5e8;
            color: #28683a;
        }

        .status.cancelled {
            background: #fde7e7;
            color: #a52b2b;
        }

        .order-body {
            padding: 18px;
        }

        .items-title {
            font-size: 17px;
            margin-bottom: 14px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding: 13px 0;
            border-bottom: 1px solid var(--border);
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .item-details {
            margin-top: 5px;
            font-size: 12px;
            color: var(--brown-light);
            line-height: 1.6;
        }

        .item-price {
            font-weight: 700;
            font-size: 14px;
            white-space: nowrap;
        }

        .order-summary {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 7px 0;
            font-size: 13px;
        }

        .summary-row span:first-child {
            color: var(--brown-light);
        }

        .summary-row.total {
            margin-top: 5px;
            padding-top: 13px;
            border-top: 1px dashed var(--border);
            font-size: 17px;
            font-weight: 700;
        }

        .summary-row.total span:first-child {
            color: var(--brown);
        }

        .empty-history {
            text-align: center;
            padding: 45px 18px;
            background: var(--cream);
            border: 1px dashed var(--border);
            border-radius: 12px;
        }

        .empty-icon {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .empty-history h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .empty-history p {
            color: var(--brown-light);
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .shop-link,
        .back-link {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 8px;
            background: var(--pink);
            color: var(--white);
            font-size: 14px;
            font-weight: 600;
        }

        .shop-link:hover,
        .back-link:hover {
            background: var(--pink-dark);
        }

        .back-link {
            margin-top: 8px;
        }

        footer {
            margin-top: 50px;
            padding: 22px;
            border-top: 1px solid var(--border);
            color: var(--brown-light);
            text-align: center;
            font-size: 13px;
        }

        @media (max-width: 760px) {
            .navbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-links {
                gap: 14px;
            }

            .account-layout {
                grid-template-columns: 1fr;
            }

            .history-card {
                padding: 18px;
            }

            .order-top,
            .order-body {
                padding: 14px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="navbar">

        <a href="index.html" class="logo">
            <img src="logo.png" alt="The Knot House Logo">
            <span>The Knot House</span>
        </a>

        <nav class="nav-links" aria-label="Main navigation">
            <a href="index.html">Home</a>
            <a href="product.html">Products</a>
            <a href="my-account.php">My Account</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

    </div>
</header>

<main class="page">

    <section class="welcome">

        <div class="eyebrow">Your personal space</div>

        <h1>My History 🕘</h1>

        <p>
            Hello, <?= escapeOutput($fullName) ?>!
            View all your previous orders at The Knot House.
        </p>

    </section>

    <div class="account-layout">

        <!-- Account Sidebar -->
        <aside class="sidebar">

            <h2>My Account</h2>

            <a href="my-account.php">
                👤 My Profile
            </a>

            <a href="my-orders.php">
                📦 My Orders
            </a>

            <a href="my-favourites.php">
                ♡ My Favourites
            </a>

            <a href="my-addresses.php">
                📍 My Addresses
            </a>

            <a href="my-history.php" class="active">
                🕘 My History
            </a>

            <a href="profile.php">
                ✏️ Edit Profile
            </a>

            <a href="logout.php">
                ↪ Logout
            </a>

        </aside>

        <!-- Order History -->
        <section class="history-card">

            <div class="history-heading">

                <h2>Your Order History</h2>

                <p>
                    Find your previous purchases, order dates,
                    payment information, and order status here.
                </p>

                <span class="order-count">
                    <?= count($orders) ?>
                    <?= count($orders) === 1 ? "Order" : "Orders" ?>
                </span>

            </div>

            <?php if (empty($orders)): ?>

                <div class="empty-history">

                    <div class="empty-icon">🧶</div>

                    <h3>No Orders Yet</h3>

                    <p>
                        You haven't placed any orders yet.
                        Explore our handmade crochet collection
                        and find something you love!
                    </p>

                    <a href="product.html" class="shop-link">
                        Explore Products →
                    </a>

                </div>

            <?php else: ?>

                <?php foreach ($orders as $order): ?>

                    <article class="order-card">

                        <div class="order-top">

                            <div>
                                <h3 class="order-number">
                                    Order #<?= (int) $order["id"] ?>
                                </h3>

                                <p class="order-date">
                                    Placed on
                                    <?= escapeOutput(
                                        formatOrderDate($order["created_at"])
                                    ) ?>
                                </p>
                            </div>

                            <span class="status <?= escapeOutput(
                                statusClass($order["order_status"])
                            ) ?>">
                                <?= escapeOutput(
                                    $order["order_status"] ?: "Pending"
                                ) ?>
                            </span>

                        </div>

                        <div class="order-body">

                            <h3 class="items-title">
                                Order Items
                            </h3>

                            <?php if (empty($order["items"])): ?>

                                <p class="item-details">
                                    No product details are available
                                    for this order.
                                </p>

                            <?php else: ?>

                                <?php foreach ($order["items"] as $item): ?>

                                    <div class="item-row">

                                        <div>
                                            <div class="item-name">
                                                <?= escapeOutput(
                                                    $item["product_name"]
                                                ) ?>
                                            </div>

                                            <div class="item-details">
                                                Quantity:
                                                <?= (int) $item["quantity"] ?>
                                                <br>

                                                Price per item:
                                                ₹<?= number_format(
                                                    (float) $item["price"],
                                                    2
                                                ) ?>
                                            </div>
                                        </div>

                                        <div class="item-price">
                                            ₹<?= number_format(
                                                (float) $item["subtotal"],
                                                2
                                            ) ?>
                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            <?php endif; ?>

                            <div class="order-summary">

                                <div class="summary-row">
                                    <span>Payment Method</span>

                                    <strong>
                                        <?= escapeOutput(
                                            $order["payment_method"]
                                            ?: "Not specified"
                                        ) ?>
                                    </strong>
                                </div>

                                <div class="summary-row">
                                    <span>Payment Status</span>

                                    <strong>
                                        <?= escapeOutput(
                                            $order["payment_status"]
                                            ?: "Not specified"
                                        ) ?>
                                    </strong>
                                </div>

                                <div class="summary-row total">
                                    <span>Total Amount</span>

                                    <span>
                                        ₹<?= number_format(
                                            (float) $order["total_amount"],
                                            2
                                        ) ?>
                                    </span>
                                </div>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

            <a href="my-account.php" class="back-link">
                ← Back to My Account
            </a>

        </section>

    </div>

</main>

<footer>
    © 2026 The Knot House. Handmade with love 🩷
</footer>

</body>
</html>