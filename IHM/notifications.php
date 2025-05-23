<?php
session_start();
require_once __DIR__ . '/../Traitement/notificationTraitement.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /IHM/connexion.php');
    exit();
}

$notifications = getNotificationsUtilisateur($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Notifications - MiniLoc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include (__DIR__ . '/navbar.php'); ?>
    
    <div class="container mt-4">
        <h2>Mes Notifications</h2>
        <?php foreach ($notifications as $notification): ?>
            <div class="card mb-3 <?php echo $notification['lu'] ? 'bg-light' : ''; ?>">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($notification['titre']); ?></h5>
                    <p class="card-text"><?php echo htmlspecialchars($notification['message']); ?></p>
                    <small class="text-muted">
                        <?php echo date('d/m/Y H:i', strtotime($notification['date_creation'])); ?>
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>