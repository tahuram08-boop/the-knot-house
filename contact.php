<?php

header("Content-Type: application/json; charset=UTF-8");

/* =========================================================
   THE KNOT HOUSE - CONTACT MESSAGE HANDLER
   ========================================================= */


/* =========================================================
   DATABASE CONNECTION
   ========================================================= */

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


/* Check database connection */

if ($conn->connect_error) {

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit;
}


$conn->set_charset("utf8mb4");


/* =========================================================
   ALLOW ONLY POST REQUEST
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);

    $conn->close();

    exit;
}


/* =========================================================
   GET FORM DATA
   ========================================================= */

$name = isset($_POST["name"])
    ? trim($_POST["name"])
    : "";

$email = isset($_POST["email"])
    ? trim($_POST["email"])
    : "";

$phone = isset($_POST["phone"])
    ? trim($_POST["phone"])
    : "";

$subject = isset($_POST["subject"])
    ? trim($_POST["subject"])
    : "";

$message = isset($_POST["message"])
    ? trim($_POST["message"])
    : "";


/* =========================================================
   VALIDATION
   ========================================================= */

if (
    $name === "" ||
    $email === "" ||
    $subject === "" ||
    $message === ""
) {

    echo json_encode([
        "success" => false,
        "message" => "Please fill all required fields."
    ]);

    $conn->close();

    exit;
}


/* =========================================================
   EMAIL VALIDATION
   ========================================================= */

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email address."
    ]);

    $conn->close();

    exit;
}


/* =========================================================
   INSERT MESSAGE INTO DATABASE
   ========================================================= */

$sql = "
    INSERT INTO contact_messages
    (
        name,
        email,
        phone,
        subject,
        message,
        status
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        'unread'
    )
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare the message."
    ]);

    $conn->close();

    exit;
}


/* =========================================================
   BIND DATA
   ========================================================= */

$stmt->bind_param(
    "sssss",
    $name,
    $email,
    $phone,
    $subject,
    $message
);


/* =========================================================
   SAVE MESSAGE
   ========================================================= */

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Your message has been sent successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Unable to save your message."
    ]);
}


/* =========================================================
   CLOSE CONNECTION
   ========================================================= */

$stmt->close();

$conn->close();

?>