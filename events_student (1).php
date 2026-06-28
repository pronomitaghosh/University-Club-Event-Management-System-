<?php
require_once 'config.php';

// ── Session check ──
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userName = $_SESSION['user_name'] ?? 'Member';

// ── Fetch events ──
$upcoming = [];
$past     = [];

$result = $conn->query("SELECT * FROM events ORDER BY event_date ASC, event_time ASC");
if ($result) {
    $today = date('Y-m-d');
    while ($row = $result->fetch_assoc()) {
        if ($row['event_date'] >= $today) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Events | CPC Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --teal: #1a7a6e;
            --teal-dark: #125a51;
            --teal-light: #e6f5f3;
            --deep: #0e3d38;
            --gold: #c89b3c;
            --text: #1e2d2b;
            --muted: #6b7f7d;
            --white: #ffffff;
            --bg: #f4f9f8;
            --border: #cde5e2;
            --sidebar-width: 260px;
        }

        body { font-family: 'DM Sans', sans-serif; background: var(--bg); min-height: 100vh; display: flex; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--deep) 0%, var(--teal-dark) 100%);
            color: var(--white); position: fixed; top: 0; bottom: 0; left: 0;
            display: flex; flex-direction: column; z-index: 100;
        }
        .sidebar-header {
            padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .sidebar-header .logo { font-size: 1.8rem; margin-bottom: 6px; }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; font-size: 1.1rem; }

        .sidebar-menu { list-style: none; padding: 24px 16px; flex: 1; }
        .menu-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; color: rgba(255,255,255,0.75);
            text-decoration: none; font-size: 0.95rem; font-weight: 500;
            border-radius: 10px; transition: background 0.2s;
        }
        .menu-link:hover, .menu-link.active {
            color: var(--white); background: rgba(255,255,255,0.15);
        }
        .menu-link.active { border-left: 4px solid var(--gold); }

        .sidebar-footer { padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.1); }
        .btn-logout { display: flex; align-items: center; gap: 10px; color: #ff8a8a; text-decoration: none; font-size: 0.9rem; font-weight: 600; }

        /* ── Main wrapper ── */
        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; }

        .topbar {
            background: var(--white); height: 70px; padding: 0 40px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 50;
        }
        .role-badge {
            padding: 6px 14px; border-radius: 20px; font-size: 0.8rem;
            font-weight: 700; background: var(--teal-light); color: var(--teal-dark);
        }

        /* ── Content ── */
        .content-body { padding: 40px; }

        .page-header { margin-bottom: 28px; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.9rem; color: var(--deep); }
        .page-sub { color: var(--muted); font-size: 0.95rem; margin-top: 4px; }

        /* Summary pills */
        .summary-bar { display: flex; gap: 14px; margin-bottom: 32px; flex-wrap: wrap; }
        .summary-pill {
            background: var(--white); border: 1px solid var(--border); border-radius: 40px;
            padding: 8px 18px; font-size: 0.88rem; font-weight: 600; color: #4a6260;
            display: flex; align-items: center; gap: 8px;
        }
        .summary-pill span { color: var(--teal); font-size: 1rem; font-weight: 700; }

        /* Section label */
        .section-label {
            font-size: 0.78rem; font-weight: 700; letter-spacing: 0.08em;
            text-transform: uppercase; color: #9bb0ae; margin: 0 0 14px;
        }

        /* Event grid */
        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 22px;
            margin-bottom: 40px;
        }

        /* Event card */
        .event-card {
            background: var(--white); border-radius: 16px; border: 1px solid var(--border);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04); overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex; flex-direction: column;
        }
        .event-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(26,122,110,0.12); }

        /* Card top banner */
        .card-top {
            background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
            padding: 20px 20px 20px 20px; color: var(--white);
            position: relative; min-height: 90px;
        }
        .card-top.past { background: linear-gradient(135deg, #8fa7a4 0%, #6b8784 100%); }

        .date-badge {
            position: absolute; top: 16px; right: 16px;
            background: rgba(255,255,255,0.22); backdrop-filter: blur(4px);
            border-radius: 10px; padding: 6px 12px; text-align: center; min-width: 54px;
        }
        .date-badge .day   { font-size: 1.5rem; font-weight: 700; line-height: 1; }
        .date-badge .month { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.9; margin-top: 2px; }

        .card-title {
            font-family: 'Playfair Display', serif; font-size: 1.1rem;
            line-height: 1.35; padding-right: 76px;
        }

        /* Card body */
        .card-body { padding: 18px 20px 20px; flex: 1; display: flex; flex-direction: column; }

        .meta-row { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--muted); margin-bottom: 7px; }
        .meta-row:last-of-type { margin-bottom: 0; }

        .event-desc {
            font-size: 0.87rem; color: #5a7270; line-height: 1.55;
            border-top: 1px solid #eef4f3; padding-top: 12px; margin-top: 12px; flex: 1;
        }

        /* Status badges */
        .status-badge {
            display: inline-block; font-size: 0.75rem; font-weight: 700;
            padding: 4px 12px; border-radius: 20px; margin-top: 14px; letter-spacing: 0.03em;
        }
        .badge-upcoming  { background: var(--teal-light); color: var(--teal-dark); }
        .badge-past      { background: #f0f0f0; color: #888; }

        /* Empty state */
        .empty-state { text-align: center; padding: 50px 20px; color: #9bb0ae; }
        .empty-state .icon { font-size: 2.5rem; margin-bottom: 10px; }
        .empty-state p { font-size: 0.95rem; }
    </style>
</head>
<body>

<!-- ── Sidebar ── -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">💻</div>
        <h2>CPC Portal</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard_student.php" class="menu-link">📊 Dashboard</a></li>
        <li><a href="events_student.php"    class="menu-link active">📅 Club Events</a></li>
        <li><a href="announcements_student.php" class="menu-link">📬 Announcements</a></li>
        <li><a href="profile_student.php"   class="menu-link">👤 My Profile</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout">🚪 Logout</a>
    </div>
</div>

<!-- ── Main ── -->
<div class="main-wrapper">

    <!-- Topbar -->
    <div class="topbar">
        <span class="role-badge">💻 CPC Member</span>
        <strong><?= htmlspecialchars($userName) ?></strong>
    </div>

    <!-- Content -->
    <div class="content-body">

        <div class="page-header">
            <h1 class="page-title">📅 Club Events</h1>
            <p class="page-sub">Computer & Programming Club — Kishoreganj University</p>
        </div>

        <!-- Summary -->
        <div class="summary-bar">
            <div class="summary-pill">🟢 Upcoming <span><?= count($upcoming) ?></span></div>
            <div class="summary-pill">⏳ Past <span><?= count($past) ?></span></div>
        </div>

        <!-- ── Upcoming Events ── -->
        <div class="section-label">🟢 Upcoming Events</div>

        <?php if (empty($upcoming)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>No upcoming events at the moment. Check back soon!</p>
        </div>
        <?php else: ?>
        <div class="event-grid">
            <?php foreach ($upcoming as $ev): ?>
            <div class="event-card">
                <div class="card-top">
                    <div class="date-badge">
                        <div class="day"><?= date('d', strtotime($ev['event_date'])) ?></div>
                        <div class="month"><?= date('M', strtotime($ev['event_date'])) ?></div>
                    </div>
                    <div class="card-title"><?= htmlspecialchars($ev['title']) ?></div>
                </div>
                <div class="card-body">
                    <?php if (!empty($ev['event_time'])): ?>
                    <div class="meta-row">🕐 <?= date('h:i A', strtotime($ev['event_time'])) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($ev['venue'])): ?>
                    <div class="meta-row">📍 <?= htmlspecialchars($ev['venue']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($ev['description'])): ?>
                    <div class="event-desc"><?= nl2br(htmlspecialchars($ev['description'])) ?></div>
                    <?php endif; ?>
                    <span class="status-badge badge-upcoming">✅ Upcoming</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ── Past Events ── -->
        <?php if (!empty($past)): ?>
        <div class="section-label">⏳ Past Events</div>
        <div class="event-grid">
            <?php foreach (array_reverse($past) as $ev): ?>
            <div class="event-card">
                <div class="card-top past">
                    <div class="date-badge">
                        <div class="day"><?= date('d', strtotime($ev['event_date'])) ?></div>
                        <div class="month"><?= date('M', strtotime($ev['event_date'])) ?></div>
                    </div>
                    <div class="card-title"><?= htmlspecialchars($ev['title']) ?></div>
                </div>
                <div class="card-body">
                    <?php if (!empty($ev['event_time'])): ?>
                    <div class="meta-row">🕐 <?= date('h:i A', strtotime($ev['event_time'])) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($ev['venue'])): ?>
                    <div class="meta-row">📍 <?= htmlspecialchars($ev['venue']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($ev['description'])): ?>
                    <div class="event-desc"><?= nl2br(htmlspecialchars($ev['description'])) ?></div>
                    <?php endif; ?>
                    <span class="status-badge badge-past">🏁 Completed</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /content-body -->
</div><!-- /main-wrapper -->

</body>
</html>
