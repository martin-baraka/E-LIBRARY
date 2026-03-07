<?php
session_start();

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
    session_destroy();
    header("Location: index.php?login_error=Session+expired.+Please+sign+in+again.");
    exit();
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

require_once 'db.php';
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Ensure ext tables exist
$conn->query("CREATE TABLE IF NOT EXISTS ext_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id VARCHAR(100) NOT NULL,
    book_source VARCHAR(20) NOT NULL DEFAULT 'gutenberg',
    title VARCHAR(255), author VARCHAR(255), cover_url VARCHAR(500),
    bookmarked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_book (user_id, book_id, book_source)
)");
$conn->query("CREATE TABLE IF NOT EXISTS ext_reading_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id VARCHAR(100) NOT NULL,
    book_source VARCHAR(20) NOT NULL DEFAULT 'gutenberg',
    title VARCHAR(255), author VARCHAR(255), cover_url VARCHAR(500),
    progress INT DEFAULT 0,
    last_read TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY user_book (user_id, book_id, book_source)
)");

// Stats
$r = $conn->prepare("SELECT COUNT(*) as c FROM ext_bookmarks WHERE user_id=?");
$r->bind_param("i",$user_id); $r->execute();
$bookmark_count = $r->get_result()->fetch_assoc()['c'];

$r2 = $conn->prepare("SELECT COUNT(*) as c FROM ext_reading_history WHERE user_id=? AND progress=100");
$r2->bind_param("i",$user_id); $r2->execute();
$completed_count = $r2->get_result()->fetch_assoc()['c'];

$r3 = $conn->prepare("SELECT COUNT(*) as c FROM ext_reading_history WHERE user_id=? AND progress>0 AND progress<100");
$r3->bind_param("i",$user_id); $r3->execute();
$inprogress_count = $r3->get_result()->fetch_assoc()['c'];

// Reading streak
$r4 = $conn->prepare("SELECT DATE(last_read) as d FROM ext_reading_history WHERE user_id=? GROUP BY DATE(last_read) ORDER BY d DESC");
$r4->bind_param("i",$user_id); $r4->execute();
$dates = $r4->get_result()->fetch_all(MYSQLI_ASSOC);
$streak = 0; $check_date = new DateTime('today');
foreach ($dates as $row) {
    $d = new DateTime($row['d']);
    if ($d->format('Y-m-d') === $check_date->format('Y-m-d')) { $streak++; $check_date->modify('-1 day'); } else break;
}

