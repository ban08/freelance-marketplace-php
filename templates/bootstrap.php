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

// Migração SQLite: adiciona colunas em falta na tabela users
try {
    // Adiciona coluna 'tipo' se ainda não existir
    $pdo->exec("ALTER TABLE users ADD COLUMN tipo TEXT NOT NULL DEFAULT 'cliente';");
} catch (PDOException $e) {
    // ignora se já existe
}

try {
    // Adiciona coluna 'is_admin' se ainda não existir
    $pdo->exec("ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0;");
} catch (PDOException $e) {
    // ignora se já existe
}

try {
    // Adiciona coluna 'username' se ainda não existir
    $pdo->exec("ALTER TABLE users ADD COLUMN username TEXT;");
} catch (PDOException $e) {
    // ignora se já existe
}

try {
    // Adiciona coluna 'name' se ainda não existir
    $pdo->exec("ALTER TABLE users ADD COLUMN name TEXT;");
} catch (PDOException $e) {
    // ignora se já existe
}

try {
    // Adiciona coluna 'bio' se ainda não existir
    $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT;");
} catch (PDOException $e) {
    // ignora se já existe
}

try {
    // Adiciona coluna 'profile_picture' se ainda não existir
    $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture TEXT;");
} catch (PDOException $e) {
    // ignora se já existe
}

try {
    // Adiciona coluna 'joined_date' se ainda não existir
    $pdo->exec("ALTER TABLE users ADD COLUMN joined_date DATETIME DEFAULT CURRENT_TIMESTAMP;");
} catch (PDOException $e) {
    // ignora se já existe
}
