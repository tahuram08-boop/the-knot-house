<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "knot_house");

if ($conn->connect_error) {
    die("Database connection failed. Please check MySQL in XAMPP.");
}

$conn->set_charset("utf8mb4");

// Only accept form submissions
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.html");
    exit();
}

// Get registration form values
$fullName = trim($_POST["full_name"] ?? "");
$email = strtolower(trim($_POST["email"] ?? ""));
$phone = trim($_POST["phone"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

$error = "";

// Validate form
if ($fullName === "" || $email === "" || $password === "" || $confirmPassword === "") {
    $error = "Please fill in all required fields.";
} elseif (strlen($fullName) < 2) {
    $error = "Please enter your full name.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Please enter a valid email address.";
} elseif (strlen($password) < 8) {
    $error = "Password must contain at least 8 characters.";
} elseif ($password !== $confirmPassword) {
    $error = "Passwords do not match.";
}

// Check whether the email already exists
if ($error === "") {
    $check = $conn->prepare(
        "SELECT id FROM customers WHERE email = ? LIMIT 1"
    );

    if (!$check) {
        $error = "Unable to check your email. Please try again.";
    } else {
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "This email is already registered. Please log in instead.";
        }

        $check->close();
    }
}

// Create the new customer account
if ($error === "") {

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insert = $conn->prepare(
        "INSERT INTO customers (full_name, email, phone, password)
         VALUES (?, ?, ?, ?)"
    );

    if (!$insert) {
        $error = "Unable to create your account. Please try again.";
    } else {

        $insert->bind_param(
            "ssss",
            $fullName,
            $email,
            $phone,
            $hashedPassword
        );

        if ($insert->execute()) {

            // Get the ID of the customer just inserted
            $customerId = $conn->insert_id;

            $insert->close();
            $conn->close();

            // Start a fresh session for this newly registered customer
            session_regenerate_id(true);

            $_SESSION["customer_id"] = $customerId;
            $_SESSION["customer_name"] = $fullName;
            $_SESSION["customer_email"] = $email;

            // Open this customer's account page
            header("Location: my-account.php");
            exit();

        } else {

            if ($insert->errno === 1062) {
                $error = "This email is already registered. Please log in instead.";
            } else {
                $error = "Account creation failed. Please check your database and try again.";
            }

            $insert->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registration Error | The Knot House</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fffaf7;
            color: #513d3d;
            font-family: "DM Sans", sans-serif;
        }

        .error-card {
            width: 100%;
            max-width: 480px;
            padding: 35px;
            text-align: center;
            background: #ffffff;
            border: 1px solid #ead9d6;
            border-radius: 18px;
            box-shadow: 0 12px 35px rgba(141, 85, 94, 0.10);
        }

        h1 {
            font-family: "Playfair Display", serif;
            color: #8d555e;
        }

        p {
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .btn {
            display: inline-block;
            margin: 8px 5px 0;
            padding: 12px 20px;
            border-radius: 8px;
            background: #a96771;
            color: white;
            text-decoration: none;
            font-weight: 600;
        }

        .btn:hover {
            background: #8d555e;
        }
    </style>
</head>

<body>
    <div class="error-card">
        <h1>Account Not Created</h1>

        <p>
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>

        <a class="btn" href="register.html">Try Again</a>
        <a class="btn" href="login.php">Login</a>
    </div>
</body>
</html>
