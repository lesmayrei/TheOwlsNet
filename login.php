<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Prepare insert
    $stmt = $conn->prepare(
        "INSERT INTO users (email, username, password_hash, status, created_at) 
         VALUES (?, ?, ?, 'active', NOW())"
    );
    $stmt->bind_param("sss", $email, $username, $password_hash);

    if ($stmt->execute()) {
        echo "Registration successful!";

        // Auto-login after registration
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['username'] = $username;

        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;  // Shows duplicate email/username errors
    }

    $stmt->close();
    $conn->close();
}
?>

<!-- simple HTML form -->
<form action="register.php" method="POST">
    <input type="email" name="email" placeholder="Enter email" required><br>
    <input type="text" name="username" placeholder="Choose username" required><br>
    <input type="password" name="password" placeholder="Create password" required><br>
    <button type="submit">Register</button>
</form>
