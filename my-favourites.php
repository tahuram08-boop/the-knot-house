
<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "knot_house";

// Check customer login
if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit;
}

// Connect to database
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed. Please try again later.");
}

$conn->set_charset("utf8mb4");

$customerId = (int) $_SESSION["customer_id"];

// Fetch favourite products
$sql = "SELECT
            p.id,
            p.product_name,
            p.category,
            p.price,
            p.description,
            p.image,
            p.stock,
            p.status,
            f.created_at
        FROM favourites AS f
        INNER JOIN products AS p ON f.product_id = p.id
        WHERE f.customer_id = ?
        ORDER BY f.created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $conn->close();
    die("Unable to load favourite products.");
}

$stmt->bind_param("i", $customerId);
$stmt->execute();

$result = $stmt->get_result();

$favourites = [];

while ($row = $result->fetch_assoc()) {
    $favourites[] = $row;
}

$stmt->close();
$conn->close();

// Escape output safely
function escape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Prepare the image path
function getImagePath($image)
{
    $image = trim((string) $image);

    // Remove ../ prefixes
    $image = preg_replace('/^(\.\.\/)+/', '', $image);

    // Remove a leading slash
    $image = ltrim($image, '/');

    return $image;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Favourites | The Knot House</title>

    <meta name="description"
          content="Explore your saved handmade crochet favourites at The Knot House.">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    <style>
        :root {
            --pink: #a96771;
            --dark-pink: #8d555e;
            --light-pink: #f8e8e7;
            --pink-soft: #f3d8d9;
            --cream: #fffaf7;
            --cream-dark: #f6eee9;
            --brown: #513d3d;
            --brown-light: #806969;
            --border: #ead9d6;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--brown);
            min-height: 100vh;
        }

        /* Header */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 250, 247, 0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 12px 5%;
        }

        .navbar {
            max-width: 1250px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--brown);
            text-decoration: none;
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .logo img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        nav {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        nav a {
            text-decoration: none;
            color: var(--brown);
            font-size: 13px;
            font-weight: 600;
            transition: color 0.2s;
        }

        nav a:hover {
            color: var(--pink);
        }

        .favourites-nav {
            background: var(--light-pink);
            color: var(--dark-pink);
            padding: 10px 14px;
            border-radius: 25px;
        }

        /* Page heading */
        .page-title {
            text-align: center;
            padding: 52px 20px 35px;
        }

        .eyebrow {
            color: var(--pink);
            letter-spacing: 3px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 13px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 5vw, 46px);
            font-weight: 600;
            margin-bottom: 13px;
        }

        .page-title p {
            color: var(--brown-light);
            font-size: 14px;
            line-height: 1.8;
        }

        .favourite-count {
            display: inline-block;
            margin-top: 16px;
            background: var(--light-pink);
            color: var(--dark-pink);
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 700;
        }

        /* Product grid */
        .container {
            max-width: 1250px;
            margin: auto;
            padding: 8px 5% 75px;
            min-height: 300px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 24px;
            align-items: stretch;
        }

        .product-card {
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(141, 85, 94, 0.12);
        }

        /* Consistent image dimensions */
        .image-wrapper {
            position: relative;
            width: 100%;
            height: 260px;
            background: var(--cream-dark);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            padding: 10px;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.03);
        }

        .image-fallback {
            display: none;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 10px;
            color: var(--dark-pink);
            text-align: center;
            padding: 20px;
        }

        .image-fallback span {
            font-size: 42px;
        }

        .image-fallback small {
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .heart-icon {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--white);
            color: var(--dark-pink);
            font-size: 19px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        /* Product details */
        .product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 18px;
        }

        .category {
            color: var(--pink);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 9px;
        }

        .product-info h2 {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 9px;
            overflow-wrap: anywhere;
        }

        .description {
            color: var(--brown-light);
            font-size: 12px;
            line-height: 1.7;
            margin-bottom: 13px;
            overflow-wrap: anywhere;
        }

        .price {
            color: var(--dark-pink);
            font-size: 20px;
            font-weight: 700;
            margin-top: auto;
            padding-top: 6px;
            margin-bottom: 12px;
        }

        .stock {
            font-size: 11px;
            margin-bottom: 15px;
        }

        .in-stock {
            color: #43845b;
        }

        .out-of-stock {
            color: #bd4545;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            min-width: 0;
            min-height: 40px;
            padding: 10px 8px;
            border: none;
            border-radius: 7px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .view-btn {
            background: var(--pink);
            color: var(--white);
        }

        .view-btn:hover {
            background: var(--dark-pink);
        }

        .remove-btn {
            background: var(--light-pink);
            color: var(--dark-pink);
        }

        .remove-btn:hover {
            background: var(--pink-soft);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Empty favourites */
        .empty {
            max-width: 650px;
            margin: 0 auto;
            text-align: center;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 55px 25px;
        }

        .empty-heart {
            font-size: 60px;
            color: var(--pink);
            margin-bottom: 15px;
        }

        .empty h2 {
            font-family: 'Playfair Display', serif;
            font-size: 27px;
            margin-bottom: 12px;
        }

        .empty p {
            color: var(--brown-light);
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 23px;
        }

        .shop-btn {
            display: inline-block;
            background: var(--pink);
            color: var(--white);
            text-decoration: none;
            padding: 13px 23px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
        }

        .shop-btn:hover {
            background: var(--dark-pink);
        }

        /* Footer */
        footer {
            background: var(--light-pink);
            text-align: center;
            padding: 25px 15px;
            font-size: 12px;
            color: var(--brown);
        }

        /* Responsive layouts */
        @media (max-width: 1050px) {
            .product-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 18px;
            }

            .image-wrapper {
                height: 240px;
            }

            .navbar {
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        @media (max-width: 720px) {
            nav {
                gap: 13px;
            }

            .product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .image-wrapper {
                height: 210px;
            }

            .product-info {
                padding: 14px;
            }

            .product-info h2 {
                font-size: 17px;
            }
        }

        @media (max-width: 420px) {
            .image-wrapper {
                height: 170px;
            }

            .product-image {
                padding: 6px;
            }

            .product-info {
                padding: 10px;
            }

            .product-info h2 {
                font-size: 15px;
            }

            .description {
                font-size: 11px;
            }

            .price {
                font-size: 17px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="navbar">

        <a href="index.html" class="logo">
            <img src="logo.png" alt="The Knot House Logo">
            <span>The Knot House</span>
        </a>

        <nav>
            <a href="index.html">Home</a>
            <a href="about.html">About Us</a>
            <a href="product.html">Products</a>
            <a href="gallery.html">Gallery</a>
            <a href="contact.html">Contact Us</a>
            <a href="my-favourites.php" class="favourites-nav">
                ♥ Favourites
            </a>
        </nav>

    </div>
</header>

<section class="page-title">

    <div class="eyebrow">SAVED WITH LOVE</div>

    <h1>My Favourites ♡</h1>

    <p>
        Your favourite handmade crochet pieces,
        all together in one place.
    </p>

    <div style="display: flex; justify-content: center; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 16px;">

    <div class="favourite-count" id="favouriteCount">
        <?php echo count($favourites); ?>
        <?php echo count($favourites) === 1 ? 'Favourite Item' : 'Favourite Items'; ?>
    </div>

    <a href="my-account.php" class="shop-btn">
        ← Back to My Account
    </a>

</div>

</section>

<main class="container">

    <?php if (empty($favourites)): ?>

        <section class="empty">

            <div class="empty-heart">♡</div>

            <h2>No favourites yet!</h2>

            <p>
                You haven't saved any crochet treasures yet.
                Explore our handmade collection and save
                the pieces you love.
            </p>

            <a href="product.html" class="shop-btn">
                Explore Products
            </a>

        </section>

    <?php else: ?>

        <div class="product-grid" id="productGrid">

            <?php foreach ($favourites as $product): ?>

                <?php
                $imagePath = getImagePath($product["image"]);
                $stock = (int) $product["stock"];
                $status = strtolower(trim((string) $product["status"]));
                $available = $stock > 0 && $status === "active";
                ?>

                <article
                    class="product-card"
                    data-product-id="<?php echo (int) $product["id"]; ?>"
                >

                    <div class="image-wrapper">

                        <?php if ($imagePath !== ""): ?>

                            <img
                                class="product-image"
                                src="<?php echo escape($imagePath); ?>"
                                alt="<?php echo escape($product["product_name"]); ?>"
                                loading="lazy"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            >

                            <div class="image-fallback">
                                <span>🧶</span>
                                <small>
                                    Image unavailable
                                </small>
                            </div>

                        <?php else: ?>

                            <div class="image-fallback" style="display:flex;">
                                <span>🧶</span>
                                <small>Image unavailable</small>
                            </div>

                        <?php endif; ?>

                        <span class="heart-icon" aria-label="Favourite">
                            ♥
                        </span>

                    </div>

                    <div class="product-info">

                        <div class="category">
                            <?php echo escape($product["category"]); ?>
                        </div>

                        <h2>
                            <?php echo escape($product["product_name"]); ?>
                        </h2>

                        <p class="description">
                            <?php echo escape($product["description"]); ?>
                        </p>

                        <div class="price">
                            ₹<?php echo number_format((float) $product["price"], 2); ?>
                        </div>

                        <div class="stock <?php echo $available ? 'in-stock' : 'out-of-stock'; ?>">
                            <?php
                            echo $available
                                ? "In Stock: " . $stock
                                : "Currently Unavailable";
                            ?>
                        </div>

                        <div class="actions">

                            <a href="product.html" class="btn view-btn">
                                Shop Products
                            </a>

                            <button
                                type="button"
                                class="btn remove-btn"
                                onclick="removeFavourite(<?php echo (int) $product["id"]; ?>, this)"
                            >
                                ♥ Remove
                            </button>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<footer>
    © <?php echo date("Y"); ?> The Knot House.
    Handmade with love ♡
</footer>

<script>
async function removeFavourite(productId, button) {

    if (!confirm("Remove this product from your favourites?")) {
        return;
    }

    button.disabled = true;
    button.textContent = "Removing...";

    try {

        const formData = new FormData();
        formData.append("action", "toggle");
        formData.append("product_id", productId);

        const response = await fetch("toggle-favourite.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error("Server request failed.");
        }

        if (data.success && data.favourite === false) {

            const card = button.closest(".product-card");

            if (card) {
                card.remove();
            }

            const remainingCards =
                document.querySelectorAll(".product-card").length;

            const countElement =
                document.getElementById("favouriteCount");

            if (countElement) {
                countElement.textContent =
                    remainingCards +
                    (remainingCards === 1
                        ? " Favourite Item"
                        : " Favourite Items");
            }

            if (remainingCards === 0) {
                window.location.reload();
            }

        } else {

            if (data.login_required) {
                alert("Please log in to manage your favourites.");
                window.location.href = "login.php";
                return;
            }

            alert(data.message || "Unable to remove this favourite.");

            button.disabled = false;
            button.textContent = "♥ Remove";
        }

    } catch (error) {

        alert("Something went wrong. Please try again.");

        button.disabled = false;
        button.textContent = "♥ Remove";
    }
}
</script>

</body>
</html>
