<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';

    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    // Validation
    if (strlen($username) < 3)
        { header("Location: index.php?register_error=Username+must+be+at+least+3+characters"); exit(); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        { header("Location: index.php?register_error=Please+enter+a+valid+email+address"); exit(); }
    if (strlen($password) < 6)
        { header("Location: index.php?register_error=Password+must+be+at+least+6+characters"); exit(); }
    if ($password !== $confirm)
        { header("Location: index.php?register_error=Passwords+do+not+match"); exit(); }

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        header("Location: index.php?register_error=An+account+with+that+email+already+exists"); exit();
    }
    $check->close();

    // Check if username exists
    $check2 = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check2->bind_param("s", $username);
    $check2->execute();
    $check2->store_result();
    if ($check2->num_rows > 0) {
        header("Location: index.php?register_error=That+username+is+already+taken"); exit();
    }
    $check2->close();

    // Insert user
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        $stmt->close(); $conn->close();

        // Auto-login after registration
        $_SESSION['user_id']  = $new_id;
        $_SESSION['username'] = $username;
        $_SESSION['last_activity'] = time();
        header("Location: dashboard.php"); exit();
    } else {
        header("Location: index.php?register_error=Registration+failed.+Please+try+again."); exit();
    }
} else {
    header("Location: index.php"); exit();
}
?>
