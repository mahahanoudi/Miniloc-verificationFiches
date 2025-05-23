<?php
require_once __DIR__ . '/../BD/connexion.php';

function creerUtilisateur($data) {
    global $conn;
    try {
        $stmt = $conn->prepare("INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['nom'], $data['email'], password_hash($data['mot_de_passe'], PASSWORD_DEFAULT), $data['role']]);
        header('Location: /IHM/connexion.php');
        exit();
    } catch(PDOException $e) {
        return false;
    }
}