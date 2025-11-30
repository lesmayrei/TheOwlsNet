<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'DB.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];

$usernameMsg = '';
$passwordMsg = '';
$visibilityMsg = '';
$deactivateMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])) {
    $formType = $_POST['form_type'];

    if ($formType === 'username') {
        $newUsername = isset($_POST['new_username']) ? trim($_POST['new_username']) : '';
        if ($newUsername === '') {
            $usernameMsg = 'Please enter a new username.';
        } else {
            if (isset($conn) && !$conn->connect_error) {
                $stmt = $conn->prepare('UPDATE users SET username = ? WHERE user_id = ?');
                if ($stmt) {
                    $stmt->bind_param('si', $newUsername, $userId);
                    if ($stmt->execute()) {
                        $usernameMsg = 'Username updated.';
                        $_SESSION['username'] = $newUsername;
                    } else {
                        $usernameMsg = 'Could not update username.';
                    }
                    $stmt->close();
                } else {
                    $usernameMsg = 'Could not update username.';
                }
            } else {
                $usernameMsg = 'Database connection error.';
            }
        }
    } elseif ($formType === 'password') {
        $current = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm = isset($_POST['confirm_new_password']) ? $_POST['confirm_new_password'] : '';

        if ($current === '' || $new === '' || $confirm === '') {
            $passwordMsg = 'Please fill in all password fields.';
        } elseif ($new !== $confirm) {
            $passwordMsg = 'New passwords do not match.';
        } else {
            if (isset($conn) && !$conn->connect_error) {
                $stmt = $conn->prepare('SELECT password_hash FROM users WHERE user_id = ? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('i', $userId);
                    $stmt->execute();
                    $stmt->bind_result($hash);
                    if ($stmt->fetch()) {
                        if (password_verify($current, $hash)) {
                            $stmt->close();
                            $newHash = password_hash($new, PASSWORD_BCRYPT);
                            $upd = $conn->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
                            if ($upd) {
                                $upd->bind_param('si', $newHash, $userId);
                                if ($upd->execute()) {
                                    $passwordMsg = 'Password updated.';
                                } else {
                                    $passwordMsg = 'Could not update password.';
                                }
                                $upd->close();
                            } else {
                                $passwordMsg = 'Could not update password.';
                            }
                        } else {
                            $passwordMsg = 'Current password is incorrect.';
                            $stmt->close();
                        }
                    } else {
                        $passwordMsg = 'Could not load current password.';
                        $stmt->close();
                    }
                } else {
                    $passwordMsg = 'Could not update password.';
                }
            } else {
                $passwordMsg = 'Database connection error.';
            }
        }
    } elseif ($formType === 'visibility') {
        $visibility = isset($_POST['visibility']) ? $_POST['visibility'] : 'public';
        if (isset($conn) && !$conn->connect_error) {
            $stmt = $conn->prepare('UPDATE profiles SET visibility = ? WHERE user_id = ?');
            if ($stmt) {
                $stmt->bind_param('si', $visibility, $userId);
                if ($stmt->execute()) {
                    $visibilityMsg = 'Profile visibility updated.';
                } else {
                    $visibilityMsg = 'Could not update visibility.';
                }
                $stmt->close();
            } else {
                $visibilityMsg = 'Could not update visibility.';
            }
        } else {
            $visibilityMsg = 'Database connection error.';
        }
    } elseif ($formType === 'deactivate') {
        if (isset($conn) && !$conn->connect_error) {
            $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                if ($stmt->execute()) {
                    $deactivateMsg = 'Account deactivated.';
                    session_unset();
                    session_destroy();
                    header('Location: index.php');
                    exit();
                } else {
                    $deactivateMsg = 'Could not deactivate account.';
                }
                $stmt->close();
            } else {
                $deactivateMsg = 'Could not deactivate account.';
            }
        } else {
            $deactivateMsg = 'Database connection error.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings – The Owls Net</title>
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
            max-width: 720px;
            margin: 2rem auto 3rem auto;
            padding: 0 1.5rem;
        }

        .page-title {
            font-size: 1.7rem;
            margin-bottom: 0.3rem;
        }

        .page-subtitle {
            font-size: 0.95rem;
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }

        .card {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card h2 {
            font-size: 1.1rem;
            margin: 0 0 0.5rem 0;
        }

        .card p {
            font-size: 0.9rem;
            color: #9ca3af;
            margin: 0 0 1rem 0;
        }

        label {
            display: block;
            margin-bottom: 0.25rem;
            color: #cbd5e1;
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.65rem;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            border: 1px solid #475569;
            background: #0f172a;
            color: #f8fafc;
            font-family: inherit;
            font-size: 0.95rem;
        }

        input:focus {
            outline: none;
            border-color: #38bdf8;
        }

        .radio-group {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0;
        }

        .btn-row {
            margin-top: 0.8rem;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.55rem 1.2rem;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #38bdf8;
            color: #020617;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #0ea5e9;
        }

        .btn-secondary {
            background: transparent;
            color: #e5e7eb;
            border: 1px solid #4b5563;
        }

        .btn-danger {
            background: #b91c1c;
            color: #fee2e2;
            font-weight: 600;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .danger-text {
            color: #fecaca;
            font-size: 0.9rem;
        }

        .message {
            margin-top: 0.6rem;
            font-size: 0.9rem;
            color: #e5e7eb;
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
    <h1 class="page-title">Account Settings</h1>
    <p class="page-subtitle">
        Manage your username, password, profile visibility, and account status.
        (Later, this page will be connected to PHP and the database.)
    </p>

    <!-- Change username -->
    <section class="card">
        <h2>Change username</h2>
        <p>Update the username that other students see on your profile and posts.</p>

        <form action="settings.php" method="POST">
            <input type="hidden" name="form_type" value="username">
            <label for="new_username">New username</label>
            <input
                type="text"
                id="new_username"
                name="new_username"
                placeholder="Enter a new username"
                required
            >

            <div class="btn-row">
                <button type="submit" class="btn btn-primary">Save username</button>
            </div>
            <?php if ($usernameMsg !== ''): ?>
                <p class="message"><?php echo $usernameMsg; ?></p>
            <?php endif; ?>
        </form>
    </section>

    <!-- Change password -->
    <section class="card">
        <h2>Change password</h2>
        <p>Choose a new password to secure your account.</p>

        <form action="settings.php" method="POST">
            <input type="hidden" name="form_type" value="password">
            <label for="current_password">Current password</label>
            <input
                type="password"
                id="current_password"
                name="current_password"
                placeholder="Enter current password"
                required
            >

            <label for="new_password">New password</label>
            <input
                type="password"
                id="new_password"
                name="new_password"
                placeholder="Enter new password"
                required
            >

            <label for="confirm_new_password">Confirm new password</label>
            <input
                type="password"
                id="confirm_new_password"
                name="confirm_new_password"
                placeholder="Re-enter new password"
                required
            >

            <div class="btn-row">
                <button type="submit" class="btn btn-primary">Save password</button>
            </div>
            <?php if ($passwordMsg !== ''): ?>
                <p class="message"><?php echo $passwordMsg; ?></p>
            <?php endif; ?>
        </form>
    </section>

    <!-- Profile visibility -->
    <section class="card">
        <h2>Profile visibility</h2>
        <p>Choose whether your profile is public or only visible to approved followers.</p>

        <form action="settings.php" method="POST">
            <input type="hidden" name="form_type" value="visibility">
            <div class="radio-group">
                <label>
                    <input type="radio" name="visibility" value="public" checked>
                    Public profile
                </label>
                <label>
                    <input type="radio" name="visibility" value="private">
                    Private profile
                </label>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary">Save visibility</button>
            </div>
            <?php if ($visibilityMsg !== ''): ?>
                <p class="message"><?php echo $visibilityMsg; ?></p>
            <?php endif; ?>
        </form>
    </section>

    <!-- Deactivate account -->
    <section class="card">
        <h2>Deactivate account</h2>
        <p class="danger-text">
            Deactivating your account will disable your profile and hide your posts from the feed.
            You can describe the exact behavior here when you implement it.
        </p>

        <form action="settings.php" method="POST">
            <input type="hidden" name="form_type" value="deactivate">
            <div class="btn-row">
                <button type="submit" class="btn btn-danger">Deactivate account</button>
            </div>
            <?php if ($deactivateMsg !== ''): ?>
                <p class="message"><?php echo $deactivateMsg; ?></p>
            <?php endif; ?>
        </form>
    </section>
</main>

<footer>
    The Owls Net · CSC 335 · Settings Page Prototype
</footer>

</body>
</html>
