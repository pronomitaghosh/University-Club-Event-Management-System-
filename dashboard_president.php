<?php
session_start();
require_once 'config.php';
require_once 'db.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['president', 'member'], true)) {
    header('Location: login_president.html');
    exit();
}

ensure_contact_message_schema($conn);

$pending_count = 0;
$res = $conn->query("SELECT COUNT(*) as cnt FROM join_requests WHERE status = 'pending'");
if ($res) { $row = $res->fetch_assoc(); $pending_count = (int)($row['cnt'] ?? 0); }

$event_count = 0;
$res2 = $conn->query("SELECT COUNT(*) as cnt FROM events WHERE event_date >= CURDATE()");
if ($res2) { $row2 = $res2->fetch_assoc(); $event_count = (int)($row2['cnt'] ?? 0); }

$notice_count = 0;
$res3 = $conn->query("SELECT COUNT(*) as cnt FROM notices");
if ($res3) { $row3 = $res3->fetch_assoc(); $notice_count = (int)($row3['cnt'] ?? 0); }

$unread_message_count = 0;
$unread_sql = "SELECT COUNT(*) as cnt FROM contact_messages WHERE (is_read = 0 OR status = 'pending')";
$res4 = $conn->query($unread_sql);
if ($res4) { $row4 = $res4->fetch_assoc(); $unread_message_count = (int)($row4['cnt'] ?? 0); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>President Dashboard | CPC Kishoreganj University</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --teal: #1a7a6e; --teal-dark: #125a51; --deep: #0e3d38;
            --gold: #c89b3c; --white: #ffffff; --bg: #f0f6f5;
            --border: #cde5e2; --sidebar-width: 260px;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); min-height: 100vh; display: flex; }

        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--deep) 0%, var(--teal-dark) 100%);
            color: var(--white); position: fixed; top: 0; bottom: 0; left: 0; display: flex; flex-direction: column;
        }
        .sidebar-header { padding: 24px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; font-size: 1.15rem; }
        .sidebar-menu { list-style: none; padding: 24px 16px; flex: 1; }
        .menu-link {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            color: rgba(255,255,255,0.75); text-decoration: none; font-size: 0.95rem;
            border-radius: 10px; transition: background 0.2s;
        }
        .menu-link:hover, .menu-link.active { color: var(--white); background: rgba(255,255,255,0.15); }
        .menu-link.active { border-left: 4px solid var(--gold); }

        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 40px; }

        .summary-card {
            background: white; padding: 30px; border-radius: 16px;
            border: 1px solid var(--border); box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }

        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 25px; }

        .grid-item {
            background: #f4f9f8; padding: 24px 20px; border-radius: 12px;
            border: 1px solid var(--border); text-align: center;
            text-decoration: none; display: block;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .grid-item:hover { box-shadow: 0 4px 16px rgba(26,122,110,0.12); transform: translateY(-2px); }
        .grid-item h4 { color: #6b7f7d; font-size: 0.85rem; font-weight: 600; margin-bottom: 10px; }
        .grid-item h2 { font-size: 2rem; font-family: 'Playfair Display', serif; }

        .color-teal { color: var(--teal); }
        .color-gold  { color: var(--gold); }

        .pending-card { border-color: #f5c97a; background: #fffbf0; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <div style="font-size:1.6rem;">👑</div>
        <h2>President Panel</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard_president.php" class="menu-link active">📊 Overview</a></li>
        <li><a href="manage_members.php" class="menu-link">👥 Manage Members</a></li>
        <li><a href="create_event.php" class="menu-link">📅 Create Event</a></li>
        <li><a href="club_notice.php" class="menu-link">📢 Club Notice</a></li>
        <li><a href="president_messages.php" class="menu-link">📨 Messages</a></li>
    </ul>
    <div style="padding: 20px 24px;">
        <a href="#" id="logoutBtn" style="color:#ff8a8a; text-decoration:none; font-weight:600;">🚪 Logout</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="summary-card">
        <h2 style="font-family:'Playfair Display', serif; color: var(--deep);">President Overview</h2>
        <p style="color: #6b7f7d; font-size: 0.95rem; margin-top: 4px;">Welcome back, President.</p>
        <div class="grid-3">
            <a href="manage_members.php" class="grid-item pending-card">
                <h4>Pending Requests</h4>
                <h2 class="color-gold"><?= $pending_count ?></h2>
            </a>
            <a href="create_event.php" class="grid-item">
                <h4>Upcoming Events</h4>
                <h2 class="color-teal"><?= $event_count ?></h2>
            </a>
            <a href="club_notice.php" class="grid-item">
                <h4>Notices Posted</h4>
                <h2 class="color-teal"><?= $notice_count ?></h2>
            </a>
            <a href="president_messages.php" class="grid-item pending-card">
                <h4>Unread Messages</h4>
                <h2 class="color-gold"><?= $unread_message_count ?></h2>
            </a>
        </div>
    </div>
</div>

<script>
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        sessionStorage.removeItem('isPresidentLoggedIn');
        sessionStorage.removeItem('presidentEmail');
        window.location.href = 'login_president.html';
    });
</script>
</body>
</html>
