<?php
session_start();

// Only logged-in customers can view their orders
if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "knot_house");

if ($conn->connect_error) {
    die("Database connection failed. Please check XAMPP.");
}

$conn->set_charset("utf8mb4");

// Get the logged-in customer's information
$customerEmail = $_SESSION["customer_email"] ?? "";
$customerName = $_SESSION["customer_name"] ?? "Customer";

// Find orders belonging to this customer
$sql = "SELECT id, customer_name, total_amount,
               payment_method, payment_status,
               order_status, created_at
        FROM orders
        WHERE customer_email = ?
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $conn->close();
    die("Unable to load your orders. Please try again.");
}

$stmt->bind_param("s", $customerEmail);
$stmt->execute();

$result = $stmt->get_result();
$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$stmt->close();
$conn->close();

// Safely display text in HTML
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Choose a CSS class for the order status
function statusClass($status) {
    $status = strtolower(trim((string)$status));

    if ($status === "delivered") {
        return "delivered";
    }

    if ($status === "cancelled") {
        return "cancelled";
    }

    if ($status === "shipped" || $status === "out for delivery") {
        return "shipped";
    }

    if (
        $status === "confirmed" ||
        $status === "processing" ||
        $status === "packed"
    ) {
        return "processing";
    }

    return "pending";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Orders | The Knot House</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
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
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "DM Sans", sans-serif;
            background: var(--cream);
            color: var(--brown);
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(255, 250, 247, 0.97);
            border-bottom: 1px solid var(--border);
        }

        .navbar {
            max-width: 1150px;
            margin: auto;
            padding: 13px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .logo img {
            width: 45px;
            height: 45px;
            object-fit: contain;
        }

        .logo span {
            font-family: "Playfair Display", serif;
            color: var(--pink-dark);
            font-size: 22px;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: var(--pink);
        }

        .main {
            max-width: 1050px;
            width: 92%;
            margin: 40px auto 65px;
        }

        .page-heading {
            padding: 30px;
            background: linear-gradient(135deg, #f3d8d9, #fff0e8);
            border: 1px solid var(--border);
            border-radius: 17px;
            margin-bottom: 28px;
        }

        .eyebrow {
            color: var(--pink-dark);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 9px;
        }

        h1 {
            font-family: "Playfair Display", serif;
            font-size: 34px;
            margin-bottom: 9px;
        }

        .page-heading p {
            font-size: 14px;
            line-height: 1.7;
            color: var(--brown-light);
        }

        .orders-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .orders-heading h2 {
            font-family: "Playfair Display", serif;
            font-size: 24px;
        }

        .order-count {
            background: var(--pink-light);
            color: var(--pink-dark);
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .order-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            margin-bottom: 18px;
            overflow: hidden;
        }

        .order-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            padding: 20px 23px;
            background: #fffdfb;
            border-bottom: 1px solid var(--border);
        }

        .order-number {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 6px;
        }

        .order-date {
            color: var(--brown-light);
            font-size: 12px;
        }

        .status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .pending {
            background: #fff0ce;
            color: #89651a;
        }

        .processing {
            background: #e8e7ff;
            color: #5144a0;
        }

        .shipped {
            background: #dff1ff;
            color: #24668d;
        }

        .delivered {
            background: #e1f5e8;
            color: #267448;
        }

        .cancelled {
            background: #fde4e4;
            color: #a33b3b;
        }

        .order-body {
            padding: 22px 23px;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .info-label {
            display: block;
            color: var(--brown-light);
            font-size: 11px;
            margin-bottom: 7px;
        }

        .info-value {
            font-size: 13px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .order-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 23px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
        }

        .total-label {
            color: var(--brown-light);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .total-amount {
            color: var(--pink-dark);
            font-size: 23px;
            font-weight: 700;
        }

        .button {
            display: inline-block;
            padding: 11px 16px;
            background: var(--pink);
            color: white;
            border: 0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .button:hover {
            background: var(--pink-dark);
        }

        .empty-state {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            text-align: center;
            padding: 50px 22px;
        }

        .empty-icon {
            font-size: 52px;
            margin-bottom: 15px;
        }

        .empty-state h2 {
            font-family: "Playfair Display", serif;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--brown-light);
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        footer {
            padding: 25px 15px;
            text-align: center;
            border-top: 1px solid var(--border);
            color: var(--brown-light);
            font-size: 12px;
        }

        @media (max-width: 650px) {
            .navbar {
                flex-wrap: wrap;
            }

            .nav-links {
                gap: 13px;
                flex-wrap: wrap;
            }

            .logo span {
                font-size: 19px;
            }

            .page-heading {
                padding: 24px 20px;
            }

            h1 {
                font-size: 28px;
            }

            .order-info {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .order-top,
            .order-body {
                padding: 18px;
            }

            .order-bottom .button {
                width: 100%;
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

        <nav class="nav-links">
            <a href="index.html">Home</a>
            <a href="product.html">Products</a>
            <a href="my-account.php">My Account</a>
            <a href="logout.php">Logout</a>
        </nav>

    </div>
</header>

<main class="main">

    <section class="page-heading">
        <div class="eyebrow">YOUR SHOPPING JOURNEY</div>

        <h1>My Orders 📦</h1>

        <p>
            Hello, <?= h($customerName) ?>!
            Here you can view your previous orders and check their status.
            Thank you for supporting handmade crochet.
        </p>
    </section>

    <div class="orders-heading">
    <h2>Order History</h2>

    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">

        <span class="order-count">
            <?= count($orders) ?>
            <?= count($orders) === 1 ? "Order" : "Orders" ?>
        </span>

        <a href="my-account.php" class="button">
            ← Back to My Account
        </a>

    </div>
</div>

    <?php if (!empty($orders)): ?>

        <?php foreach ($orders as $order): ?>

            <article class="order-card">

                <div class="order-top">

                    <div>
                        <div class="order-number">
                            Order #<?= h($order["id"]) ?>
                        </div>

                        <div class="order-date">
                            Placed on
                            <?php
                            $timestamp = strtotime($order["created_at"]);
                            echo $timestamp
                                ? h(date("d M Y, h:i A", $timestamp))
                                : "Date unavailable";
                            ?>
                        </div>
                    </div>

                    <span class="status <?= h(statusClass($order["order_status"])) ?>">
                        <?= h($order["order_status"] ?: "Pending") ?>
                    </span>

                </div>

                <div class="order-body">

                    <div class="order-info">

                        <div>
                            <span class="info-label">Customer</span>
                            <span class="info-value">
                                <?= h($order["customer_name"]) ?>
                            </span>
                        </div>

                        <div>
                            <span class="info-label">Payment Method</span>
                            <span class="info-value">
                                <?= h($order["payment_method"]) ?>
                            </span>
                        </div>

                        <div>
                            <span class="info-label">Payment Status</span>
                            <span class="info-value">
                                <?= h($order["payment_status"]) ?>
                            </span>
                        </div>

                    </div>

                    <div class="order-bottom">

                        <div>
                            <div class="total-label">Order Total</div>

                            <div class="total-amount">
                                ₹<?= number_format((float)$order["total_amount"], 2) ?>
                            </div>
                        </div>

                        <a
                            class="button"
                            href="track-order.html?order_id=<?= rawurlencode((string)$order["id"]) ?>&amp;email=<?= rawurlencode($customerEmail) ?>"
                        >
                            Track Order →
                        </a>

                    </div>

                </div>

            </article>

        <?php endforeach; ?>

    <?php else: ?>

        <section class="empty-state">

            <div class="empty-icon">🧶</div>

            <h2>No Orders Yet</h2>

            <p>
                You haven't placed any orders using this account yet.
                Explore our handmade crochet collection and find something
                special for yourself or someone you love.
            </p>

            <a href="product.html" class="button">
                Explore Products
            </a>

        </section>

    <?php endif; ?>

</main>

<footer>
    © <?= date("Y") ?> The Knot House. Handmade with love 🧶
</footer>

</body>
</html>
