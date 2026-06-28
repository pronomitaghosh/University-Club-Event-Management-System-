<?php
// One-time migration script to hash plaintext passwords.
// Run from command line: php migrate_plain_passwords.php
// This script only runs via CLI to avoid web exposure.

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line only.\n";
    exit(1);
}

require_once __DIR__ . '/config.php';

$select = $conn->query("SELECT user_id, password FROM users");
if (! $select) {
    echo "Query failed: " . $conn->error . "\n";
    exit(1);
}

$updateStmt = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
if (! $updateStmt) {
    echo "Prepare failed: " . $conn->error . "\n";
    exit(1);
}

$total = 0;
$migrated = 0;

while ($row = $select->fetch_assoc()) {
    $total++;
    $uid = (int)$row['user_id'];
    $stored = trim((string)$row['password']);

    // heuristic: modern password_hash strings start with '$' and are reasonably long
    $looksLikeHash = (strpos($stored, '$') === 0 && strlen($stored) >= 20);
    if ($looksLikeHash) {
        continue;
    }

    // treat stored value as plaintext password and migrate
    $newHash = password_hash($stored, PASSWORD_DEFAULT);
    $updateStmt->bind_param('si', $newHash, $uid);
    if ($updateStmt->execute()) {
        $migrated++;
        echo "Migrated user_id={$uid}\n";
    } else {
        echo "Failed to update user_id={$uid}: " . $updateStmt->error . "\n";
    }
}

$updateStmt->close();
$select->free();

echo "Done. Scanned={$total}, Migrated={$migrated}\n";
echo "Remove this file from the webroot after running.\n";

exit(0);
