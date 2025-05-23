<?php
session_start();
include '../BD/connexion.php';

// Vérifier si l'utilisateur est connecté et est un propriétaire
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'proprietaire') {
    header('Location: index.php');
    exit();
}

// Vérifier si un objet_id est fourni
if (!isset($_GET['objet_id'])) {
    header('Location: espace_partenaire.php');
    exit();
}

$objet_id = $_GET['objet_id'];

// Vérifier si l'objet appartient bien au propriétaire
$stmt = $conn->prepare("
    SELECT o.*, c.nom as categorie_nom 
    FROM objet o 
    LEFT JOIN categorie c ON o.categorie_id = c.id 
    WHERE o.id = ? AND o.proprietaire_id = ?
");
$stmt->execute([$objet_id, $_SESSION['user_id']]);
$objet = $stmt->fetch();

if (!$objet) {
    header('Location: espace_partenaire.php');
    exit();
}

// Récupérer les images de l'objet
$stmt = $conn->prepare("SELECT * FROM image WHERE objet_id = ?");
$stmt->execute([$objet_id]);
$images = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une Annonce - MiniLoc</title>
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

        .premium-section {
            background-color: var(--light-bg);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .premium-section h4 {
            color: var(--primary-color);
        }

        .premium-benefits {
            list-style: none;
            padding-left: 0;
        }

        .premium-benefits li {
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }

        .premium-benefits li:before {
            content: "✓";
            color: var(--primary-color);
            position: absolute;
            left: 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'?>

    <!-- Main Content -->
    <div class="container">
        <div class="form-container">
            <h2 class="mb-4">Créer une Annonce</h2>
            
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle"></i> En tant que partenaire, vous pouvez avoir jusqu'à 5 annonces actives simultanément.
            </div>
            
            <form  method="POST" id="annonceForm">
                <input type="hidden" name="objet_id" value="<?php echo $objet_id; ?>">

                <div class="mb-3">
                    <label class="form-label">Objet à louer</label>
                    <div class="card">
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
                                <i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($objet['prix_journalier']); ?> €/jour
                            </p>
                            <p class="card-text">
                                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($objet['description']); ?>
                            </p>
                            <?php if (!empty($images)): ?>
                                <div class="mt-2">
                                    <img src="../uploads/<?php echo htmlspecialchars($images[0]['url']); ?>" 
                                         class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="titre" class="form-label">Titre de l'annonce</label>
                    <input type="text" class="form-control" id="titre" name="titre" required>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="date_debut" class="form-label">Date de début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" required
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="date_fin" class="form-label">Date de fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" required
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="adress" class="form-label">Adresse de retrait</label>
                    <input type="text" class="form-control" id="adress" name="adress" required
                           placeholder="Ex: 123 rue de la Paix, 75001 Paris">
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="premium" name="premium">
                        <label class="form-check-label" for="premium">
                            Annonce Premium
                        </label>
                    </div>
                </div>

                <div id="premiumOptions" class="premium-section" style="display: none;">
                    <h4>Options Premium</h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date_debut_premium" class="form-label">Date de début premium</label>
                            <input type="date" class="form-control" id="date_debut_premium" name="date_debut_premium"
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="duree_premium" class="form-label">Durée premium</label>
                            <select class="form-select" id="duree_premium" name="duree_premium">
                                <option value="7">7 jours</option>
                                <option value="15">15 jours</option>
                                <option value="30">30 jours</option>
                            </select>
                        </div>
                    </div>
                    <div class="premium-benefits">
                        <h5>Avantages Premium :</h5>
                        <ul>
                            <li>Mise en avant dans les résultats de recherche</li>
                            <li>Badge "Premium" visible sur l'annonce</li>
                            <li>Statistiques détaillées des vues</li>
                            <li>Support prioritaire</li>
                        </ul>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="liste_objets.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer l'annonce
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion des options premium
        document.getElementById('premium').addEventListener('change', function() {
            const premiumOptions = document.getElementById('premiumOptions');
            premiumOptions.style.display = this.checked ? 'block' : 'none';
            
            const dateDebutPremium = document.getElementById('date_debut_premium');
            const dureePremium = document.getElementById('duree_premium');
            
            if (this.checked) {
                dateDebutPremium.required = true;
                dureePremium.required = true;
            } else {
                dateDebutPremium.required = false;
                dureePremium.required = false;
            }
        });

        // Validation des dates
        document.getElementById('date_debut').addEventListener('change', function() {
            document.getElementById('date_fin').min = this.value;
            if (document.getElementById('date_fin').value && document.getElementById('date_fin').value < this.value) {
                document.getElementById('date_fin').value = this.value;
            }
        });

        // Validation du formulaire
        document.getElementById('annonceForm').addEventListener('submit', function(e) {
            const dateDebut = new Date(document.getElementById('date_debut').value);
            const dateFin = new Date(document.getElementById('date_fin').value);
            
            if (dateFin < dateDebut) {
                e.preventDefault();
                alert('La date de fin doit être postérieure à la date de début');
                return;
            }

            if (document.getElementById('premium').checked) {
                const dateDebutPremium = new Date(document.getElementById('date_debut_premium').value);
                if (dateDebutPremium < dateDebut) {
                    e.preventDefault();
                    alert('La date de début premium doit être postérieure à la date de début de location');
                    return;
                }
            }
        });
        $("#annonceForm").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
        url: "../Traitement/traitement_annonce.php",
        method: "POST",
        dataType: 'json', // Spécifiez explicitement le type de réponse attendu
        data: $(this).serialize(),
        success: function(response) {
            // Vérifiez si la réponse est valide
            if (response && response.success) {
                window.location.href = response.redirect;
            } else {
                alert(response.message || "Réponse inattendue du serveur");
            }
        },
        error: function(xhr) {
            // Affichez l'erreur complète pour le débogage
            console.error(xhr.responseText);
            alert("Erreur : " + xhr.statusText);
        }
    });
});
    </script>
</body>
</html>