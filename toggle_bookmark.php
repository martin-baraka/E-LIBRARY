<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['error'=>'Not logged in']); exit(); }
require_once 'db.php';

$userId   = $_SESSION['user_id'];
$bookId   = trim($_POST['book_id'] ?? '');
$source   = trim($_POST['source']  ?? 'gutenberg');
$title    = trim($_POST['title']   ?? '');
$author   = trim($_POST['author']  ?? '');
$coverUrl = trim($_POST['cover_url'] ?? '');

if (empty($bookId)) { echo json_encode(['error'=>'Missing book_id']); exit(); }

$conn->query("CREATE TABLE IF NOT EXISTS ext_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id VARCHAR(100) NOT NULL,
    book_source VARCHAR(20) NOT NULL DEFAULT 'gutenberg',
    title VARCHAR(255),
    author VARCHAR(255),
    cover_url VARCHAR(500),
    bookmarked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_book (user_id, book_id, book_source)
)");

$chk = $conn->prepare("SELECT id FROM ext_bookmarks WHERE user_id=? AND book_id=? AND book_source=?");
if (!$chk) { echo json_encode(['error' => $conn->error]); exit(); }
$chk->bind_param("iss", $userId, $bookId, $source);
$chk->execute(); $chk->store_result();

if ($chk->num_rows > 0) {
    $del = $conn->prepare("DELETE FROM ext_bookmarks WHERE user_id=? AND book_id=? AND book_source=?");
    $del->bind_param("iss", $userId, $bookId, $source);
    $del->execute();
    echo json_encode(['bookmarked' => false]);
} else {
    $ins = $conn->prepare("INSERT INTO ext_bookmarks (user_id, book_id, book_source, title, author, cover_url) VALUES (?,?,?,?,?,?)");
    $ins->bind_param("isssss", $userId, $bookId, $source, $title, $author, $coverUrl);
    $ins->execute();
    echo json_encode(['bookmarked' => true]);
}
?>
