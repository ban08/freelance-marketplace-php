<?php
// bootstrap.php

if (session_status() === PHP_SESSION_NONE) {  
    session_start();
}

$dbPath = __DIR__ . "/../database.sqlite";
$pdo = new PDO("sqlite:$dbPath", null, null, [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Migração SQLite: adiciona coluna 'tipo' se ainda não existir
try {
    $pdo->exec("
      ALTER TABLE users 
      ADD COLUMN tipo TEXT NOT NULL DEFAULT 'cliente';
    ");
} catch (PDOException $e) {
    // ignora se já existe
}
