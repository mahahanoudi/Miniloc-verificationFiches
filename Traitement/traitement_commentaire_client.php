<?php

include('../Traitement/traitement_commentaires.php');
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $user = getClientInfo($id);
    $commentaires = getCommentairesSurClient($id);

    ob_start();
    ?>
    <img src="<?= !empty($user['img_profil']) ? htmlspecialchars($user['img_profil']) : '../photos/default-profile.jpg' ?>" 
        alt="Profil client" 
        class="rounded-circle mb-3 d-block mx-auto" 
        style="width: 120px; height: 120px; object-fit: cover;">
    <h6 class="text-center"><?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></h6>
    <p><strong>Email :</strong> <?= htmlspecialchars($user['email']); ?></p>

    <h6 class="mt-4">Évaluations :</h6>
    <?php if (empty($commentaires)): ?>
        <div class='alert alert-info'>Aucun commentaire visible pour ce client.</div>
    <?php else:
        foreach ($commentaires as $commentaire): ?>
            <div class='card mb-3'>
                <div class='card-header fw-bold'><?= htmlspecialchars($commentaire['nom'] . ' ' . $commentaire['prenom']) ?> - Note : <?= $commentaire['note'] ?> ⭐</div>
                <div class='card-body'>
                    <p class='card-text'><?= htmlspecialchars($commentaire['commentaire']) ?></p>
                    <p class='text-muted small'>Posté le <?= $commentaire['date'] ?></p>
                </div>
            </div>
        <?php endforeach;
    endif;

    echo ob_get_clean();
}
?>