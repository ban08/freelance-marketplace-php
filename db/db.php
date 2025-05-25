<?php
// db.php - estabelecer ligação PDO à base de dados SQLite e ativar chaves estrangeiras
try {
    $pdo = new PDO("sqlite:" . __DIR__ . "/../database.sqlite");
    // Definir o modo de erro do PDO para exceções
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Definir o modo de fetch para arrays associativos
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Ativar enforcement de foreign keys (por omissão, o SQLite não as impõe sem este comando)
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    // Em caso de erro na ligação, terminar a execução e mostrar mensagem de erro
    die("Falha BD: " . $e->getMessage());
}
?>
