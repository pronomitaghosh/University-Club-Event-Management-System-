<?php
session_start();

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'clubproject';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$checkStudentId = $conn->query("SHOW COLUMNS FROM users LIKE 'student_id'");
if ($checkStudentId && $checkStudentId->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN student_id VARCHAR(50) DEFAULT NULL UNIQUE AFTER full_name");
}

$checkDepartment = $conn->query("SHOW COLUMNS FROM users LIKE 'department'");
if ($checkDepartment && $checkDepartment->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL AFTER email");
}
