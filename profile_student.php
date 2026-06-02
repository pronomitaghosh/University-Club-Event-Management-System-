<?php
require_once 'config.php';
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: login_student.html');
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($fullName === '' || $email === '' || $department === '') {
        $message = 'Full name, email, and department are required.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
        $stmt->bind_param('si', $email, $userId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $message = 'That email is already used by another account.';
            $messageType = 'error';
        } else {
            $stmt->close();
            $stmt = $conn->prepare('UPDATE users SET full_name = ?, email = ?, department = ?, phone = ? WHERE user_id = ?');
            $stmt->bind_param('ssssi', $fullName, $email, $department, $phone, $userId);
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $fullName;
                $message = 'Profile updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Unable to save profile changes. Please try again.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

$stmt = $conn->prepare('SELECT full_name, student_id, email, department, phone, role, created_at FROM users WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($fullName, $studentId, $email, $department, $phone, $role, $createdAt);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | CPC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght=700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --teal: #1a7a6e; --teal-dark: #125a51; --deep: #0e3d38; --gold: #c89b3c; --text: #1e2d2b; --muted: #6b7f7d; --white: #ffffff; --bg: #f4f9f8; --border: #cde5e2; --sidebar-width: 260px; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); min-height: 100vh; display: flex; }
        .sidebar { width: var(--sidebar-width); background: linear-gradient(180deg, var(--deep) 0%, var(--teal-dark) 100%); color: var(--white); position: fixed; top: 0; bottom: 0; left: 0; display: flex; flex-direction: column; z-index: 100; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; font-size: 1.1rem; }
        .sidebar-menu { list-style: none; padding: 24px 16px; flex: 1; }
        .menu-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: rgba(255,255,255,0.75); text-decoration: none; font-size: 0.95rem; font-weight: 500; border-radius: 10px; }
        .menu-link:hover, .menu-link.active { color: var(--white); background: rgba(255,255,255,0.15); }
        .menu-link.active { border-left: 4px solid var(--gold); }
        .sidebar-footer { padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.1); }
        .btn-logout { display: inline-flex; align-items: center; gap: 10px; color: #ff8a8a; text-decoration: none; font-size: 0.9rem; font-weight: 600; }
        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; }
        .topbar { background: var(--white); height: 70px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); }
        .topbar h3 { font-size: 1rem; color: var(--muted); }
        .topbar .user-name { font-weight: 700; }
        .content-body { padding: 40px; max-width: 900px; width: 100%; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 2rem; color: var(--deep); margin-bottom: 20px; }
        .profile-card { background: var(--white); border-radius: 20px; border: 1px solid var(--border); padding: 35px; }
        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; }
        .alert.success { background: #e6f5ed; color: #0f6d3a; }
        .alert.error { background: #fdecea; color: #b02a37; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        @media(max-width: 700px) { .form-grid { grid-template-columns: 1fr; } }
        .form-group { display: flex; flex-direction: column; }
        label { font-size: 0.95rem; font-weight: 600; color: var(--deep); margin-bottom: 10px; }
        input, select { padding: 14px 16px; border: 1px solid var(--border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; color: var(--text); background: #fff; transition: border-color 0.2s ease; }
        input:focus, select:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(26,122,110,0.1); }
        input[readonly] { background: #f1f5f4; cursor: not-allowed; }
        .btn-save { padding: 14px 28px; background: var(--teal); color: var(--white); border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background 0.2s ease; }
        .btn-save:hover { background: var(--teal-dark); }
        .profile-meta { display: grid; grid-template-columns: repeat(3, minmax(160px, 1fr)); gap: 16px; margin-top: 30px; }
        .meta-card { padding: 18px 20px; background: #f9fcfb; border-radius: 14px; border: 1px solid var(--border); }
        .meta-card strong { display: block; font-size: 1.5rem; margin-bottom: 8px; color: var(--deep); }
        .meta-card span { color: var(--muted); font-size: 0.95rem; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-top: 20px; }
        .actions a { display: inline-block; padding: 12px 18px; background: #1a7a6e; color: white; text-decoration: none; border-radius: 10px; font-weight: 600; }
        .actions a.password { background: #125a51; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><div class="logo">💻</div><h2>CPC Student</h2></div>
        <ul class="sidebar-menu">
            <li><a href="dashboard_student.php" class="menu-link">📊 Dashboard</a></li>
            <li><a href="events_student.html" class="menu-link">📅 Club Events</a></li>
            <li><a href="announcements_student.html" class="menu-link">📬 Announcements</a></li>
            <li><a href="profile_student.php" class="menu-link active">👤 My Profile</a></li>
        </ul>
        <div class="sidebar-footer"><a href="logout.php" class="btn-logout">🚪 Logout</a></div>
    </div>

    <div class="main-wrapper">
        <div class="topbar"><div><h3>Welcome back,</h3><div class="user-name"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></div></div><span class="role-badge"><?php echo htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div class="content-body">
            <h1 class="page-title">My Profile</h1>
            <div class="profile-card">
                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo $messageType === 'error' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="POST" action="profile_student.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="student_id">Student ID</label>
                            <input type="text" id="student_id" value="<?php echo htmlspecialchars($studentId ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" name="department" required>
                                <option value="CSE" <?php echo $department === 'CSE' ? 'selected' : ''; ?>>Computer Science & Engineering (CSE)</option>
                                <option value="EEE" <?php echo $department === 'EEE' ? 'selected' : ''; ?>>Electrical & Electronic Engineering (EEE)</option>
                                <option value="BBA" <?php echo $department === 'BBA' ? 'selected' : ''; ?>>Bachelor of Business Administration (BBA)</option>
                                <option value="English" <?php echo $department === 'English' ? 'selected' : ''; ?>>Department of English</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional">
                        </div>
                    </div>
                    <button type="submit" class="btn-save">Save Profile Changes</button>
                </form>

                <div class="profile-meta">
                    <div class="meta-card"><strong><?php echo htmlspecialchars($studentId ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong><span>Student ID</span></div>
                    <div class="meta-card"><strong><?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?></strong><span>Account Role</span></div>
                    <div class="meta-card"><strong><?php echo htmlspecialchars(date('F j, Y', strtotime($createdAt)), ENT_QUOTES, 'UTF-8'); ?></strong><span>Member since</span></div>
                </div>

                <div class="actions">
                    <a href="dashboard_student.php">Back to Dashboard</a>
                    <a href="change_password.php" class="password">Change Password</a>
                    <a href="logout.php">Sign Out</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
