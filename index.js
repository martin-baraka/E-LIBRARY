<?php
session_start();

// If already logged in, go straight to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>E-Library</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #f4f6f9; font-family: 'DM Sans', Arial, sans-serif; }
        header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
        nav { background-color: #34495e; padding: 10px; text-align: center; }
        nav a { color: white; text-decoration: none; margin: 0 15px; font-weight: bold; }
        nav a:hover { text-decoration: underline; }
        .search-bar { text-align: center; margin: 20px; }
        .search-bar input { width: 50%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-family: 'DM Sans', sans-serif; }
        .container { width: 90%; margin: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; padding-bottom: 40px; }
        .book-card { background-color: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; transition: transform 0.3s ease; }
        .book-card:hover { transform: translateY(-5px); }
        .book-card img { width: 100%; height: 250px; object-fit: cover; border-radius: 5px; }
        .book-card h3 { margin: 10px 0; }
        .book-card p { color: #555; font-size: 14px; }
        .book-card button { margin-top: 10px; padding: 8px 12px; border: none; border-radius: 5px; background-color: #2c3e50; color: white; cursor: pointer; }
        .book-card button:hover { background-color: #1a252f; }

        .auth-section { background: #0f1b14; padding: 60px 20px; display: flex; justify-content: center; gap: 28px; flex-wrap: wrap; }
        .auth-section-title { text-align: center; font-family: 'Playfair Display', serif; color: #e8f5e9; font-size: 1.6rem; width: 100%; margin-bottom: 8px; }
        .auth-section-sub { text-align: center; color: #5a7a5e; font-size: 0.9rem; width: 100%; margin-bottom: 20px; }
        .auth-card { background: #162119; border: 1px solid #2a3d2e; border-radius: 16px; padding: 36px 32px; width: 100%; max-width: 340px; box-shadow: 0 16px 40px rgba(0,0,0,0.35); display: flex; flex-direction: column; align-items: center; gap: 18px; }
        .auth-card h2 { font-family: 'Playfair Display', serif; color: #e8f5e9; font-size: 1.6rem; }
        .auth-card .card-sub { color: #5a7a5e; font-size: 0.88rem; text-align: center; margin-top: -10px; }
        .msg-error { background: rgba(192,57,43,0.15); border: 1px solid #c0392b; color: #e74c3c; padding: 10px 14px; border-radius: 8px; font-size: 0.83rem; width: 100%; text-align: center; }
        .msg-success { background: rgba(46,125,50,0.15); border: 1px solid #2e7d32; color: #66bb6a; padding: 10px 14px; border-radius: 8px; font-size: 0.83rem; width: 100%; text-align: center; }
        .auth-trigger-btn { background: #3a8c4a; color: #fff; border: none; border-radius: 10px; padding: 12px 36px; font-size: 0.95rem; font-family: 'DM Sans', sans-serif; font-weight: 500; cursor: pointer; transition: background 0.2s, transform 0.15s; width: 100%; }
        .auth-trigger-btn:hover { background: #2e7a3c; transform: translateY(-1px); }
        .auth-form { width: 100%; display: flex; flex-direction: column; gap: 12px; overflow: hidden; max-height: 0; opacity: 0; transition: max-height 0.45s ease, opacity 0.4s ease; }
        .auth-form.visible { max-height: 500px; opacity: 1; }
        .auth-form input { width: 100%; padding: 12px 14px; border-radius: 9px; border: 1px solid #2a3d2e; background: #0f1b14; color: #d4ecd7; font-size: 0.9rem; font-family: 'DM Sans', sans-serif; outline: none; transition: border-color 0.2s; }
        .auth-form input:focus { border-color: #3a8c4a; }
        .auth-form input::placeholder { color: #3d5c42; }
        .auth-form button[type="submit"] { background: #3a8c4a; color: #fff; border: none; border-radius: 9px; padding: 12px; font-size: 0.95rem; font-family: 'DM Sans', sans-serif; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        .auth-form button[type="submit"]:hover { background: #2e7a3c; }
        .switch-link { font-size: 0.85rem; color: #5a7a5e; text-align: center; }
        .switch-link a { color: #6fcf80; font-weight: 600; text-decoration: none; cursor: pointer; }
        .switch-link a:hover { text-decoration: underline; }
        footer { background-color: #0a1410; color: #5a7a5e; text-align: center; padding: 20px; font-size: 0.88rem; }
    </style>
</head>
<body>

<header>
    <h1>📚 My E-Library</h1>
    <p>Your Digital Reading Hub</p>
</header>

<nav>
    <a href="#">Home</a>
    <a href="#">Categories</a>
    <a href="#">New Arrivals</a>
    <a href="#">Contact</a>
</nav>

<div class="search-bar">
    <input type="text" placeholder="Search for books...">
</div>

<div class="container">
    <div class="book-card">
        <img src="https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c" alt="Book Cover 1">
        <h3>The Art of Reading</h3><p>John Smith</p><button>Read More</button>
    </div>
    <div class="book-card">
        <img src="https://images.unsplash.com/photo-1512820790803-83ca734da794" alt="Book Cover 2">
        <h3>Digital Future</h3><p>Emily Johnson</p><button>Read More</button>
    </div>
    <div class="book-card">
        <img src="https://images.unsplash.com/photo-1495446815901-a7297e633e8d" alt="Book Cover 3">
        <h3>Knowledge World</h3><p>Michael Brown</p><button>Read More</button>
    </div>
    <div class="book-card">
        <img src="https://images.unsplash.com/photo-1524995997946-a1c2e315a42f" alt="Book Cover 4">
        <h3>Learning Everyday</h3><p>Sarah Wilson</p><button>Read More</button>
    </div>
</div>

<!-- Auth Cards at Bottom -->
<div class="auth-section">
    <p class="auth-section-title">Join E-Library Today</p>
    <p class="auth-section-sub">Sign in to your account or create a new one to start reading.</p>

    <!-- Sign In Card -->
    <div class="auth-card">
        <h2>Sign In</h2>
        <p class="card-sub">Welcome back! Access your library.</p>
        <?php if (!empty($_GET['login_error'])): ?>
            <div class="msg-error"><?php echo htmlspecialchars($_GET['login_error']); ?></div>
        <?php endif; ?>
        <button class="auth-trigger-btn" id="signin-btn" onclick="revealForm('signin-form','signin-btn')">Sign In</button>
        <form id="signin-form" class="auth-form" method="POST" action="signin.php">
            <input type="email"    name="email"    placeholder="Email"    required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Login</button>
        </form>
        <p class="switch-link">New user? <a onclick="switchToRegister()">Create a free account →</a></p>
    </div>

    <!-- Register Card -->
    <div class="auth-card">
        <h2>Register</h2>
        <p class="card-sub">New here? Create your free account.</p>
        <?php if (!empty($_GET['register_error'])): ?>
            <div class="msg-error"><?php echo htmlspecialchars($_GET['register_error']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['register_success'])): ?>
            <div class="msg-success">Account created! You can now sign in.</div>
        <?php endif; ?>
        <button class="auth-trigger-btn" id="register-btn" onclick="revealForm('register-form','register-btn')">Register</button>
        <form id="register-form" class="auth-form" method="POST" action="register.php">
            <input type="text"     name="username" placeholder="Username" required />
            <input type="email"    name="email"    placeholder="Email"    required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Register</button>
        </form>
        <p class="switch-link">Already have an account? <a onclick="switchToSignin()">Sign in →</a></p>
    </div>
</div>

<footer>
    <p>&copy; 2026 My E-Library | All Rights Reserved</p>
</footer>

<script>
function revealForm(formId, btnId) {
    document.getElementById(formId).classList.add('visible');
    document.getElementById(btnId).style.display = 'none';
}
function switchToRegister() {
    revealForm('register-form', 'register-btn');
    document.getElementById('register-form').closest('.auth-card').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function switchToSignin() {
    revealForm('signin-form', 'signin-btn');
    document.getElementById('signin-form').closest('.auth-card').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
window.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('login_error')) {
        revealForm('signin-form', 'signin-btn');
        document.getElementById('signin-form').scrollIntoView({ behavior: 'smooth' });
    }
    if (params.get('register_error') || params.get('register_success')) {
        revealForm('register-form', 'register-btn');
        document.getElementById('register-form').scrollIntoView({ behavior: 'smooth' });
    }
});
</script>
</body>
</html>
const books = [
    {
        title: "The Art of Reading",
        author: "John Smith",
        image: "https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c"
    },
    {
        title: "Digital Future",
        author: "Emily Johnson",
        image: "https://images.unsplash.com/photo-1512820790803-83ca734da794"
    },
    {
        title: "Knowledge World",
        author: "Michael Brown",
        image: "https://images.unsplash.com/photo-1495446815901-a7297e633e8d"
    },
    {
        title: "Learning Everyday",
        author: "Sarah Wilson",
        image: "https://images.unsplash.com/photo-1524995997946-a1c2e315a42f"
    }
];

// Select Elements
const container = document.querySelector(".container");
const searchInput = document.querySelector(".search-bar input");

// Display Books
function displayBooks(bookArray) {
    container.innerHTML = "";

    bookArray.forEach(book => {
        const bookCard = document.createElement("div");
        bookCard.classList.add("book-card");

        bookCard.innerHTML = `
            <img src="${book.image}" alt="${book.title}">
            <h3>${book.title}</h3>
            <p>${book.author}</p>
            <button onclick="readMore('${book.title}')">Read More</button>
        `;

        container.appendChild(bookCard);
    });
}

// Read More Button
function readMore(title) {
    alert("You selected: " + title);
}

// Search Function
searchInput.addEventListener("keyup", function () {
    const searchValue = this.value.toLowerCase();

    const filteredBooks = books.filter(book =>
        book.title.toLowerCase().includes(searchValue) ||
        book.author.toLowerCase().includes(searchValue)
    );

    displayBooks(filteredBooks);
});

// Initialize
displayBooks(books);
