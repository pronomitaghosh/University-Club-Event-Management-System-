<?php
session_start();
require_once 'config.php';

// --- Handle POST: Add new notice ---
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_notice') {
        $title   = trim($conn->real_escape_string($_POST['title']));
        $body    = trim($conn->real_escape_string($_POST['body']));
        $pinned  = isset($_POST['pinned']) ? 1 : 0;

        if ($title && $body) {
            $sql = "INSERT INTO notices (title, body, pinned, created_at) VALUES ('$title', '$body', $pinned, NOW())";
            if ($conn->query($sql)) {
                $success_msg = 'Notice posted successfully!';
            } else {
                $error_msg = 'Database error: ' . $conn->error;
            }
        } else {
            $error_msg = 'Title and body are required.';
        }
    }

    if ($_POST['action'] === 'delete_notice') {
        $id = intval($_POST['notice_id']);
        $conn->query("DELETE FROM notices WHERE id = $id");
        $success_msg = 'Notice deleted.';
    }

    if ($_POST['action'] === 'toggle_pin') {
        $id  = intval($_POST['notice_id']);
        $pin = intval($_POST['current_pin']) === 1 ? 0 : 1;
        $conn->query("UPDATE notices SET pinned = $pin WHERE id = $id");
    }
}

// --- Fetch notices ---
$notices = [];
$res = $conn->query("SELECT * FROM notices ORDER BY pinned DESC, created_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) $notices[] = $row;
}

