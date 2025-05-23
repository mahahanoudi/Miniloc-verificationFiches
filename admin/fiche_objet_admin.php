<?php
session_start();
include ('../Traitement/detailsAnnonce.php'); 
include('../Traitement/traitement_commentaires.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Produit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   
</head>
<style>
    body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

h2, h4 {
    color: #333;
}

.btn-primary {
    background-color: #007bff;
    border: none;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.commentaires-container {
    margin-top: 20px;
}

.commentaire-card {
    background-color: #ffffff;
    border-left: 5px solid #0d6efd;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    padding: 15px 20px;
}

.commentaire-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.commentaire-auteur {
    font-weight: bold;
    font-size: 1.1rem;
    margin-bottom: 5px;
}

.commentaire-note {
    color: #ffc107;
    font-size: 1rem;
}

.commentaire-texte {
    margin-top: 10px;
    font-size: 0.95rem;
    color: #333;
}

.commentaire-date {
    font-size: 0.85rem;
    color: #777;
    text-align: right;
    margin-top: 10px;
}

</style>
<body class="bg-light">
    <?php
   
    // Vérification de la connexion de l'admin
    if (!isset($_SESSION['admin_id']) ) {
        echo '<div class="container text-center my-5">
                <div class="alert alert-danger">
                    <h4>Accès refusé</h4>
                    <p>Vous devez être connecté en tant qu’administrateur pour accéder à cette page.</p>
                    <a href="../IHM/connexion_admin.php" class="btn btn-primary mt-3">Se connecter</a>
                </div>
            </div>';
        exit(); 
    }
    ?>
    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6 text-center">
            <img id="product-image" src="../photos/<?= htmlspecialchars($_SESSION['image'] ?? 'images.png') ?>" alt="Produit" class="img-fluid rounded shadow">

        </div>
        <div class="col-md-6">
            <h2 id="product-name" class="fw-bold mb-3"><?= htmlspecialchars($_SESSION['details'][0]['nom']) ?></h2>
            <h4 id="product-price" class="text-success mb-4"><?= htmlspecialchars($_SESSION['details'][0]['prix_journalier']) ?> Dh/jour</h4>
            <p id="product-description" >
            <strong>Description de l'objet:</strong> <?= htmlspecialchars($_SESSION['details'][0]['description']) ?>
            </p>
            <p><strong>Adresse de l'objet :</strong> <?= htmlspecialchars($_SESSION['details'][0]['ville']) ?></p>
            <p><strong>Adresse de l'annonce :</strong> <?= htmlspecialchars($_SESSION['details'][0]['adress']) ?></p>
            <p><strong>Status de l'objet :</strong> <?= htmlspecialchars($_SESSION['details'][0]['etat']) ?></p>
            <p><strong>Nombre de location précédente :</strong> <?= isset($_SESSION['nbr_annonce']) && $_SESSION['nbr_annonce'] !== null  ? $_SESSION['nbr_annonce']  : 'Cest la prmière publication de cet objet' ?></p>
            <p><strong>Évaluation de l'objet :</strong> 
                <?= isset($_SESSION['note']) && $_SESSION['note'] !== null 
                    ? $_SESSION['note'] . ' ⭐' 
                    : 'Pas encore noté' ?>
            </p>
            <p><strong>Commentaire:</strong></p>
           <?php
 
            $objet_id = isset($_SESSION['objet_id']) ? intval($_SESSION['objet_id']) : 0;
            if (!$objet_id) {
                echo "<p>Erreur : Objet non spécifié.</p>";
                exit;
            }

            $commentaires = getCommentaires($objet_id);

            if (empty($commentaires)) {
                echo "<p>Aucun commentaire visible pour cet objet pour le moment.</p>";
            } else {
                echo "<div class='commentaires-container'>";
                foreach ($commentaires as $commentaire) {
                    echo "<div class='commentaire-card'>";
                    echo "<div class='commentaire-header'>";
                    echo "<div class='commentaire-auteur'>{$commentaire['nom']} {$commentaire['prenom']}</div>";
                    echo "<div class='commentaire-note'>{$commentaire['note']} ⭐</div>";
                    echo "</div>";
                    echo "<div class='commentaire-texte'>{$commentaire['commentaire']}</div>";
                    echo "<div class='commentaire-date'>Posté le {$commentaire['date']}</div>";
                    echo "</div>";
                }
                echo "</div>";

            }
            ?>
            <p>
                

            <strong>Partenaire :</strong>
            <a href="#" data-bs-toggle="modal" data-bs-target="#partenaireModal">
                Voir le profil du partenaire
            </a>
           </p>
           <!-- Utiliser directement l'ID de l'annonce stocké dans la session -->
           <button class="btn btn-outline-secondary" onclick="goBack()">Retour</button>
        </div>
    </div>
</div>

<div class="modal fade" id="partenaireModal" tabindex="-1" aria-labelledby="partenaireModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="partenaireModalLabel">Propriétaire de cet Objet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        
        <img src="<?= !empty($_SESSION['proprietaire']['img_profil']) ? htmlspecialchars($_SESSION['proprietaire']['img_profil']) : '../photos/default-profile.jpg'?>" alt="Profil partenaire" 
        class="rounded-circle mb-3 d-block mx-auto" 
        style="width: 120px; height: 120px; object-fit: cover;">
        <h6 class="text-center"><?= htmlspecialchars($_SESSION['proprietaire']['nom']) ?> <?= htmlspecialchars($_SESSION['proprietaire']['prenom']) ?></h6>
        
        <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['proprietaire']['email']) ?></p>
        <p><strong>Évaluations :</strong> <?= is_numeric($moyenne) ? $moyenne . ' ⭐' : $moyenne ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('poussette-card').addEventListener('click', function () {
    const modal = new bootstrap.Modal(document.getElementById('poussetteModal'));
    modal.show();
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function goBack() {
        window.history.back();
    }
</script>

</body>
</html>