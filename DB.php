<?php
$host = "localhost";     // or 127.0.0.1
$user = "root";          // your DB username
$password = "";          // your DB password
$dbname = "owlsnet";     // your existing DB

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>


