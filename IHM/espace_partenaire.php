<?php
session_start();
require_once __DIR__ . '/../BD/connexion.php';
require_once __DIR__ . '/../Traitement/objetTraitement.php';
require_once __DIR__ . '/../Traitement/annonceTraitement.php';
require_once __DIR__ . '/../Traitement/notificationTraitement.php';

// Vérifier si l'utilisateur est connecté et est un partenaire
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'proprietaire') {
    header('Location: /IHM/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$objets = getObjetsByProprietaire($user_id);
$annonces = getAnnoncesByProprietaire($user_id);
$notifications = getNotificationsUtilisateur($user_id);
$nb_notifications = getNombreNotificationsNonLues($user_id);

// Récupérer le nom de l'utilisateur
$stmt = $conn->prepare("SELECT nom FROM utilisateur WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = $user ? $user['nom'] : 'Partenaire';
?>
<?php
require_once __DIR__ . '/../Traitement/traitement_espace_partenaire.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Partenaire - MiniLoc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FFB6C1;
            --secondary-color: #87CEEB;
            --accent-color: #B0E0E6;
            --text-color: #333;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
        }

        .nav-link:hover {
            color: white !important;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-secondary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .action-buttons .btn {
            margin: 0 5px;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include (__DIR__ . '/navbar.php'); ?>

    <!-- Main Content -->
    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Objets</h5>
                        <p class="card-text display-4"><?php echo count($objets); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Annonces Actives</h5>
                        <p class="card-text display-4"><?php echo getNombreAnnoncesActives($user_id); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Annonces Archivées</h5>
                        <p class="card-text display-4"><?php echo count($annonces) - getNombreAnnoncesActives($user_id); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mb-4">
            <div class="col-12">
                <a href="/IHM/form_objet.php" class="btn btn-primary me-2">
                    <i class="fas fa-plus"></i> Ajouter un Objet
                </a>
                <a href="/IHM/form_annonce.php" class="btn btn-secondary me-2">
                    <i class="fas fa-plus"></i> Créer une Annonce
                </a>
                <a href="/IHM/mes_annonces.php" class="btn btn-outline-dark">
                    <i class="fas fa-list"></i> Suivre vos annonces
                </a>
            </div>
        </div>

        <!-- Objects List -->
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Mes Objets</h2>
                <?php if (empty($objets)): ?>
                    <div class="alert alert-info">
                        Vous n'avez pas encore d'objets. Commencez par en ajouter un !
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($objets as $objet): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <?php
                                    $images = getImagesObjet($objet['id']);
                                    $image_url = !empty($images) ? '/uploads/' . $images[0]['url'] : '/assets/images/default.jpg';
                                    ?>
                                    <img src="<?php echo $image_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($objet['nom']); ?>" style="height: 200px; object-fit: cover;">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($objet['nom']); ?></h5>
                                        <p class="card-text">
                                            <strong>Catégorie:</strong> <?php echo htmlspecialchars($objet['categorie_nom']); ?><br>
                                            <strong>Prix:</strong> <?php echo number_format($objet['prix_journalier'], 2); ?> DHS/jour
                                        </p>
                                        <div class="action-buttons">
                                            <a href="/IHM/form_objet.php?id=<?php echo $objet['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Modifier
                                            </a>
                                            <a href="/IHM/form_annonce.php?objet_id=<?php echo $objet['id']; ?>" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-plus"></i> Créer Annonce
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>