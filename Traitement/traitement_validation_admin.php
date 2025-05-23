<?php
session_start();
include_once '../BD/connexion.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

// Vérification de la connexion de l'admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../IHM/connexion_admin.php");
    exit();
}

// Récupération des paramètres
$action = $_GET['action'] ?? '';
$annonceId = $_GET['id'] ?? 0;

// Vérification de la validité de l'annonce
if (!$annonceId) {
    $_SESSION['message_error'] = "Identifiant d'annonce invalide.";
    header("Location: ../admin/tableau_de_bord_admin.php");
    exit();
}

// Fonction pour envoyer un e-mail avec PHPMailer
function sendEmail($destinataire, $sujet, $message, $nom, $prenom) {
    $mail = new PHPMailer(true);
    
    try {
        // Paramètres du serveur
        $mail->isSMTP();                                       // Utiliser SMTP
        $mail->Host       = 'smtp.gmail.com';                // Spécifiez le serveur SMTP
        $mail->SMTPAuth   = true;                              // Activer l'authentification SMTP
        $mail->Username   = 'tihami.yassmine@etu.uae.ac.ma';         // SMTP username
        $mail->Password   = 'jbbvokuenrhwrafe';              // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;    // Activer le cryptage TLS
        $mail->Port       = 587;                               // Port TCP pour se connecter

        // Destinataires
        $mail->setFrom('noreply@miniloc.com', 'Miniloc');
        $mail->addAddress($destinataire, $nom . ' ' . $prenom);

        // Contenu
        $mail->isHTML(true);                                
        $mail->Subject = $sujet;
        
        // Version HTML du message
        $htmlMessage = nl2br(htmlspecialchars($message));
        $mail->Body    = $htmlMessage;
        $mail->AltBody = $message;  // Version texte pour les clients qui ne supportent pas le HTML

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Journaliser l'erreur plutôt que de l'afficher
        error_log("Échec de l'envoi de l'e-mail. Erreur: {$mail->ErrorInfo}");
        return false;
    }
}

