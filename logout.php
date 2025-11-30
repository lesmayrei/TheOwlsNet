<?php
session_start();

// clear session data
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logged out · The Owls Net</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 0;
            background: #0f172a;
            color: #f9fafb;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        header {
            background: #020617;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #1f2937;
        }
        .logo {
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        nav a {
            color: #e5e7eb;
            margin-left: 1rem;
            text-decoration: none;
            font-size: 0.95rem;
        }
        nav a:hover {
            text-decoration: underline;
        }
        main {
            max-width: 480px;
            margin: 3rem auto;
            padding: 0 1.5rem;
            text-align: center;
        }
        h1 {
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
        }
        p {
            font-size: 0.95rem;
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }
        .btn-row {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
        }
        .btn-link {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            border-radius: 999px;
            font-size: 0.9rem;
            text-decoration: none;
            border: 1px solid #4b5563;
            color: #e5e7eb;
            background: transparent;
        }
        .btn-link-primary {
            border-color: #38bdf8;
            color: #020617;
            background: #38bdf8;
            font-weight: 600;
        }
        .btn-link-primary:hover {
            background: #0ea5e9;
        }
        .btn-link:hover {
            border-color: #9ca3af;
        }
        footer {
            margin-top: auto;
            padding: 1.5rem 2rem;
            font-size: 0.8rem;
            color: #6b7280;
            border-top: 1px solid #1f2937;
            text-align: center;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">The Owls Net</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
        <a href="feed.php">Feed</a>
    </nav>
</header>

<main>
    <h1>You’ve been logged out.</h1>
    <p>Your session has ended successfully.</p>
    <div class="btn-row">
        <a class="btn-link btn-link-primary" href="login.php">Log in again</a>
        <a class="btn-link" href="index.php">Back to home</a>
    </div>
</main>

<footer>
    The Owls Net · CSC 335
</footer>
</body>
</html>