<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register_student.html');
    exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$student_id = trim($_POST['student_id'] ?? '');
$email = trim($_POST['email'] ?? '');
$department = trim($_POST['department'] ?? '');
$password = $_POST['password'] ?? '';

if ($fullname === '' || $student_id === '' || $email === '' || $department === '' || $password === '') {
    $error = 'Please fill in all required fields.';
    header('Location: register_student.html?error=' . urlencode($error));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address.';
    header('Location: register_student.html?error=' . urlencode($error));
    exit;
}

if (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters long.';
    header('Location: register_student.html?error=' . urlencode($error));
    exit;
}

$stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? OR student_id = ?');
$stmt->bind_param('ss', $email, $student_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $error = 'Email or student ID is already registered.';
    header('Location: register_student.html?error=' . urlencode($error));
    exit;
}
$stmt->close();

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare('INSERT INTO users (full_name, student_id, email, department, password, role) VALUES (?, ?, ?, ?, ?, "student")');
$stmt->bind_param('sssss', $fullname, $student_id, $email, $department, $passwordHash);
if ($stmt->execute()) {
    $stmt->close();
    header('Location: login_student.html?success=' . urlencode('Registration successful. Please log in.'));
    exit;
}

$error = 'Registration failed. Please try again.';
header('Location: register_student.html?error=' . urlencode($error));
exit;
