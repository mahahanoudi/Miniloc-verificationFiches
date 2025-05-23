<?php
session_start();
  // Envoi de la notification email au propriétaire
  require __DIR__ . '/PHPMailer/src/PHPMailer.php';
  require __DIR__ . '/PHPMailer/src/SMTP.php';
  require __DIR__ . '/PHPMailer/src/Exception.php';

  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour effectuer une réservation.";
    header('Location: ../IHM/connexion.php');
    exit;
}

// Vérifier que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../IHM/produits.php');
    exit;
}

include_once('../BD/connexion.php');

// Récupérer les données du formulaire
$annonce_id = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;
$date_debut = isset($_POST['date_debut']) ? $_POST['date_debut'] : '';
$date_fin = isset($_POST['date_fin']) ? $_POST['date_fin'] : '';
$option_de_livraison = isset($_POST['option_de_livraison']) ? $_POST['option_de_livraison'] : '';
$address_de_livraison = isset($_POST['address_de_livraison']) ? $_POST['address_de_livraison'] : '';
$client_id = $_SESSION['user_id'];

// Validation des données
if (!$annonce_id || !$date_debut || !$date_fin || !$option_de_livraison) {
    $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
    header('Location: ../IHM/formulaire_reservation.php?annonce_id=' . $annonce_id);
    exit;
}

// Vérifier que la date de début est avant la date de fin
if (strtotime($date_debut) > strtotime($date_fin)) {
    $_SESSION['error'] = "La date de début doit être antérieure à la date de fin.";
    header('Location: ../IHM/formulaire_reservation.php?annonce_id=' . $annonce_id);
    exit;
}

// Vérification de l'adresse si option de livraison à domicile
if ($option_de_livraison === 'domicile' && empty($address_de_livraison)) {
    $_SESSION['error'] = "L'adresse de livraison est obligatoire pour la livraison à domicile.";
    header('Location: ../IHM/formulaire_reservation.php?annonce_id=' . $annonce_id);
    exit;
}

try {
    // Vérifier que l'annonce existe et que l'objet associé est disponible (non_loue)
    $query = "SELECT a.*, o.nom as objet_nom, o.id as objet_id, o.etat as objet_etat, a.proprietaire_id 
              FROM annonce a 
              JOIN objet o ON a.objet_id = o.id 
              WHERE a.id = :annonce_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':annonce_id', $annonce_id, PDO::PARAM_INT);
    $stmt->execute();
    $annonce = $stmt->fetch();

    if (!$annonce) {
        $_SESSION['error'] = "L'annonce demandée n'existe pas.";
        header('Location: ../IHM/produits.php');
        exit;
    }

    if ($annonce['objet_etat'] !== 'non_loue') {
        $_SESSION['error'] = "Cet objet n'est pas disponible à la location actuellement.";
        header('Location: ../IHM/produits.php');
        exit;
    }

    // Vérifier que la période demandée est dans la plage de disponibilité de l'annonce
    if (strtotime($date_debut) < strtotime($annonce['date_debut']) || 
        strtotime($date_fin) > strtotime($annonce['date_fin'])) {
        $_SESSION['error'] = "Les dates sélectionnées ne sont pas dans la période de disponibilité de l'annonce.";
        header('Location: ../IHM/formulaire_reservation.php?annonce_id=' . $annonce_id);
        exit;
    }

    // Vérifier que la période n'est pas déjà réservée
    $query_check = "SELECT COUNT(*) FROM reservation 
                    WHERE annonce_id = :annonce_id 
                    AND statut != 'rejete'
                    AND (
                        (date_debut <= :date_debut AND date_fin >= :date_debut) OR
                        (date_debut <= :date_fin AND date_fin >= :date_fin) OR
                        (date_debut >= :date_debut AND date_fin <= :date_fin)
                    )";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bindParam(':annonce_id', $annonce_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':date_debut', $date_debut);
    $stmt_check->bindParam(':date_fin', $date_fin);
    $stmt_check->execute();
    
    if ($stmt_check->fetchColumn() > 0) {
        $_SESSION['error'] = "La période sélectionnée n'est pas disponible. Veuillez choisir d'autres dates.";
        header('Location: ../IHM/formulaire_reservation.php?annonce_id=' . $annonce_id);
        exit;
    }

    // Empêcher un propriétaire de réserver sa propre annonce
    if ($client_id == $annonce['proprietaire_id']) {
        $_SESSION['error'] = "Vous ne pouvez pas réserver votre propre annonce.";
        header('Location: ../IHM/produits.php');
        exit;
    }

    // Démarrer une transaction
    $conn->beginTransaction();

    // Insertion de la réservation
    $query_insert = "INSERT INTO reservation (client_id, annonce_id, date_debut, date_fin, statut, option_de_livraison, address_de_livraison) 
                     VALUES (:client_id, :annonce_id, :date_debut, :date_fin, 'en_attente', :option_de_livraison, :address_de_livraison)";
    $stmt_insert = $conn->prepare($query_insert);
    $stmt_insert->bindParam(':client_id', $client_id, PDO::PARAM_INT);
    $stmt_insert->bindParam(':annonce_id', $annonce_id, PDO::PARAM_INT);
    $stmt_insert->bindParam(':date_debut', $date_debut);
    $stmt_insert->bindParam(':date_fin', $date_fin);
    $stmt_insert->bindParam(':option_de_livraison', $option_de_livraison);
    $stmt_insert->bindParam(':address_de_livraison', $address_de_livraison);
    $stmt_insert->execute();
    
    $reservation_id = $conn->lastInsertId();
    
    $couvre_toute_periode = false;
    // on verifie d'abord si cette réservation couvre toute la période
    if (strtotime($date_debut) <= strtotime($annonce['date_debut']) && 
        strtotime($date_fin) >= strtotime($annonce['date_fin'])) {
        $couvre_toute_periode = true;
    } else {
        // 2. Sinon, vérifier si, combinée avec les autres réservations, toute la période est couverte
        // Récupérer toutes les réservations pour cette annonce (y compris la nouvelle)
        $query_all_reservations = "SELECT date_debut, date_fin 
                                  FROM reservation 
                                  WHERE annonce_id = :annonce_id 
                                  AND statut != 'annulee'";
        $stmt_all_reservations = $conn->prepare($query_all_reservations);
        $stmt_all_reservations->bindParam(':annonce_id', $annonce_id, PDO::PARAM_INT);
        $stmt_all_reservations->execute();
        $all_reservations = $stmt_all_reservations->fetchAll();
        
        // Vérifier si les réservations couvrent toute la période de l'annonce
        $date_debut_annonce = strtotime($annonce['date_debut']);
        $date_fin_annonce = strtotime($annonce['date_fin']);
        
        // Créer un tableau pour suivre les jours réservés
        $jours_reserves = array();
        $jour_debut = $date_debut_annonce;
        $jour_fin = $date_fin_annonce;
        
        // Pour chaque jour de la période de l'annonce, vérifier s'il est réservé
        for ($jour = $jour_debut; $jour <= $jour_fin; $jour += 86400) { // 86400 secondes = 1 jour
            $date_jour = date('Y-m-d', $jour);
            $jours_reserves[$date_jour] = false;
            
            // Vérifier si ce jour est couvert par une réservation
            foreach ($all_reservations as $reservation) {
                $res_debut = strtotime($reservation['date_debut']);
                $res_fin = strtotime($reservation['date_fin']);
                
                if ($jour >= $res_debut && $jour <= $res_fin) {
                    $jours_reserves[$date_jour] = true;
                    break;
                }
            }
        }
        
        // Vérifier si tous les jours sont réservés
        $all_days_reserved = true;
        foreach ($jours_reserves as $jour => $est_reserve) {
            if (!$est_reserve) {
                $all_days_reserved = false;
                break;
            }
        }
        
        if ($all_days_reserved) {
            $couvre_toute_periode = true;
        }
    }
    
    // Si toute la période est réservée, mettre à jour l'état de l'objet à "loue"
    if ($couvre_toute_periode) {
        $query_update_objet = "UPDATE objet SET etat = 'loue' WHERE id = :objet_id";
        $stmt_update_objet = $conn->prepare($query_update_objet);
        $stmt_update_objet->bindParam(':objet_id', $annonce['objet_id'], PDO::PARAM_INT);
        $stmt_update_objet->execute();
    }
    
    // Valider la transaction
    $conn->commit();
    
  $query_proprio = "SELECT email FROM utilisateur WHERE id = :proprietaire_id";
