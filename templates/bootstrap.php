<?php
// bootstrap.php
session_start();

// Define the INCLUDES_DIR constant (change the path as needed)
define('INCLUDES_DIR', __DIR__ . '/includes');

$dbPath = __DIR__ . "/database.sqlite";
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
