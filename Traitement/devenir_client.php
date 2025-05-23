<?php
session_start();
require_once '../BD/connexion.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Met à jour est_client = 1
    $stmt = $conn->prepare("UPDATE utilisateur SET est_client = 1 WHERE id = ?");
    $stmt->execute([$userId]);

    $_SESSION['is_client'] = 1;

    header("Location: ../IHM/index.php");
    exit;
} else {
    echo "Accès non autorisé.";
}
?>
