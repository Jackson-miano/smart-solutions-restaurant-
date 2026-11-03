<?php
require "config.php";
require_login();

$errors = [];
$info = "";
$confirmation = null;

$date   = $_POST["date"] ?? "";
$time   = $_POST["time"] ?? "";
$guests = (int)($_POST["guests"] ?? 2);
$qty    = $_POST["qty"] ?? [];
if (!is_array($qty)) $qty = [];

$menu = $pdo->query("SELECT id, name, price FROM menu_items WHERE available = 1 ORDER BY name")
            ->fetchAll(PDO::FETCH_ASSOC);

// Smallest table that fits the party and is not already booked in that slot
function find_table($pdo, $date, $time, $guests) {
    $sql = "SELECT id, table_name FROM restaurant_tables
            WHERE capacity >= ?
              AND id NOT IN (
                  SELECT table_id FROM reservations
                  WHERE res_date = ? AND res_time = ? AND status <> 'cancelled'
              )
            ORDER BY capacity ASC LIMIT 1";
    $s = $pdo->prepare($sql);
    $s->execute([$guests, $date, $time]);
    return $s->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "check";

    // ---------- Validation ----------
    $d = DateTime::createFromFormat("Y-m-d", $date);
    if (!$d || $d->format("Y-m-d") !== $date || $date < date("Y-m-d")) {
        $errors[] = "Choose a valid date (today or later).";
    }
    if (!in_array($time, $slots, true)) {
        $errors[] = "Choose a time slot.";
    }
    if ($guests < 1 || $guests > 20) {
        $errors[] = "Guests must be between 1 and 20.";
    }

    if (empty($errors)) {
        $table = find_table($pdo, $date, $time, $guests);

        if (!$table) {
            $errors[] = "No table is free for that date, time and party size. Please try another time.";
        } elseif ($action === "check") {
            $info = "A table for $guests is available on $date at $time. You can book it below.";
        } else {
            // ---------- Save booking to the database ----------
            try {
                $pdo->beginTransaction();

                // Re-check inside the transaction in case someone just booked it
                $table = find_table($pdo, $date, $time, $guests);
                if (!$table) throw new Exception("taken");

                $ins = $pdo->prepare(
                    "INSERT INTO reservations (user_id, table_id, res_date, res_time, guests)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $ins->execute([$_SESSION["user_id"], $table["id"], $date, $time, $guests]);
                $rid = $pdo->lastInsertId();

                $itm = $pdo->prepare(
                    "INSERT INTO reservation_items (reservation_id, menu_item_id, quantity) VALUES (?, ?, ?)"
                );
                $total = 0;
                $ordered = [];
                foreach ($menu as $m) {
                    $q = (int)($qty[$m["id"]] ?? 0);
                    if ($q > 0) {
                        $q = min($q, 20);
                        $itm->execute([$rid, $m["id"], $q]);
                        $total += $m["price"] * $q;
                        $ordered[] = $m["name"] . " x" . $q;
                    }
                }

                $pdo->commit();

                $confirmation = [
                    "id" => $rid, "table" => $table["table_name"], "date" => $date,
                    "time" => $time, "guests" => $guests,
                    "items" => $ordered, "total" => $total,
                ];
                $qty = [];
            } catch (Exception $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = "Sorry, that table was just taken or something went wrong. Please try again.";
            }
        }
    }
}

page_top("Book a Table");
?>
<h2>Book a Table</h2>

<?php if ($confirmation): ?>
    <div class="success">
        <strong>Reservation #<?= e($confirmation["id"]) ?> received!</strong><br>
        <?= e($confirmation["table"]) ?> for <?= e($confirmation["guests"]) ?> guest(s)
        on <?= e($confirmation["date"]) ?> at <?= e($confirmation["time"]) ?>.<br>
        <?php if ($confirmation["items"]): ?>
            Pre-ordered: <?= e(implode(", ", $confirmation["items"])) ?>
            (KES <?= number_format($confirmation["total"], 2) ?>)<br>
        <?php endif; ?>
        Status: pending until the restaurant confirms. <a href="my_reservations.php">View my reservations</a>
    </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
<?php endif; ?>
<?php if ($info): ?><div class="info"><?= e($info) ?></div><?php endif; ?>

<form action="" method="POST" class="card">
    <label for="date">Date:</label>
    <input type="date" name="date" id="date" min="<?= date('Y-m-d') ?>" value="<?= e($date) ?>" required>

    <label for="time">Time:</label>
    <select name="time" id="time" required>
        <option value="">Choose a time</option>
        <?php foreach ($slots as $s): ?>
            <option value="<?= e($s) ?>" <?= $time === $s ? "selected" : "" ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="guests">Number of guests:</label>
    <input type="number" name="guests" id="guests" min="1" max="20" value="<?= e($guests) ?>" required>

    <h3>Pre-order meals (optional)</h3>
    <?php foreach ($menu as $m): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <span><?= e($m["name"]) ?> - KES <?= number_format($m["price"], 2) ?></span>
            <input class="qty" type="number" min="0" max="20" name="qty[<?= (int)$m["id"] ?>]" value="<?= e($qty[$m["id"]] ?? 0) ?>">
        </div>
    <?php endforeach; ?>

    <button type="submit" name="action" value="check" class="btn-grey">Check availability</button>
    <button type="submit" name="action" value="book">Book table</button>
</form>
<?php page_bottom(); ?>