<?php
// Show PHP errors for debugging (you can turn this off later)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'DB.php';

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    $stmt = $conn->prepare(
        "SELECT user_id, username, password_hash 
         FROM users 
         WHERE email = ? AND status = 'active'"
    );

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $username, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;

                $update = $conn->prepare("UPDATE users SET last_login_at = NOW() WHERE user_id = ?");
                if ($update) {
                    $update->bind_param("i", $user_id);
                    $update->execute();
                    $update->close();
                }

                // redirect to HOME (index.php, not index.html)
                header("Location: index.php");
                exit();
            } else {
                $login_error = "Incorrect password.";
            }
        } else {
            $login_error = "No account found with that email.";
        }

        $stmt->close();
    } else {
        $login_error = "Login error. Please try again later.";
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login · The Owls Net</title>
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
            max-width: 400px;
            margin: 3rem auto;
            padding: 0 1.5rem;
        }
        h1 {
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
        }
        p.subtitle {
            font-size: 0.95rem;
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }
        .card {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1.25rem 1.5rem;
        }
        label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
            color: #e5e7eb;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.5rem 0.6rem;
            border-radius: 6px;
            border: 1px solid #4b5563;
            background: #0f172a;
            color: #f9fafb;
            font-size: 0.95rem;
            margin-bottom: 0.9rem;
        }
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #38bdf8;
        }
        .btn {
            width: 100%;
            padding: 0.6rem;
            border-radius: 999px;
            border: none;
            background-color: #38bdf8;
            color: #020617;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0ea5e9;
        }
        .error {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #fca5a5;
            text-align: center;
        }
        .small-link {
            margin-top: 0.9rem;
            font-size: 0.85rem;
            text-align: center;
            color: #9ca3af;
        }
        .small-link a {
            color: #38bdf8;
            text-decoration: none;
        }
        .small-link a:hover {
            text-decoration: underline;
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
        <a href="register.php">Register</a>
        <a href="feed.php">Feed</a>
    </nav>
</header>

<main>
    <h1>Login</h1>
    <p class="subtitle">Sign in to access your campus feed and profile.</p>

    <div class="card">
        <form action="login.php" method="POST">
            <label for="email">SCSU Email</label>
            <input type="email" id="email" name="email" required
                   placeholder="you@southernct.edu">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button class="btn" type="submit">Log in</button>

            <?php if ($login_error !== ''): ?>
                <div class="error">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
        </form>

        <div class="small-link">
            Don’t have an account yet?
            <a href="register.php">Create one</a>
        </div>
    </div>
</main>

<footer>
    The Owls Net · CSC 335
</footer>
</body>
</html>