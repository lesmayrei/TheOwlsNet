<?php
// Show PHP errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'DB.php'; // Make sure DB.php is in the same folder

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Lookup user in database
    $stmt = $conn->prepare(
        "SELECT user_id, username, password_hash 
         FROM users 
         WHERE email = ? AND status = 'active'"
    );

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {

        $stmt->bind_result($user_id, $username, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {

            // Create session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;

            // Update last login timestamp
            $update = $conn->prepare("UPDATE users SET last_login_at = NOW() WHERE user_id = ?");
            $update->bind_param("i", $user_id);
            $update->execute();

            // Redirect to index.html after successful login
            header("Location: index.html");
            exit();

        } else {
            echo "<p style='color:red; text-align:center;'>Incorrect password.</p>";
        }

    } else {
        echo "<p style='color:red; text-align:center;'>No account found with that email.</p>";
    }

    $stmt->close();
    $conn->close();
}
?>
