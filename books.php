<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Browse Books – E-Library</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{
      --bg:#0a1410;--surface:#121e17;--card:#162119;--border:#243329;
      --green:#3a8c4a;--green-dim:#2e7a3c;--text:#d4ecd7;--muted:#5a7a5e;--accent:#6fcf80;
    }
    body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;}

    /* Sidebar */
    .sidebar{position:fixed;top:0;left:0;width:240px;height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;padding:32px 20px;gap:6px;z-index:100;overflow-y:auto;}
    .sidebar .logo{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--accent);margin-bottom:28px;padding-left:8px;letter-spacing:-0.3px;}
    .sidebar a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;color:var(--muted);text-decoration:none;font-size:0.93rem;font-weight:500;transition:background 0.2s,color 0.2s;}
    .sidebar a:hover,.sidebar a.active{background:rgba(58,140,74,0.15);color:var(--accent);}
    .sidebar a .icon{font-size:1.1rem;width:20px;text-align:center;}
    .sidebar .logout{margin-top:auto;color:#c0392b;}
    .sidebar .logout:hover{background:rgba(192,57,43,0.12);color:#e74c3c;}
    .sidebar .section-label{font-size:0.7rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:1px;padding:14px 12px 6px;opacity:0.6;}

    /* Main */
    .main{margin-left:240px;padding:36px 40px;flex:1;min-width:0;}
    .top-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:30px;gap:20px;flex-wrap:wrap;}
    .page-title{font-family:'Playfair Display',serif;font-size:1.8rem;color:var(--text);}
    .page-title span{color:var(--accent);}

    /* Search & Filters */
    .controls{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:28px;}
    .search-wrap{position:relative;flex:1;min-width:260px;}
    .search-wrap .icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:1rem;pointer-events:none;}
    .search-wrap input{width:100%;padding:11px 14px 11px 40px;border-radius:10px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:0.9rem;font-family:'DM Sans',sans-serif;outline:none;transition:border-color 0.2s;}
    .search-wrap input:focus{border-color:var(--green);}
    .search-wrap input::placeholder{color:var(--muted);}
    .filter-select{padding:11px 14px;border-radius:10px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:0.88rem;font-family:'DM Sans',sans-serif;outline:none;cursor:pointer;}
    .filter-select:focus{border-color:var(--green);}
    .source-tabs{display:flex;background:var(--card);border:1px solid var(--border);border-radius:10px;overflow:hidden;}
    .source-tabs button{padding:10px 18px;border:none;background:transparent;color:var(--muted);font-size:0.85rem;font-family:'DM Sans',sans-serif;font-weight:500;cursor:pointer;transition:all 0.2s;}
    .source-tabs button.active{background:var(--green);color:#fff;}

    /* Stats strip */
    .results-strip{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px;}
    .results-count{font-size:0.85rem;color:var(--muted);}
    .results-count span{color:var(--accent);font-weight:600;}
    .view-toggle{display:flex;gap:6px;}
    .view-toggle button{background:var(--card);border:1px solid var(--border);color:var(--muted);border-radius:7px;padding:6px 10px;cursor:pointer;font-size:0.9rem;transition:all 0.2s;}
    .view-toggle button.active{background:var(--green);border-color:var(--green);color:#fff;}

    /* Book Grid */
    .book-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:20px;}
    .book-grid.list-view{grid-template-columns:1fr;}

    .book-card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:transform 0.25s,box-shadow 0.25s,border-color 0.25s;cursor:pointer;text-decoration:none;display:block;color:inherit;}
    .book-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,0.4);border-color:var(--green);}
    .book-card .cover{width:100%;aspect-ratio:2/3;object-fit:cover;background:linear-gradient(135deg,#162119,#0a1a0f);display:block;}
    .book-card .cover-placeholder{width:100%;aspect-ratio:2/3;background:linear-gradient(135deg,#1a2e20 0%,#0d1a10 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;}
    .book-card .cover-placeholder .emoji{font-size:2.8rem;}
    .book-card .cover-placeholder .initials{font-family:'Playfair Display',serif;color:var(--accent);font-size:0.8rem;text-align:center;padding:0 10px;opacity:0.7;line-height:1.3;}
    .book-card .info{padding:14px;}
    .book-card .source-badge{display:inline-block;font-size:0.68rem;font-weight:600;padding:2px 8px;border-radius:8px;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;}
    .badge-gutenberg{background:rgba(111,207,128,0.12);color:var(--accent);border:1px solid rgba(111,207,128,0.25);}
    .badge-openlibrary{background:rgba(52,152,219,0.12);color:#5dade2;border:1px solid rgba(52,152,219,0.25);}
    .book-card .title{font-family:'Playfair Display',serif;font-size:0.95rem;color:var(--text);line-height:1.3;margin-bottom:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
    .book-card .author{font-size:0.78rem;color:var(--muted);margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;}
    .book-card .subjects{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:10px;}
    .book-card .subject-tag{font-size:0.67rem;padding:2px 7px;border-radius:6px;background:rgba(58,140,74,0.1);color:#5a8c62;border:1px solid rgba(58,140,74,0.15);}
    .book-card .read-btn{display:block;width:100%;padding:8px;border:none;border-radius:8px;background:var(--green);color:#fff;font-size:0.82rem;font-family:'DM Sans',sans-serif;font-weight:600;cursor:pointer;transition:background 0.2s;text-align:center;text-decoration:none;}
    .book-card .read-btn:hover{background:var(--green-dim);}

    /* List view */
    .book-grid.list-view .book-card{display:flex;align-items:center;gap:16px;}
    .book-grid.list-view .book-card .cover,.book-grid.list-view .book-card .cover-placeholder{width:64px;height:96px;min-width:64px;aspect-ratio:unset;border-radius:6px;}
    .book-grid.list-view .book-card .info{flex:1;display:flex;align-items:center;gap:16px;flex-wrap:wrap;}
    .book-grid.list-view .book-card .text-group{flex:1;min-width:180px;}
    .book-grid.list-view .book-card .read-btn{width:auto;padding:8px 20px;}

    /* States */
    .loading-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:20px;}
    .skeleton{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;animation:pulse 1.5s ease-in-out infinite;}
    .skeleton .sk-img{width:100%;aspect-ratio:2/3;background:linear-gradient(90deg,#162119 25%,#1e3028 50%,#162119 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;}
    .skeleton .sk-line{height:12px;border-radius:4px;margin:8px 12px;background:linear-gradient(90deg,#162119 25%,#1e3028 50%,#162119 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;}
    .skeleton .sk-line.short{width:60%;}
    @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:0.7}}

    .empty-state{text-align:center;padding:80px 20px;color:var(--muted);}
    .empty-state .emoji{font-size:3rem;margin-bottom:16px;}
    .empty-state h3{font-family:'Playfair Display',serif;color:var(--text);font-size:1.3rem;margin-bottom:8px;}

    /* Pagination */
    .pagination{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:40px;padding-bottom:20px;}
    .pagination button{padding:8px 16px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:0.88rem;font-family:'DM Sans',sans-serif;cursor:pointer;transition:all 0.2s;}
    .pagination button:hover:not(:disabled){background:var(--green);border-color:var(--green);}
    .pagination button:disabled{opacity:0.3;cursor:not-allowed;}
    .pagination .page-info{color:var(--muted);font-size:0.85rem;padding:0 8px;}

    @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;padding:20px 16px;}.book-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));}}
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="logo">📚 E-Library</div>
  <div class="section-label">Navigation</div>
  <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
  <a href="books.php" class="active"><span class="icon">📚</span> Browse Books</a>
  <a href="dashboard.php?tab=bookmarks"><span class="icon">🔖</span> My Bookmarks</a>
  <a href="dashboard.php?tab=history"><span class="icon">📖</span> Reading History</a>
  <div class="section-label">Account</div>
  <a href="profile.php"><span class="icon">⚙️</span> Profile</a>
  <a href="logout.php" class="logout"><span class="icon">🚪</span> Logout</a>
</aside>

<div class="main">
  <div class="top-bar">
    <h1 class="page-title">Browse <span>Books</span></h1>
    <div style="font-size:0.85rem;color:var(--muted);">Welcome back, <strong style="color:var(--accent);"><?= htmlspecialchars($username) ?></strong></div>
  </div>

  <div class="controls">
    <div class="search-wrap">
      <span class="icon">🔍</span>
      <input type="text" id="searchInput" placeholder="Search by title, author, or subject…" />
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
    </select>
    <div class="source-tabs">
      <button class="active" onclick="setSource('gutenberg',this)">Gutenberg</button>
      <button onclick="setSource('openlibrary',this)">Open Library</button>
    </div>
  </div>

  <div class="results-strip">
    <div class="results-count" id="resultsCount">Loading books…</div>
    <div class="view-toggle">
      <button class="active" onclick="setView('grid',this)" title="Grid view">⊞</button>
      <button onclick="setView('list',this)" title="List view">☰</button>
    </div>
  </div>

  <div id="bookGrid" class="book-grid"></div>
  <div class="pagination" id="pagination" style="display:none;">
    <button id="prevBtn" onclick="changePage(-1)" disabled>← Prev</button>
    <span class="page-info" id="pageInfo">Page 1</span>
    <button id="nextBtn" onclick="changePage(1)">Next →</button>
  </div>
</div>

<script>
let currentSource = 'gutenberg';
let currentPage = 1;
let currentQuery = '';
let currentSubject = '';
let debounceTimer;
let totalCount = 0;
let gutenbergNextUrl = null;
let gutenbergPrevUrl = null;

const grid = document.getElementById('bookGrid');
const resultsCount = document.getElementById('resultsCount');
const pagination = document.getElementById('pagination');
const pageInfo = document.getElementById('pageInfo');

function setSource(src, btn) {
  currentSource = src;
  currentPage = 1;
  document.querySelectorAll('.source-tabs button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  loadBooks();
}

function setView(v, btn) {
  document.querySelectorAll('.view-toggle button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  grid.className = 'book-grid' + (v === 'list' ? ' list-view' : '');
}

document.getElementById('searchInput').addEventListener('input', function() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    currentQuery = this.value.trim();
    currentPage = 1;
    loadBooks();
  }, 400);
});
document.getElementById('subjectFilter').addEventListener('change', function() {
  currentSubject = this.value;
  currentPage = 1;
  loadBooks();
});

function showSkeletons() {
  const skels = Array.from({length: 12}, () => `
    <div class="skeleton">
      <div class="sk-img"></div>
      <div class="sk-line" style="width:80%"></div>
      <div class="sk-line short"></div>
      <div class="sk-line" style="width:40%;margin-bottom:12px;"></div>
    </div>`).join('');
  grid.innerHTML = skels;
  resultsCount.textContent = 'Loading books…';
  pagination.style.display = 'none';
}

async function loadBooks() {
  showSkeletons();
  if (currentSource === 'gutenberg') {
    await loadGutenberg();
  } else {
    await loadOpenLibrary();
  }
}

async function loadGutenberg() {
  let url = 'https://gutendex.com/books/?';
  const params = new URLSearchParams();
  if (currentQuery) params.set('search', currentQuery);
  if (currentSubject) params.set('topic', currentSubject);
  params.set('page', currentPage);
  url += params.toString();

  try {
    const res = await fetch(url);
    const data = await res.json();
    totalCount = data.count;
    gutenbergNextUrl = data.next;
    gutenbergPrevUrl = data.previous;
    renderGutenbergBooks(data.results);

    resultsCount.innerHTML = `<span>${totalCount.toLocaleString()}</span> books found`;
    pagination.style.display = 'flex';
    pageInfo.textContent = `Page ${currentPage}`;
    document.getElementById('prevBtn').disabled = !gutenbergPrevUrl;
    document.getElementById('nextBtn').disabled = !gutenbergNextUrl;
  } catch(e) {
    grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="emoji">⚠️</div><h3>Couldn't load books</h3><p>Check your connection and try again.</p></div>`;
    resultsCount.textContent = 'Error loading books';
  }
}

function getGutenbergCover(book) {
  // Try formats for cover image
  const formats = book.formats || {};
  if (formats['image/jpeg']) return formats['image/jpeg'];
  // Fallback: Open Library cover by ISBN if present
  if (book.id) {
    return `https://www.gutenberg.org/cache/epub/${book.id}/pg${book.id}.cover.medium.jpg`;
  }
  return null;
}

function renderGutenbergBooks(books) {
  if (!books.length) {
    grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="emoji">📭</div><h3>No books found</h3><p>Try a different search or subject filter.</p></div>`;
    return;
  }
  grid.innerHTML = books.map(book => {
    const title = book.title || 'Untitled';
    const authors = book.authors.map(a => a.name.replace(/,\s*/, ' ').split(' ').reverse().join(' ')).join(', ') || 'Unknown Author';
    const subjects = (book.subjects || []).slice(0,2).map(s => s.split(' -- ')[0].split('(')[0].trim()).filter(s=>s.length<25);
    const coverUrl = getGutenbergCover(book);
    const coverHtml = coverUrl
      ? `<img class="cover" src="${coverUrl}" alt="${escHtml(title)}" onerror="this.parentNode.replaceChild(makePlaceholder('${escAttr(title)}'), this);">`
      : `<div class="cover-placeholder"><span class="emoji">📖</span><span class="initials">${escHtml(title.substring(0,40))}</span></div>`;

    const subjectTags = subjects.map(s=>`<span class="subject-tag">${escHtml(s)}</span>`).join('');

    return `<a class="book-card" href="reader.php?source=gutenberg&id=${book.id}">
      ${coverHtml}
      <div class="info">
        <span class="source-badge badge-gutenberg">Gutenberg</span>
        <div class="text-group">
          <div class="title">${escHtml(title)}</div>
          <div class="author">${escHtml(authors)}</div>
          <div class="subjects">${subjectTags}</div>
        </div>
        <span class="read-btn">Read Now →</span>
      </div>
    </a>`;
  }).join('');
}

async function loadOpenLibrary() {
  const query = currentQuery || currentSubject || 'classics';
  const url = `https://openlibrary.org/search.json?q=${encodeURIComponent(query)}&limit=24&page=${currentPage}&fields=key,title,author_name,subject,cover_i,first_publish_year,edition_count`;

  try {
    const res = await fetch(url);
    const data = await res.json();
    totalCount = data.numFound || 0;
    renderOpenLibraryBooks(data.docs || []);

    resultsCount.innerHTML = `<span>${totalCount.toLocaleString()}</span> books found`;
    pagination.style.display = 'flex';
    pageInfo.textContent = `Page ${currentPage}`;
    document.getElementById('prevBtn').disabled = currentPage === 1;
    document.getElementById('nextBtn').disabled = (currentPage * 24) >= totalCount;
  } catch(e) {
    grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="emoji">⚠️</div><h3>Couldn't load books</h3><p>Check your connection and try again.</p></div>`;
    resultsCount.textContent = 'Error loading books';
  }
}

function renderOpenLibraryBooks(books) {
  if (!books.length) {
    grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="emoji">📭</div><h3>No books found</h3><p>Try a different search term.</p></div>`;
    return;
  }
  grid.innerHTML = books.map(book => {
    const title = book.title || 'Untitled';
    const authors = (book.author_name || ['Unknown']).slice(0,2).join(', ');
    const subjects = (book.subject || []).slice(0,2).map(s=>s.split(' -- ')[0].trim()).filter(s=>s.length<25);
    const olKey = (book.key || '').replace('/works/','');
    const coverHtml = book.cover_i
      ? `<img class="cover" src="https://covers.openlibrary.org/b/id/${book.cover_i}-M.jpg" alt="${escHtml(title)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
         <div class="cover-placeholder" style="display:none"><span class="emoji">📘</span><span class="initials">${escHtml(title.substring(0,40))}</span></div>`
      : `<div class="cover-placeholder"><span class="emoji">📘</span><span class="initials">${escHtml(title.substring(0,40))}</span></div>`;
    const subjectTags = subjects.map(s=>`<span class="subject-tag">${escHtml(s)}</span>`).join('');

    return `<a class="book-card" href="reader.php?source=openlibrary&id=${encodeURIComponent(olKey)}&title=${encodeURIComponent(title)}">
      ${coverHtml}
      <div class="info">
        <span class="source-badge badge-openlibrary">Open Library</span>
        <div class="text-group">
          <div class="title">${escHtml(title)}</div>
          <div class="author">${escHtml(authors)}</div>
          <div class="subjects">${subjectTags}</div>
        </div>
        <span class="read-btn">Read Now →</span>
      </div>
    </a>`;
  }).join('');
}

function changePage(dir) {
  currentPage += dir;
  loadBooks();
  window.scrollTo({top:0,behavior:'smooth'});
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(str) {
  return String(str).replace(/'/g,"\\'").replace(/"/g,'&quot;');
}
function makePlaceholder(title) {
  const div = document.createElement('div');
  div.className = 'cover-placeholder';
  div.innerHTML = `<span class="emoji">📖</span><span class="initials">${title.substring(0,40)}</span>`;
  return div;
}

// Initial load
loadBooks();
</script>
</body>
</html>
