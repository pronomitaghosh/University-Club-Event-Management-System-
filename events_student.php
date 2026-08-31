<?php
session_start();
require_once 'config.php';

// ── Session check ──────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id   = (int) $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Member', ENT_QUOTES, 'UTF-8');

// ── Registration table auto-create ─────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS event_registrations (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    event_id      INT NOT NULL,
    user_id       INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reg (event_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Register action ────────────────────────────────────────────
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event_id'])) {
    $eid  = (int) $_POST['register_event_id'];

    $stmt = $conn->prepare(
        "INSERT IGNORE INTO event_registrations (event_id, user_id) VALUES (?, ?)"
    );
    $stmt->bind_param('ii', $eid, $user_id);
    $stmt->execute();

    $msg = ($stmt->affected_rows > 0) ? 'success' : 'already';
    $stmt->close();
}

// ── Fetch events + registration status for this user ──────────
$events = [];
$sql = "SELECT e.*,
        (SELECT COUNT(*) FROM event_registrations r  WHERE r.event_id = e.event_id AND r.user_id = ?) AS is_registered,
        (SELECT COUNT(*) FROM event_registrations r2 WHERE r2.event_id = e.event_id) AS total_registrations
        FROM events e
        ORDER BY e.event_date ASC, e.event_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();

