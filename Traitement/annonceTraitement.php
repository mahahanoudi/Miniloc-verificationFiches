<?php
include '../BD/connexion.php';
include 'objetTraitement.php';

/**
 * Crée une nouvelle annonce
 */
function creerAnnonce($objet_id, $proprietaire_id, $titre, $description, $prix_journalier, $date_debut, $date_fin, $adress, $premium = 0, $date_debut_premium = null, $duree_premium = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            INSERT INTO annonce (
                objet_id, proprietaire_id, titre, description, prix_journalier,
                date_debut, date_fin, adress, premium, date_debut_premium,
                duree_premium, statut, date_publication
            )
            VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'disponible', NOW()
            )
        ");
        return $stmt->execute([
            $objet_id, $proprietaire_id, $titre, $description, $prix_journalier,
            $date_debut, $date_fin, $adress, $premium, $date_debut_premium,
            $duree_premium
        ]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la création de l'annonce : " . $e->getMessage());
        return false;
    }
}

/**
 * Modifie une annonce existante
 */
function modifierAnnonce($id, $titre, $description, $prix_journalier, $date_debut, $date_fin, $adress, $premium, $date_debut_premium, $duree_premium) {
    global $conn;
    
    try {
        $sql = "UPDATE annonce SET 
                titre = ?, 
                description = ?, 
                prix_journalier = ?, 
                date_debut = ?, 
                date_fin = ?, 
                adress = ?, 
                premium = ?, 
                date_debut_premium = ?, 
                duree_premium = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $titre,
            $description,
            $prix_journalier,
            $date_debut,
            $date_fin,
            $adress,
            $premium ? 1 : 0,
            $premium ? $date_debut_premium : null,
            $premium ? $duree_premium : null,
            $id
        ]);
        
        return true;
    } catch(PDOException $e) {
        error_log("Erreur lors de la modification de l'annonce : " . $e->getMessage());
        return false;
    }
}

/**
 * Archive une annonce
 */
function archiverAnnonce($id) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE annonce SET statut = 'archive' WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Active une annonce
 */
function activerAnnonce($id) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE annonce SET statut = 'disponible' WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère une annonce par son ID
 */
function getAnnonceById($id) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT a.*, o.nom as objet_nom, o.description as objet_description, 
                   o.prix_journalier, c.nom as categorie_nom, u.nom as proprietaire_nom
            FROM annonce a
            JOIN objet o ON a.objet_id = o.id
            JOIN categorie c ON o.categorie_id = c.id
            JOIN utilisateur u ON a.proprietaire_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère toutes les annonces d'un propriétaire
 */
function getAnnoncesByProprietaire($proprietaire_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT a.*, o.nom as objet_nom, o.description as objet_description, 
                   o.prix_journalier, c.nom as categorie_nom
            FROM annonce a
            JOIN objet o ON a.objet_id = o.id
            JOIN categorie c ON o.categorie_id = c.id
            WHERE a.proprietaire_id = ?
            ORDER BY a.date_publication DESC
        ");
        $stmt->execute([$proprietaire_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Compte le nombre d'annonces actives d'un propriétaire
 */
function getNombreAnnoncesActives($proprietaire_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM annonce 
            WHERE proprietaire_id = ? AND statut = 'disponible'
        ");
        $stmt->execute([$proprietaire_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    } catch (PDOException $e) {
        return 0;
    }
} 