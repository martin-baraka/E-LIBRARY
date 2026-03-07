<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$source   = $_GET['source'] ?? 'gutenberg';
$id       = $_GET['id'] ?? '';
$username = $_SESSION['username'];

if (empty($id)) { header("Location: books.php"); exit(); }

// Fetch book metadata & content URL server-side
$bookTitle  = 'Loading…';
$bookAuthor = '';
$readUrl    = '';
$coverUrl   = '';
$subjects   = [];
$error      = '';

if ($source === 'gutenberg') {
    $meta = @file_get_contents("https://gutendex.com/books/{$id}/");
    if ($meta) {
        $data = json_decode($meta, true);
        if ($data) {
            $bookTitle  = $data['title'] ?? 'Unknown Title';
            $authors    = array_map(fn($a) => strrev(implode(' ', array_reverse(explode(', ', $a['name'])))), $data['authors'] ?? []);
            $bookAuthor = implode(', ', $authors) ?: 'Unknown Author';
            $subjects   = array_slice($data['subjects'] ?? [], 0, 5);
            $formats    = $data['formats'] ?? [];
            $coverUrl   = $formats['image/jpeg'] ?? "https://www.gutenberg.org/cache/epub/{$id}/pg{$id}.cover.medium.jpg";
            // Prefer HTML, then plain text
            $readUrl    = $formats['text/html'] ?? $formats['text/html; charset=utf-8'] ?? $formats['text/html; charset=iso-8859-1'] ?? '';
            if (!$readUrl) {
                // Try direct Gutenberg HTML URL
                $readUrl = "https://www.gutenberg.org/files/{$id}/{$id}-h/{$id}-h.htm";
            }
        }
    } else {
        $error = 'Could not load book metadata.';
    }
} else {
    // Open Library — use works endpoint, then link to Internet Archive reader
    $bookTitle  = urldecode($_GET['title'] ?? 'Book');
    $meta       = @file_get_contents("https://openlibrary.org/works/{$id}.json");
    if ($meta) {
        $data        = json_decode($meta, true);
        $bookTitle   = $data['title'] ?? $bookTitle;
        $desc        = $data['description'] ?? '';
        if (is_array($desc)) $desc = $desc['value'] ?? '';
        $subjects    = array_slice($data['subjects'] ?? [], 0, 5);
        // Get author
        $authorKeys  = array_slice($data['authors'] ?? [], 0, 2);
        $authorNames = [];
        foreach ($authorKeys as $ak) {
            $aKey = $ak['author']['key'] ?? '';
            if ($aKey) {
                $aMeta = @file_get_contents("https://openlibrary.org{$aKey}.json");
                if ($aMeta) { $aData = json_decode($aMeta,true); $authorNames[] = $aData['name'] ?? ''; }
            }
        }
        $bookAuthor = implode(', ', array_filter($authorNames)) ?: 'Unknown Author';
        $coverUrl   = "https://covers.openlibrary.org/b/olid/{$id}-L.jpg";
    }
    // Open Library embeds via Internet Archive
    $readUrl    = "https://openlibrary.org/works/{$id}";
}

