<?php
session_start();
include '../BD/connexion.php';
include '../Traitement/objetTraitement.php';

// Vérifier si l'utilisateur est connecté et est un propriétaire
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'proprietaire') {
    header('Location: ../index.php');
    exit();
}

// Vérifier le nombre d'annonces actives
$stmt = $conn->prepare("SELECT COUNT(*) as nombre_annonces FROM annonce WHERE proprietaire_id = ? AND statut = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['nombre_annonces'] >= 5) {
    $_SESSION['error'] = "Vous avez atteint la limite de 5 annonces actives simultanées. Veuillez désactiver une annonce existante avant d'en créer une nouvelle.";
    header('Location: liste_annonces.php');
    exit();
}

// Récupérer les objets du propriétaire
$objets = getObjetsByProprietaire($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Objets - MiniLoc</title>
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

        .card-img-top {
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
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

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php' ?>

    <!-- Main Content -->
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Mes Objets</h2>
            <a href="form_objet.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ajouter un objet
            </a>
        </div>

        <?php if (empty($objets)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>Vous n'avez pas encore d'objets</h3>
                <p>Commencez par ajouter votre premier objet à louer</p>
                <a href="form_objet.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> Ajouter un objet
                </a>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($objets as $objet): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php
                            $images = getImagesObjet($objet['id']);
                            $image_url = !empty($images) ? '../uploads/' . $images[0]['url'] : '../uploads/default.jpg';
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($objet['nom']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($objet['nom']); ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($objet['categorie_nom']); ?>
                                    </small>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($objet['ville']); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-euro-sign"></i> <?php echo number_format($objet['prix_journalier'], 2); ?> DHS/ jour
                                </p>
                                <div class="d-flex justify-content-between">
                                    <a href="form_objet.php?id=<?php echo $objet['id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <button onclick="supprimerObjet(<?php echo $objet['id']; ?>)" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function supprimerObjet(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet objet ? Cette action est irréversible.')) {
                fetch('../Traitement/objetTraitement.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'supprimer',
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Erreur lors de la suppression de l\'objet');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue');
                });
            }
        }
    </script>
</body>
</html> 