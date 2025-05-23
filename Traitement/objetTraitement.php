<?php
if (!defined('OBJET_TRAITEMENT_INCLUDED')) {
    define('OBJET_TRAITEMENT_INCLUDED', true);
    
    include '../BD/connexion.php';

    // Activer l'affichage des erreurs pour le débogage
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // Vérifier si l'utilisateur est connecté et est un propriétaire
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'proprietaire') {
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
        exit;
    }

    /**
     * Crée un nouvel objet
     */
    function creerObjet($nom, $categorie_id, $description, $ville, $prix_journalier, $proprietaire_id) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                INSERT INTO objet (nom, categorie_id, description, ville, prix_journalier, proprietaire_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$nom, $categorie_id, $description, $ville, $prix_journalier, $proprietaire_id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Modifie un objet existant
     */
    function modifierObjet($id, $nom, $categorie_id, $description, $ville, $prix_journalier) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                UPDATE objet 
                SET nom = ?, categorie_id = ?, description = ?, ville = ?, prix_journalier = ?
                WHERE id = ?
            ");
            return $stmt->execute([$nom, $categorie_id, $description, $ville, $prix_journalier, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Supprime un objet
     */
    function supprimerObjet($id) {
        global $conn;
        try {
            // Supprimer d'abord les images associées
            $stmt = $conn->prepare("DELETE FROM image WHERE objet_id = ?");
            $stmt->execute([$id]);
            
            // Puis supprimer l'objet
            $stmt = $conn->prepare("DELETE FROM objet WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère un objet par son ID
     */
    function getObjetById($id) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                SELECT o.*, c.nom as categorie_nom, u.nom as proprietaire_nom
                FROM objet o
                JOIN categorie c ON o.categorie_id = c.id
                JOIN utilisateur u ON o.proprietaire_id = u.id
                WHERE o.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère tous les objets d'un propriétaire
     */
    function getObjetsByProprietaire($proprietaire_id) {
        global $conn;
        try {
            $stmt = $conn->prepare("
                SELECT o.*, c.nom as categorie_nom
                FROM objet o
                JOIN categorie c ON o.categorie_id = c.id
                WHERE o.proprietaire_id = ?
                ORDER BY o.nom
            ");
            $stmt->execute([$proprietaire_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Ajoute une image pour un objet
     */
    function ajouterImageObjet($objet_id, $image_url) {
        global $conn;
        try {
            $stmt = $conn->prepare("INSERT INTO image (url, objet_id) VALUES (?, ?)");
            return $stmt->execute([$image_url, $objet_id]);
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'image : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les images d'un objet
     */
    function getImagesObjet($objet_id) {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM image WHERE objet_id = ?");
            $stmt->execute([$objet_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des images : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Supprime une image d'un objet
     */
    function supprimerImageObjet($image_id) {
        global $conn;
        try {
            // Récupérer l'URL de l'image avant de la supprimer
            $stmt = $conn->prepare("SELECT url FROM image WHERE id = ?");
            $stmt->execute([$image_id]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($image) {
                // Supprimer le fichier physique
                $file_path = "../uploads/" . $image['url'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Supprimer l'entrée de la base de données
                $stmt = $conn->prepare("DELETE FROM image WHERE id = ?");
                return $stmt->execute([$image_id]);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'image : " . $e->getMessage());
            return false;
        }
    }

    // Traitement des requêtes
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['action'])) {
                throw new Exception('Action non spécifiée');
            }

            switch ($data['action']) {
                case 'creer':
                    if (!isset($data['nom']) || !isset($data['categorie']) || !isset($data['description']) || 
                        !isset($data['ville']) || !isset($data['prix_jour'])) {
                        throw new Exception('Données manquantes');
                    }
                    $id = creerObjet(
                        $data['nom'],
                        $data['categorie'],
                        $data['description'],
                        $data['ville'],
                        $data['prix_jour'],
                        $_SESSION['user_id']
                    );
                    echo json_encode(['success' => true, 'message' => 'Objet créé avec succès', 'id' => $id]);
                    break;

                case 'modifier':
                    if (!isset($data['id']) || !isset($data['nom']) || !isset($data['categorie']) || 
                        !isset($data['description']) || !isset($data['ville']) || !isset($data['prix_jour'])) {
                        throw new Exception('Données manquantes');
                    }
                    if (modifierObjet(
                        $data['id'],
                        $data['nom'],
                        $data['categorie'],
                        $data['description'],
                        $data['ville'],
                        $data['prix_jour']
                    )) {
                        echo json_encode(['success' => true, 'message' => 'Objet modifié avec succès']);
                    } else {
                        throw new Exception('Objet non trouvé ou accès non autorisé');
                    }
                    break;

                case 'supprimer':
                    if (!isset($data['id'])) {
                        throw new Exception('ID de l\'objet manquant');
                    }
                    if (supprimerObjet($data['id'])) {
                        echo json_encode(['success' => true, 'message' => 'Objet supprimé avec succès']);
                    } else {
                        throw new Exception('Erreur lors de la suppression de l\'objet');
                    }
                    break;

                default:
                    throw new Exception('Action non reconnue');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?> 