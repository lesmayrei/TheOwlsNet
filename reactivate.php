<?php
session_start();
require_once 'DB.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($conn) && !$conn->connect_error) {
    $identifier = trim($_POST['identifier'] ?? '');

    if ($identifier === '') {
        $error = 'Please enter a username or email.';
    } else {
        // we’ll allow either username OR email in the same box
        $stmt = $conn->prepare("
            SELECT user_id, email, username, status
            FROM users
            WHERE email = ? OR username = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $targetId = (int)$user['user_id'];

                if ($user['status'] === 'active') {
                    $message = 'This account is already active.';
                } else {
                    $upd = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
                    if ($upd) {
                        $upd->bind_param('i', $targetId);
                        if ($upd->execute()) {
                            $message = "Account for @" . htmlspecialchars($user['username']) . " has been reactivated.";
                        } else {
                            $error = 'Could not reactivate account.';
                        }
                        $upd->close();
                    } else {
                        $error = 'Could not prepare update statement.';
                    }
                }
            } else {
                $error = 'No user found with that email or username.';
            }

            $stmt->close();
        } else {
            $error = 'Database error.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reactivate Account – The Owls Net</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            background: #0f172a;
            color: #f9fafb;
            font-family: system-ui, sans-serif;
            margin: 0;
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
            max-width: 600px;
            margin: 2rem auto 3rem auto;
            padding: 0 1.5rem;
        }

        h1 {
            font-size: 1.7rem;
            margin-bottom: 0.5rem;
        }

        p.subtitle {
            color: #9ca3af;
            font-size: 0.95rem;
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
            margin-bottom: 0.35rem;
        }

        input[type="text"] {
            width: 100%;
            padding: 0.5rem;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: #f9fafb;
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #38bdf8;
        }

        .btn {
            padding: 0.45rem 1rem;
            border-radius: 999px;
            border: none;
            background: #38bdf8;
            color: #020617;
            font-size: 0.9rem;
            cursor: pointer;
            font-weight: 600;
        }

        .btn:hover {
            background: #0ea5e9;
        }

        .message {
            margin-top: 0.75rem;
            font-size: 0.9rem;
            color: #bbf7d0;
        }

        .error {
            margin-top: 0.75rem;
            font-size: 0.9rem;
            color: #fecaca;
        }

        footer {
            margin-top: 3rem;
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
        <a href="feed.php">Feed</a>
        <a href="profile.php">Profile</a>
        <a href="settings.php">Settings</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <h1>Reactivate account</h1>
    <p class="subtitle">
        Enter a username or email to set that account back to active.
    </p>

    <section class="card">
        <form action="reactivate.php" method="POST">
            <label for="identifier">Username or email</label>
            <input type="text" id="identifier" name="identifier" placeholder="e.g. testpage or test@southernct.edu">

            <button type="submit" class="btn">Reactivate</button>

            <?php if ($message !== ''): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
        </form>
    </section>
</main>

<footer>
    The Owls Net · CSC 335 ·
</footer>

</body>
</html>