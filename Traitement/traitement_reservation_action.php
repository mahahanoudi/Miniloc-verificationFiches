<?php
session_start();
include_once('../BD/connexion.php');
if (!isset($_SESSION['user_id'])) {
    header('Location: ../IHM/connexion.php');
    exit;
}
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['action'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];

    // Récupérer la réservation avec infos nécessaires
    $query = "SELECT 
    r.*, 
    a.proprietaire_id, 
    o.nom AS objet_nom, 
    o.description AS objet_description,
    o.ville AS objet_ville,
    o.prix_journalier AS objet_prix_journalier,
    u.email AS client_email, 
    u.nom AS client_nom, 
    u.prenom AS client_prenom, 
    u.CIN AS client_cin, 
    u.address AS client_address,
    p.email AS proprio_email
FROM reservation r
JOIN annonce a ON r.annonce_id = a.id
JOIN objet o ON a.objet_id = o.id
JOIN utilisateur u ON r.client_id = u.id
JOIN utilisateur p ON a.proprietaire_id = p.id
WHERE r.id = :id
";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
    $stmt->execute();
    $res = $stmt->fetch();

    if (!$res) {
        $_SESSION['error'] = "Réservation introuvable.";
        header('Location: ../IHM/mes_annonces.php');
        exit;
    }

    // Vérifier que l'utilisateur est bien le propriétaire
    if ($res['proprietaire_id'] != $user_id) {
        $_SESSION['error'] = "Action non autorisée.";
        header('Location: ../IHM/mes_annonces.php');
        exit;
    }

    $today = date('Y-m-d');

    if ($action === 'confirmer' && $res['statut'] === 'en_attente') {
        $update = $conn->prepare("UPDATE reservation SET statut = 'confirmee' WHERE id = :id");
        $update->bindParam(':id', $reservation_id, PDO::PARAM_INT);
        $update->execute();
        $_SESSION['success'] = "Réservation confirmée avec succès.";
    
        try {
            // Email to Propriétaire (Partner) with Client Details and Rental Info
            $mailProprio = new PHPMailer(true);
            $mailProprio->isSMTP();
            $mailProprio->Host = 'smtp.gmail.com';
            $mailProprio->SMTPAuth = true;
            $mailProprio->Username = 'rahali.chaimaa@etu.uae.ac.ma';
            $mailProprio->Password = 'fzsmrfmbluqbfdcf';
            $mailProprio->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailProprio->Port = 587;
            $mailProprio->CharSet = 'UTF-8';
            $mailProprio->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            $mailProprio->setFrom('no-reply@miniloc.com', 'MiniLoc');
            $mailProprio->addAddress($res['proprio_email']);
            $mailProprio->isHTML(true);
            $mailProprio->Subject = 'Détails du Client et de la Location Confirmée';
    
            $proprioBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
                <div style='background: #f8f9fa; padding: 20px; text-align: center;'>
                    <span style='font-size: 24px; font-weight: bold; color: #6c5ce7;'>👶MiniLoc </span>
                </div>
                <div style='padding: 30px;'>
                    <h2 style='color: #2d3436; margin-bottom: 20px;'>✅ Réservation Confirmée</h2>
                    <div style='background: #dff9fb; padding: 18px; border-radius: 8px; margin-bottom: 25px;'>
                        <h3 style='color: #0984e3; margin-top: 0;'>👤 Informations du Client</h3>
                        <p style='margin: 8px 0;'><strong>Nom complet :</strong> " . htmlspecialchars($res['client_nom']) . " " . htmlspecialchars($res['client_prenom']) . "</p>
                        <p style='margin: 8px 0;'><strong>CIN :</strong> " . htmlspecialchars($res['client_cin']) . "</p>
                        <p style='margin: 8px 0;'><strong>Adresse :</strong> " . htmlspecialchars($res['client_address']) . "</p>
                        <p style='margin: 8px 0;'><strong>Email :</strong> <a href='mailto:" . htmlspecialchars($res['client_email']) . "' style='color: #6c5ce7;'>" . htmlspecialchars($res['client_email']) . "</a></p>
                    </div>
                    <div style='background: #fff4e6; padding: 18px; border-radius: 8px; margin-bottom: 25px;'>
                        <h3 style='color: #e17055; margin-top: 0;'>📦 Détails de la Location</h3>
                        <p style='margin: 8px 0;'><strong>Objet :</strong> " . htmlspecialchars($res['objet_nom']) . "</p>
                         <p style='margin: 8px 0;'><strong>Description :</strong> " . nl2br(htmlspecialchars($res['objet_description'])) . "</p>
            <p style='margin: 8px 0;'><strong>Prix/jour :</strong> <span style='color: #e84393; font-weight: bold;'>" . htmlspecialchars($res['objet_prix_journalier']) . " DH</span></p>
                        <p style='margin: 8px 0;'><strong>Date de Début :</strong> " . date('d/m/Y', strtotime($res['date_debut'])) . "</p>
                        <p style='margin: 8px 0;'><strong>Date de Fin :</strong> " . date('d/m/Y', strtotime($res['date_fin'])) . "</p>
                    </div>
                    <div style='margin-top: 30px; text-align: center;'>
                        <span style='color: #636e72;'>Merci de votre confiance,<br>L'équipe <b>MiniLoc</b></span>
                    </div>
                </div>
                <div style='background: #2d3436; color: white; padding: 16px; text-align: center;'>
                    <p style='margin: 0;'>📧 contact@miniloc.com &nbsp; | &nbsp; 📱 +33 1 23 45 67 89</p>
                </div>
            </div>
            ";
            
            $mailProprio->Body = $proprioBody;
            $mailProprio->send();
    
            // Email to Client Confirming Reservation
            $mailClient = new PHPMailer(true);
            $mailClient->isSMTP();
            $mailClient->Host = 'smtp.gmail.com';
            $mailClient->SMTPAuth = true;
            $mailClient->Username = 'rahali.chaimaa@etu.uae.ac.ma';
            $mailClient->Password = 'fzsmrfmbluqbfdcf';
            $mailClient->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailClient->Port = 587;
            $mailClient->CharSet = 'UTF-8';
            $mailClient->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            $mailClient->setFrom('no-reply@miniloc.com', 'MiniLoc');
            $mailClient->addAddress($res['client_email']);
            $mailClient->isHTML(true);
            $mailClient->Subject = 'Confirmation de Votre Réservation';
    
            $clientBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
                <div style='background: #f8f9fa; padding: 20px; text-align: center;'>
                    <span style='font-size: 24px; font-weight: bold; color: #e84393;'>👶MiniLoc 🎉</span>
                </div>
                <div style='padding: 30px;'>
                    <h2 style='color: #2d3436; margin-bottom: 20px;'>Votre réservation est confirmée !</h2>
                    <div style='background: #fff4e6; padding: 18px; border-radius: 8px; margin-bottom: 25px;'>
                        <h3 style='color: #e17055; margin-top: 0;'>📦 Détails de la Location</h3>
                        <p style='margin: 8px 0;'><strong>Objet :</strong> " . htmlspecialchars($res['objet_nom']) . "</p>
                        <p style='margin: 8px 0;'><strong>Description :</strong> " . nl2br(htmlspecialchars($res['objet_description'])) . "</p>
            <p style='margin: 8px 0;'><strong>Prix /jour:</strong> <span style='color: #e84393; font-weight: bold;'>" . htmlspecialchars($res['objet_prix_journalier']) . " DH</span></p>
                        <p style='margin: 8px 0;'><strong>Dates :</strong> du " . date('d/m/Y', strtotime($res['date_debut'])) . " au " . date('d/m/Y', strtotime($res['date_fin'])) . "</p>
                    </div>
                    <div style='margin-top: 30px; text-align: center;'>
                        <span style='color: #636e72;'>Merci d'avoir choisi <b>MiniLoc</b> !<br>Nous vous souhaitons une excellente expérience.</span>
                    </div>
                </div>
                <div style='background: #2d3436; color: white; padding: 16px; text-align: center;'>
                    <p style='margin: 0;'>📧 contact@miniloc.com &nbsp; | &nbsp; 📱 +33 1 23 45 67 89</p>
                </div>
            </div>
            ";
            
            $mailClient->Body = $clientBody;
            $mailClient->send();
    
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de l'envoi des emails : " . $e->getMessage();
        }
    }

    elseif ($action === 'rejeter' && $res['statut'] === 'en_attente') {
        // Récupérer le message de justification envoyé par le propriétaire
        $message_rejet = isset($_POST['message_rejet']) ? trim($_POST['message_rejet']) : '';

        if (empty($message_rejet)) {
            $_SESSION['error'] = "Veuillez fournir un message de justification pour le rejet.";
            header('Location: ../IHM/mes_annonces.php');
            exit;
        }

        // Mettre à jour le statut en 'rejete' et enregistrer le message si vous avez une colonne pour cela
        $update = $conn->prepare("UPDATE reservation SET statut = 'rejete' WHERE id = :id");
        $update->bindParam(':id', $reservation_id, PDO::PARAM_INT);
        $update->execute();

        // Envoi de l'email au client avec le message de rejet
        try {
            $mailClient = new PHPMailer(true);
            $mailClient->isSMTP();
            $mailClient->Host = 'smtp.gmail.com';
            $mailClient->SMTPAuth = true;
            $mailClient->Username = 'rahali.chaimaa@etu.uae.ac.ma';
            $mailClient->Password = 'fzsmrfmbluqbfdcf';
            $mailClient->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailClient->Port = 587;
            $mailClient->CharSet = 'UTF-8';
            $mailClient->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mailClient->setFrom('no-reply@miniloc.com', 'MiniLoc');
            $mailClient->addAddress($res['client_email']);
            $mailClient->isHTML(true);
            $mailClient->Subject = 'Réservation Rejetée - Justification';

            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
                <div style='background: #f8f9fa; padding: 20px; text-align: center;'>
                    <span style='font-size: 24px; font-weight: bold; color: #e17055;'>👶MiniLoc</span>
                </div>
                <div style='padding: 30px;'>
                    <h2 style='color: #d63031; margin-bottom: 20px;'>❌ Réservation Rejetée</h2>
                    <div style='background: #ffe6e6; padding: 18px; border-radius: 8px; margin-bottom: 25px;'>
                        <h3 style='color: #d63031; margin-top: 0;'>Message du propriétaire</h3>
                        <p style='margin: 8px 0; font-style: italic;'>" . nl2br(htmlspecialchars($message_rejet)) . "</p>
                    </div>
                    <div style='background: #fff4e6; padding: 18px; border-radius: 8px; margin-bottom: 25px;'>
                        <h3 style='color: #e17055; margin-top: 0;'>📦 Détails de la Location</h3>
                        <p><strong>Objet :</strong> " . htmlspecialchars($res['objet_nom']) . "</p>
                        <p><strong>Description :</strong> " . nl2br(htmlspecialchars($res['objet_description'])) . "</p>
                        <p><strong>Prix /jour :</strong> <span style='color: #e84393; font-weight: bold;'>" . htmlspecialchars($res['objet_prix_journalier']) . " DH</span></p>
                        <p><strong>Dates :</strong> du " . date('d/m/Y', strtotime($res['date_debut'])) . " au " . date('d/m/Y', strtotime($res['date_fin'])) . "</p>
                    </div>
                    <div style='margin-top: 30px; text-align: center;'>
                        <span style='color: #636e72;'>Nous restons à votre disposition pour toute question.<br>L'équipe <b>MiniLoc</b></span>
                    </div>
                </div>
                <div style='background: #2d3436; color: white; padding: 16px; text-align: center;'>
                    <p style='margin: 0;'>📧 contact@miniloc.com &nbsp; | &nbsp; 📱 +33 1 23 45 67 89</p>
                </div>
            </div>
            ";

            $mailClient->Body = $body;
            $mailClient->send();

            $_SESSION['success'] = "Réservation rejetée et message envoyé au client.";

        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de l'envoi de l'email au client : " . $e->getMessage();
        }
    } elseif ($action === 'terminer' && $res['statut'] === 'confirmee' && $res['date_fin'] <= $today) {
        // Mettre à jour le statut en terminée
        $update = $conn->prepare("UPDATE reservation SET statut = 'terminee' WHERE id = :id");
        $update->bindParam(':id', $reservation_id, PDO::PARAM_INT);
        $update->execute();

        // Envoi des emails d'avis

        try {
            // PHPMailer configuration commune
            $mailClient = new PHPMailer(true);
            $mailClient->isSMTP();
            $mailClient->Host = 'smtp.gmail.com';
            $mailClient->SMTPAuth = true;
            $mailClient->Username = 'rahali.chaimaa@etu.uae.ac.ma';
            $mailClient->Password = 'fzsmrfmbluqbfdcf';
            $mailClient->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailClient->Port = 587;
            $mailClient->CharSet = 'UTF-8';
            $mailClient->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Email client
            $mailClient->setFrom('no-reply@miniloc.com', 'MiniLoc');
            $mailClient->addAddress($res['client_email']);
            $mailClient->isHTML(true);
            $lienClient = "http://localhost/Miniloc-verificationFiches/IHM/formulaire_avis_client.php?reservation_id=" . $reservation_id;
             $lienClient_prop = "http://localhost/Miniloc-verificationFiches/IHM/formulaire_avis_client_sur_proprietaire.php?reservation_id=" . $reservation_id;
            $mailClient->Subject = "Merci pour votre location ! Donnez votre avis";
            $mailClient->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
                <div style='background: #f8f9fa; padding: 20px; text-align: center;'>
                    <span style='font-size: 24px; font-weight: bold; color: #e17055;'>👶MiniLoc</span>
                </div>
                <div style='padding: 30px;'>
                    <h2 style='color: #0984e3; margin-bottom: 20px;'>✅ Fin de Location</h2>
                    <div style='background: #dfe6e9; padding: 18px; border-radius: 8px; margin-bottom: 25px;'>
                        <p>Bonjour,</p>
                        <p>Votre location de l'objet <strong>" . htmlspecialchars($res['objet_nom']) . "</strong> est terminée.</p>
                        <p>Merci de remplir le formulaire ci-dessous pour partager votre expérience :</p>
                        <div style='margin: 20px 0; text-align: center;'>
                            <a href='$lienClient' style='background-color: #6c5ce7; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Partager votre avis_sur_objet</a>
                        </div>
                          <div style='margin: 20px 0; text-align: center;'>
                            <a href='$lienClient_prop' style='background-color: #6c5ce7; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Partager votre avis sur propriteire </a>
                        </div>
                    </div>
                    <div style='margin-top: 30px; text-align: center;'>
                        <span style='color: #636e72;'>Merci d’avoir utilisé notre service.<br>L'équipe <b>MiniLoc</b></span>
                    </div>
                </div>
                <div style='background: #2d3436; color: white; padding: 16px; text-align: center;'>
                    <p style='margin: 0;'>📧 contact@miniloc.com &nbsp; | &nbsp; 📱 +33 1 23 45 67 89</p>
                </div>
            </div>
            ";
            
            $mailClient->send();

            // Email propriétaire
            $mailProprio = new PHPMailer(true);
            $mailProprio->isSMTP();
            $mailProprio->Host = 'smtp.gmail.com';
            $mailProprio->SMTPAuth = true;
            $mailProprio->Username = 'rahali.chaimaa@etu.uae.ac.ma';
            $mailProprio->Password = 'fzsmrfmbluqbfdcf';
            $mailProprio->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailProprio->Port = 587;
            $mailProprio->CharSet = 'UTF-8';
            $mailProprio->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            $mailProprio->setFrom('no-reply@miniloc.com', 'MiniLoc');
            $mailProprio->addAddress($res['proprio_email']);
            $mailProprio->isHTML(true);
            $lienProprio = "http://localhost/Miniloc-verificationFiches/IHM/formulaire_avis_proprio.php?reservation_id=" . $reservation_id;
            $mailProprio->Subject = "Votre objet a été rendu ! Donnez votre retour";
            $mailProprio->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
                <div style='background: #f8f9fa; padding: 20px; text-align: center;'>
                    <span style='font-size: 24px; font-weight: bold; color: #e17055;'>👶MiniLoc</span>
                </div>
                <div style='padding: 30px;'>
                    <h2 style='color: #0984e3; margin-bottom: 20px;'>✅ Fin de Location</h2>
                    <div style='background: #dfe6e9; padding: 18px; border-radius: 8px; margin-bottom: 25px;'>
                        <p>Bonjour,</p>
                        <p>La période de location de votre objet <strong>" . htmlspecialchars($res['objet_nom']) . "</strong> est terminée.</p>
                        <p>Merci de remplir le formulaire ci-dessous pour donner votre retour sur le client :</p>
                        <div style='margin: 20px 0; text-align: center;'>
                            <a href='$lienProprio' style='background-color: #00cec9; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Donner votre avis</a>
                        </div>
                    </div>
                    <div style='margin-top: 30px; text-align: center;'>
                        <span style='color: #636e72;'>Merci pour votre confiance.<br>L'équipe <b>MiniLoc</b></span>
                    </div>
                </div>
                <div style='background: #2d3436; color: white; padding: 16px; text-align: center;'>
                    <p style='margin: 0;'>📧 contact@miniloc.com &nbsp; | &nbsp; 📱 +33 1 23 45 67 89</p>
                </div>
            </div>
            ";
            
            $mailProprio->send();

            $_SESSION['success'] = "Réservation terminée et formulaires envoyés.";

        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de l'envoi des emails : " . $e->getMessage();
        }

    } else {
        $_SESSION['error'] = "Action non autorisée ou réservation non éligible.";
    }
} else {
    $_SESSION['error'] = "Requête invalide.";
}

header('Location: ../IHM/mes_annonces.php');
exit;