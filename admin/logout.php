<?php
session_start();

/* =========================================================
   LOGOUT PROCESS
   Logout happens ONLY after clicking "Yes, Logout"
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["confirm_logout"])) {

    /* Clear all session variables */
    $_SESSION = [];

    /* Delete session cookie */
    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    /* Destroy session */
    session_destroy();

    /* Redirect to login page */
    header("Location: login.php");
    exit();
}


/* =========================================================
   ADMIN INFORMATION
   ========================================================= */

$adminName = $_SESSION["admin_name"] ?? "Administrator";

$adminInitial = strtoupper(
    substr(trim($adminName), 0, 1)
);

if ($adminInitial === "") {
    $adminInitial = "A";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Logout Confirmation | The Knot House Admin</title>


    <!-- Google Fonts -->

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
          rel="stylesheet">


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

            --red: #b85c5c;
            --red-light: #fbeaea;
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

            min-height: 100vh;

            font-family: "DM Sans", sans-serif;

            color: var(--brown);

            background: var(--cream);

            overflow-x: hidden;
        }


        /* =====================================================
           BACKGROUND
        ===================================================== */

        .page {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            position: relative;

            padding: 30px 20px;

            overflow: hidden;
        }


        /* Decorative circles */

        .shape {

            position: absolute;

            border-radius: 50%;

            pointer-events: none;

            z-index: 0;
        }


        .shape-one {

            width: 420px;
            height: 420px;

            background: var(--light-pink);

            top: -220px;
            left: -180px;

            opacity: 0.75;
        }


        .shape-two {

            width: 500px;
            height: 500px;

            background: var(--cream-dark);

            right: -260px;
            bottom: -260px;

            opacity: 0.9;
        }


        .shape-three {

            width: 150px;
            height: 150px;

            background: var(--soft-pink);

            right: 15%;
            top: 8%;

            opacity: 0.35;
        }


        .shape-four {

            width: 90px;
            height: 90px;

            background: var(--light-pink);

            left: 12%;
            bottom: 13%;

            opacity: 0.5;
        }


        /* =====================================================
           MAIN CARD
        ===================================================== */

        .logout-container {

            width: 100%;

            max-width: 560px;

            position: relative;

            z-index: 2;
        }


        .logout-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 28px;

            padding: 42px;

            text-align: center;

            box-shadow:
                0 25px 70px rgba(81, 61, 61, 0.13);

            animation: cardIn 0.5s ease;
        }


        @keyframes cardIn {

            from {

                opacity: 0;

                transform:
                    translateY(25px)
                    scale(0.97);
            }

            to {

                opacity: 1;

                transform:
                    translateY(0)
                    scale(1);
            }
        }


        /* =====================================================
           BRAND
        ===================================================== */

        .brand {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 12px;

            margin-bottom: 35px;
        }


        .brand-icon {

            width: 48px;
            height: 48px;

            border-radius: 14px;

            background: var(--light-pink);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;
        }


        .brand-info {

            text-align: left;
        }


        .brand-name {

            font-family: "Playfair Display", serif;

            font-size: 21px;

            font-weight: 700;

            color: var(--brown);

            line-height: 1.2;
        }


        .brand-subtitle {

            margin-top: 4px;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1.6px;

            text-transform: uppercase;

            color: var(--pink);
        }


        /* =====================================================
           LOGOUT ICON
        ===================================================== */

        .logout-icon {

            width: 92px;
            height: 92px;

            margin: 0 auto 25px;

            border-radius: 50%;

            background: var(--red-light);

            border: 8px solid #fff6f5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 39px;

            animation: iconFloat 2.5s ease-in-out infinite;
        }


        @keyframes iconFloat {

            0%,
            100% {

                transform: translateY(0);
            }

            50% {

                transform: translateY(-5px);
            }
        }


        /* =====================================================
           HEADING
        ===================================================== */

        .title {

            font-family: "Playfair Display", serif;

            font-size: 32px;

            line-height: 1.25;

            color: var(--brown);

            margin-bottom: 12px;
        }


        .subtitle {

            max-width: 410px;

            margin: 0 auto;

            font-size: 15px;

            line-height: 1.7;

            color: var(--brown-light);
        }


        .subtitle strong {

            color: var(--brown);

            font-weight: 700;
        }


        /* =====================================================
           ADMIN INFO
        ===================================================== */

        .admin-box {

            margin-top: 28px;

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 13px 15px;

            border: 1px solid var(--border);

            border-radius: 14px;

            background: var(--cream);

            text-align: left;
        }


        .admin-avatar {

            width: 42px;
            height: 42px;

            flex-shrink: 0;

            border-radius: 50%;

            background: var(--pink);

            color: var(--white);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 15px;

            font-weight: 700;
        }


        .admin-details {

            min-width: 0;
        }


        .admin-label {

            display: block;

            font-size: 11px;

            color: var(--brown-light);

            margin-bottom: 2px;
        }


        .admin-name {

            display: block;

            font-size: 14px;

            font-weight: 700;

            color: var(--brown);

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        /* =====================================================
           CONFIRMATION MESSAGE
        ===================================================== */

        .confirmation-box {

            margin-top: 18px;

            padding: 15px 17px;

            border-radius: 14px;

            background: var(--light-pink);

            border: 1px solid #efd5d4;

            display: flex;

            align-items: flex-start;

            gap: 11px;

            text-align: left;
        }


        .confirmation-icon {

            font-size: 19px;

            line-height: 1.4;

            flex-shrink: 0;
        }


        .confirmation-text {

            font-size: 13px;

            line-height: 1.6;

            color: var(--brown-light);
        }


        .confirmation-text strong {

            color: var(--brown);
        }


        /* =====================================================
           BUTTON AREA
        ===================================================== */

        .button-area {

            display: flex;

            gap: 12px;

            margin-top: 28px;
        }


        .btn {

            flex: 1;

            min-height: 52px;

            border-radius: 13px;

            border: none;

            padding: 14px 18px;

            font-family: "DM Sans", sans-serif;

            font-size: 14px;

            font-weight: 700;

            text-decoration: none;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }


        /* Cancel */

        .cancel-btn {

            background: var(--white);

            color: var(--brown);

            border: 1px solid var(--border);
        }


        .cancel-btn:hover {

            background: var(--cream);

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px rgba(81, 61, 61, 0.08);
        }


        /* Logout */

        .logout-btn {

            background: var(--dark-pink);

            color: var(--white);

            border: 1px solid var(--dark-pink);
        }


        .logout-btn:hover {

            background: var(--pink);

            transform: translateY(-2px);

            box-shadow:
                0 10px 25px rgba(141, 85, 94, 0.25);
        }


        .btn:active {

            transform: translateY(0);
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .footer {

            margin-top: 25px;

            font-size: 12px;

            color: #aa9999;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .page {

                padding: 18px;
            }


            .logout-card {

                padding: 32px 22px;

                border-radius: 22px;
            }


            .brand {

                margin-bottom: 28px;
            }


            .brand-name {

                font-size: 19px;
            }


            .logout-icon {

                width: 82px;
                height: 82px;

                font-size: 34px;
            }


            .title {

                font-size: 27px;
            }


            .subtitle {

                font-size: 14px;
            }


            .button-area {

                flex-direction: column;
            }


            .btn {

                width: 100%;
            }
        }


        @media (max-width: 380px) {

            .logout-card {

                padding: 28px 17px;
            }


            .title {

                font-size: 25px;
            }


            .admin-box {

                padding: 11px;
            }

        }

    </style>

</head>


<body>


    <!-- =====================================================
         BACKGROUND
    ====================================================== -->

    <div class="page">

        <div class="shape shape-one"></div>

        <div class="shape shape-two"></div>

        <div class="shape shape-three"></div>

        <div class="shape shape-four"></div>


        <!-- =================================================
             LOGOUT CARD
        ================================================== -->

        <div class="logout-container">

            <div class="logout-card">


                <!-- BRAND -->

                <div class="brand">

                    <div class="brand-icon">
                        🧶
                    </div>

                    <div class="brand-info">

                        <div class="brand-name">
                            The Knot House
                        </div>

                        <div class="brand-subtitle">
                            Admin Panel
                        </div>

                    </div>

                </div>


                <!-- LOGOUT ICON -->

                <div class="logout-icon">
                    🚪
                </div>


                <!-- TITLE -->

                <h1 class="title">
                    Are You Sure?
                </h1>


                <p class="subtitle">

                    Do you really want to logout from
                    <strong>The Knot House Admin Panel</strong>?

                </p>


                <!-- CURRENT ADMIN -->

                <div class="admin-box">

                    <div class="admin-avatar">
                        <?php echo htmlspecialchars($adminInitial); ?>
                    </div>

                    <div class="admin-details">

                        <span class="admin-label">
                            Currently signed in as
                        </span>

                        <span class="admin-name">
                            <?php echo htmlspecialchars($adminName); ?>
                        </span>

                    </div>

                </div>


                <!-- CONFIRMATION INFORMATION -->

                <div class="confirmation-box">

                    <div class="confirmation-icon">
                        🔐
                    </div>

                    <div class="confirmation-text">

                        <strong>Your admin session will end.</strong>

                        You will need to enter your admin
                        credentials again to access the dashboard.

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="button-area">


                    <!-- CANCEL -->

                    <a href="dashboard.php"
                       class="btn cancel-btn">

                        ← Cancel

                    </a>


                    <!-- YES LOGOUT -->

                    <form method="POST"
                          style="flex: 1; margin: 0;">

                        <button type="submit"
                                name="confirm_logout"
                                value="1"
                                class="btn logout-btn">

                            🚪 Yes, Logout

                        </button>

                    </form>

                </div>


                <!-- FOOTER -->

                <div class="footer">

                    The Knot House • Handmade with love 🧶

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         PREVENT BACK BUTTON
    ====================================================== -->

    <script>

        /*
         * Keep the confirmation page from being accidentally
         * skipped when using the browser back button.
         */

        if (window.history && window.history.pushState) {

            window.history.pushState(
                null,
                "",
                window.location.href
            );

            window.onpopstate = function () {

                window.history.pushState(
                    null,
                    "",
                    window.location.href
                );

            };

        }

    </script>

</body>

</html>