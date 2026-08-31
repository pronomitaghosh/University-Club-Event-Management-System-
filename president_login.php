<?php
session_start();

$identifier = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';

$PRESIDENT_EMAIL = 'president@gmail.com';
$PRESIDENT_PASSWORD = '1111111';

if ($identifier === $PRESIDENT_EMAIL && $password === $PRESIDENT_PASSWORD) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_email'] = $identifier;
    $_SESSION['user_name'] = 'President';
    $_SESSION['user_role'] = 'member';
    $_SESSION['role'] = 'president';
    header('Location: dashboard_president.php');
    exit();
}

header('Location: login_president.html?error=' . urlencode('Invalid email or password.'));
exit();