$stmt_proprio = $conn->prepare($query_proprio);
$stmt_proprio->bindParam(':proprietaire_id', $annonce['proprietaire_id'], PDO::PARAM_INT);
$stmt_proprio->execute();
$proprio = $stmt_proprio->fetch();

    try {
        $mail = new PHPMailer(true);
        
        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rahali.chaimaa@etu.uae.ac.ma'; 
        $mail->Password = 'fzsmrfmbluqbfdcf';
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
        
        $mail->setFrom('no-reply@miniloc.com', 'MiniLoc');
        $mail->addAddress($proprio['email']);
        
        $mail->isHTML(true);
        $mail->Subject = 'Nouvelle réservation pour votre objet ' . htmlspecialchars($annonce['objet_nom']);
        
        $body = "<p>Bonjour,</p>";
        $body .= "<p>Une nouvelle réservation a été effectuée pour votre objet <strong>" . htmlspecialchars($annonce['objet_nom']) . "</strong>.</p>";
        $body .= "<p><strong>Détails de la réservation :</strong></p>";
        $body .= "<ul>";
        $body .= "<li>Date de début : " . htmlspecialchars($date_debut) . "</li>";
        $body .= "<li>Date de fin : " . htmlspecialchars($date_fin) . "</li>";
        $body .= "<li>Option de livraison : " . htmlspecialchars($option_de_livraison) . "</li>";
        if ($option_de_livraison === 'domicile') {
            $body .= "<li>Adresse de livraison : " . nl2br(htmlspecialchars($address_de_livraison)) . "</li>";
        }
        $body .= "</ul>";
        $body .= "<p>Veuillez vous connecter à votre compte pour voir les détails et valider cette réservation.</p>";
        $body .= "<p>Merci,<br>L'équipe MiniLoc</p>";
        
        $mail->Body = $body;
        
        $mail->send();
        
    } catch (Exception $e) {
        error_log("Erreur envoi mail réservation: " . $mail->ErrorInfo);
        // On ne bloque pas la suite si l'email ne part pas
    }

     // Ajouter un message de succès et rediriger
    $_SESSION['success'] = "Votre réservation a été confirmée avec succès ! Elle est maintenant en attente de validation par le propriétaire.";
    header('Location: ../IHM/formulaire_reservation.php?annonce_id=' . $annonce_id);
    exit;

} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log l'erreur et afficher un message générique
    error_log("ERREUR RÉSERVATION: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la réservation. Veuillez réessayer plus tard.";
    header('Location: ../IHM/formulaire_reservation.php?annonce_id=' . $annonce_id);
    exit;
}
?>