// Determine view mode: 'president' or 'member'
// Use PHP session role if available; fallback to URL param.
$userRole = $_SESSION['user_role'] ?? '';
if ($userRole === 'student') {
    $view_mode = 'member';
} elseif ($userRole === 'president') {
    $view_mode = 'president';
} else {
    $view_mode = (isset($_GET['role']) && $_GET['role'] === 'member') ? 'member' : 'president';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Notices | CPC Kishoreganj University</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --teal: #1a7a6e; --teal-dark: #125a51; --deep: #0e3d38;
            --gold: #c89b3c; --gold-light: #f5e3b0; --white: #ffffff;
            --bg: #f0f6f5; --border: #cde5e2; --text: #1e2d2b;
            --muted: #6b7f7d; --sidebar-width: 260px;
            --red: #d94f4f; --red-light: #fdeaea;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); min-height: 100vh; display: flex; color: var(--text); }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-width); background: linear-gradient(180deg, var(--deep) 0%, var(--teal-dark) 100%);
            color: var(--white); position: fixed; top: 0; bottom: 0; left: 0;
            display: flex; flex-direction: column; z-index: 100;
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

        /* ── MAIN ── */
        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 40px; max-width: 900px; }

        /* ── PAGE HEADER ── */
        .page-header { margin-bottom: 32px; }
        .page-header h1 { font-family: 'Playfair Display', serif; font-size: 2rem; color: var(--deep); }
        .page-header p { color: var(--muted); margin-top: 4px; font-size: 0.95rem; }

        /* ── BULLETIN BOARD CARD ── */
        .board-card {
            background: var(--white); border-radius: 18px; padding: 32px;
            border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 28px;
        }
        .board-card h3 {
            font-family: 'Playfair Display', serif; font-size: 1.2rem;
            color: var(--deep); margin-bottom: 20px;
            border-bottom: 2px solid var(--border); padding-bottom: 12px;
        }

        /* ── FORM ── */
        .notice-form { display: flex; flex-direction: column; gap: 14px; }
        .form-row { display: flex; gap: 14px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; flex: 1; }
        label { font-size: 0.82rem; font-weight: 600; color: var(--muted); letter-spacing: 0.04em; text-transform: uppercase; }
        input[type="text"], textarea {
            padding: 12px 14px; border: 1.5px solid var(--border); border-radius: 10px;
            font-family: 'DM Sans', sans-serif; font-size: 0.95rem; color: var(--text);
            background: #f7fbfa; transition: border-color 0.2s, box-shadow 0.2s; resize: vertical;
        }
        input[type="text"]:focus, textarea:focus {
            outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(26,122,110,0.12);
        }
        textarea { min-height: 110px; }
        .pin-row { display: flex; align-items: center; gap: 10px; }
        .pin-toggle { width: 18px; height: 18px; accent-color: var(--gold); cursor: pointer; }
        .pin-label { font-size: 0.9rem; color: var(--text); }

        .btn-post {
            background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
            color: var(--white); border: none; padding: 13px 28px; border-radius: 10px;
            font-family: 'DM Sans', sans-serif; font-size: 0.95rem; font-weight: 600;
            cursor: pointer; transition: opacity 0.2s, transform 0.1s; align-self: flex-start;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-post:hover { opacity: 0.88; transform: translateY(-1px); }
        .btn-post:active { transform: translateY(0); }

        /* ── NOTICES LIST ── */
        #notices-list { display: flex; flex-direction: column; gap: 16px; }

        .notice-item {
            background: #f7fbfa; border: 1.5px solid var(--border); border-radius: 14px;
            padding: 20px 22px; position: relative; transition: box-shadow 0.2s;
            animation: fadeSlideIn 0.35s ease both;
        }
        .notice-item:hover { box-shadow: 0 4px 16px rgba(26,122,110,0.10); }
        .notice-item.pinned { border-color: var(--gold); background: #fffdf4; }

        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .notice-meta {
            display: flex; align-items: center; gap: 10px; margin-bottom: 8px;
            flex-wrap: wrap;
        }
        .badge-pin {
            background: var(--gold-light); color: #8a6a0a; font-size: 0.72rem;
            font-weight: 700; padding: 3px 9px; border-radius: 20px; letter-spacing: 0.06em;
        }
        .badge-new {
            background: #e0f4f1; color: var(--teal); font-size: 0.72rem;
            font-weight: 700; padding: 3px 9px; border-radius: 20px;
        }
        .notice-time { font-size: 0.78rem; color: var(--muted); margin-left: auto; }
        .notice-title { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--deep); margin-bottom: 8px; }
        .notice-body { font-size: 0.93rem; color: #3d5a57; line-height: 1.65; white-space: pre-wrap; }

        .notice-actions { display: flex; gap: 8px; margin-top: 14px; }
        .btn-sm {
            font-size: 0.78rem; font-weight: 600; padding: 6px 14px;
            border-radius: 8px; border: none; cursor: pointer;
            font-family: 'DM Sans', sans-serif; transition: opacity 0.15s;
        }
        .btn-sm:hover { opacity: 0.8; }
        .btn-pin  { background: var(--gold-light); color: #8a6a0a; }
        .btn-del  { background: var(--red-light);  color: var(--red); }

        .empty-state {
            text-align: center; padding: 48px 20px; color: var(--muted);
        }
        .empty-state .icon { font-size: 2.5rem; margin-bottom: 12px; }
        .empty-state p { font-size: 0.95rem; }

        /* ── LIVE INDICATOR ── */
        .live-bar {
            display: flex; align-items: center; gap: 10px; margin-bottom: 16px;
            font-size: 0.83rem; color: var(--muted);
        }
        .live-dot {
            width: 8px; height: 8px; background: #2ecc71; border-radius: 50%;
            animation: pulse 1.8s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.45; transform: scale(0.8); }
        }

        /* ── TOAST ── */
        .toast {
            position: fixed; bottom: 32px; right: 32px; z-index: 999;
            background: var(--deep); color: var(--white); padding: 14px 22px;
            border-radius: 12px; font-size: 0.9rem; font-weight: 500;
            box-shadow: 0 8px 30px rgba(0,0,0,0.18);
            transform: translateY(20px); opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
            pointer-events: none; max-width: 320px;
        }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.success { border-left: 4px solid #2ecc71; }
        .toast.error   { border-left: 4px solid var(--red); }

        /* ── LAST UPDATED ── */
        #last-updated { font-size: 0.78rem; color: var(--muted); font-style: italic; }
    </style>
</head>
<body>

<?php if ($view_mode === 'president'): ?>
<!-- PRESIDENT SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <div style="font-size:1.6rem;">👑</div>
        <h2>President Panel</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard_president.php" class="menu-link">📊 Overview</a></li>
        <li><a href="manage_members.php" class="menu-link">👥 Manage Members</a></li>
        <li><a href="create_event.php" class="menu-link">📅 Create Event</a></li>
        <li><a href="club_notice.php" class="menu-link active">📢 Club Notice</a></li>
    </ul>
    <div style="padding: 20px 24px;">
        <a href="#" id="logoutBtn" style="color:#ff8a8a; text-decoration:none; font-weight:600;">🚪 Logout</a>
    </div>
</div>
<?php else: ?>
<!-- MEMBER SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <div style="font-size:1.6rem;">🎓</div>
        <h2>Member Panel</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="profile_student.php" class="menu-link">🏠 My Profile</a></li>
        <li><a href="events_student.php" class="menu-link">📅 Events</a></li>
        <li><a href="club_notice.php?role=member" class="menu-link active">📢 Notices</a></li>
    </ul>
    <div style="padding: 20px 24px;">
        <a href="#" id="logoutBtn" style="color:#ff8a8a; text-decoration:none; font-weight:600;">🚪 Logout</a>
    </div>
</div>
<?php endif; ?>

<!-- MAIN CONTENT -->
<div class="main-wrapper">
    <div class="page-header">
        <h1>📢 Club Bulletin Board</h1>
        <p>
            <?= $view_mode === 'president'
                ? 'Post important updates and announcements for all club members.'
                : 'Stay up to date with the latest club announcements.' ?>
        </p>
    </div>

    <?php if ($view_mode === 'president'): ?>
    <!-- POST NOTICE FORM -->
    <div class="board-card">
        <h3>✍️ Post a New Notice</h3>
        <div class="notice-form">
            <div class="form-group">
                <label for="ntitle">Notice Title</label>
                <input type="text" id="ntitle" placeholder="e.g. Monthly Meeting – July 2025" maxlength="150">
            </div>
            <div class="form-group">
                <label for="nbody">Notice Body</label>
                <textarea id="nbody" placeholder="Write the full announcement here..."></textarea>
            </div>
            <div class="pin-row">
                <input type="checkbox" id="npinned" class="pin-toggle">
                <label for="npinned" class="pin-label" style="text-transform:none; letter-spacing:0;">📌 Pin this notice to the top</label>
            </div>
            <button class="btn-post" onclick="postNotice()">
                <span>📤</span> Post Notice
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- BULLETIN BOARD -->
    <div class="board-card">
        <h3>📋 All Notices</h3>
        <div class="live-bar">
            <span class="live-dot"></span>
            <span>Live — auto-refreshes every 30 seconds</span>
            <span id="last-updated" style="margin-left:auto;"></span>
        </div>
        <div id="notices-list">
            <!-- injected by JS -->
            <?php foreach ($notices as $n): ?>
            <div data-id="<?= $n['id'] ?>" data-pinned="<?= $n['pinned'] ?>"
                 class="notice-item <?= $n['pinned'] ? 'pinned' : '' ?>"
                 style="display:none;">
                <div class="notice-meta">
                    <?php if ($n['pinned']): ?><span class="badge-pin">📌 Pinned</span><?php endif; ?>
                    <span class="notice-time"><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></span>
                </div>
                <div class="notice-title"><?= htmlspecialchars($n['title']) ?></div>
                <div class="notice-body"><?= htmlspecialchars($n['body']) ?></div>
                <?php if ($view_mode === 'president'): ?>
                <div class="notice-actions">
                    <button class="btn-sm btn-pin"
                        onclick="togglePin(<?= $n['id'] ?>, <?= $n['pinned'] ?>)">
                        <?= $n['pinned'] ? '📍 Unpin' : '📌 Pin' ?>
                    </button>
                    <button class="btn-sm btn-del"
                        onclick="deleteNotice(<?= $n['id'] ?>)">
                        🗑 Delete
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($notices)): ?>
            <div class="empty-state" id="empty-state">
                <div class="icon">📭</div>
                <p>No notices posted yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
const IS_PRESIDENT = <?= $view_mode === 'president' ? 'true' : 'false' ?>;
const ROLE_PARAM   = IS_PRESIDENT ? '' : '?role=member';

// Logout (server-side session is handled by logout.php)
document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    window.location.href = 'logout.php';
});