// Continue reading
$cr = $conn->prepare("SELECT * FROM ext_reading_history WHERE user_id=? AND progress>0 AND progress<100 ORDER BY last_read DESC LIMIT 4");
$cr->bind_param("i",$user_id); $cr->execute();
$continue_books = $cr->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Dashboard – E-Library</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--bg:#0a1410;--surface:#121e17;--card:#162119;--border:#243329;--green:#3a8c4a;--green-dim:#2e7a3c;--text:#d4ecd7;--muted:#5a7a5e;--accent:#6fcf80;}
    body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;}

    .sidebar{position:fixed;top:0;left:0;width:240px;height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;padding:32px 20px;gap:6px;z-index:100;overflow-y:auto;}
    .sidebar .logo{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--accent);margin-bottom:28px;padding-left:8px;}
    .sidebar a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;color:var(--muted);text-decoration:none;font-size:0.93rem;font-weight:500;transition:background 0.2s,color 0.2s;}
    .sidebar a:hover,.sidebar a.active{background:rgba(58,140,74,0.15);color:var(--accent);}
    .sidebar a .icon{font-size:1.1rem;width:20px;text-align:center;}
    .sidebar .sect-label{font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);padding:14px 12px 4px;opacity:0.55;}
    .sidebar .logout{margin-top:auto;color:#c0392b;}
    .sidebar .logout:hover{background:rgba(192,57,43,0.12);color:#e74c3c;}

    .main{margin-left:240px;padding:36px 40px;min-height:100vh;}

    .topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:36px;flex-wrap:wrap;gap:14px;}
    .topbar h1{font-family:'Playfair Display',serif;font-size:1.8rem;color:var(--text);}
    .user-pill{display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--border);border-radius:30px;padding:8px 16px;font-size:0.88rem;color:var(--muted);text-decoration:none;}
    .avatar{width:32px;height:32px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;color:#fff;}

    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:18px;margin-bottom:40px;}
    .stat-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:22px 20px;display:flex;flex-direction:column;gap:6px;transition:transform 0.2s;}
    .stat-card:hover{transform:translateY(-2px);}
    .stat-card .label{font-size:0.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;}
    .stat-card .value{font-size:2.2rem;font-weight:700;color:var(--accent);font-family:'Playfair Display',serif;}
    .stat-card .sub{font-size:0.78rem;color:var(--muted);}

    .sec-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;}
    .sec-hdr h2{font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--text);}
    .sec-count{font-size:0.82rem;color:var(--muted);}
    .sec-count span{color:var(--accent);font-weight:600;}

    .continue-list{display:flex;flex-direction:column;gap:12px;margin-bottom:44px;}
    .continue-item{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:16px;text-decoration:none;color:inherit;transition:border-color 0.2s;}
    .continue-item:hover{border-color:var(--green);}
    .ci-cover{width:46px;height:64px;object-fit:cover;border-radius:5px;flex-shrink:0;}
    .ci-placeholder{width:46px;height:64px;border-radius:5px;flex-shrink:0;background:linear-gradient(135deg,#1a2e20,#0d1a10);display:flex;align-items:center;justify-content:center;font-size:1.4rem;}
    .continue-item .meta{flex:1;min-width:0;}
    .continue-item .meta h4{font-size:0.9rem;color:var(--text);margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .continue-item .meta p{font-size:0.76rem;color:var(--muted);margin-bottom:8px;}
    .progress-bar{height:5px;background:var(--border);border-radius:3px;overflow:hidden;}
    .progress-bar .fill{height:100%;background:var(--green);border-radius:3px;}
    .pct{font-size:0.8rem;color:var(--accent);font-weight:700;white-space:nowrap;}

    .book-controls{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:20px;}
    .search-wrap{position:relative;flex:1;min-width:220px;}
    .search-wrap .si{position:absolute;left:13px;top:50%;transform:translateY(-50%);pointer-events:none;}
    .search-wrap input{width:100%;padding:10px 13px 10px 38px;border-radius:9px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:0.88rem;font-family:'DM Sans',sans-serif;outline:none;transition:border-color 0.2s;}
    .search-wrap input:focus{border-color:var(--green);}
    .search-wrap input::placeholder{color:var(--muted);}
    .filter-select{padding:10px 13px;border-radius:9px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:0.85rem;font-family:'DM Sans',sans-serif;outline:none;cursor:pointer;}
    .filter-select:focus{border-color:var(--green);}
    .src-tabs{display:flex;background:var(--card);border:1px solid var(--border);border-radius:9px;overflow:hidden;}
    .src-tabs button{padding:9px 16px;border:none;background:transparent;color:var(--muted);font-size:0.82rem;font-family:'DM Sans',sans-serif;font-weight:500;cursor:pointer;transition:all 0.2s;}
    .src-tabs button.active{background:var(--green);color:#fff;}

    .book-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:20px;margin-bottom:30px;}
    .book-card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:transform 0.25s,box-shadow 0.25s,border-color 0.25s;text-decoration:none;display:block;color:inherit;}
    .book-card:hover{transform:translateY(-5px);box-shadow:0 16px 40px rgba(0,0,0,0.4);border-color:var(--green);}
    .bc-cover{width:100%;aspect-ratio:2/3;object-fit:cover;display:block;background:#162119;}
    .bc-placeholder{width:100%;aspect-ratio:2/3;background:linear-gradient(135deg,#1a2e20,#0d1a10);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;}
    .bc-placeholder .bp-icon{font-size:2.6rem;}
    .bc-placeholder .bp-title{font-size:0.7rem;color:var(--muted);text-align:center;padding:0 10px;line-height:1.3;}
    .bc-info{padding:12px;}
    .bc-badge{display:inline-block;font-size:0.65rem;font-weight:700;padding:2px 7px;border-radius:7px;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.4px;}
    .badge-g{background:rgba(111,207,128,0.1);color:var(--accent);border:1px solid rgba(111,207,128,0.2);}
    .badge-ol{background:rgba(52,152,219,0.1);color:#5dade2;border:1px solid rgba(52,152,219,0.2);}
    .bc-title{font-size:0.88rem;font-weight:600;color:var(--text);line-height:1.3;margin-bottom:3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
    .bc-author{font-size:0.75rem;color:var(--muted);margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;}
    .bc-btn{display:block;width:100%;padding:8px;border:none;border-radius:8px;background:var(--green);color:#fff;font-size:0.8rem;font-family:'DM Sans',sans-serif;font-weight:600;text-align:center;}

    .skeleton{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;}
    .sk-img{width:100%;aspect-ratio:2/3;background:linear-gradient(90deg,#162119 25%,#1e3028 50%,#162119 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;}
    .sk-line{height:10px;border-radius:4px;margin:7px 10px;background:linear-gradient(90deg,#162119 25%,#1e3028 50%,#162119 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;}
    .sk-line.short{width:55%;}
    @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

    .pagination{display:flex;align-items:center;justify-content:center;gap:8px;padding-bottom:48px;}
    .pagination button{padding:9px 20px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:0.85rem;font-family:'DM Sans',sans-serif;cursor:pointer;transition:all 0.2s;}
    .pagination button:hover:not(:disabled){background:var(--green);border-color:var(--green);color:#fff;}
    .pagination button:disabled{opacity:0.3;cursor:not-allowed;}
    .pg-info{font-size:0.82rem;color:var(--muted);padding:0 8px;}
    .no-results{color:var(--muted);font-size:0.88rem;font-style:italic;padding:40px 20px;text-align:center;grid-column:1/-1;}

    @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;padding:20px 16px;}}
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="logo">📚 E-Library</div>
  <a href="dashboard.php" class="active"><span class="icon">🏠</span> Dashboard</a>
  <div class="sect-label">Source</div>
  <a href="#" onclick="setSource('gutenberg',document.getElementById('srcGutenberg'));return false;"><span class="icon">📗</span> Gutenberg</a>
  <a href="#" onclick="setSource('openlibrary',document.getElementById('srcOpenlib'));return false;"><span class="icon">📘</span> Open Library</a>
  <div class="sect-label">Genres</div>
  <a href="#" onclick="setSubject('fiction');return false;"><span class="icon">🌍</span> Fiction</a>
  <a href="#" onclick="setSubject('mystery');return false;"><span class="icon">🔍</span> Mystery</a>
  <a href="#" onclick="setSubject('science fiction');return false;"><span class="icon">🚀</span> Sci-Fi</a>
  <a href="#" onclick="setSubject('history');return false;"><span class="icon">📜</span> History</a>
  <a href="#" onclick="setSubject('philosophy');return false;"><span class="icon">💡</span> Philosophy</a>
  <a href="#" onclick="setSubject('adventure');return false;"><span class="icon">⚔️</span> Adventure</a>
  <a href="#" onclick="setSubject('poetry');return false;"><span class="icon">✍️</span> Poetry</a>
  <a href="#" onclick="setSubject('biography');return false;"><span class="icon">👤</span> Biography</a>
  <div class="sect-label">Account</div>
  <a href="profile.php"><span class="icon">⚙️</span> Profile</a>
  <a href="logout.php" class="logout"><span class="icon">🚪</span> Logout</a>
</aside>

<div class="main">
  <div class="topbar">
    <h1>Welcome, <?= htmlspecialchars($username) ?>! 👋</h1>
    <a href="profile.php" class="user-pill">
      <div class="avatar"><?= strtoupper(substr($username,0,1)) ?></div>
      <?= htmlspecialchars($username) ?>
    </a>
  </div>

  <div class="stats">
    <div class="stat-card"><span class="label">Bookmarked</span><span class="value"><?= $bookmark_count ?></span><span class="sub">Saved books</span></div>
    <div class="stat-card"><span class="label">Completed</span><span class="value"><?= $completed_count ?></span><span class="sub">Books finished</span></div>
    <div class="stat-card"><span class="label">In Progress</span><span class="value"><?= $inprogress_count ?></span><span class="sub">Currently reading</span></div>
    <div class="stat-card"><span class="label">Reading Streak</span><span class="value"><?= $streak ?></span><span class="sub"><?= $streak > 0 ? 'Days in a row 🔥' : 'Start reading today!' ?></span></div>
  </div>

  <?php if (!empty($continue_books)): ?>
  <div class="sec-hdr"><h2>Continue Reading</h2></div>
  <div class="continue-list">
    <?php foreach($continue_books as $cb): ?>
    <a class="continue-item" href="reader.php?source=<?= $cb['book_source'] ?>&id=<?= urlencode($cb['book_id']) ?>&title=<?= urlencode($cb['title']) ?>">
      <?php if($cb['cover_url']): ?>
        <img class="ci-cover" src="<?= htmlspecialchars($cb['cover_url']) ?>" alt="" onerror="this.style.display='none'">
      <?php else: ?>
        <div class="ci-placeholder">📖</div>
      <?php endif; ?>
      <div class="meta">
        <h4><?= htmlspecialchars($cb['title']) ?></h4>
        <p><?= htmlspecialchars($cb['author']) ?></p>
        <div class="progress-bar"><div class="fill" style="width:<?= $cb['progress'] ?>%"></div></div>
      </div>
      <span class="pct"><?= $cb['progress'] ?>%</span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="sec-hdr">
    <h2>Browse Books</h2>
    <span class="sec-count" id="resultCount">Loading…</span>
  </div>

  <div class="book-controls">
    <div class="search-wrap">
      <span class="si">🔍</span>
      <input type="text" id="searchInput" placeholder="Search by title, author, or topic…" />
    </div>
    <select class="filter-select" id="subjectFilter">
      <option value="">All Subjects</option>
      <option value="fiction">Fiction</option>
      <option value="science fiction">Sci-Fi</option>
      <option value="mystery">Mystery</option>
      <option value="romance">Romance</option>
      <option value="history">History</option>
      <option value="philosophy">Philosophy</option>
      <option value="adventure">Adventure</option>
      <option value="poetry">Poetry</option>
      <option value="children">Children</option>
      <option value="biography">Biography</option>
    </select>
    <div class="src-tabs">
      <button class="active" id="srcGutenberg" onclick="setSource('gutenberg',this)">📗 Gutenberg</button>
      <button id="srcOpenlib" onclick="setSource('openlibrary',this)">📘 Open Library</button>
    </div>
  </div>

  <div class="book-grid" id="bookGrid"></div>
  <div class="pagination" id="pagination" style="display:none;">
    <button id="prevBtn" onclick="changePage(-1)" disabled>← Prev</button>
    <span class="pg-info" id="pageInfo">Page 1</span>
    <button id="nextBtn" onclick="changePage(1)">Next →</button>
  </div>
</div>

<script>
let src = 'gutenberg', page = 1, query = '', subject = '', hasNext = false, timer;
const grid = document.getElementById('bookGrid');
const countEl = document.getElementById('resultCount');
const pag = document.getElementById('pagination');
const subSel = document.getElementById('subjectFilter');

function setSubject(s) {
  subject = s; page = 1;
  subSel.value = s;
  loadBooks();
  grid.scrollIntoView({behavior:'smooth', block:'start'});
}
function setSource(s, btn) {
  src = s; page = 1;
  document.querySelectorAll('.src-tabs button').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  loadBooks();
}
document.getElementById('searchInput').addEventListener('input', function(){
  clearTimeout(timer);
  timer = setTimeout(()=>{ query=this.value.trim(); page=1; loadBooks(); }, 420);
});
subSel.addEventListener('change', function(){ subject=this.value; page=1; loadBooks(); });

function skeletons() {
  grid.innerHTML = Array.from({length:12},()=>`<div class="skeleton"><div class="sk-img"></div><div class="sk-line" style="width:80%"></div><div class="sk-line short"></div><div class="sk-line" style="width:36%;margin-bottom:10px"></div></div>`).join('');
  countEl.textContent='Loading…'; pag.style.display='none';
}

async function loadBooks() {
  skeletons();
  src==='gutenberg' ? await loadGutenberg() : await loadOpenLib();
}

async function loadGutenberg() {
  const p=new URLSearchParams();
  if(query) p.set('search',query);
  if(subject) p.set('topic',subject);
  p.set('page',page);
  try {
    const d=await(await fetch('https://gutendex.com/books/?'+p)).json();
    hasNext=!!d.next;
    countEl.innerHTML=`<span>${(d.count||0).toLocaleString()}</span> books found`;
    renderGutenberg(d.results||[]);
    showPag();
  } catch(e){ grid.innerHTML='<p class="no-results">⚠️ Could not load books. Check your internet connection.</p>'; countEl.textContent='Error'; }
}

async function loadOpenLib() {
  const q=query||subject||'classic literature';
  try {
    const d=await(await fetch(`https://openlibrary.org/search.json?q=${encodeURIComponent(q)}&limit=20&page=${page}&fields=key,title,author_name,cover_i`)).json();
    hasNext=(page*20)<(d.numFound||0);
    countEl.innerHTML=`<span>${(d.numFound||0).toLocaleString()}</span> books found`;
    renderOpenLib(d.docs||[]);
    showPag();
  } catch(e){ grid.innerHTML='<p class="no-results">⚠️ Could not load books. Check your internet connection.</p>'; countEl.textContent='Error'; }
}

function showPag() {
  pag.style.display='flex';
  document.getElementById('pageInfo').textContent='Page '+page;
  document.getElementById('prevBtn').disabled=page===1;
  document.getElementById('nextBtn').disabled=!hasNext;
}

function renderGutenberg(books) {
  if(!books.length){grid.innerHTML='<p class="no-results">📭 No books found.</p>';return;}
  grid.innerHTML=books.map(b=>{
    const title=b.title||'Untitled';
    const author=(b.authors||[]).map(a=>{const p=a.name.split(', ');return p.length>1?p[1]+' '+p[0]:a.name;}).join(', ')||'Unknown';
    const cover=(b.formats||{})['image/jpeg']||`https://www.gutenberg.org/cache/epub/${b.id}/pg${b.id}.cover.medium.jpg`;
    return `<a class="book-card" href="reader.php?source=gutenberg&id=${b.id}">
      <img class="bc-cover" src="${e(cover)}" alt="${e(title)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
      <div class="bc-placeholder" style="display:none"><span class="bp-icon">📖</span><span class="bp-title">${e(title.substring(0,40))}</span></div>
      <div class="bc-info"><span class="bc-badge badge-g">Gutenberg</span><div class="bc-title">${e(title)}</div><div class="bc-author">${e(author)}</div><span class="bc-btn">Read Now →</span></div>
    </a>`;
  }).join('');
}

function renderOpenLib(books) {
  if(!books.length){grid.innerHTML='<p class="no-results">📭 No books found.</p>';return;}
  grid.innerHTML=books.map(b=>{
    const title=b.title||'Untitled';
    const author=(b.author_name||['Unknown'])[0];
    const olKey=(b.key||'').replace('/works/','');
    const coverHtml=b.cover_i
      ?`<img class="bc-cover" src="https://covers.openlibrary.org/b/id/${b.cover_i}-M.jpg" alt="${e(title)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><div class="bc-placeholder" style="display:none"><span class="bp-icon">📘</span><span class="bp-title">${e(title.substring(0,40))}</span></div>`
      :`<div class="bc-placeholder"><span class="bp-icon">📘</span><span class="bp-title">${e(title.substring(0,40))}</span></div>`;
    return `<a class="book-card" href="reader.php?source=openlibrary&id=${encodeURIComponent(olKey)}&title=${encodeURIComponent(title)}">
      ${coverHtml}
      <div class="bc-info"><span class="bc-badge badge-ol">Open Library</span><div class="bc-title">${e(title)}</div><div class="bc-author">${e(author)}</div><span class="bc-btn">Read Now →</span></div>
    </a>`;
  }).join('');
}

function changePage(dir){ page+=dir; loadBooks(); window.scrollTo({top:grid.offsetTop-20,behavior:'smooth'}); }
function e(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

loadBooks();
</script>
</body>
</html>
