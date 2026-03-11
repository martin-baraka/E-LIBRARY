<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>E-Library – Your Digital Reading Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --dark:#0f1b14; --surface:#162119; --border:#2a3d2e; --green:#3a8c4a; --green-dim:#2e7a3c; --accent:#6fcf80; --text:#d4ecd7; --muted:#5a7a5e; }
        body { background:#f4f6f9; font-family:'DM Sans',Arial,sans-serif; }

        header { background:#2c3e50; color:white; padding:24px 20px; text-align:center; }
        header h1 { font-family:'Playfair Display',serif; font-size:2rem; }
        header p { opacity:0.7; margin-top:4px; }

        nav { background:#34495e; padding:12px; text-align:center; }
        nav a { color:white; text-decoration:none; margin:0 15px; font-weight:600; font-size:0.9rem; transition:color 0.2s; }
        nav a:hover { color:#6fcf80; }

        .hero { background:linear-gradient(135deg,#162119 0%,#0f2d1a 50%,#0a1a0f 100%); padding:64px 20px; text-align:center; }
        .hero h2 { font-family:'Playfair Display',serif; font-size:2.4rem; color:#e8f5e9; margin-bottom:14px; line-height:1.2; }
        .hero p { color:#5a7a5e; font-size:1.05rem; max-width:500px; margin:0 auto 28px; line-height:1.6; }
        .hero-btns { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
        .btn-primary { background:var(--green); color:#fff; border:none; padding:13px 32px; border-radius:10px; font-size:0.95rem; font-family:'DM Sans',sans-serif; font-weight:600; cursor:pointer; transition:background 0.2s; text-decoration:none; display:inline-block; }
        .btn-primary:hover { background:var(--green-dim); }
        .btn-outline { background:transparent; color:#6fcf80; border:1.5px solid var(--green); padding:13px 32px; border-radius:10px; font-size:0.95rem; font-family:'DM Sans',sans-serif; font-weight:600; cursor:pointer; transition:all 0.2s; text-decoration:none; display:inline-block; }
        .btn-outline:hover { background:rgba(58,140,74,0.1); }

        .stats-bar { background:#2c3e50; display:flex; justify-content:center; gap:48px; padding:20px; flex-wrap:wrap; }
        .stat-item { text-align:center; color:white; }
        .stat-item .num { font-family:'Playfair Display',serif; font-size:1.8rem; color:#6fcf80; }
        .stat-item .lbl { font-size:0.8rem; opacity:0.6; margin-top:2px; }

        /* Books section */
        .books-section { background:#f4f6f9; padding:40px 20px 60px; }
        .books-header { text-align:center; margin-bottom:24px; }
        .books-header h3 { font-family:'Playfair Display',serif; font-size:1.6rem; color:#2c3e50; margin-bottom:6px; }
        .books-header p { color:#666; font-size:0.9rem; }

        .books-controls { display:flex; gap:10px; align-items:center; justify-content:center; flex-wrap:wrap; margin-bottom:28px; }
        .search-wrap { position:relative; }
        .search-wrap .si { position:absolute; left:14px; top:50%; transform:translateY(-50%); pointer-events:none; font-size:0.95rem; }
        .search-wrap input { width:340px; max-width:90vw; padding:11px 14px 11px 40px; border-radius:10px; border:1.5px solid #ddd; font-family:'DM Sans',sans-serif; font-size:0.9rem; outline:none; background:#fff; transition:border-color 0.2s; }
        .search-wrap input:focus { border-color:var(--green); }
        .filter-select { padding:11px 14px; border-radius:10px; border:1.5px solid #ddd; background:#fff; color:#333; font-size:0.88rem; font-family:'DM Sans',sans-serif; outline:none; cursor:pointer; }
        .filter-select:focus { border-color:var(--green); }
        .src-tabs { display:flex; background:#fff; border:1.5px solid #ddd; border-radius:10px; overflow:hidden; }
        .src-tabs button { padding:10px 18px; border:none; background:transparent; color:#777; font-size:0.83rem; font-family:'DM Sans',sans-serif; font-weight:500; cursor:pointer; transition:all 0.2s; }
        .src-tabs button.active { background:var(--green); color:#fff; }

        .results-bar { display:flex; align-items:center; justify-content:space-between; max-width:1200px; margin:0 auto 18px; padding:0 10px; flex-wrap:wrap; gap:8px; }
        .results-count { font-size:0.83rem; color:#888; }
        .results-count span { color:var(--green); font-weight:700; }

        .book-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(185px,1fr)); gap:20px; max-width:1200px; margin:0 auto; }

        /* Real book cards */
        .book-card { background:#fff; border-radius:12px; box-shadow:0 4px 14px rgba(0,0,0,0.08); overflow:hidden; transition:transform 0.25s,box-shadow 0.25s; cursor:pointer; position:relative; }
        .book-card:hover { transform:translateY(-5px); box-shadow:0 14px 36px rgba(0,0,0,0.14); }
        .book-card .bc-cover { width:100%; aspect-ratio:2/3; object-fit:cover; display:block; background:#e8f5e9; }
        .book-card .bc-placeholder { width:100%; aspect-ratio:2/3; background:linear-gradient(135deg,#e8f5e9,#c8e6c9); display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; }
        .book-card .bc-placeholder .bp-icon { font-size:2.8rem; }
        .book-card .bc-placeholder .bp-title { font-size:0.7rem; color:#5a8c62; text-align:center; padding:0 10px; line-height:1.3; }
        .book-card .bc-info { padding:13px; }
        .book-card .bc-badge { display:inline-block; font-size:0.65rem; font-weight:700; padding:2px 7px; border-radius:7px; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.4px; }
        .badge-g { background:rgba(58,140,74,0.1); color:#2e7a3c; border:1px solid rgba(58,140,74,0.2); }
        .badge-ol { background:rgba(52,152,219,0.1); color:#2980b9; border:1px solid rgba(52,152,219,0.2); }
        .book-card h3 { font-size:0.88rem; font-weight:600; color:#2c3e50; line-height:1.3; margin-bottom:3px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .book-card .author { font-size:0.76rem; color:#888; margin-bottom:10px; display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden; }
        .book-card .lock-overlay { position:absolute; inset:0; background:rgba(0,0,0,0.0); display:flex; align-items:flex-end; pointer-events:none; }
        .book-card .bc-btn { display:block; width:100%; padding:8px; border:none; border-radius:8px; background:#2c3e50; color:#fff; font-size:0.8rem; font-family:'DM Sans',sans-serif; font-weight:600; cursor:pointer; transition:background 0.2s; text-align:center; }
        .book-card .bc-btn:hover { background:#1a252f; }

        /* Skeleton */
        .skeleton { background:#fff; border-radius:12px; box-shadow:0 4px 14px rgba(0,0,0,0.06); overflow:hidden; }
        .sk-img { width:100%; aspect-ratio:2/3; background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%); background-size:200% 100%; animation:shimmer 1.5s infinite; }
        .sk-line { height:10px; border-radius:4px; margin:8px 12px; background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%); background-size:200% 100%; animation:shimmer 1.5s infinite; }
        .sk-line.short { width:55%; }
        @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

        /* Pagination */
        .pagination { display:flex; align-items:center; justify-content:center; gap:8px; margin-top:36px; }
        .pagination button { padding:9px 22px; border-radius:9px; border:1.5px solid #ddd; background:#fff; color:#333; font-size:0.88rem; font-family:'DM Sans',sans-serif; cursor:pointer; transition:all 0.2s; }
        .pagination button:hover:not(:disabled) { background:var(--green); border-color:var(--green); color:#fff; }
        .pagination button:disabled { opacity:0.3; cursor:not-allowed; }
        .pagination .pg-info { font-size:0.83rem; color:#888; padding:0 8px; }
        .no-results { color:#999; font-size:0.9rem; font-style:italic; padding:50px 20px; text-align:center; grid-column:1/-1; }

        /* Auth */
        .auth-section { background:var(--dark); padding:70px 20px; display:flex; justify-content:center; gap:28px; flex-wrap:wrap; }
        .auth-section-title { text-align:center; font-family:'Playfair Display',serif; color:#e8f5e9; font-size:1.8rem; width:100%; margin-bottom:6px; }
        .auth-section-sub { text-align:center; color:var(--muted); font-size:0.92rem; width:100%; margin-bottom:28px; }
        .auth-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:36px 32px; width:100%; max-width:340px; box-shadow:0 16px 40px rgba(0,0,0,0.35); display:flex; flex-direction:column; align-items:center; gap:16px; }
        .auth-card h2 { font-family:'Playfair Display',serif; color:#e8f5e9; font-size:1.6rem; }
        .auth-card .card-sub { color:var(--muted); font-size:0.88rem; text-align:center; margin-top:-8px; }
        .msg-error { background:rgba(192,57,43,0.15); border:1px solid #c0392b; color:#e74c3c; padding:10px 14px; border-radius:8px; font-size:0.83rem; width:100%; text-align:center; }
        .msg-success { background:rgba(46,125,50,0.15); border:1px solid #2e7d32; color:#66bb6a; padding:10px 14px; border-radius:8px; font-size:0.83rem; width:100%; text-align:center; }
        .auth-trigger-btn { background:var(--green); color:#fff; border:none; border-radius:10px; padding:12px 36px; font-size:0.95rem; font-family:'DM Sans',sans-serif; font-weight:600; cursor:pointer; transition:background 0.2s,transform 0.15s; width:100%; }
        .auth-trigger-btn:hover { background:var(--green-dim); transform:translateY(-1px); }
        .auth-form { width:100%; display:flex; flex-direction:column; gap:12px; overflow:hidden; max-height:0; opacity:0; transition:max-height 0.45s ease,opacity 0.4s ease; }
        .auth-form.visible { max-height:600px; opacity:1; }
        .auth-form input { width:100%; padding:12px 14px; border-radius:9px; border:1px solid var(--border); background:#0f1b14; color:var(--text); font-size:0.9rem; font-family:'DM Sans',sans-serif; outline:none; transition:border-color 0.2s; }
        .auth-form input:focus { border-color:var(--green); }
        .auth-form input::placeholder { color:#3d5c42; }
        .auth-form .input-hint { font-size:0.75rem; color:var(--muted); margin-top:-6px; }
        .auth-form .remember-row { display:flex; align-items:center; gap:8px; color:#5a7a5e; font-size:0.83rem; cursor:pointer; }
        .auth-form .remember-row input { width:auto; accent-color:var(--green); }
        .auth-form button[type="submit"] { background:var(--green); color:#fff; border:none; border-radius:9px; padding:12px; font-size:0.95rem; font-family:'DM Sans',sans-serif; font-weight:600; cursor:pointer; transition:background 0.2s; }
        .auth-form button[type="submit"]:hover { background:var(--green-dim); }
        .auth-form button[type="submit"]:disabled { background:#2a3d2e; cursor:not-allowed; }
        .switch-link { font-size:0.85rem; color:var(--muted); text-align:center; }
        .switch-link a { color:var(--accent); font-weight:600; text-decoration:none; cursor:pointer; }
        .switch-link a:hover { text-decoration:underline; }

        footer { background:#0a1410; color:#5a7a5e; text-align:center; padding:24px; font-size:0.88rem; }
    </style>
</head>
<body>

<header>
    <h1>📚 E-Library</h1>
    <p>Your Digital Reading Hub</p>
</header>
<nav>
    <a href="#">Home</a>
    <a href="#books">Browse Books</a>
    <a href="#auth-section" onclick="setTimeout(()=>switchToSignin(),400)">Sign In</a>
    <a href="#auth-section" onclick="setTimeout(()=>switchToRegister(),400)">Register</a>
</nav>

<div class="hero">
    <h2>Read More. Learn More.<br>Grow More.</h2>
    <p>Access thousands of free classic books from Project Gutenberg and Open Library — no subscription, no cost, ever.</p>
    <div class="hero-btns">
        <a href="#auth-section" class="btn-primary" onclick="setTimeout(()=>switchToRegister(),500)">Get Started Free</a>
        <a href="#books" class="btn-outline">Browse Books</a>
    </div>
</div>

<div class="stats-bar">
    <div class="stat-item"><div class="num">70,000+</div><div class="lbl">Free Books</div></div>
    <div class="stat-item"><div class="num">10+</div><div class="lbl">Genres</div></div>
    <div class="stat-item"><div class="num">Free</div><div class="lbl">Forever</div></div>
</div>

<!-- Live Books Section -->
<div class="books-section" id="books">
    <div class="books-header">
        <h3>Explore Real Books</h3>
        <p>Browse thousands of free titles — sign in to read the full content</p>
    </div>

    <div class="books-controls">
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
            <option value="biography">Biography</option>
        </select>
        <div class="src-tabs">
            <button class="active" id="srcGutenberg" onclick="setSource('gutenberg',this)">📗 Gutenberg</button>
            <button id="srcOpenlib" onclick="setSource('openlibrary',this)">📘 Open Library</button>
        </div>
    </div>

    <div class="results-bar">
        <div class="results-count" id="resultCount">Loading books…</div>
    </div>

    <div class="book-grid" id="bookGrid"></div>

    <div class="pagination" id="pagination" style="display:none;">
        <button id="prevBtn" onclick="changePage(-1)" disabled>← Prev</button>
        <span class="pg-info" id="pageInfo">Page 1</span>
        <button id="nextBtn" onclick="changePage(1)">Next →</button>
    </div>
</div>

<!-- Auth Section -->
<div class="auth-section" id="auth-section">
    <p class="auth-section-title">Join E-Library Today</p>
    <p class="auth-section-sub">Create a free account to read full books, bookmark favourites, and track your progress.</p>

    <!-- Sign In -->
    <div class="auth-card" id="signin-card">
        <h2>Sign In</h2>
        <p class="card-sub">Welcome back! Access your library.</p>
        <?php if (!empty($_GET['login_error'])): ?>
            <div class="msg-error"><?= htmlspecialchars($_GET['login_error']) ?></div>
        <?php endif; ?>
        <button class="auth-trigger-btn" id="signin-btn" onclick="revealForm('signin-form','signin-btn')">Sign In</button>
        <form id="signin-form" class="auth-form" method="POST" action="signin.php">
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Password" required />
            <label class="remember-row"><input type="checkbox" name="remember"> Remember me for 30 days</label>
            <button type="submit">Login</button>
        </form>
        <p class="switch-link">New user? <a onclick="switchToRegister()">Create a free account →</a></p>
    </div>

    <!-- Register -->
    <div class="auth-card" id="register-card">
        <h2>Register</h2>
        <p class="card-sub">New here? It takes 30 seconds.</p>
        <?php if (!empty($_GET['register_error'])): ?>
            <div class="msg-error"><?= htmlspecialchars($_GET['register_error']) ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['register_success'])): ?>
            <div class="msg-success">✅ Account created! You can now sign in.</div>
        <?php endif; ?>
        <button class="auth-trigger-btn" id="register-btn" onclick="revealForm('register-form','register-btn')">Register</button>
        <form id="register-form" class="auth-form" method="POST" action="register.php">
            <input type="text" name="username" placeholder="Username" required />
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" id="reg-pw" placeholder="Password (min 6 chars)" minlength="6" required />
            <input type="password" name="confirm_password" id="reg-cpw" placeholder="Confirm Password" required oninput="checkPw()"/>
            <p class="input-hint" id="pw-hint"></p>
            <button type="submit" id="reg-btn">Create Account</button>
        </form>
        <p class="switch-link">Already have an account? <a onclick="switchToSignin()">Sign in →</a></p>
    </div>
</div>

<footer><p>&copy; 2026 E-Library | All Rights Reserved</p></footer>

<script>
// ── Live Book Grid ──────────────────────────────────────────────────
let src = 'gutenberg', page = 1, query = '', subject = '', hasNext = false, timer;
const grid    = document.getElementById('bookGrid');
const countEl = document.getElementById('resultCount');
const pag     = document.getElementById('pagination');
const subSel  = document.getElementById('subjectFilter');

function setSource(s, btn) {
    src = s; page = 1;
    document.querySelectorAll('.src-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    loadBooks();
}

document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(timer);
    timer = setTimeout(() => { query = this.value.trim(); page = 1; loadBooks(); }, 420);
});
subSel.addEventListener('change', function() { subject = this.value; page = 1; loadBooks(); });

function skeletons() {
    grid.innerHTML = Array.from({length:12}, () => `
        <div class="skeleton">
            <div class="sk-img"></div>
            <div class="sk-line" style="width:82%"></div>
            <div class="sk-line short"></div>
            <div class="sk-line" style="width:36%;margin-bottom:10px"></div>
        </div>`).join('');
    countEl.textContent = 'Loading books…';
    pag.style.display = 'none';
}

async function loadBooks() {
    skeletons();
    src === 'gutenberg' ? await loadGutenberg() : await loadOpenLib();
}

async function loadGutenberg() {
    const p = new URLSearchParams();
    if (query)   p.set('search', query);
    if (subject) p.set('topic',  subject);
    p.set('page', page);
    try {
        const d = await (await fetch('https://gutendex.com/books/?' + p)).json();
        hasNext = !!d.next;
        countEl.innerHTML = `<span>${(d.count||0).toLocaleString()}</span> books found`;
        renderGutenberg(d.results || []);
        showPag();
    } catch(e) {
        grid.innerHTML = '<p class="no-results">⚠️ Could not load books. Check your internet connection.</p>';
        countEl.textContent = 'Error loading books';
    }
}

async function loadOpenLib() {
    const q = query || subject || 'classic literature';
    try {
        const d = await (await fetch(`https://openlibrary.org/search.json?q=${encodeURIComponent(q)}&limit=20&page=${page}&fields=key,title,author_name,cover_i`)).json();
        hasNext = (page * 20) < (d.numFound || 0);
        countEl.innerHTML = `<span>${(d.numFound||0).toLocaleString()}</span> books found`;
        renderOpenLib(d.docs || []);
        showPag();
    } catch(e) {
        grid.innerHTML = '<p class="no-results">⚠️ Could not load books. Check your internet connection.</p>';
        countEl.textContent = 'Error loading books';
    }
}

function showPag() {
    pag.style.display = 'flex';
    document.getElementById('pageInfo').textContent = 'Page ' + page;
    document.getElementById('prevBtn').disabled = page === 1;
    document.getElementById('nextBtn').disabled = !hasNext;
}

function renderGutenberg(books) {
    if (!books.length) { grid.innerHTML = '<p class="no-results">📭 No books found. Try a different search.</p>'; return; }
    grid.innerHTML = books.map(b => {
        const title  = b.title || 'Untitled';
        const author = (b.authors||[]).map(a => {
            const pts = a.name.split(', ');
            return pts.length > 1 ? pts[1]+' '+pts[0] : a.name;
        }).join(', ') || 'Unknown';
        const cover = (b.formats||{})['image/jpeg'] || `https://www.gutenberg.org/cache/epub/${b.id}/pg${b.id}.cover.medium.jpg`;
        return `<div class="book-card" onclick="scrollAndSignin()">
            <img class="bc-cover" src="${e(cover)}" alt="${e(title)}"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div class="bc-placeholder" style="display:none"><span class="bp-icon">📖</span><span class="bp-title">${e(title.substring(0,40))}</span></div>
            <div class="bc-info">
                <span class="bc-badge badge-g">Gutenberg</span>
                <h3>${e(title)}</h3>
                <p class="author">${e(author)}</p>
                <button class="bc-btn">🔒 Sign in to Read</button>
            </div>
        </div>`;
    }).join('');
}

function renderOpenLib(books) {
    if (!books.length) { grid.innerHTML = '<p class="no-results">📭 No books found. Try a different search.</p>'; return; }
    grid.innerHTML = books.map(b => {
        const title  = b.title || 'Untitled';
        const author = (b.author_name||['Unknown'])[0];
        const coverHtml = b.cover_i
            ? `<img class="bc-cover" src="https://covers.openlibrary.org/b/id/${b.cover_i}-M.jpg" alt="${e(title)}"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
               <div class="bc-placeholder" style="display:none"><span class="bp-icon">📘</span><span class="bp-title">${e(title.substring(0,40))}</span></div>`
            : `<div class="bc-placeholder"><span class="bp-icon">📘</span><span class="bp-title">${e(title.substring(0,40))}</span></div>`;
        return `<div class="book-card" onclick="scrollAndSignin()">
            ${coverHtml}
            <div class="bc-info">
                <span class="bc-badge badge-ol">Open Library</span>
                <h3>${e(title)}</h3>
                <p class="author">${e(author)}</p>
                <button class="bc-btn">🔒 Sign in to Read</button>
            </div>
        </div>`;
    }).join('');
}

function changePage(dir) {
    page += dir;
    loadBooks();
    document.getElementById('books').scrollIntoView({behavior:'smooth', block:'start'});
}

function e(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Auth helpers ─────────────────────────────────────────────────────
function revealForm(fId,bId){ document.getElementById(fId).classList.add('visible'); document.getElementById(bId).style.display='none'; }
function switchToRegister(){ revealForm('register-form','register-btn'); document.getElementById('register-card').scrollIntoView({behavior:'smooth',block:'center'}); }
function switchToSignin(){ revealForm('signin-form','signin-btn'); document.getElementById('signin-card').scrollIntoView({behavior:'smooth',block:'center'}); }
function scrollAndSignin(){ document.getElementById('auth-section').scrollIntoView({behavior:'smooth'}); setTimeout(switchToSignin, 400); }
function checkPw(){
    const pw=document.getElementById('reg-pw').value, cpw=document.getElementById('reg-cpw').value,
          h=document.getElementById('pw-hint'), b=document.getElementById('reg-btn');
    if(!cpw.length){ h.textContent=''; return; }
    if(pw===cpw){ h.textContent='✅ Passwords match'; h.style.color='#66bb6a'; b.disabled=false; }
    else{ h.textContent='❌ Passwords do not match'; h.style.color='#e74c3c'; b.disabled=true; }
}
window.addEventListener('DOMContentLoaded', function(){
    const p = new URLSearchParams(window.location.search);
    if(p.get('login_error')){ revealForm('signin-form','signin-btn'); document.getElementById('signin-card').scrollIntoView({behavior:'smooth'}); }
    if(p.get('register_error')||p.get('register_success')){ revealForm('register-form','register-btn'); document.getElementById('register-card').scrollIntoView({behavior:'smooth'}); }
});

// Load books on page open
loadBooks();
</script>
</body>
</html>
