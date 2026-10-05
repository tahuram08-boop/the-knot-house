<?php

session_start();

/* =========================================================
   THE KNOT HOUSE - ADMIN LOGIN
   ========================================================= */


/* =========================================================
   DATABASE CONNECTION
   ========================================================= */

$host = "localhost";
$username = "root";
$db_password = "";
$database = "knot_house";

$conn = mysqli_connect(
    $host,
    $username,
    $db_password,
    $database
);


/* Check Database Connection */

if (!$conn) {

    die("Database connection failed: " . mysqli_connect_error());

}


/* =========================================================
   VARIABLES
   ========================================================= */

$error = "";

$email = "";


/* =========================================================
   CHECK LOGIN FORM
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /* Get Form Data */

    $email = trim($_POST["email"] ?? "");

    $password = trim($_POST["password"] ?? "");


    /* =====================================================
       VALIDATION
       ===================================================== */

    if ($email == "" || $password == "") {

        $error = "Please enter both email and password.";

    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }

    else {

        /* =================================================
           CHECK ADMIN FROM DATABASE
           ================================================= */

        $query = "
            SELECT id, name, email, password
            FROM admin
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $query);


        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);


            /* =================================================
               CHECK USER
               ================================================= */

            if ($result && mysqli_num_rows($result) == 1) {

                $admin = mysqli_fetch_assoc($result);


                /* =================================================
                   PASSWORD CHECK

                   Current database uses plain password:
                   admin123

                   Later we can upgrade this to password_hash().
                   ================================================= */

                if ($password === $admin["password"]) {


                    /* =================================================
                       CREATE SESSION
                       ================================================= */

                    $_SESSION["admin_id"] = $admin["id"];

                    $_SESSION["admin_name"] = $admin["name"];

                    $_SESSION["admin_email"] = $admin["email"];


                    /* =================================================
                       REDIRECT TO DASHBOARD
                       ================================================= */

                    header("Location: dashboard.php");

                    exit();


                } else {

                    $error = "Incorrect password. Please try again.";

                }


            } else {

                $error = "No admin account found with this email.";

            }


            mysqli_stmt_close($stmt);


        } else {

            $error = "Unable to process login. Please try again.";

        }

    }

}


