<?php
include '../BD/connexion.php';

// Fonction pour créer une notification
function creerNotification($utilisateur_id, $contenu, $contenu_email, $sujet_email, $annonce_id = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("INSERT INTO notification (utilisateur_id, contenu, contenu_email, sujet_email, annonce_id) 
                               VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$utilisateur_id, $contenu, $contenu_email, $sujet_email, $annonce_id]);
    } catch (PDOException $e) {
        error_log("Erreur création notification: " . $e->getMessage());
        return false;
    }
}

// Fonction pour marquer une notification comme lue
function marquerNotificationLue($id) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE notification SET lue = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Erreur marquage notification lue: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer les notifications d'un utilisateur
function getNotificationsUtilisateur($utilisateur_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM notification 
                               WHERE utilisateur_id = ? 
                               ORDER BY date_creation DESC");
        $stmt->execute([$utilisateur_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur récupération notifications: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer le nombre de notifications non lues
function getNombreNotificationsNonLues($utilisateur_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count 
                               FROM notification 
                               WHERE utilisateur_id = ? AND lue = 0");
        $stmt->execute([$utilisateur_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    } catch (PDOException $e) {
        error_log("Erreur comptage notifications non lues: " . $e->getMessage());
        return false;
    }
}

// Fonction pour envoyer une notification par email
function envoyerNotificationEmail($email, $sujet, $contenu) {
    // Configuration des en-têtes
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: MiniLoc <noreply@miniloc.com>' . "\r\n";

    // Envoi de l'email
    return mail($email, $sujet, $contenu, $headers);
}

// Fonction pour notifier les clients intéressés d'une nouvelle annonce
function notifierClientsNouvelleAnnonce($annonce_id) {
    global $conn;
    try {
        // Récupérer les informations de l'annonce
        $stmt = $conn->prepare("SELECT a.*, o.nom as objet_nom, u.email as proprietaire_email 
                               FROM annonce a 
                               JOIN objet o ON a.objet_id = o.id 
                               JOIN utilisateur u ON a.proprietaire_id = u.id 
                               WHERE a.id = ?");
        $stmt->execute([$annonce_id]);
        $annonce = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($annonce) {
            // Récupérer les clients qui ont choisi d'être notifiés
            $stmt = $conn->prepare("SELECT id, email FROM utilisateur WHERE est_client = 1");
            $stmt->execute();
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($clients as $client) {
                $contenu = "Une nouvelle annonce a été publiée : " . $annonce['objet_nom'];
                $contenu_email = "Bonjour,<br><br>Une nouvelle annonce a été publiée sur MiniLoc :<br><br>" .
                                "Objet : " . $annonce['objet_nom'] . "<br>" .
                                "Prix journalier : " . $annonce['prix_journalier'] . "€<br>" .
                                "Ville : " . $annonce['adress'] . "<br><br>" .
                                "Connectez-vous pour plus de détails !";
                
                creerNotification($client['id'], $contenu, $contenu_email, "Nouvelle annonce sur MiniLoc", $annonce_id);
                envoyerNotificationEmail($client['email'], "Nouvelle annonce sur MiniLoc", $contenu_email);
            }
        }
        return true;
    } catch (PDOException $e) {
        error_log("Erreur notification clients: " . $e->getMessage());
        return false;
    }
}
?> 