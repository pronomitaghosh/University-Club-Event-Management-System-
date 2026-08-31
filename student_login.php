<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_student.html');
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    header('Location: login_student.html?error=' . urlencode('Email and password are required.'));
    exit;
}

$role = 'student';

$stmt = $conn->prepare('SELECT user_id, full_name, email, password FROM users WHERE role = ? AND email = ? LIMIT 1');
if (!$stmt) {
    die('Query prepare failed: ' . $conn->error);
}

$stmt->bind_param('ss', $role, $identifier);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    header('Location: login_student.html?error=' . urlencode('No student found with this email.'));
    exit;
}

$stmt->bind_result($id, $fullname, $emailFound, $passwordHash);
$stmt->fetch();
$stmt->close();

$passwordOk = false;

if (password_verify($password, $passwordHash)) {
    $passwordOk = true;
} else {
    $stored   = trim((string)$passwordHash);
    $provided = trim((string)$password);
    $looksLikeHash = (strpos($stored, '$') === 0 && strlen($stored) >= 20);

    if (!$looksLikeHash && hash_equals($stored, $provided)) {
        $newHash = password_hash($provided, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
        if ($updateStmt) {
            $uid = (int)$id;
            $updateStmt->bind_param('si', $newHash, $uid);
            $updateStmt->execute();
            $updateStmt->close();
            $passwordOk = true;
        }
    }
}

if (!$passwordOk) {
    header('Location: login_student.html?error=' . urlencode('Wrong password.'));
    exit;
}

$_SESSION['user_id']    = (int)$id;
$_SESSION['user_name']  = $fullname ?: '';
$_SESSION['user_email'] = $emailFound ?: $identifier;
$_SESSION['user_role']  = $role;

header('Location: dashboard_student.php');
exit;