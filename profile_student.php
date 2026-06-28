<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: login_student.html');
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare('SELECT full_name, student_id, email, department, session_year, phone, joined_at, status FROM users WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($fullName, $studentId, $email, $department, $sessionYear, $phone, $joinedAt, $status);
$stmt->fetch();
$stmt->close();

$displayName = trim($fullName ?: 'Student');
$shortName   = explode(' ', $displayName)[0] ?: $displayName;
$joinedDate  = $joinedAt ? date('F j, Y', strtotime($joinedAt)) : 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile | CPC Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --teal:#1a7a6e;--teal-dark:#125a51;--teal-light:#e6f5f3;
  --deep:#0e3d38;--gold:#c89b3c;--text:#1e2d2b;--muted:#6b7f7d;
  --white:#fff;--bg:#f0f6f5;--border:#cde5e2;--sw:270px;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;}

.sidebar{width:var(--sw);background:linear-gradient(180deg,var(--deep) 0%,var(--teal-dark) 100%);color:#fff;position:fixed;top:0;bottom:0;left:0;display:flex;flex-direction:column;z-index:100;}
.sb-head{padding:30px 24px 24px;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:14px;}
.sb-logo{width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;}
.sb-head h2{font-family:'Playfair Display',serif;font-size:1rem;line-height:1.3;color:#fff;}
.sb-head small{display:block;font-size:.72rem;color:rgba(255,255,255,.5);margin-top:2px;font-weight:400;}
.sb-menu{list-style:none;padding:20px 14px;flex:1;}
.sb-menu li{margin-bottom:4px;}
.menu-link{display:flex;align-items:center;gap:12px;padding:12px 16px;color:rgba(255,255,255,.7);text-decoration:none;font-size:.9rem;font-weight:600;border-radius:12px;transition:all .2s;}
.menu-link .icon{width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
.menu-link:hover{color:#fff;background:rgba(255,255,255,.1);}
.menu-link.active{color:#fff;background:rgba(255,255,255,.15);border-left:3px solid var(--gold);}
.menu-link.active .icon{background:rgba(200,155,60,.25);}
.sb-foot{padding:18px 14px;border-top:1px solid rgba(255,255,255,.1);}
.sb-foot a{display:flex;align-items:center;gap:12px;color:#ff9a9a;text-decoration:none;font-size:.9rem;font-weight:600;padding:10px 16px;border-radius:10px;transition:background .2s;}
.sb-foot a:hover{background:rgba(255,100,100,.1);}

.main{margin-left:var(--sw);flex:1;display:flex;flex-direction:column;min-height:100vh;}
.topbar{background:var(--white);height:68px;padding:0 36px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:50;}
.topbar-title{font-family:'Playfair Display',serif;font-size:1.15rem;color:var(--deep);}
.user-chip{display:flex;align-items:center;gap:10px;background:var(--teal-light);padding:8px 16px 8px 10px;border-radius:30px;}
.user-avatar{width:34px;height:34px;background:var(--teal);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem;}
.user-chip span{font-size:.88rem;font-weight:600;color:var(--teal-dark);}

.content{padding:36px 40px 60px;max-width:1000px;width:100%;margin:0 auto;}

.profile-card{background:var(--white);border:1px solid var(--border);border-radius:18px;padding:30px 32px;}
.profile-top{display:flex;align-items:center;gap:20px;margin-bottom:26px;padding-bottom:22px;border-bottom:1px solid var(--border);}
.profile-avatar{width:80px;height:80px;background:linear-gradient(135deg,var(--teal),var(--teal-dark));border-radius:20px;display:flex;align-items:center;justify-content:center;color:#fff;font-family:'Playfair Display',serif;font-size:2rem;flex-shrink:0;}
.profile-meta h2{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--deep);margin-bottom:6px;}
.profile-meta span{font-size:.85rem;color:var(--muted);}
.badge-active{display:inline-block;background:#e3f7ec;color:#1f9d55;font-size:.75rem;font-weight:700;padding:3px 10px;border-radius:20px;margin-left:8px;}

.profile-fields{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;}
.pf{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:16px 18px;}
.pf label{font-size:.72rem;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px;}
.pf strong{font-size:.95rem;color:var(--deep);font-weight:600;word-break:break-all;}
</style>
</head>
<body>

<div class="sidebar">
  <div class="sb-head">
    <div class="sb-logo">💻</div>
    <div>
      <h2>CPC Portal</h2>
      <small>Kishoreganj University</small>
    </div>
  </div>
  <ul class="sb-menu">
    <li><a href="dashboard_student.php" class="menu-link"><span class="icon">📊</span> Dashboard</a></li>
    <li><a href="events_student.php" class="menu-link"><span class="icon">📅</span> Club Events</a></li>
    <li><a href="club_notice.php?role=member" class="menu-link"><span class="icon">📢</span> Announcements</a></li>
    <li><a href="profile_student.php" class="menu-link active"><span class="icon">👤</span> My Profile</a></li>
    <li><a href="change_password.php" class="menu-link"><span class="icon">🔐</span> Change Password</a></li>
  </ul>
  <div class="sb-foot">
    <a href="logout.php">🚪 Logout</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <span class="topbar-title">My Profile</span>
    <div class="user-chip">
      <div class="user-avatar"><?= strtoupper(mb_substr($shortName, 0, 1)) ?></div>
      <span><?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>

  <div class="content">
    <div class="profile-card">
      <div class="profile-top">
        <div class="profile-avatar"><?= strtoupper(mb_substr($shortName, 0, 1)) ?></div>
        <div class="profile-meta">
          <h2><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
            <?php if (($status ?: 'active') === 'active'): ?><span class="badge-active">Active</span><?php endif; ?>
          </h2>
          <span>Joined on <?= $joinedDate ?></span>
        </div>
      </div>

      <div class="profile-fields">
        <div class="pf"><label>Student ID</label><strong><?= htmlspecialchars($studentId ?: '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="pf"><label>Email</label><strong><?= htmlspecialchars($email ?: '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="pf"><label>Department</label><strong><?= htmlspecialchars($department ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="pf"><label>Session/Year</label><strong><?= htmlspecialchars($sessionYear ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="pf"><label>Phone</label><strong><?= htmlspecialchars($phone ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="pf"><label>Role</label><strong>CPC Member (Student)</strong></div>
      </div>
    </div>
  </div>
</div>

</body>
</html>