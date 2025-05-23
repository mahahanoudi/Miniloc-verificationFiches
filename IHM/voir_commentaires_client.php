<?php
include('../Traitement/traitement_commentaires.php');
$user_id = 2;
$commentaires = getCommentairesSurClient($user_id);
$user=getClientInfo($user_id);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modal Client</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<a href="#" data-bs-toggle="modal" data-bs-target="#partenaireModal">Voir le profil du partenaire</a>

<div class="modal fade" id="partenaireModal" tabindex="-1" aria-labelledby="partenaireModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="partenaireModalLabel">Fiche du client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <img src="<?= !empty($user['img_profil']) ? htmlspecialchars($user['img_profil']) : '../photos/default-profile.jpg' ?>" 
        alt="Profil client" 
        class="rounded-circle mb-3 d-block mx-auto" 
        style="width: 120px; height: 120px; object-fit: cover;">
        <h6 class="text-center"><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></h6>
        <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>


        <h6 class="mt-4">Évaluations :</h6>
        <?php
        if (empty($commentaires)) {
            echo "<div class='alert alert-info'>Aucun commentaire visible pour ce client.</div>";
        } else {
            foreach ($commentaires as $commentaire) {
                echo "<div class='card mb-3'>";
                echo "<div class='card-header fw-bold'>{$commentaire['nom']} {$commentaire['prenom']} - Note : {$commentaire['note']} ⭐</div>";
                echo "<div class='card-body'>";
                echo "<p class='card-text'>{$commentaire['commentaire']}</p>";
                echo "<p class='text-muted small'>Posté le {$commentaire['date']}</p>";
                echo "</div>";
                echo "</div>";
            }
        }
        ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
