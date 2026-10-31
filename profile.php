<?php
session_start();

/* Only logged-in customers can edit their profile */
if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

/* Database connection */
$conn = new mysqli("localhost", "root", "", "knot_house");

if ($conn->connect_error) {
    die("Database connection failed. Please try again later.");
}

$conn->set_charset("utf8mb4");

$customerId = (int) $_SESSION["customer_id"];
$error = "";
$success = "";

/* Get current customer details */
$stmt = $conn->prepare(
    "SELECT id, full_name, email, phone, password
     FROM customers
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $customerId);
$stmt->execute();

$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$fullName = $customer["full_name"];
$email = $customer["email"];
$phone = $customer["phone"] ?? "";

/* Update profile */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullName = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    $currentPassword = $_POST["current_password"] ?? "";
    $newPassword = $_POST["new_password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($fullName === "" || $email === "") {

        $error = "Name and email address are required.";

    } elseif (strlen($fullName) > 100) {

        $error = "Name must not exceed 100 characters.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($phone) > 20) {

        $error = "Phone number must not exceed 20 characters.";

    } else {

        /* Check if another customer uses this email */
        $check = $conn->prepare(
            "SELECT id FROM customers
             WHERE email = ? AND id != ?
             LIMIT 1"
        );

        $check->bind_param("si", $email, $customerId);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $error = "This email is already used by another account.";

        } else {

            $changingPassword = (
                $currentPassword !== "" ||
                $newPassword !== "" ||
                $confirmPassword !== ""
            );

            if ($changingPassword) {

                if (
                    $currentPassword === "" ||
                    $newPassword === "" ||
                    $confirmPassword === ""
                ) {
                    $error = "Fill in all three password fields to change your password.";

                } elseif (!password_verify($currentPassword, $customer["password"])) {

                    $error = "Your current password is incorrect.";

                } elseif (strlen($newPassword) < 8) {

                    $error = "Your new password must contain at least 8 characters.";

                } elseif ($newPassword !== $confirmPassword) {

                    $error = "The new passwords do not match.";
                }
            }

            if ($error === "") {

                if ($changingPassword) {

                    $hashedPassword = password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    );

                    $update = $conn->prepare(
                        "UPDATE customers
                         SET full_name = ?, email = ?, phone = ?, password = ?
                         WHERE id = ?"
                    );

                    $update->bind_param(
                        "ssssi",
                        $fullName,
                        $email,
                        $phone,
                        $hashedPassword,
                        $customerId
                    );

                } else {

                    $update = $conn->prepare(
                        "UPDATE customers
                         SET full_name = ?, email = ?, phone = ?
                         WHERE id = ?"
                    );

                    $update->bind_param(
                        "sssi",
                        $fullName,
                        $email,
                        $phone,
                        $customerId
                    );
                }

                if ($update->execute()) {

                    $_SESSION["customer_name"] = $fullName;
                    $_SESSION["customer_email"] = $email;

                    $success = "Your profile has been updated successfully!";

                    /* Refresh current customer details */
                    $stmt = $conn->prepare(
                        "SELECT id, full_name, email, phone, password
                         FROM customers
                         WHERE id = ?
                         LIMIT 1"
                    );

                    $stmt->bind_param("i", $customerId);
                    $stmt->execute();

                    $result = $stmt->get_result();
                    $customer = $result->fetch_assoc();

                    $stmt->close();

                } else {

                    $error = "Unable to update your profile. Please try again.";
                }

                $update->close();
            }
        }

        $check->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profile | The Knot House</title>

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
            --green: #4d9569;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
            color: var(--brown);
            background: var(--cream);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }

        .navbar {
            max-width: 1100px;
            margin: auto;
            padding: 17px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            font-weight: 700;
            color: var(--dark-pink);
        }

        .back-link {
            color: var(--dark-pink);
            font-size: 13px;
            font-weight: 700;
        }

        .container {
            max-width: 720px;
            margin: 42px auto;
            padding: 0 20px;
        }

        .heading {
            margin-bottom: 23px;
        }

        .eyebrow {
            margin-bottom: 9px;
            color: var(--pink);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        h1, h2 {
            font-family: 'Playfair Display', serif;
        }

        h1 {
            margin-bottom: 9px;
            font-size: 34px;
        }

        .heading p {
            color: var(--brown-light);
            font-size: 14px;
            line-height: 1.7;
        }

        .form-card {
            padding: 30px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 12px 35px rgba(141, 85, 94, 0.07);
        }

        .section-title {
            margin-bottom: 20px;
            font-size: 22px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: var(--cream);
            color: var(--brown);
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        input:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(169, 103, 113, 0.10);
        }

        .hint {
            margin-top: 6px;
            color: var(--brown-light);
            font-size: 11px;
            line-height: 1.6;
        }

        .divider {
            height: 1px;
            margin: 28px 0;
            background: var(--border);
        }

        .message {
            padding: 13px 15px;
            margin-bottom: 20px;
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.6;
        }

        .error {
            color: #a53e3e;
            background: #fbeaea;
        }

        .success {
            color: #34774d;
            background: #e8f5ed;
        }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 13px 20px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-primary {
            background: var(--pink);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--dark-pink);
        }

        .btn-secondary {
            border-color: var(--border);
            background: var(--white);
            color: var(--brown);
        }

        .btn-secondary:hover {
            background: var(--light-pink);
        }

        .footer {
            margin-top: 45px;
            padding: 22px;
            border-top: 1px solid var(--border);
            text-align: center;
            color: var(--brown-light);
            font-size: 12px;
        }

        @media (max-width: 500px) {
            .navbar {
                padding: 15px;
            }

            .brand {
                font-size: 19px;
            }

            .container {
                margin-top: 25px;
            }

            .form-card {
                padding: 22px 17px;
            }

            h1 {
                font-size: 29px;
            }

            .buttons .btn {
                width: 100%;
            }
        }
		.heading-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 18px;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 16px;
    background: var(--light-pink);
    color: var(--dark-pink);
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    transition: 0.2s ease;
}