// ── TOAST ──
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `toast ${type} show`;
    setTimeout(() => { t.className = 'toast'; }, 3200);
}

// ── RENDER NOTICES ──
function renderNotices(items) {
    const list  = document.getElementById('notices-list');
    const empty = document.getElementById('empty-state');

    // Remove old dynamic items
    list.querySelectorAll('.notice-item.dynamic').forEach(el => el.remove());

    // Make PHP-rendered items visible (initial load only)
    list.querySelectorAll('.notice-item[style*="display:none"]').forEach(el => {
        el.style.display = '';
    });

    if (!items) {
        // first load – items already in DOM via PHP
        if (empty) empty.style.display = list.querySelectorAll('.notice-item').length ? 'none' : '';
        return;
    }

    // Clear all and re-render from fetched data
    list.querySelectorAll('.notice-item').forEach(el => el.remove());

    if (!items.length) {
        if (!empty) {
            const e = document.createElement('div');
            e.className = 'empty-state'; e.id = 'empty-state';
            e.innerHTML = '<div class="icon">📭</div><p>No notices posted yet.</p>';
            list.appendChild(e);
        } else {
            list.appendChild(empty);
        }
        return;
    }

    // Remove empty state if notices exist
    const existEmpty = document.getElementById('empty-state');
    if (existEmpty) existEmpty.remove();

    const now = new Date();
    items.forEach(n => {
        const created = new Date(n.created_at.replace(' ', 'T'));
        const diffHrs = (now - created) / 3600000;
        const isNew   = diffHrs < 24;
        const dateStr = created.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
                      + ', ' + created.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

        const div = document.createElement('div');
        div.className = `notice-item dynamic ${n.pinned == 1 ? 'pinned' : ''}`;
        div.dataset.id     = n.id;
        div.dataset.pinned = n.pinned;

        div.innerHTML = `
            <div class="notice-meta">
                ${n.pinned == 1 ? '<span class="badge-pin">📌 Pinned</span>' : ''}
                ${isNew ? '<span class="badge-new">✨ New</span>' : ''}
                <span class="notice-time">${dateStr}</span>
            </div>
            <div class="notice-title">${escHtml(n.title)}</div>
            <div class="notice-body">${escHtml(n.body)}</div>
            ${IS_PRESIDENT ? `
            <div class="notice-actions">
                <button class="btn-sm btn-pin" onclick="togglePin(${n.id}, ${n.pinned})">
                    ${n.pinned == 1 ? '📍 Unpin' : '📌 Pin'}
                </button>
                <button class="btn-sm btn-del" onclick="deleteNotice(${n.id})">
                    🗑 Delete
                </button>
            </div>` : ''}
        `;
        list.appendChild(div);
    });

    // Update last-refreshed
    document.getElementById('last-updated').textContent =
        'Last updated: ' + new Date().toLocaleTimeString();
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── FETCH (real-time refresh) ──
function fetchNotices(silent = false) {
    fetch('fetch_notices.php')
        .then(r => r.json())
        .then(data => {
            renderNotices(data);
            if (!silent) showToast('Notices refreshed ✓', 'success');
        })
        .catch(() => {
            if (!silent) showToast('Could not refresh notices.', 'error');
        });
}

// Initial render from PHP data, then auto-refresh
renderNotices(null);
setInterval(() => fetchNotices(true), 30000);

// ── POST NOTICE ──
function postNotice() {
    const title  = document.getElementById('ntitle').value.trim();
    const body   = document.getElementById('nbody').value.trim();
    const pinned = document.getElementById('npinned').checked ? 1 : 0;

    if (!title || !body) { showToast('Please fill in both title and body.', 'error'); return; }

    const fd = new FormData();
    fd.append('action', 'add_notice');
    fd.append('title',  title);
    fd.append('body',   body);
    if (pinned) fd.append('pinned', '1');

    fetch('club_notice.php', { method: 'POST', body: fd })
        .then(() => {
            document.getElementById('ntitle').value  = '';
            document.getElementById('nbody').value   = '';
            document.getElementById('npinned').checked = false;
            showToast('✅ Notice posted!', 'success');
            fetchNotices(true);
        })
        .catch(() => showToast('Failed to post. Try again.', 'error'));
}

// ── DELETE ──
function deleteNotice(id) {
    if (!confirm('Delete this notice?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_notice');
    fd.append('notice_id', id);
    fetch('club_notice.php', { method: 'POST', body: fd })
        .then(() => { showToast('🗑 Notice deleted.', 'success'); fetchNotices(true); })
        .catch(() => showToast('Delete failed.', 'error'));
}

// ── TOGGLE PIN ──
function togglePin(id, currentPin) {
    const fd = new FormData();
    fd.append('action', 'toggle_pin');
    fd.append('notice_id', id);
    fd.append('current_pin', currentPin);
    fetch('club_notice.php', { method: 'POST', body: fd })
        .then(() => { showToast(currentPin == 1 ? 'Unpinned.' : '📌 Pinned to top!', 'success'); fetchNotices(true); })
        .catch(() => showToast('Action failed.', 'error'));
}
</script>
</body>
</html>
