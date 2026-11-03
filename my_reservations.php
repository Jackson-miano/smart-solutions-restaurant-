<?php
require "config.php";
require_login();

$msg = "";

// ---------- Cancel a reservation (only your own) ----------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_id"])) {
    $s = $pdo->prepare(
        "UPDATE reservations SET status = 'cancelled'
         WHERE id = ? AND user_id = ? AND status <> 'cancelled'"
    );
    $s->execute([(int)$_POST["cancel_id"], $_SESSION["user_id"]]);
    $msg = $s->rowCount() ? "Reservation cancelled." : "That reservation could not be cancelled.";
}

$sql = "SELECT r.*, t.table_name,
            (SELECT GROUP_CONCAT(CONCAT(mi.name, ' x', ri.quantity) SEPARATOR ', ')
               FROM reservation_items ri JOIN menu_items mi ON mi.id = ri.menu_item_id
              WHERE ri.reservation_id = r.id) AS items,
            (SELECT SUM(mi.price * ri.quantity)
               FROM reservation_items ri JOIN menu_items mi ON mi.id = ri.menu_item_id
              WHERE ri.reservation_id = r.id) AS total
        FROM reservations r
        JOIN restaurant_tables t ON t.id = r.table_id
        WHERE r.user_id = ?
        ORDER BY r.res_date DESC, r.res_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION["user_id"]]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

page_top("My Reservations");
?>
<h2>My Reservations</h2>
<?php if ($msg): ?><div class="info"><?= e($msg) ?></div><?php endif; ?>

<?php if (!$rows): ?>
    <p>You have no reservations yet. <a href="reserve.php">Book a table</a></p>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
    <div class="card">
        <strong>#<?= e($r["id"]) ?> - <?= e($r["res_date"]) ?> at <?= e(substr($r["res_time"], 0, 5)) ?></strong><br>
        <?= e($r["table_name"]) ?>, <?= e($r["guests"]) ?> guest(s)<br>
        Status: <strong><?= e(ucfirst($r["status"])) ?></strong><br>
        <?php if ($r["items"]): ?>
            Pre-order: <?= e($r["items"]) ?> (KES <?= number_format($r["total"], 2) ?>)<br>
        <?php endif; ?>
        <?php if ($r["status"] !== "cancelled" && $r["res_date"] >= date("Y-m-d")): ?>
            <form action="" method="POST" onsubmit="return confirm('Cancel this reservation?');">
                <input type="hidden" name="cancel_id" value="<?= (int)$r["id"] ?>">
                <button type="submit">Cancel</button>
            </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<?php page_bottom(); ?>