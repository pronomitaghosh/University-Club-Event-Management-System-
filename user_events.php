<?php
require_once 'config.php';

// Fetch upcoming events first, then past
$upcoming = [];
$past = [];

$result = $conn->query("SELECT * FROM events ORDER BY event_date ASC, event_time ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (strtotime($row['event_date']) >= strtotime(date('Y-m-d'))) {
            $upcoming[] = $row;
        } else {
            $past[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Club Events | CPC Kishoreganj University</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --teal: #1a7a6e; --teal-dark: #125a51; --deep: #0e3d38;
            --gold: #c89b3c; --white: #ffffff; --bg: #f0f6f5;
            --border: #cde5e2; --sidebar-width: 260px;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); min-height: 100vh; display: flex; }

        /* ── Sidebar (User) ── */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--deep) 0%, var(--teal-dark) 100%);
            color: var(--white); position: fixed; top: 0; bottom: 0; left: 0;
            display: flex; flex-direction: column;
        }
        .sidebar-header { padding: 24px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; font-size: 1.1rem; }
        .sidebar-menu { list-style: none; padding: 24px 16px; flex: 1; }
        .menu-link {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            color: rgba(255,255,255,0.75); text-decoration: none; font-size: 0.95rem;
            border-radius: 10px; transition: background 0.2s;
        }
        .menu-link:hover, .menu-link.active { color: var(--white); background: rgba(255,255,255,0.15); }
        .menu-link.active { border-left: 4px solid var(--gold); }

        /* ── Main ── */
        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 40px; }

        .page-title { font-family: 'Playfair Display', serif; color: var(--deep); font-size: 1.8rem; }
        .page-sub   { color: #6b7f7d; font-size: 0.95rem; margin-top: 4px; margin-bottom: 30px; }

        /* ── Section Header ── */
        .section-label {
            font-size: 0.8rem; font-weight: 700; letter-spacing: 0.08em;
            text-transform: uppercase; color: #9bb0ae; margin: 32px 0 14px;
        }

        /* ── Event Cards ── */
        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }

        .event-card {
            background: white; border-radius: 16px; border: 1px solid var(--border);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04); overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .event-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(26,122,110,0.1); }

        .event-card-top {
            background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
            padding: 20px; color: white; position: relative;
        }
        .event-card-top.past-top { background: linear-gradient(135deg, #8fa7a4 0%, #6b8784 100%); }

        .event-date-badge {
            position: absolute; top: 16px; right: 16px;
            background: rgba(255,255,255,0.2); border-radius: 10px;
            padding: 6px 12px; text-align: center; min-width: 56px;
        }
        .event-date-badge .day { font-size: 1.4rem; font-weight: 700; line-height: 1; }
        .event-date-badge .month { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.85; }

        .event-card-title { font-family: 'Playfair Display', serif; font-size: 1.1rem; padding-right: 72px; line-height: 1.3; }

        .event-card-body { padding: 18px 20px; }

        .meta-row { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #6b7f7d; margin-bottom: 8px; }
        .meta-row:last-child { margin-bottom: 0; }

        .event-desc {
            font-size: 0.88rem; color: #5a7270; margin-top: 12px;
            line-height: 1.55; border-top: 1px solid #eef4f3; padding-top: 12px;
        }

        .badge-upcoming {
            display: inline-block; background: #e6f4f1; color: var(--teal-dark);
            font-size: 0.75rem; font-weight: 700; padding: 3px 10px; border-radius: 20px;
            margin-top: 12px; letter-spacing: 0.03em;
        }
        .badge-past {
            display: inline-block; background: #f0f0f0; color: #888;
            font-size: 0.75rem; font-weight: 700; padding: 3px 10px; border-radius: 20px;
            margin-top: 12px; letter-spacing: 0.03em;
        }

        .empty-state { text-align: center; padding: 40px 20px; color: #9bb0ae; font-size: 0.95rem; }
        .empty-state .icon { font-size: 2.5rem; margin-bottom: 10px; }

        /* ── Summary bar ── */
        .summary-bar {
            display: flex; gap: 16px; margin-bottom: 10px; flex-wrap: wrap;
        }
        .summary-pill {
            background: white; border: 1px solid var(--border); border-radius: 40px;
            padding: 8px 18px; font-size: 0.88rem; font-weight: 600; color: #4a6260;
            display: flex; align-items: center; gap: 8px;
        }
        .summary-pill span { color: var(--teal); font-size: 1rem; font-weight: 700; }
    </style>
</head>
<body>

<!-- User Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <div style="font-size:1.6rem;">🖥️</div>
        <h2>CPC Member Panel</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="user_dashboard.php" class="menu-link">🏠 Dashboard</a></li>
        <li><a href="user_events.php" class="menu-link active">📅 Events</a></li>
        <li><a href="user_notices.php" class="menu-link">📢 Notices</a></li>
        <li><a href="user_profile.php" class="menu-link">👤 My Profile</a></li>
    </ul>
    <div style="padding: 20px 24px;">
        <a href="#" id="logoutBtn" style="color:#ff8a8a; text-decoration:none; font-weight:600;">🚪 Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-wrapper">

    <h1 class="page-title">📅 Club Events</h1>
    <p class="page-sub">Stay up to date with all CPC Kishoreganj University events.</p>

    <!-- Summary Pills -->
    <div class="summary-bar">
        <div class="summary-pill">🟢 Upcoming <span><?= count($upcoming) ?></span></div>
        <div class="summary-pill">⏳ Past <span><?= count($past) ?></span></div>
    </div>

    <!-- Upcoming Events -->
    <div class="section-label">🟢 Upcoming Events</div>
    <?php if (empty($upcoming)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>No upcoming events at the moment. Check back soon!</p>
        </div>
    <?php else: ?>
    <div class="events-grid">
        <?php foreach ($upcoming as $ev): ?>
        <div class="event-card">
            <div class="event-card-top">
                <div class="event-date-badge">
                    <div class="day"><?= date('d', strtotime($ev['event_date'])) ?></div>
                    <div class="month"><?= date('M', strtotime($ev['event_date'])) ?></div>
                </div>
                <div class="event-card-title"><?= htmlspecialchars($ev['title']) ?></div>
            </div>
            <div class="event-card-body">
                <?php if ($ev['event_time']): ?>
                <div class="meta-row">🕐 <?= date('h:i A', strtotime($ev['event_time'])) ?></div>
                <?php endif; ?>
                <?php if ($ev['venue']): ?>
                <div class="meta-row">📍 <?= htmlspecialchars($ev['venue']) ?></div>
                <?php endif; ?>
                <?php if ($ev['description']): ?>
                <div class="event-desc"><?= nl2br(htmlspecialchars($ev['description'])) ?></div>
                <?php endif; ?>
                <div><span class="badge-upcoming">✅ Upcoming</span></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Past Events -->
    <?php if (!empty($past)): ?>
    <div class="section-label">⏳ Past Events</div>
    <div class="events-grid">
        <?php foreach (array_reverse($past) as $ev): ?>
        <div class="event-card">
            <div class="event-card-top past-top">
                <div class="event-date-badge">
                    <div class="day"><?= date('d', strtotime($ev['event_date'])) ?></div>
                    <div class="month"><?= date('M', strtotime($ev['event_date'])) ?></div>
                </div>
                <div class="event-card-title"><?= htmlspecialchars($ev['title']) ?></div>
            </div>
            <div class="event-card-body">
                <?php if ($ev['event_time']): ?>
                <div class="meta-row">🕐 <?= date('h:i A', strtotime($ev['event_time'])) ?></div>
                <?php endif; ?>
                <?php if ($ev['venue']): ?>
                <div class="meta-row">📍 <?= htmlspecialchars($ev['venue']) ?></div>
                <?php endif; ?>
                <?php if ($ev['description']): ?>
                <div class="event-desc"><?= nl2br(htmlspecialchars($ev['description'])) ?></div>
                <?php endif; ?>
                <div><span class="badge-past">🏁 Completed</span></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script>
    // Adjust this key based on how your user session is tracked
    if (sessionStorage.getItem('isUserLoggedIn') !== 'true') {
        window.location.href = 'login.html';
    }
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        sessionStorage.clear();
        window.location.href = 'login.html';
    });
</script>
</body>
</html>
