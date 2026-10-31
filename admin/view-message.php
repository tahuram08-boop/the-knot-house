<?php

/* =========================================================
   THE KNOT HOUSE - VIEW CUSTOMER MESSAGE
   ========================================================= */

session_start();

/* DATABASE CONNECTION */

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
   CHECK MESSAGE ID
   ========================================================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    die("Invalid message ID.");

}

$id = (int) $_GET["id"];


/* =========================================================
   MARK MESSAGE AS READ
   Only unread messages will change to read.
   ========================================================= */

$updateSql = "
    UPDATE contact_messages
    SET status = 'read'
    WHERE id = ?
    AND status = 'unread'
";

$updateStmt = $conn->prepare($updateSql);

if ($updateStmt) {

    $updateStmt->bind_param("i", $id);
    $updateStmt->execute();
    $updateStmt->close();

}


/* =========================================================
   GET MESSAGE
   ========================================================= */

$sql = "
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
    WHERE id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Unable to load message.");
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();
    $conn->close();

    die("Message not found.");

}

$row = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        View Message | The Knot House Admin
    </title>


    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
          rel="stylesheet">


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            font-family: "DM Sans", sans-serif;
            background: #f8e8e7;
            color: #513d3d;
            min-height: 100vh;
        }


        /* =========================
           HEADER
           ========================= */

        .header {
            background: #fffaf7;
            padding: 18px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 3px 15px rgba(81, 61, 61, 0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }


        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #513d3d;
        }


        .logo img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }


        .logo-text {
            font-family: "Playfair Display", serif;
            font-size: 24px;
            font-weight: 700;
        }


        .back-btn {
            text-decoration: none;
            background: #a96771;
            color: white;
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
        }


        .back-btn:hover {
            background: #8d555e;
            transform: translateY(-2px);
        }


        /* =========================
           MAIN
           ========================= */

        .container {
            width: 92%;
            max-width: 900px;
            margin: 40px auto;
        }


        .page-title {
            text-align: center;
            margin-bottom: 30px;
        }


        .page-title h1 {
            font-family: "Playfair Display", serif;
            font-size: 38px;
            color: #8d555e;
        }


        /* =========================
           MESSAGE CARD
           ========================= */

        .message-card {
            background: #fffaf7;
            border-radius: 22px;
            padding: 35px;
            box-shadow: 0 8px 30px rgba(81, 61, 61, 0.08);
        }


        /* =========================
           CUSTOMER INFO
           ========================= */

        .customer-info {
            background: #f8e8e7;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }


        .info-row {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            line-height: 1.5;
        }


        .info-row:last-child {
            margin-bottom: 0;
        }


        .info-label {
            font-weight: 700;
            min-width: 90px;
            color: #8d555e;
        }


        .info-value {
            color: #513d3d;
            word-break: break-word;
        }


        /* =========================
           SUBJECT
           ========================= */

        .subject-box {
            margin-bottom: 25px;
        }


        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #8d555e;
            margin-bottom: 10px;
        }


        .subject {
            font-size: 20px;
            font-family: "Playfair Display", serif;
            color: #513d3d;
        }


        /* =========================
           MESSAGE
           ========================= */

        .message-box {
            background: #fff;
            border: 1px solid #eadbd8;
            border-radius: 15px;
            padding: 22px;
            margin-bottom: 25px;
        }


        .message-text {
            line-height: 1.8;
            white-space: pre-wrap;
            color: #6d5d5d;
        }


        /* =========================
           STATUS
           ========================= */

        .status-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }


        .status-label {
            font-weight: 700;
        }


        .status {
            display: inline-block;
            padding: 6px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: capitalize;
        }


        .status-read {
            background: #e8eefc;
            color: #526da8;
        }


        .status-replied {
            background: #e4f6e9;
            color: #38804b;
        }


        .status-unread {
            background: #fff0c9;
            color: #9a6b00;
        }


        /* =========================
           ADMIN REPLY
           ========================= */

        .reply-box {
            background: #fdf0ef;
            border-left: 5px solid #a96771;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }


        .reply-box h3 {
            color: #8d555e;
            margin-bottom: 10px;
        }


        .reply-text {
            color: #6d5d5d;
            line-height: 1.7;
            white-space: pre-wrap;
        }


        .no-reply {
            background: #fff4f1;
            border: 1px dashed #d9b9b5;
            border-radius: 12px;
            padding: 20px;
            color: #8d555e;
            text-align: center;
            margin-top: 20px;
        }


        /* =========================
           ACTIONS
           ========================= */

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            flex-wrap: wrap;
        }


        .btn {
            display: inline-block;
            text-decoration: none;
            border: none;
            cursor: pointer;
            padding: 11px 20px;
            border-radius: 25px;
            font-family: "DM Sans", sans-serif;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s;
        }


        .btn-primary {
            background: #a96771;
            color: white;
        }


        .btn-primary:hover {
            background: #8d555e;
            transform: translateY(-2px);
        }


        .btn-secondary {
            background: #eadbd8;
            color: #513d3d;
        }


        .btn-secondary:hover {
            background: #dec9c5;
        }


        /* =========================
           MOBILE
           ========================= */

        @media (max-width: 600px) {

            .header {
                padding: 15px 4%;
            }


            .logo-text {
                font-size: 19px;
            }


            .logo img {
                width: 42px;
                height: 42px;
            }


            .back-btn {
                padding: 8px 12px;
                font-size: 12px;
            }


            .container {
                width: 94%;
                margin: 25px auto;
            }


            .page-title h1 {
                font-size: 30px;
            }


            .message-card {
                padding: 20px;
                border-radius: 17px;
            }


            .info-row {
                flex-direction: column;
                gap: 3px;
            }


            .info-label {
                min-width: auto;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
     ========================= -->

<header class="header">

    <a href="../index.html" class="logo">

        <img
            src="../logo.png"
            alt="The Knot House Logo"
        >

        <span class="logo-text">
            The Knot House
        </span>

    </a>


    <a
        href="messages.php"
        class="back-btn"
    >
        ← All Messages
    </a>

</header>



<!-- =========================
     MAIN
     ========================= -->

<main class="container">


    <div class="page-title">

        <h1>💌 Customer Message</h1>

    </div>



    <div class="message-card">


        <!-- CUSTOMER INFORMATION -->

        <div class="customer-info">


            <div class="info-row">

                <span class="info-label">
                    Name:
                </span>

                <span class="info-value">

                    <?php
                    echo htmlspecialchars($row["name"]);
                    ?>

                </span>

            </div>



            <div class="info-row">

                <span class="info-label">
                    Email:
                </span>

                <span class="info-value">

                    <?php
                    echo htmlspecialchars($row["email"]);
                    ?>

                </span>

            </div>



            <div class="info-row">

                <span class="info-label">
                    Phone:
                </span>

                <span class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $row["phone"] ?: "Not provided"
                    );

                    ?>

                </span>

            </div>



            <div class="info-row">

                <span class="info-label">
                    Sent:
                </span>

                <span class="info-value">

                    <?php

                    echo date(
                        "d M Y, h:i A",
                        strtotime($row["created_at"])
                    );

                    ?>

                </span>

            </div>


        </div>



        <!-- SUBJECT -->

        <div class="subject-box">

            <div class="section-title">
                Subject
            </div>

            <div class="subject">

                <?php
                echo htmlspecialchars($row["subject"]);
                ?>

            </div>

        </div>



        <!-- CUSTOMER MESSAGE -->

        <div class="section-title">
            Customer Message
        </div>


        <div class="message-box">

            <div class="message-text">

                <?php

                echo htmlspecialchars(
                    $row["message"]
                );

                ?>

            </div>

        </div>



        <!-- STATUS -->

        <div class="status-row">

            <span class="status-label">
                Status:
            </span>


            <?php

            $status = $row["status"];

            $statusClass = "status-unread";

            if ($status === "read") {
                $statusClass = "status-read";
            }

            if ($status === "replied") {
                $statusClass = "status-replied";
            }

            ?>


            <span class="status <?php echo $statusClass; ?>">

                <?php
                echo htmlspecialchars($status);
                ?>

            </span>

        </div>



        <!-- ADMIN REPLY -->

        <?php if (!empty($row["admin_reply"])): ?>


            <div class="reply-box">

                <h3>
                    💗 Admin Reply
                </h3>


                <div class="reply-text">

                    <?php

                    echo htmlspecialchars(
                        $row["admin_reply"]
                    );

                    ?>

                </div>


                <?php if (!empty($row["replied_at"])): ?>

                    <small>

                        Replied on:

                        <?php

                        echo date(
                            "d M Y, h:i A",
                            strtotime($row["replied_at"])
                        );

                        ?>

                    </small>

                <?php endif; ?>


            </div>


        <?php else: ?>


            <div class="no-reply">

                💬 No reply has been sent yet.

            </div>


        <?php endif; ?>



        <!-- ACTION BUTTONS -->

        <div class="actions">


            <a
                href="messages.php"
                class="btn btn-secondary"
            >
                ← Back to Messages
            </a>


            <a
                href="reply-message.php?id=<?php echo (int)$row["id"]; ?>"
                class="btn btn-primary"
            >
                💌 Reply to Customer
            </a>


        </div>


    </div>


</main>


</body>

</html>


<?php

$conn->close();

?>