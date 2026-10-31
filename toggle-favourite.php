
<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "knot_house";

// Send JSON response
function sendResponse($success, $message, $extra = [])
{
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));
    exit;
}

// Check customer login
if (!isset($_SESSION["customer_id"])) {
    sendResponse(false, "Please log in to save your favourite products.", [
        "login_required" => true
    ]);
}

// Connect to database
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    sendResponse(false, "Unable to connect to the database.");
}

$conn->set_charset("utf8mb4");

// Get logged-in customer ID
$customerId = (int) $_SESSION["customer_id"];

// Get requested action
$action = $_GET["action"] ?? $_POST["action"] ?? "";

// LIST FAVOURITES
if ($action === "list") {

    $stmt = $conn->prepare(
        "SELECT product_id
         FROM favourites
         WHERE customer_id = ?"
    );

    $stmt->bind_param("i", $customerId);
    $stmt->execute();

    $result = $stmt->get_result();
    $favourites = [];

    while ($row = $result->fetch_assoc()) {
        $favourites[] = (int) $row["product_id"];
    }

    $stmt->close();
    $conn->close();

    sendResponse(true, "Favourites loaded.", [
        "favourites" => $favourites
    ]);
}

// Validate action
if ($action !== "toggle") {
    $conn->close();
    sendResponse(false, "Invalid favourites request.");
}

// Get product ID
$productId = filter_input(
    INPUT_POST,
    "product_id",
    FILTER_VALIDATE_INT
);

if (!$productId || $productId < 1) {
    $conn->close();
    sendResponse(false, "Invalid product.");
}

// Check whether product exists
$stmt = $conn->prepare(
    "SELECT id FROM products WHERE id = ? LIMIT 1"
);

$stmt->bind_param("i", $productId);
$stmt->execute();

$productResult = $stmt->get_result();
$productExists = $productResult->num_rows > 0;

$stmt->close();

if (!$productExists) {
    $conn->close();
    sendResponse(false, "This product could not be found.");
}

// Check whether product is already a favourite
$stmt = $conn->prepare(
    "SELECT id
     FROM favourites
     WHERE customer_id = ? AND product_id = ?
     LIMIT 1"
);

$stmt->bind_param("ii", $customerId, $productId);
$stmt->execute();

$result = $stmt->get_result();
$isFavourite = $result->num_rows > 0;

$stmt->close();

// Remove favourite if already saved
if ($isFavourite) {

    $stmt = $conn->prepare(
        "DELETE FROM favourites
         WHERE customer_id = ? AND product_id = ?"
    );

    $stmt->bind_param("ii", $customerId, $productId);

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();

        sendResponse(false, "Unable to remove this favourite.");
    }

    $stmt->close();
    $conn->close();

    sendResponse(true, "Removed from favourites.", [
        "favourite" => false,
        "product_id" => $productId
    ]);
}

// Add product to favourites
$stmt = $conn->prepare(
    "INSERT INTO favourites (customer_id, product_id)
     VALUES (?, ?)"
);

$stmt->bind_param("ii", $customerId, $productId);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();

    sendResponse(false, "Unable to save this favourite.");
}

$stmt->close();
$conn->close();

sendResponse(true, "Added to favourites.", [
    "favourite" => true,
    "product_id" => $productId
]);
?>
