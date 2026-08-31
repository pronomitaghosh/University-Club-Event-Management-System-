<?php
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "clubproject";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function get_contact_message_columns($conn) {
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM contact_messages");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

function ensure_contact_message_schema($conn) {
    $columns = get_contact_message_columns($conn);

    if (!in_array('phone', $columns, true)) {
        $conn->query("ALTER TABLE contact_messages ADD COLUMN phone VARCHAR(30) NULL AFTER email");
    }

    if (!in_array('is_read', $columns, true)) {
        $conn->query("ALTER TABLE contact_messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER message");
    }

    if (!in_array('created_at', $columns, true)) {
        if (in_array('submitted_at', $columns, true)) {
            $conn->query("ALTER TABLE contact_messages CHANGE submitted_at created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        } else {
            $conn->query("ALTER TABLE contact_messages ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER message");
        }
    }

    if (in_array('status', $columns, true) && !in_array('answered', $conn->query("SELECT DISTINCT status FROM contact_messages")->fetch_all() ?? [], true)) {
        // no-op, keep compatibility with legacy enum values
    }

    $columns = get_contact_message_columns($conn);
    if (in_array('status', $columns, true) && !in_array('is_read', $columns, true)) {
        $conn->query("ALTER TABLE contact_messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
    }
}
?>