mysqli_close($conn);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="The Knot House Admin Login"
    >

    <title>
        Admin Login | The Knot House
    </title>


    <!-- =====================================================
         GOOGLE FONTS
         ===================================================== -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        /* =====================================================
           RESET
           ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


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

            --danger: #b34d57;

        }


        /* =====================================================
           BODY
           ===================================================== */

        body {

            font-family: "DM Sans", sans-serif;

            min-height: 100vh;

            color: var(--brown);

            background:

                radial-gradient(
                    circle at 10% 20%,
                    rgba(243, 216, 217, 0.75),
                    transparent 30%
                ),

                radial-gradient(
                    circle at 90% 80%,
                    rgba(248, 232, 231, 0.85),
                    transparent 35%
                ),

                var(--cream);

            overflow-x: hidden;

        }


        /* =====================================================
           DECORATIVE BACKGROUND
           ===================================================== */

        .background-decoration {

            position: fixed;

            inset: 0;

            pointer-events: none;

            overflow: hidden;

            z-index: 0;

        }


        .circle {

            position: absolute;

            border-radius: 50%;

            opacity: 0.45;

            filter: blur(1px);

        }


        .circle-one {

            width: 280px;

            height: 280px;

            background: var(--soft-pink);

            top: -100px;

            left: -80px;

        }


        .circle-two {

            width: 360px;

            height: 360px;

            background: var(--light-pink);

            bottom: -150px;

            right: -120px;

        }


        .circle-three {

            width: 100px;

            height: 100px;

            background: #ead2c8;

            top: 18%;

            right: 12%;

        }


        /* =====================================================
           MAIN CONTAINER
           ===================================================== */

        .login-wrapper {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 35px 20px;

            position: relative;

            z-index: 1;

        }


        /* =====================================================
           LOGIN CONTAINER
           ===================================================== */

        .login-container {

            width: 100%;

            max-width: 1050px;

            min-height: 620px;

            display: grid;

            grid-template-columns: 0.9fr 1.1fr;

            background: rgba(255, 255, 255, 0.88);

            backdrop-filter: blur(18px);

            -webkit-backdrop-filter: blur(18px);

            border: 1px solid rgba(234, 219, 216, 0.9);

            border-radius: 30px;

            overflow: hidden;

            box-shadow:

                0 30px 80px rgba(81, 61, 61, 0.14);

            animation: pageAppear 0.7s ease;

        }


        @keyframes pageAppear {

            from {

                opacity: 0;

                transform: translateY(25px) scale(0.98);

            }

            to {

                opacity: 1;

                transform: translateY(0) scale(1);

            }

        }


        /* =====================================================
           LEFT BRAND PANEL
           ===================================================== */

        .brand-panel {

            position: relative;

            padding: 55px 45px;

            background:

                linear-gradient(
                    145deg,
                    #f8e8e7,
                    #f3d8d9
                );

            display: flex;

            flex-direction: column;

            justify-content: space-between;

            overflow: hidden;

        }


        .brand-panel::before {

            content: "";

            position: absolute;

            width: 300px;

            height: 300px;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.35);

            top: -150px;

            right: -120px;

        }


        .brand-panel::after {

            content: "";

            position: absolute;

            width: 220px;

            height: 220px;

            border-radius: 50%;

            background: rgba(169, 103, 113, 0.08);

            bottom: -100px;

            left: -80px;

        }


        .brand-content {

            position: relative;

            z-index: 2;

        }


        /* =====================================================
           LOGO ICON
           ===================================================== */

        .logo-icon {

            width: 90px;

            height: 90px;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.78);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 45px;

            margin-bottom: 28px;

            box-shadow:

                0 15px 30px rgba(141, 85, 94, 0.12);

            animation: yarnFloat 3s ease-in-out infinite;

        }


        @keyframes yarnFloat {

            0%,
            100% {

                transform: translateY(0);

            }

            50% {

                transform: translateY(-7px);

            }

        }


        /* =====================================================
           BRAND TITLE
           ===================================================== */

        .brand-panel h1 {

            font-family: "Playfair Display", serif;

            font-size: clamp(32px, 4vw, 45px);

            line-height: 1.1;

            color: var(--dark-pink);

            margin-bottom: 15px;

        }


        .brand-subtitle {

            max-width: 360px;

            color: var(--brown-light);

            line-height: 1.8;

            font-size: 15px;

        }


        /* =====================================================
           FEATURE LIST
           ===================================================== */

        .features {

            position: relative;

            z-index: 2;

            margin-top: 35px;

        }


        .feature {

            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 15px;

            color: var(--brown);

            font-size: 14px;

            font-weight: 500;

        }


        .feature-icon {

            width: 34px;

            height: 34px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: rgba(255, 255, 255, 0.65);

        }


        /* =====================================================
           BRAND FOOTER
           ===================================================== */

        .brand-footer {

            position: relative;

            z-index: 2;

            color: var(--brown-light);

            font-size: 12px;

            line-height: 1.7;

        }


        .brand-footer strong {

            color: var(--dark-pink);

        }


        /* =====================================================
           LOGIN PANEL
           ===================================================== */

        .login-panel {

            padding: 60px 65px;

            display: flex;

            align-items: center;

            justify-content: center;

        }


        .login-content {

            width: 100%;

            max-width: 420px;

        }


        /* =====================================================
           LOGIN HEADER
           ===================================================== */

        .login-header {

            margin-bottom: 32px;

        }


        .small-label {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 7px 12px;

            border-radius: 30px;

            background: var(--light-pink);

            color: var(--dark-pink);

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 1px;

            text-transform: uppercase;

            margin-bottom: 18px;

        }


        .login-header h2 {

            font-family: "Playfair Display", serif;

            font-size: 34px;

            color: var(--brown);

            margin-bottom: 10px;

        }


        .login-header p {

            color: var(--brown-light);

            font-size: 14px;

            line-height: 1.7;

        }


        /* =====================================================
           ERROR MESSAGE
           ===================================================== */

        .error-message {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            background: #fff2f2;

            border: 1px solid #efc9cc;

            color: var(--danger);

            padding: 13px 15px;

            border-radius: 12px;

            margin-bottom: 22px;

            font-size: 13px;

            line-height: 1.5;

            animation: errorShake 0.35s ease;

        }


        @keyframes errorShake {

            0%,
            100% {

                transform: translateX(0);

            }

            25% {

                transform: translateX(-4px);

            }

            75% {

                transform: translateX(4px);

            }

        }


        /* =====================================================
           FORM GROUP
           ===================================================== */

        .form-group {

            margin-bottom: 21px;

        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            color: var(--brown);

            font-size: 13px;

            font-weight: 700;

        }


        /* =====================================================
           INPUT WRAPPER
           ===================================================== */

        .input-wrapper {

            position: relative;

        }


        .input-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform: translateY(-50%);

            font-size: 17px;

            pointer-events: none;

            opacity: 0.75;

        }


        .form-input {

            width: 100%;

            height: 52px;

            border: 1px solid var(--border);

            border-radius: 13px;

            padding: 0 15px 0 45px;

            background: #fffdfb;

            color: var(--brown);

            font-family: inherit;

            font-size: 14px;

            outline: none;

            transition: all 0.25s ease;

        }


        .form-input::placeholder {

            color: #ad9c9c;

        }


        .form-input:focus {

            border-color: var(--pink);

            background: white;

            box-shadow:

                0 0 0 4px rgba(169, 103, 113, 0.09);

        }


        /* =====================================================
           PASSWORD TOGGLE
           ===================================================== */

        .password-input {

            padding-right: 50px;

        }


        .password-toggle {

            position: absolute;

            right: 12px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            cursor: pointer;

            font-size: 17px;

            padding: 6px;

            opacity: 0.65;

        }


        .password-toggle:hover {

            opacity: 1;

        }


        /* =====================================================
           LOGIN BUTTON
           ===================================================== */

        .login-button {

            width: 100%;

            height: 53px;

            border: none;

            border-radius: 13px;

            background: var(--pink);

            color: var(--white);

            font-family: inherit;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 10px;

            transition: all 0.3s ease;

            margin-top: 8px;

            box-shadow:

                0 10px 25px rgba(169, 103, 113, 0.18);

        }


        .login-button:hover {

            background: var(--dark-pink);

            transform: translateY(-2px);

            box-shadow:

                0 15px 30px rgba(141, 85, 94, 0.23);

        }


        .login-button:active {

            transform: translateY(0);

        }


        .button-arrow {

            font-size: 19px;

            transition: transform 0.25s ease;

        }


        .login-button:hover .button-arrow {

            transform: translateX(4px);

        }


        /* =====================================================
           SECURITY NOTE
           ===================================================== */

        .security-note {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            margin-top: 18px;

            color: #8d7c7c;

            font-size: 11px;

        }


        /* =====================================================
           BACK TO WEBSITE
           ===================================================== */

        .back-website {

            display: flex;

            justify-content: center;

            margin-top: 25px;

        }


        .back-website a {

            color: var(--dark-pink);

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            transition: 0.25s;

        }


        .back-website a:hover {

            color: var(--pink);

            text-decoration: underline;

        }


        /* =====================================================
           RESPONSIVE - TABLET
           ===================================================== */

        @media (max-width: 900px) {

            .login-container {

                max-width: 600px;

                grid-template-columns: 1fr;

            }


            .brand-panel {

                padding: 40px;

                min-height: 390px;

            }


            .brand-footer {

                margin-top: 30px;

            }


            .login-panel {

                padding: 45px 40px;

            }

        }


        /* =====================================================
           RESPONSIVE - MOBILE
           ===================================================== */

        @media (max-width: 550px) {

            .login-wrapper {

                padding: 15px;

            }


            .login-container {

                border-radius: 22px;

            }


            .brand-panel {

                padding: 35px 25px;

                min-height: auto;

            }


            .logo-icon {

                width: 70px;

                height: 70px;

                font-size: 35px;

                margin-bottom: 20px;

            }


            .brand-panel h1 {

                font-size: 31px;

            }


            .brand-subtitle {

                font-size: 13px;

            }


            .features {

                margin-top: 25px;

            }


            .brand-footer {

                margin-top: 25px;

            }


            .login-panel {

                padding: 40px 25px;

            }


            .login-header h2 {

                font-size: 29px;

            }

        }


        /* =====================================================
           VERY SMALL MOBILE
           ===================================================== */

        @media (max-width: 360px) {

            .login-panel {

                padding: 35px 18px;

            }


            .brand-panel {

                padding: 30px 20px;

            }


            .login-header h2 {

                font-size: 26px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     BACKGROUND DECORATION
     ========================================================= -->

<div class="background-decoration">

    <div class="circle circle-one"></div>

    <div class="circle circle-two"></div>

    <div class="circle circle-three"></div>

</div>



<!-- =========================================================
     LOGIN WRAPPER
     ========================================================= -->

<main class="login-wrapper">


    <section class="login-container">


        <!-- =================================================
             LEFT BRAND PANEL
             ================================================= -->

        <div class="brand-panel">


            <div class="brand-content">


                <div class="logo-icon">

                    🧶

                </div>


                <h1>
                    The Knot House
                </h1>


                <p class="brand-subtitle">

                    A beautiful space where handmade crochet
                    meets creativity, warmth and love.

                </p>



                <!-- FEATURES -->

                <div class="features">


                    <div class="feature">

                        <span class="feature-icon">
                            🛍️
                        </span>

                        Manage Products

                    </div>


                    <div class="feature">

                        <span class="feature-icon">
                            📦
                        </span>

                        Manage Orders

                    </div>


                    <div class="feature">

                        <span class="feature-icon">
                            📊
                        </span>

                        Track Your Store

                    </div>


                    <div class="feature">

                        <span class="feature-icon">
                            💬
                        </span>

                        Manage Customer Feedback

                    </div>


                </div>

            </div>



            <!-- BRAND FOOTER -->

            <div class="brand-footer">

                🪡 Handmade with love by

                <strong>
                    The Knot House
                </strong>

                <br>

                Admin Management System

            </div>


        </div>



        <!-- =================================================
             RIGHT LOGIN PANEL
             ================================================= -->

        <div class="login-panel">


            <div class="login-content">


                <!-- LOGIN HEADER -->

                <div class="login-header">


                    <span class="small-label">

                        🔐 Admin Access

                    </span>


                    <h2>
                        Welcome Back 👋
                    </h2>


                    <p>

                        Sign in to manage your crochet store
                        and keep everything running smoothly.

                    </p>


                </div>



                <!-- =================================================
                     ERROR MESSAGE
                     ================================================= -->

                <?php if (!empty($error)) { ?>

                    <div class="error-message">

                        <span>⚠️</span>

                        <span>
                            <?php echo htmlspecialchars($error); ?>
                        </span>

                    </div>

                <?php } ?>



                <!-- =================================================
                     LOGIN FORM
                     ================================================= -->

                <form
                    method="POST"
                    action=""
                    autocomplete="on"
                >


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>


                        <div class="input-wrapper">

                            <span class="input-icon">
                                ✉️
                            </span>


                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-input"
                                placeholder="admin@knothouse.com"
                                value="<?php echo htmlspecialchars($email); ?>"
                                autocomplete="email"
                                required
                            >

                        </div>

                    </div>



                    <!-- PASSWORD -->

                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>


                        <div class="input-wrapper">

                            <span class="input-icon">
                                🔒
                            </span>


                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input password-input"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                id="password"
                                required
                            >


                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword()"
                                id="passwordToggle"
                                aria-label="Show password"
                            >
                                👁️
                            </button>

                        </div>

                    </div>



                    <!-- LOGIN BUTTON -->

                    <button
                        type="submit"
                        class="login-button"
                    >

                        <span>
                            Login to Admin Panel
                        </span>


                        <span class="button-arrow">
                            →
                        </span>

                    </button>


                </form>



                <!-- SECURITY NOTE -->

                <div class="security-note">

                    🔒

                    <span>
                        Your admin session is protected.
                    </span>

                </div>



                <!-- BACK TO WEBSITE -->

                <div class="back-website">

                    <a href="../index.html">

                        ← Back to The Knot House

                    </a>

                </div>


            </div>

        </div>


    </section>

</main>



<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->

<script>

    /* =========================================================
       SHOW / HIDE PASSWORD
       ========================================================= */

    function togglePassword() {

        const passwordInput =
            document.getElementById("password");

        const toggleButton =
            document.getElementById("passwordToggle");


        if (passwordInput.type === "password") {

            passwordInput.type = "text";

            toggleButton.textContent = "🙈";

            toggleButton.setAttribute(
                "aria-label",
                "Hide password"
            );

        } else {

            passwordInput.type = "password";

            toggleButton.textContent = "👁️";

            toggleButton.setAttribute(
                "aria-label",
                "Show password"
            );

        }

    }


    /* =========================================================
       AUTO FOCUS
       ========================================================= */

    window.addEventListener("load", function () {

        const emailInput =
            document.getElementById("email");

        if (emailInput) {

            emailInput.focus();

        }

    });


    /* =========================================================
       PREVENT MULTIPLE SUBMISSIONS
       ========================================================= */

    document.querySelector("form").addEventListener(
        "submit",
        function () {

            const button =
                document.querySelector(".login-button");

            button.style.opacity = "0.8";

            button.style.cursor = "wait";

        }
    );

</script>


</body>

</html>