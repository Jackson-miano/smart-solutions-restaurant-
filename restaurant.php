<?php
session_start();

// Only logged-in users can see this page
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>
<?php
require "config.php";
require_login();

$menu = $pdo->query("SELECT name, description, price FROM menu_items WHERE available = 1 ORDER BY name")
            ->fetchAll(PDO::FETCH_ASSOC);

page_top("SmartSolutionsRestaurant");
?>
<section class="hero">
    <div class="hero-copy">
        <p class="hero-hello">Welcome back, <?= e($_SESSION["first_name"]) ?></p>
        <h1>
            <span>Book your table.</span>
            <span>Order before you arrive.</span>
        </h1>
        <p>Pick a time, choose your meals, and your table is ready when you walk in.</p>
        <a class="btn" href="reserve.php">Book a table</a>
    </div>

    <div class="menu-board">
        <h2>On the menu</h2>
        <?php if (!$menu): ?>
            <p class="empty">The menu is being updated. Check back soon.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($menu as $m): ?>
                    <li>
                        <div class="menu-line">
                            <span class="dish"><?= e($m["name"]) ?></span>
                            <span class="leader"></span>
                            <span class="price">KES <?= number_format($m["price"]) ?></span>
                        </div>
                        <p class="desc"><?= e($m["description"]) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
<?php page_bottom(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartSolutionsRestaurant</title>
</head>
<body>
    <h1>Welcome, <?= htmlspecialchars($_SESSION["first_name"]) ?>!</h1>
    <p>Smart Solutions Restaurant</p>
    <a href="login.php?logout=1">Log out</a>
</body>
</html>