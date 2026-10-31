<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$username = "root";
$password = "";
$database = "knot_house";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$adminName = $_SESSION["admin_name"] ?? "Admin";
$adminInitial = strtoupper(substr(trim($adminName), 0, 1));

if ($adminInitial === "") {
    $adminInitial = "A";
}

/* Get all messages */
$messages = [];

$result = $conn->query("SELECT * FROM contact_messages ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>All Messages | The Knot House Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --pink: #a96771;
            --dark-pink: #8d555e;
            --light-pink: #f8e8e7;
            --soft-pink: #f3d8d9;
            --cream: #fffaf7;
            --white: #ffffff;
            --brown: #513d3d;
            --brown-light: #806969;
            --border: #eadbd8;
            --green: #4d9569;
            --green-light: #e8f5ed;
            --red: #b85c5c;
            --red-light: #fbeaea;
            --sidebar-width: 255px;
        }

        body {
            font-family: "DM Sans", sans-serif;
            background: #fdf7f5;
            color: var(--brown);
            min-height: 100vh;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--white);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .brand {
            padding: 25px 20px;
            border-bottom: 1px solid var(--border);
        }

        .brand h1 {
            font-family: "Playfair Display", serif;
            font-size: 24px;
            color: var(--pink);
            margin-bottom: 5px;
        }

        .brand p {
            font-size: 11px;
            letter-spacing: 2px;
            color: var(--brown-light);
        }

        .sidebar-nav {
            padding: 20px 14px;
            flex: 1;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 15px;
            margin-bottom: 6px;
            text-decoration: none;
            color: var(--brown-light);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        .nav-link:hover {
            background: var(--light-pink);
            color: var(--pink);
        }

        .nav-link.active {
            background: var(--light-pink);
            color: var(--pink);
        }

        .nav-icon {
            width: 25px;
            text-align: center;
            font-size: 18px;
        }

        .admin-box {
            padding: 18px;
            border-top: 1px solid var(--border);
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--pink);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .admin-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--brown);
        }

        .admin-role {
            font-size: 11px;
            color: var(--brown-light);
        }

        .logout-btn {
            display: block;
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            background: var(--red-light);
            color: var(--red);
            font-size: 13px;
            font-weight: 600;
        }

        .logout-btn:hover {
            background: #f5d7d7;
        }

        /* MAIN */

        .main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 35px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title h2 {
            font-family: "Playfair Display", serif;
            font-size: 32px;
            color: var(--brown);
            margin-bottom: 5px;
        }

        .page-title p {
            color: var(--brown-light);
            font-size: 14px;
        }

        .back-btn {
            display: inline-block;
            text-decoration: none;
            padding: 11px 18px;
            background: var(--white);
            color: var(--pink);
            border: 1px solid var(--border);
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
        }

        .back-btn:hover {
            background: var(--light-pink);
        }

        /* MESSAGE CARD */

        .messages-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            overflow: hidden;
        }

        .card-header {
            padding: 22px 25px;
            border-bottom: 1px solid var(--border);
        }

        .card-header h3 {
            font-family: "Playfair Display", serif;
            font-size: 21px;
            color: var(--brown);
            margin-bottom: 5px;
        }

        .card-header p {
            color: var(--brown-light);
            font-size: 13px;
        }

        .message-list {
            padding: 10px 25px 25px;
        }

        .message-item {
            padding: 20px 0;
            border-bottom: 1px solid var(--border);
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-top {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 10px;
        }

        .customer-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--brown);
        }

        .message-date {
            font-size: 12px;
            color: var(--brown-light);
            white-space: nowrap;
        }

        .customer-email {
            font-size: 13px;
            color: var(--pink);
            margin-bottom: 10px;
        }

        .message-subject {
            font-size: 14px;
            font-weight: 700;
            color: var(--brown);
            margin-bottom: 7px;
        }

        .message-text {
            font-size: 14px;
            line-height: 1.7;
            color: var(--brown-light);
        }

        .empty-message {
            text-align: center;
            padding: 60px 20px;
            color: var(--brown-light);
        }

        .empty-message .icon {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .empty-message h3 {
            font-family: "Playfair Display", serif;
            font-size: 22px;
            color: var(--brown);
            margin-bottom: 7px;
        }

        /* RESPONSIVE */

        @media (max-width: 800px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                padding: 25px;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        @media (max-width: 600px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .message-top {
                flex-direction: column;
                gap: 5px;
            }

            .message-date {
                white-space: normal;
            }
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="brand">
        <h1>🧶 The Knot House</h1>
        <p>ADMIN PANEL</p>
    </div>

    <nav class="sidebar-nav">

        <a href="dashboard.php" class="nav-link">
            <span class="nav-icon">📊</span>
            Dashboard
        </a>

        <a href="products.php" class="nav-link">
            <span class="nav-icon">🛍️</span>
            Products
        </a>

        <a href="orders.php" class="nav-link">
            <span class="nav-icon">📦</span>
            Orders
        </a>

        <a href="message.php" class="nav-link active">
            <span class="nav-icon">💌</span>
            Messages
        </a>

        <a href="products.php" class="nav-link">
            <span class="nav-icon">📋</span>
            Inventory
        </a>

        <a href="../index.html" class="nav-link">
            <span class="nav-icon">🏠</span>
            View Store
        </a>

        <a href="add-product.php" class="nav-link">
            <span class="nav-icon">➕</span>
            Add Product
        </a>

    </nav>

    <div class="admin-box">

        <div class="admin-info">

            <div class="admin-avatar">
                <?php echo htmlspecialchars($adminInitial); ?>
            </div>

            <div>
                <div class="admin-name">
                    <?php echo htmlspecialchars($adminName); ?>
                </div>

                <div class="admin-role">
                    Administrator
                </div>
            </div>

        </div>

        <a href="logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</aside>


<!-- MAIN CONTENT -->

<main class="main">

    <div class="top-bar">

        <div class="page-title">

            <h2>All Messages</h2>

            <p>
                View messages received from your customers.
            </p>

        </div>

        <a href="message.php" class="back-btn">
            ← Back to Messages
        </a>

    </div>


    <div class="messages-card">

        <div class="card-header">

            <h3>💌 Customer Messages</h3>

            <p>
                All contact messages received from The Knot House website.
            </p>

        </div>


        <div class="message-list">

            <?php if (count($messages) > 0): ?>

                <?php foreach ($messages as $message): ?>

                    <div class="message-item">

                        <div class="message-top">

                            <div class="customer-name">
                                <?php
                                echo htmlspecialchars(
                                    $message["name"] ??
                                    $message["customer_name"] ??
                                    "Customer"
                                );
                                ?>
                            </div>

                            <div class="message-date">
                                <?php
                                echo htmlspecialchars(
                                    $message["created_at"] ??
                                    $message["date"] ??
                                    ""
                                );
                                ?>
                            </div>

                        </div>


                        <div class="customer-email">

                            <?php
                            echo htmlspecialchars(
                                $message["email"] ??
                                $message["customer_email"] ??
                                ""
                            );
                            ?>

                        </div>


                        <div class="message-subject">

                            <?php
                            echo htmlspecialchars(
                                $message["subject"] ??
                                "Customer Message"
                            );
                            ?>

                        </div>


                        <div class="message-text">

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $message["message"] ??
                                    $message["content"] ??
                                    ""
                                )
                            );
                            ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-message">

                    <div class="icon">💌</div>

                    <h3>No Messages Found</h3>

                    <p>
                        There are currently no customer messages.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>

</body>
</html>

<?php
$conn->close();
?>