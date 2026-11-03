  <?php
     // Database connection parameters
$host = "localhost";
$dbname = "restaurant_db";
$dbuser = "root";
$dbpass = "";

$errors = [];
$success = "";
$username = $fname = $lname = $email = $phone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ---------- Collect and clean input ----------
    $username = trim($_POST["username"] ?? "");
    $fname    = trim($_POST["fname"] ?? "");
    $lname    = trim($_POST["lname"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $phone    = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm  = $_POST["confirm_password"] ?? "";

    // ---------- Validation ----------
    if ($username === "" || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }
    if ($fname === "") $errors[] = "First name is required.";
    if ($lname === "") $errors[] = "Last name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }
    if (!preg_match('/^\+?[0-9]{9,15}$/', $phone)) {
        $errors[] = "Enter a valid phone number (9-15 digits, may start with +).";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // ---------- Save to database ----------
    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check for existing username or email
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);

            if ($check->fetch()) {
                $errors[] = "Username or email is already registered.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, first_name, last_name, email, phone, password)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$username, $fname, $lname, $email, $phone, $hash]);
                $success = "Registration successful! You can now log in.";
                $username = $fname = $lname = $email = $phone = "";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error. Please try again later.";
        }
    }
}

// Helper to safely print values back into the form
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f2f2f2; margin: 0; }
        .main-content { display: flex; justify-content: center; padding: 30px 15px; }
        .form { background: #fff; padding: 25px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
        h2 { text-align: center; margin-top: 0; }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; margin-top: 20px; padding: 12px; background: #c0392b; color: #fff; border: 0; border-radius: 4px; font-size: 16px; cursor: pointer; }
        .error { background: #fdecea; color: #b71c1c; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .success { background: #e8f5e9; color: #1b5e20; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="main-content">
    <div class="form">
        <form action="" method="POST">
            <h2>Register Form</h2>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $err): ?>
                        <div><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?= e($success) ?></div>
            <?php endif; ?>

            <label for="username">Username:</label>
            <input type="text" name="username" id="username" placeholder="Enter your username" value="<?= e($username) ?>" required>

            <label for="firstname">First Name:</label>
            <input type="text" name="fname" id="firstname" placeholder="e.g. Annie" value="<?= e($fname) ?>" required>

            <label for="lastname">Last Name:</label>
            <input type="text" name="lname" id="lastname" placeholder="e.g. Wanjiku" value="<?= e($lname) ?>" required>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" placeholder="e.g. annie@example.com" value="<?= e($email) ?>" required>

            <label for="phone">Phone Number:</label>
            <input type="tel" name="phone" id="phone" placeholder="e.g. +254712345678" value="<?= e($phone) ?>" required>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" placeholder="At least 8 characters" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter your password" required>

            <button type="submit">Register</button>
        </form>
    </div>
</div>
</body>
</html>
    
</body>
</html>