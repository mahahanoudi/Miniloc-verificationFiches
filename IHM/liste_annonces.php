<?php
session_start();
include '../BD/connexion.php';

// Vérifier si l'utilisateur est connecté et est un propriétaire
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'proprietaire') {
    header('Location: ../index.php');
    exit();
}

// Récupérer les annonces du propriétaire
$stmt = $conn->prepare("
    SELECT a.*, o.nom as objet_nom, o.categorie_id, o.prix_journalier, c.nom as categorie_nom,
           (SELECT url FROM image WHERE objet_id = o.id ORDER BY id ASC LIMIT 1) as image_url
    FROM annonce a
    JOIN objet o ON a.objet_id = o.id
    LEFT JOIN categorie c ON o.categorie_id = c.id
    WHERE a.proprietaire_id = ?
    ORDER BY a.date_publication DESC
");
$stmt->execute([$_SESSION['user_id']]);
$annonces = $stmt->fetchAll();

// Débogage pour voir les données
error_log("Données des annonces : " . print_r($annonces, true));

// Ajouter ce code au début du fichier, juste après la connexion à la base de données
if (isset($_GET['action']) && isset($_GET['id'])) {
    try {
        // Vérifier que l'annonce appartient bien au propriétaire
        $stmt = $conn->prepare("SELECT proprietaire_id FROM annonce WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $result = $stmt->fetch();

        if (!$result || $result['proprietaire_id'] !== $_SESSION['user_id']) {
            throw new Exception("Vous n'êtes pas autorisé à modifier cette annonce.");
        }

        if ($_GET['action'] === 'archiver') {
            // Mettre à jour le statut de l'annonce en 'archivée'
            $stmt = $conn->prepare("UPDATE annonce SET statut = 'archivée' WHERE id = ? AND proprietaire_id = ?");
            $success = $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            if ($success) {
                $_SESSION['success'] = "L'annonce a été archivée avec succès.";
            }
        } elseif ($_GET['action'] === 'activer') {
            // Vérifier le nombre d'annonces actives
            $stmt = $conn->prepare("SELECT COUNT(*) as nombre_annonces FROM annonce WHERE proprietaire_id = ? AND statut = 'active'");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['nombre_annonces'] >= 5) {
                throw new Exception("Vous avez atteint la limite de 5 annonces actives simultanées.");
            }

            // Mettre à jour le statut de l'annonce en 'active'
            $stmt = $conn->prepare("UPDATE annonce SET statut = 'active' WHERE id = ? AND proprietaire_id = ?");
            $success = $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            if ($success) {
                $_SESSION['success'] = "L'annonce a été réactivée avec succès.";
            }
        }

        if (!$success) {
            throw new Exception("Erreur lors de la modification du statut de l'annonce.");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: liste_annonces.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Annonces - MiniLoc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FFB6C1;    /* Rose clair */
            --secondary-color: #87CEEB;   /* Bleu clair */
            --accent-color: #B0E0E6;      /* Bleu poudre */
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

        .announcement-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            height: 100%;
            border: 1px solid var(--accent-color);
        }

        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .announcement-image {
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .announcement-title {
            color: var(--text-color);
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .announcement-price {
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.1rem;
        }

        .premium-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .status-active {
            background-color: var(--secondary-color);
            color: white;
        }

        .status-inactive {
            background-color: var(--primary-color);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid var(--accent-color);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
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

        .btn-success {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-color);
        }

        .btn-success:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: white;
        }

        .archive-btn {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .archive-btn:hover {
            background-color: #ff9aac;
            border-color: #ff9aac;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'?>

    <!-- Main Content -->
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Mes Annonces</h2>
                <?php
                $stmt = $conn->prepare("SELECT COUNT(*) as nombre_annonces FROM annonce WHERE proprietaire_id = ? AND statut = 'active'");
                $stmt->execute([$_SESSION['user_id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Vous avez <?php echo $result['nombre_annonces']; ?> annonce(s) active(s) sur 5 possibles
                </p>
            </div>
            <a href="liste_objets.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Créer une annonce
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($annonces)): ?>
            <div class="empty-state">
                <i class="fas fa-bullhorn"></i>
                <h3>Vous n'avez pas encore d'annonces</h3>
                <p class="text-muted">Commencez par créer une annonce pour l'un de vos objets</p>
                <a href="liste_objets.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> Créer une annonce
                </a>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($annonces as $annonce): ?>
                    <div class="col">
                        <div class="announcement-card">
                            <?php if ($annonce['image_url']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($annonce['image_url']); ?>" 
                                     class="announcement-image w-100" alt="Image de l'objet"
                                     onerror="this.onerror=null; this.src='../uploads/default.jpg';">
                            <?php else: ?>
                                <div class="announcement-image w-100 bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h3 class="announcement-title">
                                        <?php echo htmlspecialchars($annonce['objet_nom']); ?>
                                    </h3>
                                    <?php if ($annonce['premium']): ?>
                                        <span class="premium-badge">
                                            <i class="fas fa-star"></i> Premium
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <p class="text-muted mb-2">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($annonce['categorie_nom']); ?>
                                </p>

                                <p class="mb-2">
                                    <i class="fas fa-box"></i> <?php echo htmlspecialchars($annonce['objet_nom']); ?>
                                </p>

                                <p class="mb-2">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($annonce['adress']); ?>
                                </p>

                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="announcement-price">
                                        <?php echo number_format($annonce['prix_journalier'], 2); ?> €/jour
                                    </span>
                                    <span class="status-badge <?php echo $annonce['statut'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $annonce['statut'] === 'active' ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>

                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i> Publiée le <?php echo date('d/m/Y', strtotime($annonce['date_publication'])); ?>
                                    </small>
                                </div>

                                <div class="mt-3 d-flex gap-2">
                                    <a href="form_modifier_annonce.php?id=<?php echo $annonce['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <?php if ($annonce['statut'] === 'active'): ?>
                                        <a href="liste_annonces.php?action=archiver&id=<?php echo $annonce['id']; ?>" 
                                           class="btn btn-sm archive-btn"
                                           onclick="return confirm('Êtes-vous sûr de vouloir archiver cette annonce ?');">
                                            <i class="fas fa-archive"></i> Archiver
                                        </a>
                                    <?php else: ?>
                                        <a href="liste_annonces.php?action=activer&id=<?php echo $annonce['id']; ?>" 
                                           class="btn btn-success btn-sm"
                                           onclick="return confirm('Êtes-vous sûr de vouloir réactiver cette annonce ?');">
                                            <i class="fas fa-check"></i> Réactiver
                                        </a>
                                    <?php endif; ?>
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
        // Gestion des boutons d'archivage et d'activation
        document.querySelectorAll('.archive-btn, .activate-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const action = this.classList.contains('archive-btn') ? 'archive' : 'activate';
                const message = action === 'archive' 
                    ? 'Êtes-vous sûr de vouloir archiver cette annonce ?' 
                    : 'Êtes-vous sûr de vouloir réactiver cette annonce ?';
                
                if (confirm(message)) {
                    // Afficher un indicateur de chargement
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
                    this.disabled = true;

                    fetch('../Traitement/traitement_annonce.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=${action}&id=${id}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erreur réseau');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Afficher un message de succès
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-success alert-dismissible fade show';
                            alertDiv.innerHTML = `
                                ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            `;
                            document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container').firstChild);
                            
                            // Recharger la page après un court délai
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            throw new Error(data.message || 'Une erreur est survenue');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Restaurer le bouton
                        this.innerHTML = originalText;
                        this.disabled = false;
                        
                        // Afficher l'erreur
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            ${error.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                        document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container').firstChild);
                    });
                }
            });
        });
    </script>
</body>
</html> 