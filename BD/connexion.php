<?php
$host = 'localhost';
$dbname = 'miniloc2';
$username = 'root';
$password = '';
$port = '3306';

try {
    $conn = new PDO( // Remplace $pdo par $conn
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log("ERREUR CONNEXION: " . $e->getMessage());
    die("Service temporairement indisponible");
}

?>