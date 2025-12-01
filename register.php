<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'DB.php';

$register_error = '';
$register_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if ($username === '' || $email === '' || $password === '' || $confirm === '') {
        $register_error = 'Please fill in all fields.';
    } elseif (!str_ends_with($email, '@southernct.edu')) {
        $register_error = 'Email must be a southernct.edu address.';
    } elseif ($password !== $confirm) {
        $register_error = 'Passwords do not match.';
    } else {
        if (isset($conn) && !$conn->connect_error) {
            $check = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
            if ($check) {
                $check->bind_param('s', $email);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $register_error = 'An account with that email already exists.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);

                    $insert = $conn->prepare('INSERT INTO users (email, password_hash, username, status, created_at) VALUES (?, ?, ?, "active", NOW())');
                    if ($insert) {
                        $insert->bind_param('sss', $email, $hash, $username);
                        if ($insert->execute()) {
                            $userId = $conn->insert_id;

                            $prof = $conn->prepare('INSERT INTO profiles (user_id) VALUES (?)');
                            if ($prof) {
                                $prof->bind_param('i', $userId);
                                $prof->execute();
                                $prof->close();
                            }

                            $register_success = 'Account created. You can log in now.';
                        } else {
                            $register_error = 'There was a problem creating your account.';
                        }
                        $insert->close();
                    } else {
                        $register_error = 'There was a problem creating your account.';
                    }
                }

                $check->close();
            } else {
                $register_error = 'There was a problem creating your account.';
            }
        } else {
            $register_error = 'Database connection error.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register – The Owls Net</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            background: #0f172a;
            color: #f8fafc;
            font-family: system-ui, sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: #1e293b;
            padding: 2rem 2.5rem;
            border-radius: 12px;
            width: 380px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.6rem;
        }
        label {
            display: block;
            margin-bottom: 0.3rem;
            color: #cbd5e1;
            font-size: 0.9rem;
        }
        input {
            width: 100%;
            padding: 0.65rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #475569;
            background: #0f172a;
            color: #f8fafc;
        }
        input:focus {
            border-color: #38bdf8;
            outline: none;
        }
        .btn {
            width: 100%;
            padding: 0.7rem;
            border-radius: 8px;
            background: #38bdf8;
            border: none;
            color: #0f172a;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        .btn:hover {
            background: #0ea5e9;
        }
        .switch {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .switch a {
            color: #38bdf8;
            text-decoration: none;
        }
        .switch a:hover {
            text-decoration: underline;
        }
        .message-error {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #fca5a5;
            text-align: center;
        }
        .message-success {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #bbf7d0;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Create Account</h1>

        <!-- Registration Form -->
        <form action="register.php" method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Choose a username" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="yourname@southernct.edu" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Create a password" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>

            <button class="btn" type="submit">Create Account</button>

            <?php if ($register_error !== ''): ?>
                <div class="message-error"><?php echo $register_error; ?></div>
            <?php endif; ?>

            <?php if ($register_success !== ''): ?>
                <div class="message-success"><?php echo $register_success; ?></div>
            <?php endif; ?>
        </form>

        <div class="switch">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>

</body>
</html>
