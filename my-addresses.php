
<?php
session_start();

/* Check customer login */
if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit;
}

/* Database connection */
$host = "localhost";
$username = "root";
$password = "";
$database = "knot_house";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed. Please check XAMPP.");
}

$conn->set_charset("utf8mb4");

$customerId = (int) $_SESSION["customer_id"];
$message = "";
$messageType = "success";

/* Create addresses table automatically */
$sql = "CREATE TABLE IF NOT EXISTS customer_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    address_label VARCHAR(50) NOT NULL DEFAULT 'Home',
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address_line VARCHAR(255) NOT NULL,
    landmark VARCHAR(150) DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    die("Unable to create the addresses table.");
}

/* Safely display text */
function escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/* Handle form submissions */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    /* ADD OR UPDATE ADDRESS */
    if ($action === "save") {

        $addressId = (int)($_POST["address_id"] ?? 0);
        $label = trim($_POST["address_label"] ?? "Home");
        $fullName = trim($_POST["full_name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $addressLine = trim($_POST["address_line"] ?? "");
        $landmark = trim($_POST["landmark"] ?? "");
        $city = trim($_POST["city"] ?? "");
        $state = trim($_POST["state"] ?? "");
        $pincode = trim($_POST["pincode"] ?? "");

        if (!in_array($label, ["Home", "Work", "Other"], true)) {
            $label = "Home";
        }

        if (
            $fullName === "" ||
            $phone === "" ||
            $addressLine === "" ||
            $city === "" ||
            $state === "" ||
            $pincode === ""
        ) {
            $message = "Please fill in all required fields.";
            $messageType = "error";

        } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {
            $message = "Please enter a valid 6-digit PIN code.";
            $messageType = "error";

        } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $message = "Please enter a valid phone number.";
            $messageType = "error";

        } else {

            /* UPDATE EXISTING ADDRESS */
            if ($addressId > 0) {

                $stmt = $conn->prepare(
                    "UPDATE customer_addresses
                     SET address_label = ?,
                         full_name = ?,
                         phone = ?,
                         address_line = ?,
                         landmark = ?,
                         city = ?,
                         state = ?,
                         pincode = ?
                     WHERE id = ? AND customer_id = ?"
                );

                $stmt->bind_param(
                    "ssssssssii",
                    $label,
                    $fullName,
                    $phone,
                    $addressLine,
                    $landmark,
                    $city,
                    $state,
                    $pincode,
                    $addressId,
                    $customerId
                );

                if ($stmt->execute() && $stmt->affected_rows >= 0) {
                    $message = "Address updated successfully.";
                } else {
                    $message = "Unable to update the address.";
                    $messageType = "error";
                }

                if ($stmt->affected_rows === 0) {
                    $check = $conn->prepare(
                        "SELECT id FROM customer_addresses
                         WHERE id = ? AND customer_id = ?"
                    );
                    $check->bind_param("ii", $addressId, $customerId);
                    $check->execute();
                    $exists = $check->get_result()->num_rows > 0;
                    $check->close();

                    if (!$exists) {
                        $message = "Address not found.";
                        $messageType = "error";
                    }
                }

                $stmt->close();

            } else {

                /* ADD NEW ADDRESS */
                $stmt = $conn->prepare(
                    "SELECT COUNT(*) AS total
                     FROM customer_addresses
                     WHERE customer_id = ?"
                );

                $stmt->bind_param("i", $customerId);
                $stmt->execute();
                $total = (int)$stmt->get_result()->fetch_assoc()["total"];
                $stmt->close();

                // First address becomes the default.
                $isDefault = ($total === 0) ? 1 : 0;

                $stmt = $conn->prepare(
                    "INSERT INTO customer_addresses
                    (customer_id, address_label, full_name, phone,
                     address_line, landmark, city, state, pincode, is_default)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "issssssssi",
                    $customerId,
                    $label,
                    $fullName,
                    $phone,
                    $addressLine,
                    $landmark,
                    $city,
                    $state,
                    $pincode,
                    $isDefault
                );

                if ($stmt->execute()) {
                    $message = "Address saved successfully.";
                } else {
                    $message = "Unable to save the address.";
                    $messageType = "error";
                }

                $stmt->close();
            }
        }
    }

    /* SET DEFAULT ADDRESS */
    elseif ($action === "default") {

        $addressId = (int)($_POST["address_id"] ?? 0);

        $check = $conn->prepare(
            "SELECT id FROM customer_addresses
             WHERE id = ? AND customer_id = ?"
        );
        $check->bind_param("ii", $addressId, $customerId);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {

            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare(
                    "UPDATE customer_addresses
                     SET is_default = 0
                     WHERE customer_id = ?"
                );
                $stmt->bind_param("i", $customerId);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare(
                    "UPDATE customer_addresses
                     SET is_default = 1
                     WHERE id = ? AND customer_id = ?"
                );
                $stmt->bind_param("ii", $addressId, $customerId);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "Default address updated successfully.";

            } catch (Throwable $e) {
                $conn->rollback();
                $message = "Unable to change the default address.";
                $messageType = "error";
            }

        } else {
            $message = "Address not found.";
            $messageType = "error";
        }
    }

    /* DELETE ADDRESS */
    elseif ($action === "delete") {

        $addressId = (int)($_POST["address_id"] ?? 0);

        // Check whether the address being deleted is the default.
        $check = $conn->prepare(
            "SELECT is_default FROM customer_addresses
             WHERE id = ? AND customer_id = ?"
        );
        $check->bind_param("ii", $addressId, $customerId);
        $check->execute();
        $address = $check->get_result()->fetch_assoc();
        $check->close();

        if ($address) {

            $wasDefault = (int)$address["is_default"] === 1;

            $stmt = $conn->prepare(
                "DELETE FROM customer_addresses
                 WHERE id = ? AND customer_id = ?"
            );
            $stmt->bind_param("ii", $addressId, $customerId);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            if ($deleted > 0) {
                $message = "Address deleted successfully.";

                // Only choose another default if the deleted one was default.
                if ($wasDefault) {
                    $check = $conn->prepare(
                        "SELECT id FROM customer_addresses
                         WHERE customer_id = ?
                         ORDER BY created_at DESC, id DESC LIMIT 1"
                    );
                    $check->bind_param("i", $customerId);
                    $check->execute();
                    $remaining = $check->get_result()->fetch_assoc();
                    $check->close();

                    if ($remaining) {
                        $newDefaultId = (int)$remaining["id"];

                        $stmt = $conn->prepare(
                            "UPDATE customer_addresses
                             SET is_default = 1
                             WHERE id = ? AND customer_id = ?"
                        );
                        $stmt->bind_param(
                            "ii",
                            $newDefaultId,
                            $customerId
                        );
                        $stmt->execute();
                        $stmt->close();
                    }
                }

            } else {
                $message = "Unable to delete the address.";
                $messageType = "error";
            }

        } else {
            $message = "Address not found.";
            $messageType = "error";
        }
    }

    else {
        $message = "Invalid request.";
        $messageType = "error";
    }
}