// Save to reading history
require_once 'db.php';
$userId = $_SESSION['user_id'];
// Create table if not exists (safe migration)
// Use separate tables for external (API) books to avoid conflict with local int book_id tables
$conn->query("CREATE TABLE IF NOT EXISTS ext_reading_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id VARCHAR(100) NOT NULL,
    book_source VARCHAR(20) NOT NULL DEFAULT 'gutenberg',
    title VARCHAR(255),
    author VARCHAR(255),
    cover_url VARCHAR(500),
    progress INT DEFAULT 0,
    last_read TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY user_book (user_id, book_id, book_source)
)");
$stmt = $conn->prepare("INSERT INTO ext_reading_history (user_id, book_id, book_source, title, author, cover_url, progress)
    VALUES (?, ?, ?, ?, ?, ?, 5)
    ON DUPLICATE KEY UPDATE last_read=NOW(), progress=GREATEST(progress,5)");
if ($stmt) {
    $stmt->bind_param("isssss", $userId, $id, $source, $bookTitle, $bookAuthor, $coverUrl);
    $stmt->execute();
}

// Check bookmark status
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
$bmStmt = $conn->prepare("SELECT id FROM ext_bookmarks WHERE user_id=? AND book_id=? AND book_source=?");
$isBookmarked = false;
if ($bmStmt) {
    $bmStmt->bind_param("iss", $userId, $id, $source);
    $bmStmt->execute();
    $bmStmt->store_result();
    $isBookmarked = $bmStmt->num_rows > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= htmlspecialchars($bookTitle) ?> – E-Library Reader</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Lora:ital,wght@0,400;0,500;1,400&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{
      --bg:#0a1410;--surface:#121e17;--card:#162119;--border:#243329;
      --green:#3a8c4a;--green-dim:#2e7a3c;--text:#d4ecd7;--muted:#5a7a5e;--accent:#6fcf80;
    }
    body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;flex-direction:column;}

    /* Top Reader Bar */
    .reader-nav{background:var(--surface);border-bottom:1px solid var(--border);padding:12px 24px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:100;flex-wrap:wrap;}
    .reader-nav .back-btn{display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;background:var(--card);border:1px solid var(--border);color:var(--muted);text-decoration:none;font-size:0.85rem;font-weight:500;transition:all 0.2s;white-space:nowrap;}
    .reader-nav .back-btn:hover{border-color:var(--green);color:var(--accent);}
    .reader-nav .breadcrumb{color:var(--muted);font-size:0.85rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .reader-nav .breadcrumb span{color:var(--text);}
    .reader-nav .actions{display:flex;gap:8px;margin-left:auto;}
    .action-btn{display:flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--muted);font-size:0.83rem;font-family:'DM Sans',sans-serif;font-weight:500;cursor:pointer;transition:all 0.2s;white-space:nowrap;}
    .action-btn:hover{border-color:var(--green);color:var(--accent);}
    .action-btn.bookmarked{background:rgba(58,140,74,0.15);border-color:var(--green);color:var(--accent);}

    /* Layout */
    .reader-layout{display:flex;flex:1;min-height:0;}

    /* Book Info Sidebar */
    .book-sidebar{width:280px;min-width:280px;background:var(--surface);border-right:1px solid var(--border);padding:28px 20px;overflow-y:auto;height:calc(100vh - 57px);position:sticky;top:57px;}
    .book-cover-wrap{width:100%;aspect-ratio:2/3;border-radius:12px;overflow:hidden;margin-bottom:20px;background:linear-gradient(135deg,#162119,#0a1a0f);box-shadow:0 8px 32px rgba(0,0,0,0.4);}
    .book-cover-wrap img{width:100%;height:100%;object-fit:cover;}
    .book-cover-wrap .no-cover{width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;font-size:3rem;}
    .book-meta h2{font-family:'Playfair Display',serif;font-size:1.15rem;color:var(--text);line-height:1.35;margin-bottom:6px;}
    .book-meta .author{font-size:0.85rem;color:var(--muted);margin-bottom:16px;}
    .book-meta .source-tag{display:inline-flex;align-items:center;gap:5px;font-size:0.72rem;font-weight:600;padding:3px 10px;border-radius:8px;margin-bottom:14px;text-transform:uppercase;letter-spacing:0.5px;}
    .tag-gutenberg{background:rgba(111,207,128,0.12);color:var(--accent);border:1px solid rgba(111,207,128,0.25);}
    .tag-openlibrary{background:rgba(52,152,219,0.12);color:#5dade2;border:1px solid rgba(52,152,219,0.25);}
    .book-meta .subjects{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:18px;}
    .book-meta .subject-tag{font-size:0.68rem;padding:3px 8px;border-radius:6px;background:rgba(58,140,74,0.1);color:#5a8c62;border:1px solid rgba(58,140,74,0.15);}
    .progress-section{margin-top:16px;padding-top:16px;border-top:1px solid var(--border);}
    .progress-section label{font-size:0.78rem;color:var(--muted);display:block;margin-bottom:8px;}
    .progress-bar-wrap{background:rgba(58,140,74,0.1);border-radius:8px;height:8px;overflow:hidden;margin-bottom:8px;}
    .progress-bar-fill{height:100%;background:linear-gradient(90deg,var(--green),var(--accent));border-radius:8px;transition:width 0.5s ease;}
    .progress-input{width:100%;padding:8px 12px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:0.85rem;font-family:'DM Sans',sans-serif;outline:none;}
    .progress-input:focus{border-color:var(--green);}
    .save-progress-btn{margin-top:8px;width:100%;padding:9px;border-radius:8px;border:none;background:var(--green);color:#fff;font-size:0.85rem;font-family:'DM Sans',sans-serif;font-weight:600;cursor:pointer;transition:background 0.2s;}
    .save-progress-btn:hover{background:var(--green-dim);}
    .progress-saved{font-size:0.78rem;color:var(--accent);margin-top:6px;display:none;}

    /* Reader Content */
    .reader-content{flex:1;min-width:0;display:flex;flex-direction:column;}

    /* Reader Controls */
    .reader-controls{background:var(--card);border-bottom:1px solid var(--border);padding:10px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
    .font-control{display:flex;align-items:center;gap:8px;font-size:0.82rem;color:var(--muted);}
    .font-btn{padding:4px 12px;border-radius:6px;border:1px solid var(--border);background:transparent;color:var(--text);cursor:pointer;font-size:0.85rem;transition:all 0.2s;}
    .font-btn:hover{border-color:var(--green);color:var(--accent);}
    .theme-toggle{display:flex;gap:6px;margin-left:auto;}
    .theme-btn{width:24px;height:24px;border-radius:50%;border:2px solid transparent;cursor:pointer;transition:border-color 0.2s;}
    .theme-btn:hover,.theme-btn.active{border-color:var(--accent);}

    /* The iframe embed */
    .reader-frame-wrap{flex:1;position:relative;min-height:600px;}
    #readerFrame{width:100%;height:100%;min-height:calc(100vh - 170px);border:none;background:#fff;}

    /* Open Library fallback panel */
    .ol-panel{padding:40px 48px;max-width:760px;margin:0 auto;}
    .ol-panel h2{font-family:'Playfair Display',serif;font-size:1.6rem;color:var(--text);margin-bottom:12px;}
    .ol-panel .desc{font-family:'Lora',serif;font-size:1rem;line-height:1.8;color:#9ab89e;margin-bottom:28px;}
    .ol-panel .open-link{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:10px;background:var(--green);color:#fff;text-decoration:none;font-weight:600;font-size:0.9rem;transition:background 0.2s;margin-bottom:12px;}
    .ol-panel .open-link:hover{background:var(--green-dim);}
    .ol-panel .note{font-size:0.8rem;color:var(--muted);line-height:1.5;}

    @media(max-width:900px){.book-sidebar{display:none;}}
    @media(max-width:600px){.reader-nav{padding:10px 14px;}.reader-controls{gap:8px;}.ol-panel{padding:24px 20px;}}
  </style>
</head>
<body>

<!-- Top nav -->
<div class="reader-nav">
  <a href="books.php" class="back-btn">← Back to Books</a>
  <div class="breadcrumb">Reading: <span><?= htmlspecialchars($bookTitle) ?></span></div>
  <div class="actions">
    <button class="action-btn <?= $isBookmarked ? 'bookmarked' : '' ?>" id="bmBtn" onclick="toggleBookmark()">
      <?= $isBookmarked ? '🔖 Bookmarked' : '+ Bookmark' ?>
    </button>
    <?php if ($source === 'gutenberg'): ?>
    <a class="action-btn" href="https://www.gutenberg.org/ebooks/<?= urlencode($id) ?>" target="_blank" rel="noopener">
      ↗ Open Original
    </a>
    <?php else: ?>
    <a class="action-btn" href="<?= htmlspecialchars($readUrl) ?>" target="_blank" rel="noopener">
      ↗ Open in Open Library
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="reader-layout">

  <!-- Sidebar -->
  <aside class="book-sidebar">
    <div class="book-cover-wrap">
      <?php if ($coverUrl): ?>
        <img src="<?= htmlspecialchars($coverUrl) ?>" alt="<?= htmlspecialchars($bookTitle) ?>" onerror="this.parentNode.innerHTML='<div class=no-cover>📖</div>'">
      <?php else: ?>
        <div class="no-cover">📖</div>
      <?php endif; ?>
    </div>
    <div class="book-meta">
      <span class="source-tag <?= $source === 'gutenberg' ? 'tag-gutenberg' : 'tag-openlibrary' ?>">
        <?= $source === 'gutenberg' ? '📗 Project Gutenberg' : '📘 Open Library' ?>
      </span>
      <h2><?= htmlspecialchars($bookTitle) ?></h2>
      <p class="author">by <?= htmlspecialchars($bookAuthor) ?></p>
      <?php if ($subjects): ?>
      <div class="subjects">
        <?php foreach(array_slice($subjects, 0, 4) as $s):
          $label = explode(' -- ', $s)[0]; $label = explode('(', $label)[0]; $label = trim($label);
          if (strlen($label) > 30) $label = substr($label,0,28).'…';
        ?>
          <span class="subject-tag"><?= htmlspecialchars($label) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="progress-section">
      <label>Reading Progress</label>
      <div class="progress-bar-wrap"><div class="progress-bar-fill" id="progressFill" style="width:5%"></div></div>
      <input type="range" min="0" max="100" value="5" class="progress-input" id="progressSlider" oninput="updateProgress(this.value)"/>
      <button class="save-progress-btn" onclick="saveProgress()">Save Progress</button>
      <div class="progress-saved" id="progressSaved">✅ Progress saved!</div>
    </div>
  </aside>

  <!-- Reader Content -->
  <div class="reader-content">
    <?php if ($source === 'gutenberg' && $readUrl): ?>
    <!-- Reader controls -->
    <div class="reader-controls">
      <div class="font-control">
        Font: <button class="font-btn" onclick="adjustFont(-1)">A−</button>
              <button class="font-btn" onclick="adjustFont(1)">A+</button>
      </div>
      <span style="font-size:0.8rem;color:var(--muted);">📖 Embedded Gutenberg reader</span>
      <div class="theme-toggle">
        <div class="theme-btn active" style="background:#fff;" onclick="setFrameBg('#fff','#111',this)" title="Light"></div>
        <div class="theme-btn" style="background:#f5f0e8;" onclick="setFrameBg('#f5f0e8','#2c2518',this)" title="Sepia"></div>
        <div class="theme-btn" style="background:#1a1a2e;" onclick="setFrameBg('#1a1a2e','#d4d4e8',this)" title="Dark"></div>
      </div>
    </div>
    <div class="reader-frame-wrap">
      <iframe id="readerFrame" src="<?= htmlspecialchars($readUrl) ?>" title="<?= htmlspecialchars($bookTitle) ?>" loading="lazy" sandbox="allow-same-origin allow-scripts allow-popups allow-forms"></iframe>
    </div>

    <?php elseif ($source === 'openlibrary'): ?>
    <!-- Open Library — use their embedded reader -->
    <div class="reader-frame-wrap">
      <iframe id="readerFrame"
        src="https://openlibrary.org/works/<?= urlencode($id) ?>/borrow?mode=preview"
        title="<?= htmlspecialchars($bookTitle) ?>"
        loading="lazy"
        sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-top-navigation">
      </iframe>
    </div>
    <!-- Fallback shown via JS if iframe fails -->
    <div id="olFallback" style="display:none;" class="ol-panel">
      <h2><?= htmlspecialchars($bookTitle) ?></h2>
      <p class="desc">This book is hosted on Open Library. Click below to read it there — some editions are available as full-text previews or borrowable e-books.</p>
      <a href="https://openlibrary.org/works/<?= urlencode($id) ?>" target="_blank" rel="noopener" class="open-link">
        📖 Read on Open Library ↗
      </a>
      <p class="note">Open Library is a project by the Internet Archive offering millions of free books.</p>
    </div>

    <?php else: ?>
    <div class="ol-panel">
      <h2><?= htmlspecialchars($bookTitle) ?></h2>
      <p class="desc">The full text of this book couldn't be loaded inline. Click below to read it directly on Project Gutenberg.</p>
      <a href="https://www.gutenberg.org/ebooks/<?= urlencode($id) ?>" target="_blank" rel="noopener" class="open-link">
        📖 Read on Project Gutenberg ↗
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
let bookmarked = <?= $isBookmarked ? 'true' : 'false' ?>;
let currentProgress = 5;

function updateProgress(val) {
  currentProgress = parseInt(val);
  document.getElementById('progressFill').style.width = val + '%';
}

async function saveProgress() {
  const res = await fetch('update_progress.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `book_id=${encodeURIComponent('<?= addslashes($id) ?>')}&source=${encodeURIComponent('<?= $source ?>')}&progress=${currentProgress}`
  });
  const saved = document.getElementById('progressSaved');
  saved.style.display = 'block';
  setTimeout(() => saved.style.display = 'none', 2000);
}

async function toggleBookmark() {
  const res = await fetch('toggle_bookmark.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `book_id=${encodeURIComponent('<?= addslashes($id) ?>')}&source=<?= $source ?>&title=${encodeURIComponent('<?= addslashes(htmlspecialchars($bookTitle)) ?>')}&author=${encodeURIComponent('<?= addslashes(htmlspecialchars($bookAuthor)) ?>')}&cover_url=${encodeURIComponent('<?= addslashes($coverUrl) ?>')}`
  });
  const data = await res.json();
  bookmarked = data.bookmarked;
  const btn = document.getElementById('bmBtn');
  btn.textContent = bookmarked ? '🔖 Bookmarked' : '+ Bookmark';
  btn.className = 'action-btn' + (bookmarked ? ' bookmarked' : '');
}

let fontSize = 100;
function adjustFont(d) {
  fontSize = Math.max(80, Math.min(150, fontSize + d * 5));
  try {
    const frame = document.getElementById('readerFrame');
    if (frame && frame.contentDocument && frame.contentDocument.body) {
      frame.contentDocument.body.style.fontSize = fontSize + '%';
    }
  } catch(e) {}
}

function setFrameBg(bg, color, btn) {
  document.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  try {
    const frame = document.getElementById('readerFrame');
    if (frame && frame.contentDocument && frame.contentDocument.body) {
      frame.contentDocument.body.style.background = bg;
      frame.contentDocument.body.style.color = color;
    }
  } catch(e) {}
}

// OL iframe fallback
const olFallback = document.getElementById('olFallback');
if (olFallback) {
  const frame = document.getElementById('readerFrame');
  if (frame) {
    frame.addEventListener('error', () => {
      frame.style.display = 'none';
      olFallback.style.display = 'block';
    });
    setTimeout(() => {
      try {
        if (!frame.contentDocument || !frame.contentDocument.body || !frame.contentDocument.body.innerHTML) {
          frame.style.display = 'none';
          olFallback.style.display = 'block';
        }
      } catch(e) {
        // cross-origin — iframe loaded OK
      }
    }, 5000);
  }
}
</script>
</body>
</html>
