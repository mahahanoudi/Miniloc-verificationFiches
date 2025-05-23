<?php
session_start();
require_once __DIR__ . '/../BD/connexion.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Met à jour est_partenaire = 1
    $stmt = $conn->prepare("UPDATE utilisateur SET est_partenaire = 1 WHERE id = ?");
    $stmt->execute([$userId]);

    $_SESSION['is_partenaire'] = 1;

    header("Location: /IHM/index.php");
    exit;
}
