<?php
session_start();
require '../BD/connexion.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
header('Content-Type: application/json');

try {
    // Vérification de l'authentification
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'proprietaire') {
        throw new Exception("Accès refusé : connexion requise");
    }

    // Vérification du nombre d'annonces actives
    $stmt = $conn->prepare("SELECT COUNT(*) as nombre_annonces FROM annonce WHERE proprietaire_id = ? AND statut = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['nombre_annonces'] >= 5) {
        throw new Exception("Vous avez atteint la limite de 5 annonces actives simultanées. Veuillez désactiver une annonce existante avant d'en créer une nouvelle.");
    }

    // Validation des données
    $required = ['objet_id', 'titre', 'date_debut', 'date_fin', 'adress'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Le champ '$field' est obligatoire");
        }
    }

    // Récupération des données
    $objet_id = htmlspecialchars($_POST['objet_id']);
    $titre = htmlspecialchars($_POST['titre']);
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $adresse = htmlspecialchars($_POST['adress']);
    $premium = isset($_POST['premium']) ? 1 : 0;
    $premium_start = null;
    $premium_end = null;

    // Vérification de la propriété de l'objet et récupération des informations
    $stmt = $conn->prepare("SELECT * FROM objet WHERE id = ? AND proprietaire_id = ?");
    $stmt->execute([$objet_id, $_SESSION['user_id']]);
    $objet = $stmt->fetch();
    
    if (!$objet) {
        throw new Exception("Cet objet ne vous appartient pas");
    }

    // Gestion des options premium
    if ($premium) {
        if (empty($_POST['date_debut_premium']) || empty($_POST['duree_premium'])) {
            throw new Exception("Configuration premium incomplète");
        }
        
        $premium_start = $_POST['date_debut_premium'];
        $duree = intval($_POST['duree_premium']);
        $premium_end = date('Y-m-d', strtotime($premium_start . " + $duree days"));
        
        if ($premium_start < $date_debut) {
            throw new Exception("La période premium doit commencer après le début de la location");
        }
    }

    $stmt = $conn->prepare("
            INSERT INTO annonce (
                objet_id, proprietaire_id, date_debut, date_fin, adress,
                premium, date_debut_premium, duree_premium, 
                date_publication, statut
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'active')
        ");
        
    

    $success = $stmt->execute([
        $objet_id,
        $_SESSION['user_id'],
     
        $date_debut,
        $date_fin,
        $adresse,
        $premium,
        $premium_start,
        $duree
    ]);

    if (!$success) {
        throw new Exception("Erreur lors de l'enregistrement : " . implode(" ", $stmt->errorInfo()));
    }

    // Récupération des informations pour la notification
    $stmt = $conn->prepare("
    SELECT 
        o.nom, 
        o.description, 
        o.ville,
        o.prix_journalier,
        i.url AS image_url
    FROM objet o
    LEFT JOIN image i ON o.id = i.objet_id
    WHERE o.id = ?
    ORDER BY i.id ASC
    LIMIT 1
");
$stmt->execute([$objet_id]);
$objet = $stmt->fetch();
$image_url = $objet['image_url'] ?? 'https://via.placeholder.com/600x400.png?text=Image+Non+Disponible';
    // Envoi des emails aux abonnés
    $stmt = $conn->query("SELECT email FROM utilisateur WHERE activate_notification = 1");
    $recipients = $stmt->fetchAll();

    foreach ($recipients as $user) {
        try {
            $mail = new PHPMailer(true);
            
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'rahali.chaimaa@etu.uae.ac.ma'; // Remplacer
            $mail->Password = 'fzsmrfmbluqbfdcf'; // Remplacer
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            // Destinataires
            $mail->setFrom('no-reply@miniloc.com', 'MiniLoc');
            $mail->addAddress($user['email']);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = "🌟 Nouvelle annonce exclusive : " . $objet['nom'];
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
                <div style='background: #f8f9fa; padding: 20px; text-align: center;'>
                    <div style='font-size: 24px; font-weight: bold; color: #e91e63; margin-right: 40px; display: inline-block;'>
                        👶 MiniLoc
                    </div>
                </div>
        
                <div style='padding: 30px;'>
                    <h1 style='color: #2d3436; margin-bottom: 25px;'>{$objet['nom']}</h1>
        
                    <div style='background: #fff4e6; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                        <h3 style='color: #e17055; margin-top: 0;'>📌 Détails clés :</h3>
                        <p style='margin: 10px 0;'>
                            <strong style='color: #2d3436;'>📍 Localisation :</strong> 
                            <span style='color: #636e72;'>{$objet['ville']}</span>
                        </p>
                        <p style='margin: 10px 0;'>
                            <strong style='color: #2d3436;'>💶 Prix/jour  :</strong> 
                            <span style='color: #e84393; font-size: 1.2em;'>{$objet['prix_journalier']}DH</span>
                        </p>
                        <p style='margin: 10px 0;'>
                            <strong style='color: #2d3436;'>📅 Disponibilité :</strong> 
                            <span style='color: #636e72;'>" . date('d/m/Y', strtotime($date_debut)) . " - " . date('d/m/Y', strtotime($date_fin)) . "</span>
                        </p>
                    </div>
        
                    <div style='background: #dff9fb; padding: 20px; border-radius: 8px;'>
                        <h3 style='color: #0984e3; margin-top: 0;'>📝 Description :</h3>
                        <p style='color: #636e72; line-height: 1.6;'>{$objet['description']}</p>
                    </div>
        
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href=# 
                           style='background: #6c5ce7; color: white; padding: 12px 25px; 
                                  text-decoration: none; border-radius: 25px; 
                                  display: inline-block; font-weight: bold;'>
                            🔍 Accede au site pour Voir l'annonce complète
                        </a>
                    </div>
                </div>
        
                <div style='background: #2d3436; color: white; padding: 20px; text-align: center;'>
                    <p style='margin: 5px 0;'>📧 Contact : contact@miniloc.com</p>
                    <p style='margin: 5px 0;'>📱 Tél : +33 1 23 45 67 89</p>
                </div>
            </div>
        ";
        
    
            $mail->AltBody = "Nouvelle annonce : ".$objet['nom']."\n".$objet['description']."\nPrix : ".$objet['prix_journalier']."€\nDisponible du ".$date_debut." au ".$date_fin;

            $mail->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi email à {$user['email']} : " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Annonce créée avec succès !',
        'redirect' => '../IHM/liste_annonces.php'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérifier si c'est une modification ou une création
        if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
            // Modification d'une annonce existante
            $annonce_id = $_POST['annonce_id'];
            $objet_id = $_POST['objet_id'];
            $date_debut = $_POST['date_debut'];
            $date_fin = $_POST['date_fin'];
            $adress = $_POST['adress'];
            $premium = isset($_POST['premium']) ? 1 : 0;
            $date_debut_premium = $premium ? $_POST['date_debut_premium'] : null;
            $duree_premium = $premium ? 30 : null; // Par défaut 30 jours si premium

            // Vérifier que l'annonce appartient bien au propriétaire
            $stmt = $conn->prepare("SELECT proprietaire_id FROM annonce WHERE id = ?");
            $stmt->execute([$annonce_id]);
            $result = $stmt->fetch();

            if (!$result || $result['proprietaire_id'] !== $_SESSION['user_id']) {
                throw new Exception("Vous n'êtes pas autorisé à modifier cette annonce.");
            }

            // Mettre à jour l'annonce
            $stmt = $conn->prepare("
                UPDATE annonce 
                SET date_debut = ?, 
                    date_fin = ?, 
                    adress = ?, 
                    premium = ?, 
                    date_debut_premium = ?, 
                    duree_premium = ?
                WHERE id = ? AND proprietaire_id = ?
            ");
            $stmt->execute([
                $date_debut, 
                $date_fin, 
                $adress,
                $premium, 
                $date_debut_premium, 
                $duree_premium,
                $annonce_id, 
                $_SESSION['user_id']
            ]);

            $_SESSION['success'] = "L'annonce a été modifiée avec succès.";
            header('Location: ../IHM/liste_annonces.php');
            exit();
        } else {
            // Création d'une nouvelle annonce
            // ... existing code ...
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

exit();