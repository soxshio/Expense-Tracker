<?php
$conn = new mysqli("localhost", "root", "", "expense_tracker");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
?>
