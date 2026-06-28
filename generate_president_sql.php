<?php
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

$argv = $_SERVER['argv'];
if (count($argv) < 4) {
    echo "Usage: php generate_president_sql.php <email> <password> <fullname> [student_id]\n";
    echo "Example: php generate_president_sql.php president@university.edu MySecret123 \"Club President\" PRES001\n";
    exit(1);
}

$email = trim($argv[1]);
$password = $argv[2];
$fullname = trim($argv[3]);
$student_id = trim($argv[4] ?? '');
$department = 'President';
$role = 'president';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email address.\n";
    exit(1);
}
if (strlen($password) < 6) {
    echo "Error: Password must be at least 6 characters long.\n";
    exit(1);
}

function sql_quote(string $value): string {
    $escaped = str_replace("'", "''", $value);
    return "'" . $escaped . "'";
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$sql = sprintf(
    "INSERT INTO users (full_name, student_id, email, department, password, role) VALUES (%s, %s, %s, %s, %s, %s);\n",
    sql_quote($fullname),
    sql_quote($student_id),
    sql_quote($email),
    sql_quote($department),
    sql_quote($passwordHash),
    sql_quote($role)
);

echo "Generated hashed password and SQL statement:\n\n";
echo "Hash:\n" . $passwordHash . "\n\n";
echo "SQL:\n" . $sql;
