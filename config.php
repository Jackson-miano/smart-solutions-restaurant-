<?php
session_start();

// ---------- Database connection (used by every page) ----------
$pdo = new PDO("mysql:host=localhost;dbname=restaurant_db;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Time slots customers can book (a booking holds a table for one slot)
$slots = ["12:00", "13:00", "14:00", "15:00", "18:00", "19:00", "20:00", "21:00"];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function require_login() {
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }
}

function is_admin() {
    global $pdo;
    if (!isset($_SESSION["user_id"])) return false;
    $s = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $s->execute([$_SESSION["user_id"]]);
    return $s->fetchColumn() === "admin";
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit("Access denied. Admins only.");
    }
}

// ---------- Shared page layout ----------
function page_top($title) {
    $admin = is_admin();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;600;700&family=Young+Serif&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="restaurant.css">
    <script src="restaurant.js" defer></script>
</head>
<body>
<header class="site-nav">
    <a class="brand" href="restaurant.php">Smart Solutions</a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-links">Menu</button>
    <nav class="nav-links" id="nav-links" aria-label="Main">
        <a href="restaurant.php">Home</a>
        <a href="reserve.php">Book a Table</a>
        <a href="my_reservations.php">My Reservations</a>
        <?php if ($admin): ?><a href="admin.php">Admin</a><?php endif; ?>
        <a href="login.php?logout=1">Log out</a>
    </nav>
</header>
<main class="container">
<?php
}

function page_bottom() {
    ?>
</main>
</body>
</html>
<?php
}