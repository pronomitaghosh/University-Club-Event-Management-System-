<?php
require_once 'config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login_student.html');
    exit;
}
$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $message = 'Please complete all fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New password and confirmation do not match.';
    } elseif (strlen($newPassword) < 6) {
        $message = 'New password must be at least 6 characters long.';
    } else {
        $stmt = $conn->prepare('SELECT password FROM users WHERE user_id = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($currentHash);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($currentPassword, $currentHash)) {
            $message = 'Current password is incorrect.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
            $stmt->bind_param('si', $newHash, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $message = 'Password changed successfully.';
                $messageType = 'success';
            } else {
                $message = 'Unable to update password. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f9f8; color: #1e2d2b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .container { background: white; padding: 32px; border-radius: 18px; box-shadow: 0 18px 44px rgba(0,0,0,0.08); width: min(520px, 90%); }
        h1 { margin-top: 0; }
        label { display: block; margin: 16px 0 6px; font-weight: 600; }
        input { width: 100%; padding: 12px 14px; border: 1px solid #cde5e2; border-radius: 10px; }
        button { margin-top: 20px; padding: 14px 20px; background: #1a7a6e; color: white; border: none; border-radius: 10px; cursor: pointer; }
        .message { margin-bottom: 16px; padding: 12px 14px; border-radius: 10px; background: #f6f6f6; }
        .message.success { background: #e6f5ed; color: #0f6d3a; }
        .message.error { background: #fdecea; color: #d93838; }
        a { display: inline-block; margin-top: 18px; color: #1a7a6e; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Change Password</h1>
        <?php if ($message !== ''): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form action="change_password.php" method="POST" autocomplete="off" novalidate>
            <div style="position:absolute;left:-9999px;visibility:hidden;">
                <input type="text" name="fake_username" autocomplete="username">
                <input type="password" name="fake_password" autocomplete="new-password">
            </div>
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password" value="" required>

            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" autocomplete="new-password" value="" required>

            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" value="" required>

            <button type="submit">Save New Password</button>
        </form>
        <a href="welcome.php">? Back to Welcome</a>
    </div>
</body>
</html>
