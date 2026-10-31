<?php
session_start();

// Check whether a customer is logged in
if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

// Get customer details before destroying the session
$customerName = $_SESSION["customer_name"] ?? "Customer";

// Escape the name before using it in JavaScript
$customerNameJS = json_encode($customerName);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Logout | The Knot House</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #fffaf7;
            color: #513d3d;
        }

        .logout-card {
            width: 100%;
            max-width: 430px;
            padding: 35px 25px;
            text-align: center;
            background: #ffffff;
            border: 1px solid #ead9d6;
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(141, 85, 94, 0.10);
        }

        .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        h1 {
            color: #8d555e;
            margin-bottom: 12px;
        }

        p {
            color: #806969;
            line-height: 1.7;
            font-size: 14px;
        }

        .button {
            display: inline-block;
            margin: 10px 5px 0;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }

        .login-button {
            background: #a96771;
            color: white;
        }

        .home-button {
            background: #f8e8e7;
            color: #8d555e;
        }
    </style>
</head>

<body>

<div class="logout-card">
    <div class="icon">🌸</div>

    <h1>Confirm Logout</h1>

    <p>
        Are you sure you want to log out of your
        The Knot House customer account?
    </p>

    <button type="button" class="button login-button"
            onclick="confirmLogout()">
        Yes, Log Me Out
    </button>

    <button type="button" class="button home-button"
            onclick="cancelLogout()">
        No, Stay Logged In
    </button>
</div>

<script>
    function confirmLogout() {
        // Submit a POST request to log out
        const form = document.createElement("form");
        form.method = "POST";
        form.action = "logout.php";

        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "confirm_logout";
        input.value = "yes";

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }

    function cancelLogout() {
        // Keep the customer logged in
        window.location.href = "my-account.php";
    }
</script>

</body>
</html>

<?php
// Process logout only after customer confirmation
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["confirm_logout"]) &&
    $_POST["confirm_logout"] === "yes"
) {
    // Save the customer's name before destroying the session
    $loggedOutName = $_SESSION["customer_name"] ?? "Customer";

    // Destroy the customer session
    $_SESSION = array();
    session_destroy();

    // Show a confirmation message after logout
    echo '<script>
        alert("You have successfully logged out of "
            + ' . json_encode($loggedOutName) . '
            + "\'s account!");
        window.location.href = "login.php";
    </script>';

    exit();
}
?>