$todayTs  = strtotime(date('Y-m-d'));
$upcoming = array_values(array_filter($events, fn($e) => strtotime($e['event_date']) >= $todayTs));
$past     = array_values(array_filter($events, fn($e) => strtotime($e['event_date']) <  $todayTs));
$myRegs   = count(array_filter($events, fn($e) => !empty($e['is_registered'])));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Events | CPC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --teal: #1a7a6e; --teal-dark: #125a51; --teal-light: #e6f5f3;
            --deep: #0e3d38; --gold: #c89b3c;
            --text: #1e2d2b; --muted: #6b7f7d;
            --white: #ffffff; --bg: #f4f9f8; --border: #cde5e2;
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
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; font-size: 1.1rem; }
        .sidebar-menu { list-style: none; padding: 24px 16px; flex: 1; }
        .menu-link {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            color: rgba(255,255,255,0.75); text-decoration: none;
            font-size: 0.95rem; font-weight: 500; border-radius: 10px;
        }
        .menu-link:hover, .menu-link.active { color: var(--white); background: rgba(255,255,255,0.15); }
        .menu-link.active { border-left: 4px solid var(--gold); }
        .sidebar-footer { padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.1); }
        .btn-logout { display: flex; align-items: center; gap: 10px; color: #ff8a8a; text-decoration: none; font-size: 0.9rem; font-weight: 600; }

        /* ── Main ── */
        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; }

        .topbar {
            background: var(--white); height: 70px; padding: 0 40px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 50;
        }
        .topbar-left { font-size: 1rem; font-weight: 600; color: var(--deep); }
        .role-badge {
            padding: 6px 14px; border-radius: 20px;
            font-size: 0.8rem; font-weight: 700;
            background: var(--teal-light); color: var(--teal-dark);
        }

        .content-body { padding: 40px; }

        .page-title { font-family: 'Playfair Display', serif; font-size: 1.8rem; color: var(--deep); }
        .page-sub   { color: var(--muted); font-size: 0.95rem; margin-top: 4px; margin-bottom: 28px; }

        /* ── Toast ── */
        .toast { display: inline-flex; align-items: center; gap: 10px; padding: 12px 20px; border-radius: 10px; font-weight: 500; font-size: 0.92rem; margin-bottom: 24px; }
        .toast-success { background: #e6f4f1; border: 1px solid var(--teal); color: var(--teal-dark); }
        .toast-info    { background: #fffbf0; border: 1px solid #e0b84a; color: #7a5c00; }

        /* ── Section Label ── */
        .section-label {
            font-size: 0.78rem; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: #9bb0ae; margin: 32px 0 16px;
            display: flex; align-items: center; gap: 8px;
        }
        .section-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        /* ── Cards ── */
        .event-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(310px, 1fr)); gap: 22px; }

        .event-card {
            background: var(--white); border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            display: flex; flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }
        .event-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(26,122,110,0.1); }
        .event-card.past-card { opacity: 0.72; }

        .card-strip { background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%); padding: 18px 20px; display: flex; align-items: flex-start; gap: 16px; }
        .card-strip.past-strip { background: linear-gradient(135deg, #8fa7a4 0%, #6b8784 100%); }

        .date-box { background: rgba(255,255,255,0.2); border-radius: 10px; padding: 8px 12px; text-align: center; min-width: 52px; flex-shrink: 0; }
        .date-box .day { font-size: 1.5rem; font-weight: 700; color: white; line-height: 1; }
        .date-box .month { font-size: 0.7rem; text-transform: uppercase; color: rgba(255,255,255,0.85); letter-spacing: 0.05em; }

        .card-title { font-family: 'Playfair Display', serif; font-size: 1.05rem; color: white; line-height: 1.35; padding-top: 4px; }

        .card-body { padding: 18px 20px; flex: 1; display: flex; flex-direction: column; gap: 0; }
        .meta-list { display: flex; flex-direction: column; gap: 7px; margin-bottom: 14px; }
        .meta-item { font-size: 0.85rem; color: var(--muted); display: flex; align-items: center; gap: 8px; }
        .meta-item strong { color: var(--text); }

        .event-desc { font-size: 0.87rem; color: #5a7270; line-height: 1.55; border-top: 1px solid #eef4f3; padding-top: 12px; margin-bottom: 16px; flex: 1; }
        .reg-count { font-size: 0.8rem; color: var(--muted); margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        /* Buttons */
        .btn-register {
            width: 100%; padding: 11px; border: none; border-radius: 10px;
            font-family: 'DM Sans', sans-serif; font-size: 0.9rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
            background: var(--teal); color: white;
        }
        .btn-register:hover { background: var(--teal-dark); }

        .btn-enrolled {
            width: 100%; padding: 11px; border: none; border-radius: 10px;
            font-family: 'DM Sans', sans-serif; font-size: 0.9rem; font-weight: 600;
            cursor: default; background: #e6f4f1; color: var(--teal-dark);
        }

        .btn-past {
            width: 100%; padding: 11px; border: none; border-radius: 10px;
            font-family: 'DM Sans', sans-serif; font-size: 0.9rem; font-weight: 600;
            cursor: default; background: #f0f0f0; color: #999;
        }

        .stats-bar { display: flex; gap: 14px; margin-bottom: 10px; flex-wrap: wrap; }
        .stat-pill { background: white; border: 1px solid var(--border); border-radius: 30px; padding: 7px 16px; font-size: 0.87rem; font-weight: 600; color: #4a6260; display: flex; align-items: center; gap: 7px; }
        .stat-pill em { color: var(--teal); font-style: normal; font-size: 0.95rem; }

        .empty { text-align: center; padding: 40px; color: var(--muted); }
        .empty .icon { font-size: 2.5rem; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div style="font-size:1.6rem;">💻</div>
        <h2>CPC Portal</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard_student.php" class="menu-link">📊 Dashboard</a></li>
        <li><a href="events_student.php" class="menu-link active">📅 Club Events</a></li>
        <li><a href="club_notice.php" class="menu-link">📬 Announcements</a></li>
        <li><a href="profile_student.php" class="menu-link">👤 My Profile</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout">🚪 Logout</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="topbar">
        <div class="topbar-left">Welcome, <?= $user_name ?> 👋</div>
        <span class="role-badge">💻 CPC Member</span>
    </div>

    <div class="content-body">
        <h1 class="page-title">📅 Club Events</h1>
        <p class="page-sub">All upcoming and past events of CPC Kishoreganj University.</p>

        <?php if ($msg === 'success'): ?>
            <div class="toast toast-success">✅ Successfully registered! See you at the event.</div>
        <?php elseif ($msg === 'already'): ?>
            <div class="toast toast-info">ℹ️ You were already registered for this event.</div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-bar">
            <div class="stat-pill">🟢 Upcoming <em><?= count($upcoming) ?></em></div>
            <div class="stat-pill">✅ My Registrations <em><?= $myRegs ?></em></div>
            <div class="stat-pill">⏳ Past <em><?= count($past) ?></em></div>
        </div>

        <!-- ── Upcoming ── -->
        <div class="section-label">🟢 Upcoming Events</div>

        <?php if (empty($upcoming)): ?>
            <div class="empty"><div class="icon">📭</div><p>No upcoming events right now. Check back soon!</p></div>
        <?php else: ?>
            <div class="event-grid">
                <?php foreach ($upcoming as $ev): ?>
                    <div class="event-card">
                        <div class="card-strip">
                            <div class="date-box">
                                <div class="day"><?= date('d', strtotime($ev['event_date'])) ?></div>
                                <div class="month"><?= date('M Y', strtotime($ev['event_date'])) ?></div>
                            </div>
                            <div class="card-title"><?= htmlspecialchars($ev['title']) ?></div>
                        </div>
                        <div class="card-body">
                            <div class="meta-list">
                                <?php if (!empty($ev['event_time'])): ?>
                                    <div class="meta-item">🕐 <strong><?= date('h:i A', strtotime($ev['event_time'])) ?></strong></div>
                                <?php endif; ?>
                                <?php if (!empty($ev['venue'])): ?>
                                    <div class="meta-item">📍 <strong><?= htmlspecialchars($ev['venue']) ?></strong></div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($ev['description'])): ?>
                                <div class="event-desc"><?= nl2br(htmlspecialchars($ev['description'])) ?></div>
                            <?php endif; ?>

                            <div class="reg-count">👥 <?= (int) $ev['total_registrations'] ?> member(s) registered</div>

                            <?php if (!empty($ev['is_registered'])): ?>
                                <button class="btn-enrolled" disabled>✅ You're Enrolled</button>
                            <?php else: ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="register_event_id" value="<?= (int)$ev['event_id'] ?>">
                                    <button type="submit" class="btn-register">🚀 Register Now</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ── Past ── -->
        <?php if (!empty($past)): ?>
            <div class="section-label">⏳ Past Events</div>
            <div class="event-grid">
                <?php foreach (array_reverse(array_values($past)) as $ev): ?>
                    <div class="event-card past-card">
                        <div class="card-strip past-strip">
                            <div class="date-box">
                                <div class="day"><?= date('d', strtotime($ev['event_date'])) ?></div>
                                <div class="month"><?= date('M Y', strtotime($ev['event_date'])) ?></div>
                            </div>
                            <div class="card-title"><?= htmlspecialchars($ev['title']) ?></div>
                        </div>
                        <div class="card-body">
                            <div class="meta-list">
                                <?php if (!empty($ev['event_time'])): ?>
                                    <div class="meta-item">🕐 <strong><?= date('h:i A', strtotime($ev['event_time'])) ?></strong></div>
                                <?php endif; ?>
                                <?php if (!empty($ev['venue'])): ?>
                                    <div class="meta-item">📍 <strong><?= htmlspecialchars($ev['venue']) ?></strong></div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($ev['description'])): ?>
                                <div class="event-desc"><?= nl2br(htmlspecialchars($ev['description'])) ?></div>
                            <?php endif; ?>

                            <div class="reg-count">👥 <?= (int) $ev['total_registrations'] ?> member(s) attended</div>
                            <button class="btn-past" disabled>🏁 Event Completed</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div><!-- /content-body -->
</div><!-- /main-wrapper -->

</body>
</html>