/* Load address for editing */
$editAddress = null;
$editId = (int)($_GET["edit"] ?? 0);

if ($editId > 0) {
    $stmt = $conn->prepare(
        "SELECT * FROM customer_addresses
         WHERE id = ? AND customer_id = ?"
    );
    $stmt->bind_param("ii", $editId, $customerId);
    $stmt->execute();
    $editAddress = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* Load customer's saved addresses */
$stmt = $conn->prepare(
    "SELECT * FROM customer_addresses
     WHERE customer_id = ?
     ORDER BY is_default DESC, created_at DESC, id DESC"
);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

function fieldValue($address, $field)
{
    return escape($address[$field] ?? "");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Addresses | The Knot House</title>
    <meta name="description"
          content="Manage your delivery addresses at The Knot House.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
          rel="stylesheet">

    <style>
        :root {
            --pink: #a96771;
            --pink-dark: #8d555e;
            --pink-light: #f8e8e7;
            --cream: #fffaf7;
            --brown: #513d3d;
            --brown-light: #806969;
            --border: #ead9d6;
            --white: #ffffff;
            --green: #52775b;
            --red: #a23e45;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "DM Sans", sans-serif;
            background: var(--cream);
            color: var(--brown);
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button, input, select, textarea {
            font: inherit;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(255, 250, 247, 0.97);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }

        .nav-inner {
            max-width: 1200px;
            min-height: 82px;
            padding: 12px 24px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            font-family: "Playfair Display", serif;
            font-size: 23px;
            font-weight: 700;
            color: var(--pink-dark);
            flex-shrink: 0;
        }

        .brand img {
            width: 52px;
            height: 52px;
            object-fit: contain;
        }

        .nav-links {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px 21px;
            font-size: 13px;
            font-weight: 600;
        }

        .nav-links a:hover,
        .nav-links .active {
            color: var(--pink);
        }

        .page {
            width: min(1100px, 92%);
            margin: 45px auto 70px;
        }

        .page-heading {
            margin-bottom: 30px;
        }

        .eyebrow {
            color: var(--pink);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        h1, h2 {
            font-family: "Playfair Display", serif;
        }

        h1 {
            font-size: clamp(30px, 4vw, 43px);
            margin: 8px 0;
        }

        .subtitle {
            color: var(--brown-light);
            font-size: 15px;
        }

        .notice {
            margin-bottom: 22px;
            padding: 13px 16px;
            border-radius: 10px;
            font-size: 14px;
            background: #eaf3e9;
            color: #315c3a;
        }

        .notice.error {
            background: #fbe8e7;
            color: #963f43;
        }

        .section-title {
            font-size: 25px;
            margin-bottom: 18px;
        }

        .address-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 45px;
        }

        .address-card {
            padding: 23px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 6px 22px rgba(81, 61, 61, 0.04);
            overflow-wrap: anywhere;
        }

        .address-card.default {
            border: 1.5px solid var(--pink);
        }

        .card-top {
            margin-bottom: 14px;
        }

        .address-label {
            display: inline-block;
            padding: 5px 11px;
            border-radius: 20px;
            background: var(--pink-light);
            color: var(--pink-dark);
            font-size: 12px;
            font-weight: 700;
        }

        .default-label {
            margin-top: 8px;
            color: var(--green);
            font-size: 12px;
            font-weight: 700;
        }

        .person-name {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .address-text {
            color: var(--brown-light);
            font-size: 14px;
            line-height: 1.8;
        }

        .card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .btn-primary {
            background: var(--pink);
            color: white;
        }

        .btn-primary:hover {
            background: var(--pink-dark);
        }

        .btn-outline {
            background: white;
            border: 1px solid var(--border);
            color: var(--brown);
        }

        .btn-outline:hover {
            background: var(--pink-light);
        }

        .btn-delete {
            color: var(--red);
            background: #fff2f1;
        }

        .btn-delete:hover {
            background: #fbe1df;
        }

        .address-form {
            background: white;
            padding: 28px;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 6px 22px rgba(81, 61, 61, 0.04);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            font-weight: 700;
        }

        .required {
            color: var(--pink);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 13px;
            color: var(--brown);
            background: #fffdfc;
            border: 1px solid var(--border);
            border-radius: 9px;
            outline: none;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(169, 103, 113, 0.1);
        }

        textarea {
            min-height: 95px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 22px;
        }

        .empty-state {
            padding: 38px 20px;
            margin-bottom: 40px;
            text-align: center;
            border: 1px dashed var(--border);
            border-radius: 16px;
            background: white;
        }

        .empty-icon {
            font-size: 40px;
            margin-bottom: 8px;
        }

        .empty-state p {
            margin: 8px 0 18px;
            color: var(--brown-light);
        }

        footer {
            padding: 22px;
            text-align: center;
            background: var(--pink-light);
            color: var(--brown-light);
            font-size: 13px;
        }

        @media (max-width: 760px) {
            .nav-inner {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            .address-grid {
                grid-template-columns: 1fr;
            }

            .page {
                margin-top: 30px;
            }
        }

        @media (max-width: 520px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .address-form,
            .address-card {
                padding: 18px;
            }

            .brand {
                font-size: 21px;
            }

            .brand img {
                width: 43px;
                height: 43px;
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
    background: var(--pink-light);
    color: var(--pink-dark);
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    transition: 0.2s ease;
}

.back-btn:hover {
    background: var(--pink);
    color: white;
}
    </style>
</head>

<body>

<header class="navbar">
    <div class="nav-inner">

        <a href="index.html" class="brand">
            <img src="logo.png" alt="The Knot House Logo">
            <span>The Knot House</span>
        </a>

        <nav class="nav-links" aria-label="Main navigation">
            <a href="index.html">Home</a>
            <a href="about.html">About Us</a>
            <a href="product.html">Products</a>
            <a href="gallery.html">Gallery</a>
            <a href="contact.html">Contact Us</a>
            <a href="my-favourites.php">My Favourites</a>
            <a href="my-addresses.php" class="active">My Addresses</a>
        </nav>

    </div>
</header>

<main class="page">

    <div class="page-heading">
    <p class="eyebrow">Your Account</p>

    <h1>My Addresses</h1>

    <p class="subtitle">
        Save your delivery addresses for a smoother checkout experience.
    </p>

    <div class="heading-actions">
        <a href="my-account.php" class="back-btn">
            ← Back to My Account
        </a>
    </div>
</div>

    <?php if ($message !== ""): ?>
        <div class="notice <?= $messageType === "error" ? "error" : "" ?>">
            <?= escape($message) ?>
        </div>
    <?php endif; ?>

    <section>
        <h2 class="section-title">
            Saved Addresses (<?= count($addresses) ?>)
        </h2>

        <?php if (empty($addresses)): ?>

            <div class="empty-state">
                <div class="empty-icon">📍</div>
                <h2>No saved addresses yet</h2>
                <p>Add your first delivery address using the form below.</p>
            </div>

        <?php else: ?>

            <div class="address-grid">

                <?php foreach ($addresses as $address): ?>

                    <article class="address-card
                        <?= $address["is_default"] ? "default" : "" ?>">

                        <div class="card-top">
                            <span class="address-label">
                                <?= escape($address["address_label"]) ?>
                            </span>

                            <?php if ($address["is_default"]): ?>
                                <div class="default-label">
                                    ✓ Default Address
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="person-name">
                            <?= escape($address["full_name"]) ?>
                        </div>

                        <div class="address-text">
                            <?= escape($address["address_line"]) ?><br>

                            <?php if (!empty($address["landmark"])): ?>
                                Near <?= escape($address["landmark"]) ?><br>
                            <?php endif; ?>

                            <?= escape($address["city"]) ?>,
                            <?= escape($address["state"]) ?> -
                            <?= escape($address["pincode"]) ?><br>

                            Phone: <?= escape($address["phone"]) ?>
                        </div>

                        <div class="card-actions">

                            <a class="btn btn-outline"
                               href="my-addresses.php?edit=<?= (int)$address["id"] ?>#address-form">
                                Edit
                            </a>

                            <?php if (!$address["is_default"]): ?>
                                <form method="post">
                                    <input type="hidden" name="action"
                                           value="default">
                                    <input type="hidden" name="address_id"
                                           value="<?= (int)$address["id"] ?>">

                                    <button class="btn btn-primary" type="submit">
                                        Set Default
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form method="post"
                                  onsubmit="return confirm('Are you sure you want to delete this address?');">
                                <input type="hidden" name="action"
                                       value="delete">
                                <input type="hidden" name="address_id"
                                       value="<?= (int)$address["id"] ?>">

                                <button class="btn btn-delete" type="submit">
                                    Delete
                                </button>
                            </form>

                        </div>
                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>
    </section>

    <section id="address-form">

        <h2 class="section-title">
            <?= $editAddress ? "Edit Address" : "Add a New Address" ?>
        </h2>

        <form class="address-form" method="post"
              action="my-addresses.php#address-form">

            <input type="hidden" name="action" value="save">

            <input type="hidden" name="address_id"
                   value="<?= (int)($editAddress["id"] ?? 0) ?>">

            <div class="form-grid">

                <div class="form-group">
                    <label for="address_label">
                        Address Type <span class="required">*</span>
                    </label>

                    <select id="address_label" name="address_label" required>
                        <?php
                        $currentLabel =
                            $editAddress["address_label"] ?? "Home";

                        foreach (["Home", "Work", "Other"] as $option):
                        ?>
                            <option value="<?= escape($option) ?>"
                                <?= $currentLabel === $option
                                    ? "selected" : "" ?>>
                                <?= escape($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="full_name">
                        Full Name <span class="required">*</span>
                    </label>

                    <input type="text" id="full_name" name="full_name"
                           maxlength="100" required
                           value="<?= fieldValue($editAddress, "full_name") ?>"
                           placeholder="Enter recipient's full name">
                </div>

                <div class="form-group">
                    <label for="phone">
                        Phone Number <span class="required">*</span>
                    </label>

                    <input type="tel" id="phone" name="phone"
                           maxlength="20" required
                           value="<?= fieldValue($editAddress, "phone") ?>"
                           placeholder="Enter contact number">
                </div>

                <div class="form-group">
                    <label for="pincode">
                        PIN Code <span class="required">*</span>
                    </label>

                    <input type="text" id="pincode" name="pincode"
                           inputmode="numeric" maxlength="6" minlength="6"
                           pattern="[0-9]{6}" required
                           value="<?= fieldValue($editAddress, "pincode") ?>"
                           placeholder="6-digit PIN code">
                </div>

                <div class="form-group full">
                    <label for="address_line">
                        Complete Address <span class="required">*</span>
                    </label>

                    <textarea id="address_line" name="address_line"
                              maxlength="255" required
                              placeholder="House/flat number, building, street, area"><?= fieldValue($editAddress, "address_line") ?></textarea>
                </div>

                <div class="form-group full">
                    <label for="landmark">Landmark (Optional)</label>

                    <input type="text" id="landmark" name="landmark"
                           maxlength="150"
                           value="<?= fieldValue($editAddress, "landmark") ?>"
                           placeholder="Nearby shop, school, or landmark">
                </div>

                <div class="form-group">
                    <label for="city">
                        City <span class="required">*</span>
                    </label>

                    <input type="text" id="city" name="city"
                           maxlength="100" required
                           value="<?= fieldValue($editAddress, "city") ?>"
                           placeholder="Enter city">
                </div>

                <div class="form-group">
                    <label for="state">
                        State <span class="required">*</span>
                    </label>

                    <input type="text" id="state" name="state"
                           maxlength="100" required
                           value="<?= fieldValue($editAddress, "state") ?>"
                           placeholder="Enter state">
                </div>

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    <?= $editAddress ? "Update Address" : "Save Address" ?>
                </button>

                <?php if ($editAddress): ?>
                    <a href="my-addresses.php#address-form"
                       class="btn btn-outline">
                        Cancel Edit
                    </a>
                <?php endif; ?>

            </div>
        </form>
    </section>

</main>

<footer>
    © <?= date("Y") ?> The Knot House · Handmade with love 🧶
</footer>

</body>
</html>
