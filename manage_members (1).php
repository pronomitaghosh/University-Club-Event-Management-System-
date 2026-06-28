<?php
require_once 'config.php';

$msg = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = intval($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id > 0 && in_array($action, ['approve', 'reject'])) {

        if ($action === 'approve') {

            // ১. join_requests থেকে data নিয়ে আসো
            $sel = $conn->prepare("SELECT id, full_name, email, phone, department, session_year, password FROM join_requests WHERE id = ?");
            $sel->bind_param("i", $id);
            $sel->execute();
            $result = $sel->get_result();
            $jr = $result->fetch_assoc();
            $sel->close();

            if ($jr) {
                // ২. users table এ insert করো (existing column names অনুযায়ী)
                $role = 'student';
                $status = 'active';
                
                $ins = $conn->prepare(
                    "INSERT INTO users (full_name, email, phone, department, session_year, password, role, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $ins->bind_param(
                    "ssssssss",
                    $jr['full_name'],
                    $jr['email'],
                    $jr['phone'],
                    $jr['department'],
                    $jr['session_year'],
                    $jr['password'],
                    $role,
                    $status
                );

                if ($ins->execute()) {
                    // ৩. join_requests এ status = 'accepted' করো
                    $upd = $conn->prepare("UPDATE join_requests SET status = 'accepted' WHERE id = ?");
                    $upd->bind_param("i", $id);
                    $upd->execute();
                    $upd->close();
                    $msg = "✅ Member সফলভাবে approve হয়েছে।";
                } else {
                    $msg = "❌ Insert error: " . $ins->error;
                    $msg_type = 'error';
                }
                $ins->close();
            } else {
                $msg = "❌ Request খুঁজে পাওয়া যায়নি।";
                $msg_type = 'error';
            }

        } else {
            // Reject: status = 'rejected' করো
            $upd = $conn->prepare("UPDATE join_requests SET status = 'rejected' WHERE id = ?");
            if ($upd) {
                $upd->bind_param("i", $id);
                $upd->execute();
                $upd->close();
                $msg = "🗑️ Request reject করা হয়েছে।";
            } else {
                $msg = "❌ Error: " . $conn->error;
                $msg_type = 'error';
            }
        }
    }
}

// Pending requests
$requests = [];
$res = $conn->query("SELECT * FROM join_requests WHERE status = 'pending' ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) $requests[] = $row;
}

