<?php
session_start();
require_once('../BD/connexion.php');
require_once('../Traitement/traitement_commentaires.php');

// Vérification de la connexion admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../IHM/connexion_admin.php');
    exit();
}

// Récupération de tous les commentaires avec les informations des utilisateurs
$commentaires = getAllCommentairesWithDetails();

// Traitement de la suppression
if (isset($_POST['delete_comment'])) {
    $comment_id = intval($_POST['comment_id']);
    if (deleteCommentaire($comment_id)) {
        $_SESSION['message'] = "Commentaire supprimé avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression du commentaire";
    }
    header('Location: gestion_commentaires.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Commentaires - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <h2 class="mb-4">Gestion des Commentaires</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Objet</th>
                        <th>Note</th>
                        <th>Commentaire</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commentaires as $commentaire): ?>
                        <tr>
                            <td><?= htmlspecialchars($commentaire['id']) ?></td>
                            <td>
                                <?= htmlspecialchars($commentaire['nom']) ?> 
                                <?= htmlspecialchars($commentaire['prenom']) ?>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($commentaire['email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($commentaire['objet_nom']) ?></td>
                            <td>
                                <span class="badge bg-warning text-dark">
                                    <?= htmlspecialchars($commentaire['note']) ?> ⭐
                                </span>
                            </td>
                            <td><?= htmlspecialchars($commentaire['commentaire']) ?></td>
                            <td><?= htmlspecialchars($commentaire['date']) ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?');">
                                    <input type="hidden" name="comment_id" value="<?= htmlspecialchars($commentaire['id']) ?>">
                                    <button type="submit" name="delete_comment" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>