.back-btn:hover {
    background: var(--pink);
    color: var(--white);
}
    </style>
</head>

<body>

<header>
    <nav class="navbar">
        <a href="index.html" class="brand">🧶 The Knot House</a>

        <a href="my-account.php" class="back-link">
            ← My Account
        </a>
    </nav>
</header>

<main class="container">

    <section class="heading">
    <div class="eyebrow">Your personal details ♡</div>

    <h1>Edit Your Profile</h1>

    <p>
        Keep your account details up to date so your shopping
        experience stays simple and convenient.
    </p>

    <div class="heading-actions">
        <a href="my-account.php" class="back-btn">
            ← Back to My Account
        </a>
    </div>
</section>

    <section class="form-card">

        <?php if ($error !== ""): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="profile.php" method="POST">

            <h2 class="section-title">Personal Information</h2>

            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="100"
                    value="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="150"
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    autocomplete="email"
                    required
                >
                <p class="hint">
                    Your email is used to sign in. It must not already belong to another customer.
                </p>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    maxlength="20"
                    value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>"
                    autocomplete="tel"
                    placeholder="Enter your phone number"
                >
            </div>

            <div class="divider"></div>

            <h2 class="section-title">Change Password</h2>

            <p class="hint" style="margin-top: -12px; margin-bottom: 20px;">
                Leave all three password fields empty if you do not want to change your password.
            </p>

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    autocomplete="current-password"
                >
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    minlength="8"
                    autocomplete="new-password"
                >
                <p class="hint">Use at least 8 characters.</p>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

            <div class="buttons">
                <button type="submit" class="btn btn-primary">
                    Save Changes ♡
                </button>

                <a href="my-account.php" class="btn btn-secondary">
                    Cancel
                </a>
            </div>

        </form>

    </section>

</main>

<footer class="footer">
    © <?php echo date("Y"); ?> The Knot House. Handmade with love ♡
</footer>

</body>
</html>