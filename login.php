<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_student.html');
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'student';

$allowedRoles = ['student', 'admin', 'president'];
if (!in_array($role, $allowedRoles, true)) {
    $role = 'student';
}

$redirectPage = 'login_student.html';
if ($role === 'admin') {
    $redirectPage = 'login_admin.html';
} elseif ($role === 'president') {
    $redirectPage = 'login_president.html';
}

if ($identifier === '' || $password === '') {
    $error = 'Email and password are required.';
    header('Location: ' . $redirectPage . '?error=' . urlencode($error));
    exit;
}

$stmt = $conn->prepare('SELECT user_id, full_name, email, password FROM users WHERE role = ? AND (email = ? OR student_id = ?)');
$stmt->bind_param('sss', $role, $identifier, $identifier);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    header('Location: ' . $redirectPage . '?error=' . urlencode('Invalid credentials.'));
    exit;
}
$stmt->bind_result($id, $fullname, $emailFound, $passwordHash);
$stmt->fetch();
$stmt->close();

if (!password_verify($password, $passwordHash)) {
    header('Location: ' . $redirectPage . '?error=' . urlencode('Invalid credentials.'));
    exit;
}

$_SESSION['user_id'] = $id;
$_SESSION['user_name'] = $fullname;
$_SESSION['user_email'] = $emailFound;
$_SESSION['user_role'] = $role;

if ($role === 'admin') {
    header('Location: dashboard_admin.php');
    exit;
} elseif ($role === 'president') {
    header('Location: dashboard_president.php');
    exit;
}

header('Location: dashboard_student.php');
exit;
