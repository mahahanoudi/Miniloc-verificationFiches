<?php
session_start();
require_once '../BD/connexion.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Non authentifié']));
}

try {
    // 1. Vérifier le double statut en BDD
    $stmt = $conn->prepare("SELECT est_client, est_partenaire, role 
                          FROM utilisateur 
                          WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData || !$userData['est_client'] || !$userData['est_partenaire']) {
        die(json_encode(['status' => 'error', 'message' => 'Action non autorisée']));
    }

    // 2. Déterminer le nouveau rôle
    $nouveauRole = ($userData['role'] === 'client') ? 'proprietaire' : 'client';

    // 3. Mise à jour en BDD avec vérification
    $updateStmt = $conn->prepare("UPDATE utilisateur 
                                SET role = :role 
                                WHERE id = :id 
                                AND est_client = 1 
                                AND est_partenaire = 1");
    $updateStmt->execute([
        ':role' => $nouveauRole,
        ':id' => $_SESSION['user_id']
    ]);

    
    if ($updateStmt->rowCount() > 0) {
        $_SESSION['role'] = $nouveauRole;
    
        // Définir l'URL de redirection ABSOLUE
        $redirectUrl = ($nouveauRole === 'client') 
                     ? '../Traitement/traitement_index.php' 
                     : '../IHM/espace_partenaire.php';
    
        echo json_encode([
            'status' => 'success',
            'redirectUrl' => $redirectUrl,
            'newRole' => $nouveauRole,
            'buttonHtml' => generateButtonHtml($nouveauRole)
        ]);
    } else {
        error_log("Échec mise à jour rôle: " . print_r($updateStmt->errorInfo(), true));
        die(json_encode(['status' => 'error', 'message' => 'Échec de la mise à jour']));
    }

} catch (PDOException $e) {
    error_log("ERREUR SWITCH ROLE: " . $e->getMessage());
    die(json_encode(['status' => 'error', 'message' => 'Erreur système']));
}

function generateButtonHtml($role) {
    $icon = ($role === 'client') ? 'fas fa-repeat' : 'fas fa-repeat';
    $text = ($role === 'client') ? 'Partenaire' : 'Client';
    
    return '<button class="btn btn-switch">
              <i class="fas fa-repeat '.$icon.' me-2"></i>
              '.$text.'
            </button>';
}


