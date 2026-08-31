<?php
session_start();
require_once 'db.php';
ensure_contact_message_schema($conn);

$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? '');
if (empty($_SESSION['user_id']) || !in_array($userRole, ['president', 'member'], true)) {
    header('Location: login_president.html');
    exit();
}

if (isset($_GET['read'])) {
    $id = intval($_GET['read']);
    $conn->query("UPDATE contact_messages SET is_read = 1 WHERE message_id = {$id}");
    $conn->query("UPDATE contact_messages SET status = 'answered' WHERE message_id = {$id}");
    header('Location: president_messages.php');
    exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM contact_messages WHERE message_id = {$id}");
    header('Location: president_messages.php');
    exit();
}

$totalMessages = (int)($conn->query("SELECT COUNT(*) AS cnt FROM contact_messages")->fetch_assoc()['cnt'] ?? 0);
$unreadMessages = (int)($conn->query("SELECT COUNT(*) AS cnt FROM contact_messages WHERE is_read = 0 OR status = 'pending'")->fetch_assoc()['cnt'] ?? 0);
$result = $conn->query("SELECT message_id, name, email, phone, message, is_read, status, created_at FROM contact_messages ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages | President Panel</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #eef7f6; }
        .page-shell { max-width: 1200px; margin: 40px auto; padding: 0 20px 40px; }
        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px; margin-bottom: 24px;
        }
        .page-header-box {
            background: linear-gradient(135deg, #0f3f3c, #1a7a6e);
            color: white; padding: 28px 30px; border-radius: 18px;
            box-shadow: 0 16px 30px rgba(15, 63, 60, 0.12);
        }
        .page-header-box h1 { margin: 0; font-size: 2rem; }
        .page-header-box p { margin-top: 8px; opacity: 0.9; }
        .summary-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px; margin: 22px 0 28px;
        }
        .summary-card {
            background: white; border: 1px solid #d9efe9; border-radius: 14px;
            padding: 20px; box-shadow: 0 8px 20px rgba(17, 24, 39, 0.04);
        }
        .summary-card h4 { color: #6b7f7d; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.06em; }
        .summary-card h2 { margin-top: 10px; font-size: 2rem; color: var(--deep, #0f3f3c); }
        .summary-card.highlight { background: #fff7ea; border-color: #f8dca8; }
        .msg-table { width: 100%; border-collapse: collapse; margin-top: 12px; background: white; border-radius: 14px; overflow: hidden; box-shadow: 0 12px 25px rgba(17,24,39,0.04); }
        .msg-table th, .msg-table td { padding: 14px 12px; border: 1px solid #dfeae7; text-align: left; vertical-align: top; }
        .msg-table th { background: #0c4642; color: white; }
        .unread-row { background: #fffaf0; }
        .status-badge {
            display: inline-block; padding: 6px 10px; border-radius: 999px;
            font-size: 0.76rem; font-weight: 700; letter-spacing: 0.03em;
        }
        .status-unread { background: #ffe7c2; color: #9a5d00; }
        .status-read { background: #def4ea; color: #0d6d47; }
        .action-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
        .action-link {
            display: inline-block; text-decoration: none; font-weight: 600; padding: 7px 10px; border-radius: 8px;
            border: 1px solid transparent; transition: 0.2s ease;
        }
        .call-link { color: #0a7a3d; background: #edfdf3; }
        .mail-link { color: #0b5ec4; background: #edf4ff; }
        .mark-link { color: #8a5b00; background: #fff4d9; }
        .delete-link { color: #b42318; background: #fff1f1; }
        .empty-box {
            text-align: center; color: #5f6c6a; padding: 30px; background: white; border: 1px solid #dfeae7; border-radius: 12px;
        }
        @media (max-width: 768px) {
            .msg-table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <div class="top-bar">
            <div class="page-header-box">
                <h1>Contact Messages</h1>
                <p>View every student or visitor message and respond quickly.</p>
            </div>
        </div>

        <div class="summary-row">
            <div class="summary-card">
                <h4>Total Messages</h4>
                <h2><?php echo $totalMessages; ?></h2>
            </div>
            <div class="summary-card highlight">
                <h4>Unread</h4>
                <h2><?php echo $unreadMessages; ?></h2>
            </div>
        </div>

        <main>
            <?php if (!$result || $result->num_rows === 0): ?>
                <div class="empty-box">No messages yet.</div>
            <?php else: ?>
                <table class="msg-table">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                            $messageId = (int)($row['message_id'] ?? 0);
                            $phone = trim((string)($row['phone'] ?? ''));
                            $message = (string)($row['message'] ?? '');
                            $createdAt = $row['created_at'] ?? '';
                            $isRead = (int)($row['is_read'] ?? (($row['status'] ?? '') === 'answered' ? 1 : 0));
                            $statusText = $isRead === 0 ? 'Unread' : 'Answered';
                        ?>
                        <tr class="<?php echo $isRead === 0 ? 'unread-row' : ''; ?>">
                            <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($phone ?: '-'); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($message)); ?></td>
                            <td>
                                <span class="status-badge <?php echo $isRead === 0 ? 'status-unread' : 'status-read'; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($createdAt); ?></td>
                            <td>
                                <div class="action-wrap">
                                    <?php if ($phone): ?>
                                        <a class="action-link call-link" href="tel:<?php echo htmlspecialchars($phone); ?>">📞 Call</a>
                                    <?php endif; ?>
                                    <a class="action-link mail-link" href="mailto:<?php echo htmlspecialchars($row['email'] ?? ''); ?>">✉️ Reply</a>
                                    <?php if ($isRead === 0): ?>
                                        <a class="action-link mark-link" href="?read=<?php echo $messageId; ?>">✅ Mark Read</a>
                                    <?php endif; ?>
                                    <a class="action-link delete-link" href="?delete=<?php echo $messageId; ?>" onclick="return confirm('Delete this message?');">🗑 Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>