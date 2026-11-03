<?php
require "config.php";
require_admin();

$msg = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // ---------- Change a reservation's status ----------
    if ($action === "status") {
        $status = $_POST["status"] ?? "";
        if (in_array($status, ["pending", "confirmed", "cancelled"], true)) {
            $s = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
            $s->execute([$status, (int)$_POST["id"]]);
            $msg = "Reservation updated.";
        }
    }

    // ---------- Add a menu item ----------
    elseif ($action === "add_item") {
        $name  = trim($_POST["name"] ?? "");
        $desc  = trim($_POST["description"] ?? "");
        $price = $_POST["price"] ?? "";
        if ($name === "") $errors[] = "Item name is required.";
        if (!is_numeric($price) || $price <= 0) $errors[] = "Enter a valid price.";
        if (empty($errors)) {
            $s = $pdo->prepare("INSERT INTO menu_items (name, description, price) VALUES (?, ?, ?)");
            $s->execute([$name, $desc, $price]);
            $msg = "Menu item added.";
        }
    }

    // ---------- Show or hide a menu item ----------
    elseif ($action === "toggle_item") {
        $s = $pdo->prepare("UPDATE menu_items SET available = 1 - available WHERE id = ?");
        $s->execute([(int)$_POST["id"]]);
        $msg = "Menu item updated.";
    }
}

$sql = "SELECT r.*, t.table_name, u.first_name, u.last_name, u.phone,
            (SELECT GROUP_CONCAT(CONCAT(mi.name, ' x', ri.quantity) SEPARATOR ', ')
               FROM reservation_items ri JOIN menu_items mi ON mi.id = ri.menu_item_id
              WHERE ri.reservation_id = r.id) AS items
        FROM reservations r
        JOIN restaurant_tables t ON t.id = r.table_id
        JOIN users u ON u.id = r.user_id
        ORDER BY r.res_date DESC, r.res_time DESC";
$reservations = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$menu = $pdo->query("SELECT * FROM menu_items ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

page_top("Admin Dashboard");
?>
<h2>Admin Dashboard</h2>
<?php if ($msg): ?><div class="success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($errors): ?>
    <div class="error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<h3>All Reservations</h3>
<div class="scroll">
<table>
    <tr><th>#</th><th>Customer</th><th>When</th><th>Table</th><th>Pre-order</th><th>Status</th></tr>
    <?php foreach ($reservations as $r): ?>
        <tr>
            <td><?= e($r["id"]) ?></td>
            <td><?= e($r["first_name"] . " " . $r["last_name"]) ?><br><small><?= e($r["phone"]) ?></small></td>
            <td><?= e($r["res_date"]) ?><br><?= e(substr($r["res_time"], 0, 5)) ?></td>
            <td><?= e($r["table_name"]) ?> (<?= e($r["guests"]) ?> guests)</td>
            <td><?= e($r["items"] ?? "-") ?></td>
            <td>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                    <select name="status">
                        <?php foreach (["pending", "confirmed", "cancelled"] as $st): ?>
                            <option value="<?= $st ?>" <?= $r["status"] === $st ? "selected" : "" ?>><?= ucfirst($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Save</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</div>

<h3>Menu Items</h3>
<div class="scroll">
<table>
    <tr><th>Name</th><th>Price (KES)</th><th>Shown to customers?</th><th></th></tr>
    <?php foreach ($menu as $m): ?>
        <tr>
            <td><?= e($m["name"]) ?><br><small><?= e($m["description"]) ?></small></td>
            <td><?= number_format($m["price"], 2) ?></td>
            <td><?= $m["available"] ? "Yes" : "Hidden" ?></td>
            <td>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="toggle_item">
                    <input type="hidden" name="id" value="<?= (int)$m["id"] ?>">
                    <button type="submit"><?= $m["available"] ? "Hide" : "Show" ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</div>

<h3>Add Menu Item</h3>
<form action="" method="POST" class="card">
    <input type="hidden" name="action" value="add_item">
    <label for="name">Name:</label>
    <input type="text" name="name" id="name" required>
    <label for="description">Description:</label>
    <input type="text" name="description" id="description">
    <label for="price">Price (KES):</label>
    <input type="number" step="0.01" min="0.01" name="price" id="price" required>
    <button type="submit">Add item</button>
</form>
<?php page_bottom(); ?>