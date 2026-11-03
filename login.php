<?php
session_start();

// ---------- Database connection ----------
$host = "localhost";
$dbname = "restaurant_db";
$dbuser = "root";
$dbpass = "";

$errors = [];
$login = "";

// ---------- Log out (visit login.php?logout=1) ----------
if (isset($_GET["logout"])) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit;
}

// If already logged in, go straight to the main page
if (isset($_SESSION["user_id"])) {
    header("Location: restaurant.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login    = trim($_POST["login"] ?? "");   // username OR email
    $password = $_POST["password"] ?? "";

    if ($login === "" || $password === "") {
        $errors[] = "Enter your username/email and password.";
    }

    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT id, username, first_name, password FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Compare typed password against the stored hash
            if ($user && password_verify($password, $user["password"])) {
                session_regenerate_id(true);
                $_SESSION["user_id"]    = $user["id"];
                $_SESSION["username"]   = $user["username"];
                $_SESSION["first_name"] = $user["first_name"];

                header("Location: restaurant.php");
                exit;
            } else {
                // Same message for both cases so attackers can't tell which was wrong
                $errors[] = "Invalid username/email or password.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error. Please try again later.";
        }
    }
}

function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f2f2f2; margin: 0; }
        .main-content { display: flex; justify-content: center; padding: 30px 15px; }
        .form { background: #fff; padding: 25px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
        h2 { text-align: center; margin-top: 0; }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; margin-top: 20px; padding: 12px; background: #c0392b; color: #fff; border: 0; border-radius: 4px; font-size: 16px; cursor: pointer; }
        .error { background: #fdecea; color: #b71c1c; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .link { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
<div class="main-content">
    <div class="form">
        <form action="" method="POST">
            <h2>Login</h2>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $err): ?>
                        <div><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <label for="login">Username or Email:</label>
            <input type="text" name="login" id="login" placeholder="Enter your username or email" value="<?= e($login) ?>" required>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required>

            <button type="submit">Log In</button>

            <div class="link">No account? <a href="register.php">Register here</a></div>
        </form>
    </div>
</div>
</body>
</html>