<?php
session_start();
include_once('../BD/connexion.php');

// Vérifier si l'utilisateur est connecté
$current_user_id = $_SESSION['user_id'] ?? null;

// Suppression d'un utilisateur si demandé
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    try {
        // Vérifier si l'utilisateur existe
        $stmt = $conn->prepare("SELECT id FROM utilisateur WHERE id = ?");
        $stmt->execute([$delete_id]);
        $user_exists = $stmt->fetch();

        if (!$user_exists) {
            $error = "L'utilisateur n'existe pas.";
        } elseif ($current_user_id && $delete_id === $current_user_id) {
            $error = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            // Suppression directe grâce au ON DELETE CASCADE
            $conn->beginTransaction();

            $stmt = $conn->prepare("DELETE FROM utilisateur WHERE id = ?");
            $stmt->execute([$delete_id]);

            $conn->commit();
            //$success = "Utilisateur supprimé avec succès.";
            //header("Location: Utilisateur_admin.php?success=" . urlencode($success));
            exit;
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupérer tous les utilisateurs
$stmt = $conn->query("SELECT id, nom, prenom, email, role FROM utilisateur ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration - Gestion des utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../admin/navbar.php'; ?>
<div class="container mt-4">
    <h1 class="text-center mb-4">Gestion des utilisateurs</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-primary">
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="text-center">Aucun utilisateur trouvé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['nom']) ?></td>
                        <td><?= htmlspecialchars($user['prenom']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <?php if ($user['id'] !== $current_user_id): ?>
                                <a href="?delete_id=<?= $user['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Confirmez-vous la suppression de cet utilisateur ?');">
                                    <i class="fas fa-trash-alt"></i> Supprimer
                                </a>
                            <?php else: ?>
                                <span class="badge bg-secondary">(Vous)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
