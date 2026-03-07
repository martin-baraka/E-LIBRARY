<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['error'=>'Not logged in']); exit(); }
require_once 'db.php';

$userId   = $_SESSION['user_id'];
$bookId   = trim($_POST['book_id'] ?? '');
$source   = trim($_POST['source']  ?? 'gutenberg');
$progress = intval($_POST['progress'] ?? 0);
$progress = max(0, min(100, $progress));

if (empty($bookId)) { echo json_encode(['error'=>'Missing book_id']); exit(); }

$stmt = $conn->prepare("UPDATE ext_reading_history SET progress=?, last_read=NOW()
    WHERE user_id=? AND book_id=? AND book_source=?");
if (!$stmt) { echo json_encode(['error' => $conn->error]); exit(); }
$stmt->bind_param("iiss", $progress, $userId, $bookId, $source);
$stmt->execute();

echo json_encode(['success' => true, 'progress' => $progress]);
?>
