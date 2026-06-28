<?php
require_once 'config.php';
header('Content-Type: application/json');

$notices = [];
$res = $conn->query("SELECT id, title, body, pinned, created_at FROM notices ORDER BY pinned DESC, created_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $notices[] = $row;
    }
}

echo json_encode($notices);
