<?php
session_start();

/* If customer is already logged in */
if (isset($_SESSION["customer_id"])) {
    header("Location: my-account.php");
    exit();
}

/* Database connection */
$host = "localhost";
$username = "root";
$password = "";
$database = "knot_house";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed. Please try again later.");
}

$conn->set_charset("utf8mb4");

$email = "";
$error = "";

/* Handle login form */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $userPassword = $_POST["password"] ?? "";

    if ($email === "" || $userPassword === "") {

        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /* Find customer by email */
        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password
             FROM customers
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($customer = $result->fetch_assoc()) {

            /* Verify the hashed password */
            if (password_verify($userPassword, $customer["password"])) {

                /* Create a fresh session ID */
                session_regenerate_id(true);

                $_SESSION["customer_id"] = $customer["id"];
                $_SESSION["customer_name"] = $customer["full_name"];
                $_SESSION["customer_email"] = $customer["email"];

                $stmt->close();
                $conn->close();

                /* Redirect to account page */
                header("Location: my-account.php");
                exit();

            } else {

                $error = "Incorrect email or password.";
            }

        } else {

            $error = "Incorrect email or password.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign In | The Knot House</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --pink: #a96771;
            --dark-pink: #8d555e;
            --light-pink: #f8e8e7;
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
            display: flex;
            flex-direction: column;
            font-family: 'DM Sans', sans-serif;
            color: var(--brown);
            background:
                radial-gradient(circle at top left, #f3d8d9 0, transparent 35%),
                var(--cream);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .navbar {
            padding: 18px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 250, 247, 0.92);
            border-bottom: 1px solid var(--border);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .brand img {
            width: 46px;
            height: 46px;
            object-fit: contain;
        }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            font-weight: 700;
            color: var(--dark-pink);
        }

        .back-link {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-pink);
        }

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 45px 18px;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            padding: 38px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: 0 15px 45px rgba(141, 85, 94, 0.10);
        }

        .icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 17px;
            border-radius: 50%;
            background: var(--light-pink);
            font-size: 30px;
        }

        .eyebrow {
            margin-bottom: 9px;
            text-align: center;
            color: var(--pink);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        h1 {
            margin-bottom: 10px;
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 32px;
        }

        .subtitle {
            margin-bottom: 27px;
            text-align: center;
            color: var(--brown-light);
            font-size: 14px;
            line-height: 1.7;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--cream);
            font-family: inherit;
            font-size: 14px;
            color: var(--brown);
            outline: none;
            transition: 0.25s;
        }

        input:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(169, 103, 113, 0.10);
        }

        .error {
            padding: 12px 14px;
            margin-bottom: 18px;
            border-radius: 9px;
            background: #fbeaea;
            color: #a53e3e;
            font-size: 13px;
            line-height: 1.5;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            margin-top: 5px;
            border: none;
            border-radius: 10px;
            background: var(--pink);
            color: var(--white);
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.25s;
        }

        .submit-btn:hover {
            background: var(--dark-pink);
            transform: translateY(-1px);
        }

        .register-text {
            margin-top: 22px;
            text-align: center;
            color: var(--brown-light);
            font-size: 13px;
        }

        .register-text a {
            color: var(--dark-pink);
            font-weight: 700;
        }

        .register-text a:hover,
        .back-link:hover {
            text-decoration: underline;
        }

        .footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            text-align: center;
            color: var(--brown-light);
            font-size: 12px;
        }

        @media (max-width: 500px) {
            .navbar {
                padding: 15px 5%;
            }

            .brand-name {
                font-size: 19px;
            }

            .brand img {
                width: 38px;
                height: 38px;
            }

            .login-card {
                padding: 27px 21px;
            }

            h1 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

    <header class="navbar">
        <a href="index.html" class="brand">
            <img
                src="logo.png"
                alt="The Knot House Logo"
                onerror="this.style.display='none'"
            >

            <span class="brand-name">The Knot House</span>
        </a>

        <a href="index.html" class="back-link">
            ← Back to Website
        </a>
    </header>

    <main>
        <section class="login-card">

            <div class="icon">🧶</div>

            <p class="eyebrow">Welcome back</p>

            <h1>Sign In</h1>

            <p class="subtitle">
                Sign in to view your orders, saved favourites
                and delivery addresses.
            </p>

            <?php if ($error !== ""): ?>
                <div class="error">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="submit-btn">
                    Sign In ♡
                </button>

            </form>

            <p class="register-text">
                Don't have an account?
                <a href="register.php">Create Account</a>
            </p>

        </section>
    </main>

    <footer class="footer">
        © <?php echo date("Y"); ?> The Knot House.
        Handmade with love ♡
    </footer>

</body>
</html>