<?php
require_once 'config.php';
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: login_student.html');
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT full_name, student_id, email, department, created_at FROM users WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($fullName, $studentId, $email, $department, $createdAt);
$stmt->fetch();
$stmt->close();

$joinedDate = $createdAt ? date('F j, Y', strtotime($createdAt)) : 'Unknown';
$shortName = explode(' ', trim($fullName))[0] ?? $fullName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | CPC</title>
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
            --sidebar-width: 280px;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }
        .sidebar { width: var(--sidebar-width); background: linear-gradient(180deg, var(--deep) 0%, var(--teal-dark) 100%); color: var(--white); position: fixed; top: 0; bottom: 0; left: 0; display: flex; flex-direction: column; z-index: 100; }
        .sidebar-header { padding: 28px 24px; border-bottom: 1px solid rgba(255,255,255,0.12); text-align: left; }
        .sidebar-header .logo { font-size: 2rem; margin-bottom: 12px; }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; font-size: 1.2rem; letter-spacing: 0.3px; }
        .sidebar-menu { list-style: none; padding: 24px 16px; flex: 1; }
        .menu-link { display: flex; align-items: center; gap: 12px; padding: 14px 16px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.95rem; font-weight: 600; border-radius: 12px; transition: background 0.2s, color 0.2s; }
        .menu-link:hover, .menu-link.active { color: #ffffff; background: rgba(255,255,255,0.12); }
        .menu-link.active { border-left: 4px solid var(--gold); }
        .sidebar-footer { padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.12); }
        .btn-logout { display: inline-flex; align-items: center; gap: 10px; color: #ffb5b5; text-decoration: none; font-size: 0.95rem; font-weight: 600; }
        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; }
        .topbar { background: var(--white); height: 74px; padding: 0 36px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); }
        .breadcrumbs { color: var(--muted); font-size: 0.95rem; }
        .topbar-right { display: flex; align-items: center; gap: 18px; }
        .topbar-user { text-align: right; }
        .topbar-user span { display: block; font-size: 0.85rem; color: var(--muted); }
        .topbar-user strong { font-size: 1.05rem; }
        .role-badge { padding: 8px 16px; border-radius: 999px; background: var(--teal-light); color: var(--teal-dark); font-size: 0.82rem; font-weight: 700; }
        .content-body { padding: 34px 36px 48px; width: 100%; max-width: 1250px; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 2.1rem; margin-bottom: 8px; color: var(--deep); }
        .page-subtitle { margin-bottom: 24px; color: var(--muted); line-height: 1.7; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; margin-bottom: 30px; }
        .stat-card { background: var(--white); border: 1px solid var(--border); border-radius: 20px; padding: 24px; display: flex; justify-content: space-between; gap: 18px; align-items: center; }
        .stat-card h3 { font-size: 0.95rem; color: var(--muted); margin-bottom: 10px; }
        .stat-card h2 { font-size: 2.2rem; color: var(--deep); }
        .stat-icon { font-size: 2.6rem; }
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 26px; }
        .panel { background: var(--white); border: 1px solid var(--border); border-radius: 22px; padding: 28px; }
        .panel .panel-title { display: flex; justify-content: space-between; align-items: center; font-family: 'Playfair Display', serif; font-size: 1.15rem; color: var(--deep); margin-bottom: 22px; }
        .panel a.action-link { color: var(--teal); text-decoration: none; font-size: 0.92rem; font-weight: 700; }
        .list-item { padding: 18px 20px; border-radius: 16px; background: var(--teal-light); margin-bottom: 16px; border: 1px solid rgba(26,122,110,0.12); }
        .list-item h4 { font-size: 1rem; margin-bottom: 6px; }
        .list-item p { font-size: 0.9rem; color: var(--deep); }
        .profile-card { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-top: 24px; }
        .profile-card .card { background: var(--white); border: 1px solid var(--border); border-radius: 20px; padding: 24px; }
        .profile-card .card strong { display: block; font-size: 1.75rem; color: var(--deep); margin-bottom: 10px; }
        .profile-card .card span { color: var(--muted); font-size: 0.95rem; }
        .action-buttons { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 24px; }
        .action-buttons a { display: inline-flex; align-items: center; justify-content: center; min-width: 180px; padding: 14px 18px; border-radius: 14px; text-decoration: none; font-weight: 700; color: var(--white); }
        .btn-primary { background: var(--teal); }
        .btn-secondary { background: var(--deep); }
        @media (max-width: 980px) { .dashboard-grid { grid-template-columns: 1fr; } .stats-grid { grid-template-columns: 1fr; } .profile-card { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">💻</div>
            <h2>CPC Student Portal</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard_student.php" class="menu-link active">📊 Dashboard</a></li>
            <li><a href="events_student.html" class="menu-link">📅 Club Events</a></li>
            <li><a href="announcements_student.html" class="menu-link">📬 Announcements</a></li>
            <li><a href="profile_student.php" class="menu-link">👤 My Profile</a></li>
        </ul>
        <div class="sidebar-footer"><a href="logout.php" class="btn-logout">🚪 Logout</a></div>
    </div>

    <div class="main-wrapper">
        <div class="topbar">
            <div>
                <div class="breadcrumbs">Home / Student Dashboard</div>
                <div class="topbar-user"><span>Welcome back,</span><strong><?php echo htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8'); ?></strong></div>
            </div>
            <div class="topbar-right">
                <span class="role-badge">Student</span>
            </div>
        </div>

        <div class="content-body">
            <h1 class="page-title">Your CPC Dashboard</h1>
            <p class="page-subtitle">This is your student control center. View your profile, manage events, and update your club membership details from one place.</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <h3>Profile Status</h3>
                        <h2>Complete</h2>
                    </div>
                    <span class="stat-icon">✅</span>
                </div>
                <div class="stat-card">
                    <div>
                        <h3>Upcoming Events</h3>
                        <h2>2</h2>
                    </div>
                    <span class="stat-icon">📅</span>
                </div>
                <div class="stat-card">
                    <div>
                        <h3>Help Requests</h3>
                        <h2>1</h2>
                    </div>
                    <span class="stat-icon">🛎️</span>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="panel">
                    <div class="panel-title">
                        <span>Upcoming club activities</span>
                        <a href="events_student.html" class="action-link">See all events</a>
                    </div>
                    <div class="list-item">
                        <h4>Hackathon Prep Workshop</h4>
                        <p>June 12, 2026 | 11:00 AM | Room A-201</p>
                    </div>
                    <div class="list-item">
                        <h4>UI/UX Skill Building Session</h4>
                        <p>June 18, 2026 | 03:00 PM | Online session</p>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title"><span>Quick actions</span></div>
                    <div class="action-buttons">
                        <a href="profile_student.php" class="btn-primary">Edit Profile</a>
                        <a href="change_password.php" class="btn-secondary">Change Password</a>
                        <a href="announcements_student.html" class="btn-primary">View Announcements</a>
                    </div>
                    <div class="profile-card">
                        <div class="card">
                            <strong><?php echo htmlspecialchars($studentId ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span>Student ID</span>
                        </div>
                        <div class="card">
                            <strong><?php echo htmlspecialchars($department ?: 'Not set', ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span>Department</span>
                        </div>
                        <div class="card">
                            <strong><?php echo htmlspecialchars($joinedDate, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span>Member since</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
