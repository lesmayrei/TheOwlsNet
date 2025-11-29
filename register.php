<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Include status and created_at
    $stmt = $conn->prepare(
        "INSERT INTO users (email, username, password_hash, status, created_at)
         VALUES (?, ?, ?, 'active', NOW())"
    );

    $stmt->bind_param("sss", $email, $username, $password_hash);

    if ($stmt->execute()) {
        echo "Registration successful!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
