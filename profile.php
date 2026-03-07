<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
require_once 'db.php';

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$msg = ''; $msg_type = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $new_username = trim($_POST['username']);
        $new_email    = trim($_POST['email']);
        $bio          = trim($_POST['bio']);

        if (strlen($new_username) < 3) { $msg = 'Username must be at least 3 characters.'; $msg_type='error'; }
        elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) { $msg = 'Please enter a valid email.'; $msg_type='error'; }
        else {
            // Check email not taken by another user
            $chk = $conn->prepare("SELECT id FROM users WHERE email=? AND id!=?");
            $chk->bind_param("si", $new_email, $user_id); $chk->execute(); $chk->store_result();
            if ($chk->num_rows > 0) { $msg = 'That email is already in use.'; $msg_type='error'; }
            else {
                $upd = $conn->prepare("UPDATE users SET username=?, email=?, bio=? WHERE id=?");
                $upd->bind_param("sssi", $new_username, $new_email, $bio, $user_id);
                if ($upd->execute()) {
                    $_SESSION['username'] = $new_username;
                    $username = $new_username;
                    $msg = 'Profile updated successfully!'; $msg_type='success';
                }
            }
        }
    }

    if ($action === 'change_password') {
        $current  = $_POST['current_password'];
        $new_pw   = $_POST['new_password'];
        $confirm  = $_POST['confirm_password'];

        $r = $conn->prepare("SELECT password FROM users WHERE id=?");
        $r->bind_param("i", $user_id); $r->execute();
        $row = $r->get_result()->fetch_assoc();

        if (!password_verify($current, $row['password'])) { $msg='Current password is incorrect.'; $msg_type='error'; }
        elseif (strlen($new_pw) < 6) { $msg='New password must be at least 6 characters.'; $msg_type='error'; }
        elseif ($new_pw !== $confirm) { $msg='New passwords do not match.'; $msg_type='error'; }
        else {
            $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $upd->bind_param("si", $hashed, $user_id);
            if ($upd->execute()) { $msg='Password changed successfully!'; $msg_type='success'; }
        }
    }

    if ($action === 'delete_account') {
        $pw = $_POST['delete_password'];
        $r  = $conn->prepare("SELECT password FROM users WHERE id=?");
        $r->bind_param("i",$user_id); $r->execute();
        $row = $r->get_result()->fetch_assoc();
        if (password_verify($pw, $row['password'])) {
            $del = $conn->prepare("DELETE FROM users WHERE id=?");
            $del->bind_param("i",$user_id); $del->execute();
            session_destroy();
            header("Location: index.php"); exit();
        } else { $msg='Incorrect password. Account not deleted.'; $msg_type='error'; }
    }
}

// Fetch user data
$r = $conn->prepare("SELECT username,email,bio,created_at FROM users WHERE id=?");
$r->bind_param("i",$user_id); $r->execute();
$user = $r->get_result()->fetch_assoc();

