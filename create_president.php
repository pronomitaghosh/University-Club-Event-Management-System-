<?php
require_once 'config.php';

if (php_sapi_name() === 'cli') {
    $argv = $_SERVER['argv'];
    if (count($argv) < 4) {
        echo "Usage: php create_president.php <email> <password> [fullname] [student_id]\n";
        echo "Example: php create_president.php president@university.edu MySecret123 'Club President' PRES001\n";
        exit(1);
    }

    $email = trim($argv[1]);
    $password = trim($argv[2]);
    $fullname = trim($argv[3] ?? 'Club President');
    $student_id = trim($argv[4] ?? '');
    $department = 'President';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Error: Invalid email address.\n";
        exit(1);
    }
    if (strlen($password) < 6) {
        echo "Error: Password must be at least 6 characters long.\n";
        exit(1);
    }

    $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? OR student_id = ?');
    $stmt->bind_param('ss', $email, $student_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "Error: User with that email or student ID already exists.\n";
        exit(1);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (full_name, student_id, email, department, password, role) VALUES (?, ?, ?, ?, ?, "president")');
    $stmt->bind_param('sssss', $fullname, $student_id, $email, $department, $passwordHash);
    if ($stmt->execute()) {
        echo "President account created successfully.\n";
        echo "Email: {$email}\n";
        echo "Password hash: {$passwordHash}\n";
        exit(0);
    }

    echo "Error: Could not create president account. " . $stmt->error . "\n";
    exit(1);
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? 'Club President');
    $student_id = trim($_POST['student_id'] ?? '');
    $department = trim($_POST['department'] ?? 'President');

    if ($email === '' || $password === '' || $fullname === '') {
        $message = 'Email, full name, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
    } else {
        $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? OR student_id = ?');
        $stmt->bind_param('ss', $email, $student_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = 'A user with that email or student ID already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (full_name, student_id, email, department, password, role) VALUES (?, ?, ?, ?, ?, "president")');
            $stmt->bind_param('sssss', $fullname, $student_id, $email, $department, $passwordHash);
            if ($stmt->execute()) {
                $message = 'President account created successfully.';
                $success = true;
            } else {
                $message = 'Unable to create account: ' . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create President Account</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f9f8; color: #1e2d2b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .container { background: white; padding: 30px; border-radius: 18px; box-shadow: 0 18px 44px rgba(0,0,0,0.08); width: min(540px, 92%); }
        h1 { margin-top: 0; font-size: 1.8rem; }
        .message { margin-bottom: 18px; padding: 14px 16px; border-radius: 12px; }
        .message.error { background: #fdecea; color: #d93838; }
        .message.success { background: #e6f5ed; color: #0f6d3a; }
        label { display: block; margin: 16px 0 6px; font-weight: 600; }
        input { width: 100%; padding: 12px 14px; border: 1px solid #cde5e2; border-radius: 10px; }
        button { margin-top: 18px; width: 100%; padding: 14px 16px; background: #1a7a6e; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create President Account</h1>
        <?php if ($message !== ''): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="POST" action="create_president.php" autocomplete="off" novalidate>
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($_POST['fullname'] ?? 'Club President', ENT_QUOTES, 'UTF-8'); ?>" required>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            <label for="student_id">President ID</label>
            <input type="text" id="student_id" name="student_id" value="<?php echo htmlspecialchars($_POST['student_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <label for="department">Department</label>
            <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($_POST['department'] ?? 'President', ENT_QUOTES, 'UTF-8'); ?>">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Create President</button>
        </form>
        <p style="margin-top: 18px; color: #6b7f7d; font-size: 0.95rem;">Use this page instead of inserting a raw SQL row so the password is hashed correctly.</p>
    </div>
</body>
</html>