// Accepted members — join_requests এ accepted status যাদের, তাদের users table থেকে দেখাবো
$members = [];
$res2 = $conn->query(
    "SELECT u.user_id, u.full_name, u.email, u.phone, u.department, u.session_year, u.created_at
     FROM users u
     INNER JOIN join_requests jr ON jr.email = u.email AND jr.status = 'accepted'
     ORDER BY u.user_id DESC"
);
if ($res2) {
    while ($row = $res2->fetch_assoc()) $members[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Members | CPC Kishoreganj University</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --teal: #1a7a6e; --teal-dark: #125a51; --deep: #0e3d38;
            --gold: #c89b3c; --white: #ffffff; --bg: #f0f6f5;
            --border: #cde5e2; --sidebar-width: 260px;
            --red: #d94f3d; --red-light: #fdecea; --green-light: #e9f7f0;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); min-height: 100vh; display: flex; }

        /* Sidebar */
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
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; color: rgba(255,255,255,0.75);
            text-decoration: none; font-size: 0.95rem; border-radius: 10px; transition: background 0.2s;
        }
        .menu-link:hover, .menu-link.active { color: var(--white); background: rgba(255,255,255,0.15); }
        .menu-link.active { border-left: 4px solid var(--gold); }

        /* Main */
        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 32px; }
        .page-title { font-family: 'Playfair Display', serif; color: var(--deep); font-size: 1.6rem; }
        .page-subtitle { color: #6b7f7d; font-size: 0.9rem; margin-top: 4px; }

        /* Flash */
        .flash { padding: 13px 18px; border-radius: 10px; font-size: 0.92rem; font-weight: 500; }
        .flash.success { background: var(--green-light); color: var(--teal-dark); border: 1px solid var(--border); }
        .flash.error   { background: var(--red-light);   color: var(--red);       border: 1px solid #f5c6cb; }

        /* Cards */
        .section-card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
        .section-head { padding: 20px 28px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .section-head h3 { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--deep); }

        .badge { background: var(--gold); color: var(--white); font-size: 0.75rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
        .badge.teal { background: var(--teal); }

        /* Table */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        thead th { background: #f4f9f8; color: var(--deep); font-weight: 600; padding: 13px 18px; text-align: left; border-bottom: 1px solid var(--border); white-space: nowrap; }
        tbody td { padding: 12px 18px; border-bottom: 1px solid #edf4f3; color: #3a5450; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f9fdfc; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 14px; border: none; border-radius: 7px; font-family: 'DM Sans', sans-serif; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: opacity 0.15s; }
        .btn:hover { opacity: 0.85; }
        .btn-approve { background: var(--teal); color: var(--white); }
        .btn-reject  { background: var(--red-light); color: var(--red); border: 1px solid #f5c6cb; }

        .reason-cell { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: help; }
        .empty { padding: 40px; text-align: center; color: #8eabaa; font-size: 0.93rem; }
        .empty span { font-size: 2rem; display: block; margin-bottom: 8px; }
        .chip { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; background: var(--green-light); color: var(--teal-dark); border: 1px solid var(--border); }
        .action-cell { display: flex; gap: 8px; flex-wrap: wrap; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div style="font-size:1.6rem;">👑</div>
        <h2>President Panel</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard_president.php" class="menu-link">📊 Overview</a></li>
        <li><a href="manage_members.php" class="menu-link active">👥 Manage Members</a></li>
        <li><a href="create_event.php" class="menu-link">📅 Create Event</a></li>
        <li><a href="club_notice.php" class="menu-link">📢 Club Notice</a></li>
    </ul>
    <div style="padding: 20px 24px;">
        <a href="#" id="logoutBtn" style="color:#ff8a8a; text-decoration:none; font-weight:600;">🚪 Logout</a>
    </div>
</div>

<div class="main-wrapper">

    <div>
        <h1 class="page-title">Manage Members</h1>
        <p class="page-subtitle">Pending join requests এবং current members এখানে দেখো।</p>
    </div>

    <?php if ($msg !== ''): ?>
        <div class="flash <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Pending Requests -->
    <div class="section-card">
        <div class="section-head">
            <h3>📥 Pending Join Requests</h3>
            <span class="badge"><?= count($requests) ?> pending</span>
        </div>
        <div class="table-wrap">
            <?php if (empty($requests)): ?>
                <div class="empty"><span>🎉</span>কোনো pending request নেই।</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Session</th>
                        <th>Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['phone'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($r['department'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($r['session_year'] ?: '—') ?></td>
                        <td>
                            <span class="reason-cell" title="<?= htmlspecialchars($r['reason'] ?: '') ?>">
                                <?= htmlspecialchars($r['reason'] ?: '—') ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-cell">
                                <form method="POST">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-approve"
                                        onclick="return confirm('এই request approve করবে?')">✅ Approve</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-reject"
                                        onclick="return confirm('Reject করবে?')">❌ Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Current Members -->
    <div class="section-card">
        <div class="section-head">
            <h3>👥 Current Members</h3>
            <span class="badge teal"><?= count($members) ?> members</span>
        </div>
        <div class="table-wrap">
            <?php if (empty($members)): ?>
                <div class="empty"><span>📭</span>এখনো কোনো approved member নেই।</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Session</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $i => $m): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($m['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td><?= htmlspecialchars($m['phone'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($m['department'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($m['session_year'] ?? '—') ?></td>
                        <td><span class="chip">✅ Active</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
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
