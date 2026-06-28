<?php
require_once 'config.php';

// Handle Create Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date  = $_POST['event_date'];
    $event_time  = $_POST['event_time'];
    $venue       = trim($_POST['venue']);
    $created_by  = 1; // Replace with actual president's user ID from session if available

    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, event_time, venue, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $title, $description, $event_date, $event_time, $venue, $created_by);

    if ($stmt->execute()) {
        $success = "✅ Event created successfully!";
    } else {
        $error = "❌ Failed to create event: " . $conn->error;
    }
    $stmt->close();
}

// Handle Delete Event
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM events WHERE event_id = $del_id");
    header("Location: create_event.php?deleted=1");
    exit;
}

// Fetch all events ordered by date
$events = [];
$result = $conn->query("SELECT * FROM events ORDER BY event_date DESC, event_time DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Events | CPC Kishoreganj University</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --teal: #1a7a6e; --teal-dark: #125a51; --deep: #0e3d38;
            --gold: #c89b3c; --white: #ffffff; --bg: #f0f6f5;
            --border: #cde5e2; --sidebar-width: 260px;
            --red: #e53e3e; --red-light: #fff5f5;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); min-height: 100vh; display: flex; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--deep) 0%, var(--teal-dark) 100%);
            color: var(--white); position: fixed; top: 0; bottom: 0; left: 0;
            display: flex; flex-direction: column;
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

        /* ── Main ── */
        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 40px; }

        .page-title { font-family: 'Playfair Display', serif; color: var(--deep); font-size: 1.8rem; }
        .page-sub   { color: #6b7f7d; font-size: 0.95rem; margin-top: 4px; margin-bottom: 30px; }

        /* ── Alert ── */
        .alert {
            padding: 14px 18px; border-radius: 10px; margin-bottom: 24px;
            font-weight: 500; font-size: 0.95rem;
        }
        .alert-success { background: #e6f4f1; border: 1px solid var(--teal); color: var(--teal-dark); }
        .alert-error   { background: var(--red-light); border: 1px solid var(--red); color: var(--red); }

        /* ── Card ── */
        .card {
            background: white; border-radius: 16px;
            border: 1px solid var(--border); box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            padding: 30px; margin-bottom: 30px;
        }
        .card h3 { font-family: 'Playfair Display', serif; color: var(--deep); margin-bottom: 20px; font-size: 1.2rem; }

        /* ── Form ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        label { font-size: 0.85rem; font-weight: 600; color: #4a6260; }
        input, textarea, select {
            padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px;
            font-family: 'DM Sans', sans-serif; font-size: 0.95rem;
            background: #f8fbfb; color: #1a1a1a; transition: border-color 0.2s;
        }
        input:focus, textarea:focus { outline: none; border-color: var(--teal); background: white; }
        textarea { resize: vertical; min-height: 90px; }

        .btn-create {
            margin-top: 6px; padding: 12px 28px;
            background: var(--teal); color: white; border: none; border-radius: 10px;
            font-family: 'DM Sans', sans-serif; font-size: 0.95rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
        }
        .btn-create:hover { background: var(--teal-dark); }

        /* ── Events Table ── */
        .events-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        .events-table th {
            background: #f4f9f8; color: #4a6260; font-weight: 600;
            padding: 12px 16px; text-align: left; border-bottom: 2px solid var(--border);
        }
        .events-table td {
            padding: 14px 16px; border-bottom: 1px solid #e8f0ef; vertical-align: middle;
        }
        .events-table tr:last-child td { border-bottom: none; }
        .events-table tr:hover td { background: #f8fffe; }

        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 0.78rem; font-weight: 600;
        }
        .badge-upcoming { background: #e6f4f1; color: var(--teal-dark); }
        .badge-past     { background: #f0f0f0; color: #888; }

        .btn-del {
            padding: 6px 14px; background: var(--red-light); color: var(--red);
            border: 1px solid #feb2b2; border-radius: 8px; font-size: 0.83rem;
            font-weight: 600; cursor: pointer; text-decoration: none;
            transition: background 0.2s;
        }
        .btn-del:hover { background: #fed7d7; }

        .empty-state { text-align: center; padding: 40px 20px; color: #9bb0ae; font-size: 0.95rem; }
        .empty-state .icon { font-size: 2.5rem; margin-bottom: 10px; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <div style="font-size:1.6rem;">👑</div>
        <h2>President Panel</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard_president.php" class="menu-link">📊 Overview</a></li>
        <li><a href="manage_members.php" class="menu-link">👥 Manage Members</a></li>
        <li><a href="create_event.php" class="menu-link active">📅 Create Event</a></li>
        <li><a href="club_notice.php" class="menu-link">📢 Club Notice</a></li>
    </ul>
    <div style="padding: 20px 24px;">
        <a href="#" id="logoutBtn" style="color:#ff8a8a; text-decoration:none; font-weight:600;">🚪 Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-wrapper">

    <h1 class="page-title">📅 Manage Events</h1>
    <p class="page-sub">Create new events and manage all existing ones.</p>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">🗑️ Event deleted successfully.</div>
    <?php endif; ?>

    <!-- Create Event Form -->
    <div class="card">
        <h3>➕ Create New Event</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-grid">
                <div class="form-group full">
                    <label for="title">Event Title *</label>
                    <input type="text" id="title" name="title" placeholder="e.g. Inter-University Programming Contest" required>
                </div>
                <div class="form-group">
                    <label for="event_date">Event Date *</label>
                    <input type="date" id="event_date" name="event_date" required>
                </div>
                <div class="form-group">
                    <label for="event_time">Event Time</label>
                    <input type="time" id="event_time" name="event_time">
                </div>
                <div class="form-group full">
                    <label for="venue">Venue</label>
                    <input type="text" id="venue" name="venue" placeholder="e.g. CS Department Auditorium">
                </div>
                <div class="form-group full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Write event details here..."></textarea>
                </div>
            </div>
            <button type="submit" class="btn-create">🚀 Create Event</button>
        </form>
    </div>

    <!-- All Events List -->
    <div class="card">
        <h3>📋 All Events</h3>
        <?php if (empty($events)): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <p>No events found. Create your first event above!</p>
            </div>
        <?php else: ?>
        <table class="events-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Venue</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $i => $ev): ?>
                <?php $isPast = strtotime($ev['event_date']) < strtotime(date('Y-m-d')); ?>
                <tr>
                    <td style="color:#9bb0ae; font-size:0.85rem;"><?= $i + 1 ?></td>
                    <td>
                        <strong><?= htmlspecialchars($ev['title']) ?></strong>
                        <?php if ($ev['description']): ?>
                            <div style="color:#9bb0ae; font-size:0.82rem; margin-top:3px;">
                                <?= htmlspecialchars(mb_substr($ev['description'], 0, 60)) ?><?= strlen($ev['description']) > 60 ? '…' : '' ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d M Y', strtotime($ev['event_date'])) ?></td>
                    <td><?= $ev['event_time'] ? date('h:i A', strtotime($ev['event_time'])) : '—' ?></td>
                    <td><?= $ev['venue'] ? htmlspecialchars($ev['venue']) : '—' ?></td>
                    <td>
                        <span class="badge <?= $isPast ? 'badge-past' : 'badge-upcoming' ?>">
                            <?= $isPast ? 'Past' : 'Upcoming' ?>
                        </span>
                    </td>
                    <td>
                        <a href="create_event.php?delete=<?= $ev['event_id'] ?>"
                           class="btn-del"
                           onclick="return confirm('Delete this event? This cannot be undone.')">
                           🗑️ Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<script>
    if (sessionStorage.getItem('isPresidentLoggedIn') !== 'true') {
        window.location.href = 'login_president.html';
    }
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        sessionStorage.removeItem('isPresidentLoggedIn');
        sessionStorage.removeItem('presidentEmail');
        window.location.href = 'login_president.html';
    });
</script>
</body>
</html>
