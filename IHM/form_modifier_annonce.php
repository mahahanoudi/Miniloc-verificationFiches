<?php
session_start();
include '../BD/connexion.php';

// Vérifier si l'utilisateur est connecté et est un propriétaire
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'proprietaire') {
    header('Location: index.php');
    exit();
}

// Vérifier si un annonce_id est fourni
if (!isset($_GET['id'])) {
    header('Location: liste_annonces.php');
    exit();
}

$annonce_id = $_GET['id'];

// Récupérer les informations de l'annonce
$stmt = $conn->prepare("
    SELECT a.*, o.nom as objet_nom, o.categorie_id, o.prix_journalier, o.description as objet_description, o.ville,
           c.nom as categorie_nom 
    FROM annonce a 
    JOIN objet o ON a.objet_id = o.id 
    LEFT JOIN categorie c ON o.categorie_id = c.id 
    WHERE a.id = ? AND a.proprietaire_id = ?
");
$stmt->execute([$annonce_id, $_SESSION['user_id']]);
$annonce = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$annonce) {
    $_SESSION['error'] = "Annonce non trouvée ou vous n'avez pas les droits pour la modifier.";
    header('Location: liste_annonces.php');
    exit();
}

// Déboguer les données de l'annonce
error_log("Données de l'annonce : " . print_r($annonce, true));

// Récupérer les images de l'objet
$stmt = $conn->prepare("SELECT * FROM image WHERE objet_id = ?");
$stmt->execute([$annonce['objet_id']]);
$images = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Annonce - MiniLoc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-label {
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 182, 193, 0.25);
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
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'?>

    <!-- Main Content -->
    <div class="container">
        <div class="form-container">
            <h2 class="mb-4">Modifier l'Annonce</h2>
            
            <form action="../Traitement/traitement_annonce.php" method="POST" id="annonceForm">
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="annonce_id" value="<?php echo $annonce_id; ?>">
                <input type="hidden" name="objet_id" value="<?php echo $annonce['objet_id']; ?>">

                <!-- Informations de l'objet (en lecture seule) -->
                <div class="mb-4">
                    <h5 class="text-muted mb-3">Informations de l'objet</h5>
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="row">
                                <?php if (!empty($images)): ?>
                                    <div class="col-md-3">
                                        <img src="../uploads/<?php echo htmlspecialchars($images[0]['url']); ?>" 
                                             class="img-fluid rounded" alt="Image de l'objet">
                                    </div>
                                <?php endif; ?>
                                <div class="col">
                                    <h6 class="mb-2"><?php echo htmlspecialchars($annonce['objet_nom']); ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($annonce['categorie_nom']); ?> |
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($annonce['ville']); ?> |
                                        <i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($annonce['prix_journalier']); ?> €/jour
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations de l'annonce (modifiables) -->
                <div class="mb-4">
                    <h5 class="mb-3">Modifier les détails de l'annonce</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date_debut" class="form-label">Date de début de disponibilité</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" required
                                   value="<?php echo isset($annonce['date_debut']) ? $annonce['date_debut'] : ''; ?>"
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="date_fin" class="form-label">Date de fin de disponibilité</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" required
                                   value="<?php echo isset($annonce['date_fin']) ? $annonce['date_fin'] : ''; ?>"
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="adress" class="form-label">Adresse de retrait</label>
                        <input type="text" class="form-control" id="adress" name="adress" required
                               value="<?php echo isset($annonce['adress']) ? htmlspecialchars($annonce['adress']) : ''; ?>"
                               placeholder="Ex: 123 rue de la Paix, 75001 Paris">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="premium" name="premium"
                                   <?php echo (isset($annonce['premium']) && $annonce['premium']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="premium">
                                <i class="fas fa-star text-warning"></i> Annonce Premium
                            </label>
                        </div>
                    </div>

                    <div id="premiumOptions" class="premium-section" style="display: <?php echo (isset($annonce['premium']) && $annonce['premium']) ? 'block' : 'none'; ?>;">
                        <h6 class="mb-3"><i class="fas fa-crown text-warning"></i> Options Premium</h6>
                        <div class="mb-3">
                            <label for="date_debut_premium" class="form-label">Date de début premium</label>
                            <input type="date" class="form-control" id="date_debut_premium" name="date_debut_premium"
                                   value="<?php echo isset($annonce['date_debut_premium']) ? $annonce['date_debut_premium'] : ''; ?>"
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Durée premium</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duree_premium" id="duree_7" value="7"
                                           <?php echo (isset($annonce['duree_premium']) && $annonce['duree_premium'] == 7) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="duree_7">
                                        <i class="fas fa-calendar-week"></i> Une semaine
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duree_premium" id="duree_15" value="15"
                                           <?php echo (isset($annonce['duree_premium']) && $annonce['duree_premium'] == 15) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="duree_15">
                                        <i class="fas fa-calendar-alt"></i> 15 jours
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duree_premium" id="duree_30" value="30"
                                           <?php echo (isset($annonce['duree_premium']) && $annonce['duree_premium'] == 30) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="duree_30">
                                        <i class="fas fa-calendar"></i> Un mois
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> L'option premium permet d'afficher votre annonce sur la première page du site.
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="liste_annonces.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#premium').change(function() {
                if(this.checked) {
                    $('#premiumOptions').show();
                    // Rendre la date de début premium et la durée obligatoires
                    $('#date_debut_premium').prop('required', true);
                    $('input[name="duree_premium"]').prop('required', true);
                } else {
                    $('#premiumOptions').hide();
                    // Désactiver les champs premium
                    $('#date_debut_premium').prop('required', false);
                    $('input[name="duree_premium"]').prop('required', false);
                }
            });

            // Validation des dates
            $('#date_debut, #date_fin').change(function() {
                var dateDebut = new Date($('#date_debut').val());
                var dateFin = new Date($('#date_fin').val());
                
                if(dateFin < dateDebut) {
                    alert('La date de fin doit être postérieure à la date de début');
                    $('#date_fin').val('');
                }
            });

            // Validation de la date de début premium
            $('#date_debut_premium').change(function() {
                var dateDebutPremium = new Date($(this).val());
                var dateDebut = new Date($('#date_debut').val());
                
                if(dateDebutPremium < dateDebut) {
                    alert('La date de début premium doit être postérieure à la date de début de location');
                    $(this).val('');
                }
            });
        });
    </script>
</body>
</html> 