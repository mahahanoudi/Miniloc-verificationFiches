<?php

include_once('../BD/connexion.php');

if (isset($_GET['id']) ) {
    $id = intval($_GET['id']);
    $note_moyenne = (isset($_GET['note']) && is_numeric($_GET['note'])) ? floatval($_GET['note']) : null;
    $stmt = $conn->prepare("
        SELECT 
            a.id as annonce_id,
            a.adress,
            o.ville,
            o.etat,
            o.nom,
            o.description,
            o.proprietaire_id,
            o.prix_journalier
        FROM annonce a
        JOIN objet o ON a.objet_id = o.id
        WHERE a.id = ?
    ");

    $stmt->execute([$id]);
    $details = $stmt->fetchAll();
    
    $stmt = $conn->prepare("
    SELECT objet_id FROM annonce a WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $objet = $stmt->fetch(); 
    $objet_id = $objet['objet_id']; 
    
    $stmt = $conn->prepare("
    SELECT url FROM image  WHERE objet_id= ?
    ");
    $stmt->execute([$objet_id]);
    $image = $stmt->fetch(); 
    $image_url = $image ? $image['url'] : null;
    
    
    
    $stmt = $conn->prepare("
    SELECT count(*) as number FROM annonce WHERE objet_id = ?
    ");
    $stmt->execute([$objet_id]);
    $nbr_annonce = $stmt->fetch();
    $nbr_publication=$nbr_annonce['number'];

    $stmt = $conn->prepare("
    SELECT id,nom, prenom, email, img_profil FROM utilisateur WHERE id = ?
    ");
    $stmt->execute([$details[0]['proprietaire_id']]);
    $proprietaire = $stmt->fetch();

    // Récupération de la moyenne d'évaluation du propriétaire
    $stmt = $conn->prepare("
        SELECT AVG(note) as moyenne FROM evaluation WHERE evalue_id = ?
    ");
    $stmt->execute([$details[0]['proprietaire_id']]);
    $moyenne_row = $stmt->fetch();
    $moyenne = $moyenne_row['moyenne'] !== null ? round($moyenne_row['moyenne'], 2) : 'Pas encore évalué';
    // Storing all necessary data in session variables
    $_SESSION['proprietaire'] = $proprietaire;
    $_SESSION['note'] = $note_moyenne;
    $_SESSION['nbr_annonce'] = $nbr_publication;
    $_SESSION['details'] = $details;
    $_SESSION['objet_id'] = $objet_id;
    $_SESSION['moyenne']=$moyenne;
    $_SESSION['image'] = $image_url;
    
    // Also store the original annonce_id directly for easy access
    $_SESSION['annonce_id'] = $id;
    
    if (!$details) {
        echo "Annonce non trouvée.";
        exit;
    }
} else {
    echo "Aucune annonce sélectionnée.";
    exit;
}
?>