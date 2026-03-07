<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit(); }

// Handle remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    require_once 'db.php';
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows === 1) {
        $row = $r->fetch_assoc();
        $_SESSION['user_id']  = $row['id'];
        $_SESSION['username'] = $row['username'];
        header("Location: dashboard.php"); exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']);

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['last_activity'] = time();

            // Remember me — 30 days
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 30), '/');
                // Add remember_token column if not exists — safe to ignore error
                $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS remember_token VARCHAR(100) DEFAULT NULL");
                $upd = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $upd->bind_param("si", $token, $row['id']);
                $upd->execute();
            }

            $stmt->close(); $conn->close();
            header("Location: dashboard.php"); exit();
        } else {
            header("Location: index.php?login_error=Incorrect+password.+Please+try+again."); exit();
        }
    } else {
        header("Location: index.php?login_error=No+account+found+with+that+email."); exit();
    }
} else {
    header("Location: index.php"); exit();
}
?>
