<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_student.html');
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';
$role       = $_POST['role'] ?? 'student';

// ✅ matches your actual enum('admin','teacher','student','member')
$allowedRoles = ['teacher', 'student', 'member'];
if (!in_array($role, $allowedRoles, true)) {
    $role = 'student';
}

$redirectPage = 'login_student.html';
if ($role === 'member') {
    $redirectPage = 'login_president.html';
}

if ($identifier === '' || $password === '') {
    $error = 'Email and password are required.';
    header('Location: ' . $redirectPage . '?error=' . urlencode($error));
    exit;
}

// Debug: if login keeps looping back, we need to know whether credentials matched.
// Enable by setting URL ?debug=1
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';


// ✅ Only columns that actually exist in the users table
$stmt = $conn->prepare('SELECT user_id, full_name, email, password FROM users WHERE role = ? AND email = ? LIMIT 1');
if (!$stmt) {
    header('Location: ' . $redirectPage . '?error=' . urlencode('Login query failed: ' . $conn->error));
    exit;
}

$stmt->bind_param('ss', $role, $identifier);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    header('Location: ' . $redirectPage . '?error=' . urlencode('Invalid credentials.'));
    exit;
}

// ✅ 4 columns selected -> 4 variables bound
$stmt->bind_result($id, $fullname, $emailFound, $passwordHash);
$stmt->fetch();
$stmt->close();

// Verify password. If the stored value is a plaintext password (legacy/mis-inserted),
// accept it (after trimming), re-hash it and update the database so future logins use a secure hash.
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
    header('Location: ' . $redirectPage . '?error=' . urlencode('Invalid credentials.'));
    exit;
}

// ✅ Session values come from real DB columns now
$_SESSION['user_id']    = $id;
$_SESSION['user_name']  = $fullname ?: '';
$_SESSION['user_email'] = $emailFound ?: $identifier;
$_SESSION['user_role']  = $role;

if ($role === 'member') {
    header('Location: dashboard_president.php');
    exit;
}

header('Location: dashboard_student.php');
exit;