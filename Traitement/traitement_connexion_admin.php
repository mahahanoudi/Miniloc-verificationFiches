<?php

session_start();
require_once('../BD/connexion.php'); // Inclure la connexion à la base de données

// Vérifier si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $mot_pass = $_POST['mot_pass'];

    // Préparer et exécuter la requête pour vérifier l'admin dans la base
    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérification de l'admin
    if ($admin && $admin['mot_pass'] === $mot_pass) {
        // Si les informations sont valides, démarrer la session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nom'] = $admin['nom'];
        $_SESSION['admin_prenom'] = $admin['prenom'];

        // Rediriger vers le tableau de bord de l'admin
        header("Location: ../admin/tableau_de_bord_admin.php");
        exit();
    } else {
        // Si la connexion échoue, afficher un message d'erreur
        $_SESSION['erreur'] = "Email ou mot de passe incorrect.";
        header("Location: ../IHM/connexion_admin.php"); // Retourner vers le formulaire de connexion
        exit();
    }
}
?>