try {
    // Selon l'action demandée
    if ($action === 'valider') {
        // Mettre à jour la visibilité de l'annonce
        $sql = "UPDATE annonce SET visibility = 1 WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $annonceId]);
        
        // Récupérer les informations du partenaire pour l'email
        $sql = "SELECT u.id AS proprietaire_id, u.email, u.nom, u.prenom, o.nom AS objet_nom 
                FROM annonce a 
                JOIN utilisateur u ON a.proprietaire_id = u.id 
                JOIN objet o ON a.objet_id = o.id 
                WHERE a.id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $annonceId]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Envoyer un email de notification au partenaire
        if ($info) {
            $destinataire = $info['email'];
            $sujet = "Miniloc - Votre annonce a ete validee";
            $message = "Bonjour " . htmlspecialchars($info['prenom']) . " " . htmlspecialchars($info['nom']) . ",\n\n";
            $message .= "Nous sommes heureux de vous informer que votre annonce pour \"" . htmlspecialchars($info['objet_nom']) . "\" a été validée par notre équipe administrative.\n";
            $message .= "Votre annonce est maintenant visible par tous les utilisateurs de Miniloc.\n\n";
            $message .= "Merci de votre confiance,\n";
            $message .= "L'équipe Miniloc";
            
            // Envoi de l'email avec PHPMailer
            $emailSent = sendEmail($destinataire, $sujet, $message, $info['prenom'], $info['nom']);
            
            // Ajout d'une notification dans la base de données
            $sqlNotif = "INSERT INTO notification (contenu, contenu_email, sujet_email, utilisateur_id, annonce_id) 
                        VALUES (:contenu, :contenu_email, :sujet_email, :utilisateur_id, :annonce_id)";
            $stmtNotif = $conn->prepare($sqlNotif);
            $stmtNotif->execute([
                ':contenu' => "Votre annonce \"" . $info['objet_nom'] . "\" a été validée",
                ':contenu_email' => $message,
                ':sujet_email' => $sujet,
                ':utilisateur_id' => $info['proprietaire_id'],
                ':annonce_id' => $annonceId
            ]);
        }
        
        $_SESSION['message_success'] = "L'annonce a été validée avec succès.";
        
    } elseif ($action === 'supprimer') {
        // 1. Récupérer les informations de l'annonce et de l'objet avant suppression
        $sql = "SELECT a.id AS annonce_id, a.objet_id, o.id AS objet_id, o.nom AS objet_nom, 
                       u.id AS proprietaire_id, u.email, u.nom, u.prenom
                FROM annonce a 
                JOIN utilisateur u ON a.proprietaire_id = u.id 
                JOIN objet o ON a.objet_id = o.id 
                WHERE a.id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $annonceId]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($info) {
            // Début de la transaction
            $conn->beginTransaction();
            
            try {
                // 2. Supprimer les références à l'annonce dans les notifications
                $sqlUpdateNotif = "UPDATE notification SET annonce_id = NULL WHERE annonce_id = :annonce_id";
                $stmtUpdateNotif = $conn->prepare($sqlUpdateNotif);
                $stmtUpdateNotif->execute([':annonce_id' => $annonceId]);
                
                // 3. Supprimer les réservations liées à l'annonce
                // Note: Si vous avez des contraintes de clé étrangère, vous devrez peut-être 
                // supprimer d'autres tables qui font référence aux réservations (comme les évaluations)
                $sqlCheckReservations = "SELECT id FROM reservation WHERE annonce_id = :annonce_id";
                $stmtCheckReservations = $conn->prepare($sqlCheckReservations);
                $stmtCheckReservations->execute([':annonce_id' => $annonceId]);
                $reservations = $stmtCheckReservations->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($reservations as $reservation) {
                    // Supprimer les évaluations liées à cette réservation
                    $sqlDeleteEvaluations = "DELETE FROM evaluation WHERE reservation_id = :reservation_id";
                    $stmtDeleteEvaluations = $conn->prepare($sqlDeleteEvaluations);
                    $stmtDeleteEvaluations->execute([':reservation_id' => $reservation['id']]);
                    
                    // Supprimer les réclamations liées à cette réservation
                    $sqlDeleteReclamations = "DELETE FROM reclamation WHERE reservation_id = :reservation_id";
                    $stmtDeleteReclamations = $conn->prepare($sqlDeleteReclamations);
                    $stmtDeleteReclamations->execute([':reservation_id' => $reservation['id']]);
                }
                
                // Maintenant supprimer les réservations
                $sqlDeleteReservations = "DELETE FROM reservation WHERE annonce_id = :annonce_id";
                $stmtDeleteReservations = $conn->prepare($sqlDeleteReservations);
                $stmtDeleteReservations->execute([':annonce_id' => $annonceId]);
                
                // 4. Supprimer l'annonce
                $sqlDeleteAnnonce = "DELETE FROM annonce WHERE id = :id";
                $stmtDeleteAnnonce = $conn->prepare($sqlDeleteAnnonce);
                $stmtDeleteAnnonce->execute([':id' => $annonceId]);
                
                // 5. Supprimer les images associées à l'objet
                $sqlDeleteImages = "DELETE FROM image WHERE objet_id = :objet_id";
                $stmtDeleteImages = $conn->prepare($sqlDeleteImages);
                $stmtDeleteImages->execute([':objet_id' => $info['objet_id']]);
                
                // 6. Supprimer l'objet
                $sqlDeleteObjet = "DELETE FROM objet WHERE id = :id";
                $stmtDeleteObjet = $conn->prepare($sqlDeleteObjet);
                $stmtDeleteObjet->execute([':id' => $info['objet_id']]);
                
                // Valider la transaction
                $conn->commit();
                
                // 7. Envoyer un email de notification au partenaire
                $destinataire = $info['email'];
                $sujet = "Miniloc - Votre annonce a été supprimée";
                $message = "Bonjour " . htmlspecialchars($info['prenom']) . " " . htmlspecialchars($info['nom']) . ",\n\n";
                $message .= "Nous vous informons que votre annonce pour \"" . htmlspecialchars($info['objet_nom']) . "\" a été supprimée par notre équipe administrative.\n";
                $message .= "Si vous avez des questions concernant cette décision, n'hésitez pas à nous contacter.\n\n";
                $message .= "Cordialement,\n";
                $message .= "L'équipe Miniloc";
                
                // Envoi de l'email avec PHPMailer
                $emailSent = sendEmail($destinataire, $sujet, $message, $info['prenom'], $info['nom']);
                
                // Ajout d'une notification dans la base de données
                $sqlNotif = "INSERT INTO notification (contenu, contenu_email, sujet_email, utilisateur_id, annonce_id) 
                            VALUES (:contenu, :contenu_email, :sujet_email, :utilisateur_id, :annonce_id)";
                $stmtNotif = $conn->prepare($sqlNotif);
                $stmtNotif->execute([
                    ':contenu' => "Votre annonce \"" . $info['objet_nom'] . "\" a été supprimée",
                    ':contenu_email' => $message,
                    ':sujet_email' => $sujet,
                    ':utilisateur_id' => $info['proprietaire_id'],
                    ':annonce_id' => null // L'annonce n'existe plus
                ]);
                
                $_SESSION['message_success'] = "L'annonce, l'objet et les images associées ont été supprimés avec succès.";
                
            } catch (PDOException $e) {
                // En cas d'erreur, annuler la transaction
                $conn->rollBack();
                throw $e; // Propager l'exception pour la capture globale
            }
        } else {
            $_SESSION['message_error'] = "Annonce introuvable.";
        }
        
    } else {
        $_SESSION['message_error'] = "Action non reconnue.";
    }
    
} catch (PDOException $e) {
    $_SESSION['message_error'] = "Erreur : " . $e->getMessage();
}

// Redirection vers la page d'administration
header("Location: ../admin/tableau_de_bord_admin.php");
exit();
?>