<?php

/* =========================================================
   THE KNOT HOUSE - REPLY TO CUSTOMER MESSAGE
   ========================================================= */

session_start();


/* =========================
   DATABASE CONNECTION
   ========================= */

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


/* =========================
   CHECK MESSAGE ID
   ========================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid message ID.");
}

$id = (int) $_GET["id"];


/* =========================
   SAVE REPLY
   ========================= */

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $adminReply = isset($_POST["admin_reply"])
        ? trim($_POST["admin_reply"])
        : "";


    /* VALIDATION */

    if ($adminReply === "") {

        $error = "Please write a reply before sending.";

    } else {


        /* UPDATE MESSAGE */

        $sql = "
            UPDATE contact_messages
            SET
                admin_reply = ?,
                status = 'replied',
                replied_at = NOW()
            WHERE id = ?
        ";

        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            $error = "Unable to prepare the reply.";

        } else {

            $stmt->bind_param(
                "si",
                $adminReply,
                $id
            );


            if ($stmt->execute()) {

                $success = "Reply saved successfully!";

            } else {

                $error = "Unable to save the reply.";

            }


            $stmt->close();

        }

    }

}


/* =========================
   GET CUSTOMER MESSAGE
   ========================= */

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
        Reply to Customer | The Knot House
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
           CONTAINER
           ========================= */

        .container {
            width: 92%;
            max-width: 850px;
            margin: 40px auto;
        }


        .page-title {
            text-align: center;
            margin-bottom: 30px;
        }


        .page-title h1 {
            font-family: "Playfair Display", serif;
            color: #8d555e;
            font-size: 38px;
        }


        .page-title p {
            margin-top: 8px;
            color: #6d5d5d;
        }


        /* =========================
           CARD
           ========================= */

        .card {
            background: #fffaf7;
            border-radius: 22px;
            padding: 35px;
            box-shadow: 0 8px 30px rgba(81, 61, 61, 0.08);
        }


        /* =========================
           CUSTOMER MESSAGE
           ========================= */

        .customer-box {
            background: #f8e8e7;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 30px;
        }


        .customer-box h2 {
            font-family: "Playfair Display", serif;
            color: #8d555e;
            font-size: 22px;
            margin-bottom: 15px;
        }


        .customer-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 20px;
        }


        .info-item strong {
            color: #8d555e;
        }


        .info-item {
            color: #6d5d5d;
            word-break: break-word;
        }


        .original-message {
            background: #fffaf7;
            border: 1px solid #eadbd8;
            border-radius: 12px;
            padding: 18px;
            margin-top: 20px;
        }


        .original-message strong {
            display: block;
            color: #8d555e;
            margin-bottom: 8px;
        }


        .original-message p {
            line-height: 1.7;
            white-space: pre-wrap;
            color: #6d5d5d;
        }


        /* =========================
           ALERTS
           ========================= */

        .success {
            background: #e4f6e9;
            color: #38804b;
            border: 1px solid #b9dfc1;
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
        }


        .error {
            background: #fde8e8;
            color: #a33a3a;
            border: 1px solid #efbcbc;
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
        }


        /* =========================
           REPLY FORM
           ========================= */

        .reply-title {
            font-family: "Playfair Display", serif;
            color: #8d555e;
            font-size: 25px;
            margin-bottom: 8px;
        }


        .reply-subtitle {
            color: #6d5d5d;
            margin-bottom: 20px;
            line-height: 1.6;
        }


        label {
            display: block;
            font-weight: 700;
            color: #513d3d;
            margin-bottom: 8px;
        }


        textarea {
            width: 100%;
            min-height: 220px;
            resize: vertical;
            border: 1px solid #eadbd8;
            border-radius: 14px;
            padding: 16px;
            font-family: "DM Sans", sans-serif;
            font-size: 15px;
            color: #513d3d;
            background: #fff;
            outline: none;
            transition: 0.3s;
        }


        textarea:focus {
            border-color: #a96771;
            box-shadow: 0 0 0 3px rgba(169, 103, 113, 0.12);
        }


        textarea::placeholder {
            color: #a99a98;
        }


        /* =========================
           BUTTONS
           ========================= */

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }


        .btn {
            display: inline-block;
            text-decoration: none;
            border: none;
            cursor: pointer;
            padding: 12px 22px;
            border-radius: 25px;
            font-family: "DM Sans", sans-serif;
            font-size: 14px;
            font-weight: 700;
            transition: 0.3s;
        }


        .send-btn {
            background: #a96771;
            color: white;
        }


        .send-btn:hover {
            background: #8d555e;
            transform: translateY(-2px);
        }


        .cancel-btn {
            background: #eadbd8;
            color: #513d3d;
        }


        .cancel-btn:hover {
            background: #dec9c5;
        }


        /* =========================
           EXISTING REPLY
           ========================= */

        .existing-reply {
            margin-top: 30px;
            padding: 20px;
            background: #fdf0ef;
            border-left: 5px solid #a96771;
            border-radius: 12px;
        }


        .existing-reply h3 {
            color: #8d555e;
            margin-bottom: 10px;
        }


        .existing-reply p {
            color: #6d5d5d;
            line-height: 1.7;
            white-space: pre-wrap;
        }


        .existing-reply small {
            display: block;
            margin-top: 10px;
            color: #8d7775;
        }


        /* =========================
           MOBILE
           ========================= */

        @media (max-width: 650px) {

            .header {
                padding: 15px 4%;
            }


            .logo img {
                width: 42px;
                height: 42px;
            }


            .logo-text {
                font-size: 19px;
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


            .card {
                padding: 20px;
                border-radius: 17px;
            }


            .customer-info {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
     ========================= -->

<header class="header">

    <a
        href="../index.html"
        class="logo"
    >

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

        <h1>
            💌 Reply to Customer
        </h1>

        <p>
            Send a reply to the customer's message.
        </p>

    </div>



    <div class="card">


        <!-- SUCCESS MESSAGE -->

        <?php if ($success !== ""): ?>

            <div class="success">

                ✅ <?php echo htmlspecialchars($success); ?>

            </div>

        <?php endif; ?>



        <!-- ERROR MESSAGE -->

        <?php if ($error !== ""): ?>

            <div class="error">

                ❌ <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>



        <!-- CUSTOMER MESSAGE -->

        <div class="customer-box">


            <h2>
                Customer Information
            </h2>


            <div class="customer-info">


                <div class="info-item">

                    <strong>Name:</strong>

                    <?php
                    echo htmlspecialchars($row["name"]);
                    ?>

                </div>


                <div class="info-item">

                    <strong>Email:</strong>

                    <?php
                    echo htmlspecialchars($row["email"]);
                    ?>

                </div>


                <div class="info-item">

                    <strong>Phone:</strong>

                    <?php

                    echo htmlspecialchars(
                        $row["phone"] ?: "Not provided"
                    );

                    ?>

                </div>


                <div class="info-item">

                    <strong>Subject:</strong>

                    <?php
                    echo htmlspecialchars($row["subject"]);
                    ?>

                </div>


            </div>



            <div class="original-message">

                <strong>
                    Customer's Message:
                </strong>


                <p>

                    <?php

                    echo htmlspecialchars(
                        $row["message"]
                    );

                    ?>

                </p>

            </div>


        </div>



        <!-- REPLY FORM -->

        <h2 class="reply-title">
            Write Your Reply
        </h2>


        <p class="reply-subtitle">

            Your reply will be saved in the customer's message
            and can later be shown to the customer.

        </p>



        <form method="POST">


            <label for="admin_reply">
                Your Reply
            </label>


            <textarea
                id="admin_reply"
                name="admin_reply"
                placeholder="Write your reply here..."
                required
            ><?php

                echo htmlspecialchars(
                    $row["admin_reply"] ?? ""
                );

            ?></textarea>



            <div class="actions">


                <button
                    type="submit"
                    class="btn send-btn"
                >
                    💌 Save Reply
                </button>


                <a
                    href="view-message.php?id=<?php echo (int)$row["id"]; ?>"
                    class="btn cancel-btn"
                >
                    ← Cancel
                </a>


            </div>


        </form>



        <!-- EXISTING REPLY -->

        <?php if (!empty($row["admin_reply"])): ?>


            <div class="existing-reply">

                <h3>
                    💗 Current Saved Reply
                </h3>


                <p>

                    <?php

                    echo htmlspecialchars(
                        $row["admin_reply"]
                    );

                    ?>

                </p>


                <?php if (!empty($row["replied_at"])): ?>

                    <small>

                        Last replied on:

                        <?php

                        echo date(
                            "d M Y, h:i A",
                            strtotime($row["replied_at"])
                        );

                        ?>

                    </small>

                <?php endif; ?>


            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>


<?php

$conn->close();

?>