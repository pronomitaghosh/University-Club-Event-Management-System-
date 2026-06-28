<?php
require_once 'config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login_student.html');
    exit;
}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Student', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f9f8; color: #1e2d2b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .container { background: white; padding: 32px; border-radius: 18px; box-shadow: 0 18px 44px rgba(0,0,0,0.08); width: min(560px, 90%); text-align: center; }
        h1 { margin: 0 0 16px; font-size: 2rem; }
        p { margin: 0 0 24px; color: #4c5c57; }
        a { display: inline-block; margin: 6px 10px; padding: 12px 20px; background: #1a7a6e; color: white; text-decoration: none; border-radius: 10px; }
        a.logout { background: #d93838; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo $userName; ?>!</h1>
        <p>You are now logged in. Use the buttons below to continue.</p>
        <a href="logout.php" class="logout">Log Out</a>
    </div>
</body>
</html>
