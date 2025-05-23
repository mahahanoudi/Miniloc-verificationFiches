<?php
session_start();
require_once('../BD/connexion.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $objet_id = intval($_POST['objet_id']);
    $noteObjet = isset($_SESSION['note']) ? $_SESSION['note'] : null;
    $client_id = intval($_POST['client_id']);
    $note = intval($_POST['noteComment']);
    $commentaire = trim($_POST['commentaire']);
    $annonce_id = isset($_GET['id_annonce']) ? intval($_GET['id_annonce']) : null;

   
   

//var_dump($objet_id, $client_id, $annonce_id,$note); exit;
    // 1. Vérifier que le client a une réservation terminée pour cet objet
    $sql = "SELECT r.id AS reservation_id, r.date_fin, a.proprietaire_id AS partenaire
        FROM reservation r 
        JOIN annonce a ON r.annonce_id = a.id 
        WHERE r.client_id = ? AND a.objet_id = ? AND r.statut = 'terminee'
        LIMIT 1";


    $stmt = $conn->prepare($sql);
    $stmt->execute([$client_id, $objet_id]);
    $reservation = $stmt->fetch();

    
    if ($reservation) {
        $reservation_id = $reservation['reservation_id'];
        $date_fin = new DateTime($reservation['date_fin']);
        $partenaire = $reservation['partenaire'];
        $today = new DateTime();

        $interval = $date_fin->diff($today)->days;

        if ($today < $date_fin) {
            $_SESSION['message'] = "La réservation n'est pas encore terminée.";
            header("Location: ../IHM/detailsAnnonce.php?id=" . $annonce_id . "&note=" . urlencode($noteObjet));


            exit;
        }

        if ($interval < 7) {
            $_SESSION['message'] = "Vous devez attendre 7 jours après la fin de la réservation pour laisser un commentaire.Vous pouvez ajouter vos commentaires en répondant au formilaire d'évaluation dans votre boite email";
            header("Location: ../IHM/detailsAnnonce.php?id=" . $annonce_id . "&note=" . urlencode($noteObjet));


            exit;
        }

        // 2. Vérifier s’il n’a pas déjà commenté
        $stmt = $conn->prepare("SELECT COUNT(*) FROM evaluation WHERE reservation_id = ? AND evaluateur_id = ?");
        $stmt->execute([$reservation_id, $client_id]);
        $dejaCommente = $stmt->fetchColumn();

        if ($dejaCommente == 0) {
            // 3. Ajouter le commentaire
            $stmt = $conn->prepare("INSERT INTO evaluation (evalue_id, evaluateur_id, note, commentaire, date, reservation_id, objet_id)
                        VALUES (?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->execute([$partenaire, $client_id, $note, $commentaire, $reservation_id, $objet_id]);

            $_SESSION['message'] = "Commentaire ajouté avec succès.";
        } else {
            $_SESSION['message'] = "Vous avez déjà laissé un commentaire pour cet objet.";
        }
    } else {
        $_SESSION['message'] = "Vous ne pouvez pas commenter ce produit car vous n'avez aucune réservation terminée pour ce produit.";
    }

    header("Location: ../IHM/detailsAnnonce.php?id=" . $annonce_id . "&note=" . urlencode($noteObjet));


    exit;
} else {
    $_SESSION['message'] = "Méthode non autorisée.";
    header("Location:../IHM/detailsAnnonce.php?id=" . $_GET['id_annonce']);
    exit;
}
?>
