<?php
/**
 * Database Connection
 * Database name: clubproject
 *
 * Change $db_user / $db_pass below if your MySQL has a username/password set.
 * Default XAMPP/WAMP setup is usually user "root" with an empty password.
 */

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "clubproject";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>