// User stats
$bm_count  = $conn->prepare("SELECT COUNT(*) as c FROM bookmarks WHERE user_id=?"); $bm_count->bind_param("i",$user_id); $bm_count->execute();
$bm_count  = $bm_count->get_result()->fetch_assoc()['c'];
$rd_count  = $conn->prepare("SELECT COUNT(*) as c FROM reading_history WHERE user_id=? AND progress=100"); $rd_count->bind_param("i",$user_id); $rd_count->execute();
$rd_count  = $rd_count->get_result()->fetch_assoc()['c'];
$ip_count  = $conn->prepare("SELECT COUNT(*) as c FROM reading_history WHERE user_id=? AND progress>0 AND progress<100"); $ip_count->bind_param("i",$user_id); $ip_count->execute();
$ip_count  = $ip_count->get_result()->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Profile – E-Library</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--bg:#0a1410;--surface:#121e17;--card:#162119;--border:#243329;--green:#3a8c4a;--green-dim:#2e7a3c;--text:#d4ecd7;--muted:#5a7a5e;--accent:#6fcf80;}
    body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;}
    .sidebar{position:fixed;top:0;left:0;width:240px;height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;padding:32px 20px;gap:6px;z-index:100;}
    .sidebar .logo{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--accent);margin-bottom:28px;padding-left:8px;}
    .sidebar a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;color:var(--muted);text-decoration:none;font-size:0.93rem;font-weight:500;transition:background 0.2s,color 0.2s;}
    .sidebar a:hover,.sidebar a.active{background:rgba(58,140,74,0.15);color:var(--accent);}
    .sidebar a .icon{font-size:1.1rem;width:20px;text-align:center;}
    .sidebar .logout{margin-top:auto;color:#c0392b;}
    .sidebar .logout:hover{background:rgba(192,57,43,0.12);color:#e74c3c;}
    .main{margin-left:240px;padding:36px 40px;max-width:860px;}
    .page-title{font-family:'Playfair Display',serif;font-size:1.8rem;color:var(--text);margin-bottom:30px;}
    .profile-header{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px;display:flex;align-items:center;gap:24px;margin-bottom:28px;}
    .big-avatar{width:72px;height:72px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:2rem;color:#fff;flex-shrink:0;}
    .profile-header .meta h2{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--text);}
    .profile-header .meta p{color:var(--muted);font-size:0.88rem;margin-top:2px;}
    .profile-stats{display:flex;gap:24px;margin-top:12px;flex-wrap:wrap;}
    .profile-stats .ps{text-align:center;}
    .profile-stats .ps .n{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--accent);}
    .profile-stats .ps .l{font-size:0.75rem;color:var(--muted);}
    .msg{padding:12px 16px;border-radius:9px;font-size:0.88rem;margin-bottom:22px;}
    .msg.success{background:rgba(46,125,50,0.15);border:1px solid #2e7d32;color:#66bb6a;}
    .msg.error{background:rgba(192,57,43,0.15);border:1px solid #c0392b;color:#e74c3c;}
    .section{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px;margin-bottom:22px;}
    .section h3{font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--text);margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    .form-group{display:flex;flex-direction:column;gap:6px;}
    .form-group.full{grid-column:1/-1;}
    .form-group label{font-size:0.82rem;color:var(--muted);font-weight:500;}
    .form-group input,.form-group textarea{padding:11px 14px;border-radius:9px;border:1px solid var(--border);background:#0f1b14;color:var(--text);font-size:0.9rem;font-family:'DM Sans',sans-serif;outline:none;transition:border-color 0.2s;resize:vertical;}
    .form-group input:focus,.form-group textarea:focus{border-color:var(--green);}
    .form-group input::placeholder,.form-group textarea::placeholder{color:#3d5c42;}
    .btn-save{background:var(--green);color:#fff;border:none;border-radius:9px;padding:11px 28px;font-size:0.92rem;font-family:'DM Sans',sans-serif;font-weight:600;cursor:pointer;transition:background 0.2s;margin-top:6px;}
    .btn-save:hover{background:var(--green-dim);}
    .btn-danger{background:rgba(192,57,43,0.15);color:#e74c3c;border:1px solid #c0392b;border-radius:9px;padding:11px 28px;font-size:0.92rem;font-family:'DM Sans',sans-serif;font-weight:600;cursor:pointer;transition:all 0.2s;margin-top:6px;}
    .btn-danger:hover{background:rgba(192,57,43,0.3);}
    @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;padding:20px 16px;}.form-grid{grid-template-columns:1fr;}}
  </style>
</head>
<body>
<aside class="sidebar">
  <div class="logo">📚 E-Library</div>
  <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
  <a href="dashboard.php"><span class="icon">📖</span> All Books</a>
  <a href="dashboard.php"><span class="icon">🔖</span> My Bookmarks</a>
  <a href="profile.php" class="active"><span class="icon">⚙️</span> Profile</a>
  <a href="logout.php" class="logout"><span class="icon">🚪</span> Logout</a>
</aside>

<div class="main">
  <h1 class="page-title">My Profile</h1>

  <?php if ($msg): ?>
    <div class="msg <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Profile Header -->
  <div class="profile-header">
    <div class="big-avatar"><?= strtoupper(substr($user['username'],0,1)) ?></div>
    <div class="meta">
      <h2><?= htmlspecialchars($user['username']) ?></h2>
      <p><?= htmlspecialchars($user['email']) ?></p>
      <p>Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
      <div class="profile-stats">
        <div class="ps"><div class="n"><?= $bm_count ?></div><div class="l">Bookmarked</div></div>
        <div class="ps"><div class="n"><?= $rd_count ?></div><div class="l">Completed</div></div>
        <div class="ps"><div class="n"><?= $ip_count ?></div><div class="l">In Progress</div></div>
      </div>
    </div>
  </div>

  <!-- Update Profile -->
  <div class="section">
    <h3>Edit Profile</h3>
    <form method="POST">
      <input type="hidden" name="action" value="update_profile"/>
      <div class="form-grid">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required/>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required/>
        </div>
        <div class="form-group full">
          <label>Bio (optional)</label>
          <textarea name="bio" rows="3" placeholder="Tell us a bit about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
        </div>
      </div>
      <button type="submit" class="btn-save">Save Changes</button>
    </form>
  </div>

  <!-- Change Password -->
  <div class="section">
    <h3>Change Password</h3>
    <form method="POST">
      <input type="hidden" name="action" value="change_password"/>
      <div class="form-grid">
        <div class="form-group full">
          <label>Current Password</label>
          <input type="password" name="current_password" placeholder="Enter your current password" required/>
        </div>
        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="new_password" placeholder="Min 6 characters" minlength="6" required/>
        </div>
        <div class="form-group">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" placeholder="Repeat new password" required/>
        </div>
      </div>
      <button type="submit" class="btn-save">Update Password</button>
    </form>
  </div>

  <!-- Delete Account -->
  <div class="section">
    <h3>Delete Account</h3>
    <p style="color:var(--muted);font-size:0.88rem;margin-bottom:16px;">This will permanently delete your account, bookmarks, and reading history. This cannot be undone.</p>
    <form method="POST" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.');">
      <input type="hidden" name="action" value="delete_account"/>
      <div class="form-group" style="max-width:320px;margin-bottom:14px;">
        <label>Enter your password to confirm</label>
        <input type="password" name="delete_password" placeholder="Your password" required/>
      </div>
      <button type="submit" class="btn-danger">Delete My Account</button>
    </form>
  </div>
</div>
</body>
</html>
