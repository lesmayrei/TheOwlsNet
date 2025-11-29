<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// FIX 1 — correct file name (case sensitive)
include 'DB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO users (email, username, password_hash, status, created_at)
         VALUES (?, ?, ?, 'active', NOW())"
    );

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sss", $email, $username, $password_hash);

    // FIX 2 — no echo, use redirect instead
    if ($stmt->execute()) {
        header("Location: index.html");
        exit();
    } else {
        die("Error: " . $stmt->error);
    }
}
?>
