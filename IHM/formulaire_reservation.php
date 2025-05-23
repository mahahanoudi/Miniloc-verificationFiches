<?php
session_start();
// on vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // on enregistre l'URL actuelle pour rediriger l'utilisateur après la connexion
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];    
    // on le rediriger vers la page de connexion
    header('Location: ../IHM/connexion.php');
    exit;
}
// on vérifie si l'ID de l'annonce est fourni
if (!isset($_GET['annonce_id']) || !is_numeric($_GET['annonce_id'])) {
    header('Location: ../IHM/produits.php');
    exit;
}
include_once('../BD/connexion.php');
$annonce_id = (int)$_GET['annonce_id'];
// on récupére les informations sur l'annonce et l'objet
$query = "SELECT a.*, o.nom as objet_nom, o.description, o.prix_journalier, o.ville, o.etat as objet_etat, i.url as image_url, c.nom as categorie_nom
          FROM annonce a
          JOIN objet o ON a.objet_id = o.id
          JOIN categorie c ON o.categorie_id = c.id
          LEFT JOIN image i ON o.id = i.objet_id
          WHERE a.id = :annonce_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':annonce_id', $annonce_id, PDO::PARAM_INT);
$stmt->execute();
$annonce = $stmt->fetch();
// on vérifie si l'annonce existe
if (!$annonce) {
    header('Location:../IHM/ produits.php');
    exit;
}
// on vérifie si l'objet est disponible et on stocke cette info
$objet_disponible = ($annonce['objet_etat'] === 'non_loue');
// Récupérer les périodes non disponibles (réservations existantes)
$query_reservations = "SELECT date_debut, date_fin 
                      FROM reservation 
                      WHERE annonce_id = :annonce_id 
                      AND statut != 'rejete'";
$stmt_reservations = $conn->prepare($query_reservations);
$stmt_reservations->bindParam(':annonce_id', $annonce_id, PDO::PARAM_INT);
$stmt_reservations->execute();
$reservations = $stmt_reservations->fetchAll();

// on prépare les dates de l'annonce pour JavaScript pour gerer période globale de disponibilité
$date_debut_annonce = $annonce['date_debut'];
$date_fin_annonce = $annonce['date_fin'];

// on prépare les dates non disponibles pour JavaScript
$dates_non_disponibles = [];
foreach ($reservations as $reservation) {
    // on convertit en timestamp pour traiter les dates
    $debut = strtotime($reservation['date_debut']);
    $fin = strtotime($reservation['date_fin']);    
    // on parcourt chaque jour de la période réservée
    for ($i = $debut; $i <= $fin; $i += 86400) {
        $dates_non_disponibles[] = date('Y-m-d', $i);
    }
}
// on convertit en format JSON pour JavaScript
$dates_non_disponibles_json = json_encode($dates_non_disponibles);
// on définit les frais de livraison
$frais_livraison = 25;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - <?= htmlspecialchars($annonce['objet_nom']) ?> - Miniloc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .reservation-container {
            background-color: #f8f9fa;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .reservation-image {
            height: 250px;
            object-fit: cover;
        }
        
        .reservation-details {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .reservation-form {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .submit-btn {
            background: linear-gradient(45deg, #87CEEB, #5CACEE);
            border: none;
            padding: 14px 20px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(135, 206, 235, 0.5);
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            background: linear-gradient(45deg, #5CACEE, #1E90FF);
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(135, 206, 235, 0.7);
        }
        
        .submit-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(135, 206, 235, 0.6);
        }
        
        .submit-btn:disabled {
            background: linear-gradient(45deg, #d1d1d1, #a9a9a9);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .btn-warning{
            background: linear-gradient(45deg,rgb(250, 223, 241),rgb(247, 203, 231));
            border: none;
            padding: 14px 20px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(249, 194, 231, 0.5);
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn-warning{
            background: linear-gradient(45deg,rgb(251, 206, 239),rgb(250, 207, 242));
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(244, 204, 240, 0.7);
        }
        .btn-secondary{
            background: linear-gradient(45deg,rgb(159, 172, 177), #5CACEE);
            border: none;
            padding: 14px 20px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(99, 108, 112, 0.5);
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn-secondary{
            background: linear-gradient(45deg,rgb(110, 118, 125),rgb(121, 125, 129));
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(109, 126, 132, 0.7);
        }
        .price-tag {
            font-size: 1.2rem;
            font-weight: 700;
            color: #87CEEB;
        }
        
        .total-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #87CEEB;
        }
        
        .disabled-date {
            text-decoration: line-through;
            color: #dc3545;
        }
        
        .flatpickr-day.disabled, .flatpickr-day.disabled:hover {
            color: rgba(57, 57, 57, 0.3);
            background: rgba(220, 53, 69, 0.1);
            cursor: not-allowed;
        }
        
        .unavailable-alert {
            background-color: #ffecec;
            border-left: 5px solid #ff5252;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #ff5252;
            font-weight: 500;
        }
        .alert-success {    
            background-color: #d4edda;
             border-color: #c3e6cb;
            color: #155724;
            border-left: 5px solid #155724;
        }
    </style>
</head>
<body style="background-color: #FFFFFF;">
    <?php include 'navbar.php'; ?>
    <!-- afficher les messages de succes -->
<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="reservation-container">
                    <!-- ici l'image de l'annonce -->
                    <img src="../uploads/<?= htmlspecialchars($annonce['image_url']) ?>" 
                         class="img-fluid w-100 reservation-image" 
                         alt="<?= htmlspecialchars($annonce['objet_nom']) ?>">                 
                    <!-- ici on affiche les détails de l'annonce -->
                    <div class="p-4">
                        <h2 class="mb-3"><?= htmlspecialchars($annonce['objet_nom']) ?></h2>
                        
                        <div class="reservation-details">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><i class="fas fa-tag me-2"></i> <strong>Catégorie:</strong> <?= htmlspecialchars($annonce['categorie_nom']) ?></p>
                                    <p><i class="fas fa-map-marker-alt me-2"></i> <strong>Ville:</strong> <?= htmlspecialchars($annonce['ville']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><i class="fas fa-money-bill-wave me-2"></i> <strong>Prix:</strong> <span class="price-tag"><?= htmlspecialchars($annonce['prix_journalier']) ?> dh/jour</span></p>
                                    <p><i class="fas fa-calendar me-2"></i> <strong>Disponible du:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($date_debut_annonce))) ?> au <?= htmlspecialchars(date('d/m/Y', strtotime($date_fin_annonce))) ?></p>
                                </div>
                            </div>
                            <p><i class="fas fa-info-circle me-2"></i> <strong>Description:</strong> <?= htmlspecialchars($annonce['description']) ?></p>
                        </div>
                       
                        <!-- ici il y a le formulaire de réservation -->
                        <div class="reservation-form">
                            <h4 class="mb-4"><i class="fas fa-calendar-check me-2"></i>Réserver cet article</h4>
                            
                            <?php if(!$objet_disponible): ?>
                                <div class="unavailable-alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>Attention :</strong> Cet objet est actuellement loué et n'est pas disponible à la réservation.
                                </div>
                            <?php endif; ?>
                            
                            <?php if(isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger">
                                    <?= $_SESSION['error']; ?>
                                    <?php unset($_SESSION['error']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="../Traitement/traitement_reservation.php" method="post" id="reservationForm">
                                <input type="hidden" name="annonce_id" value="<?= $annonce_id ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="date_debut" class="form-label">Date de début</label>
                                        <input type="text" class="form-control" id="date_debut" name="date_debut" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_fin" class="form-label">Date de fin</label>
                                        <input type="text" class="form-control" id="date_fin" name="date_fin" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="option_livraison" class="form-label">Option de livraison</label>
                                    <select class="form-select" id="option_livraison" name="option_de_livraison" required>
                                        <option value="">Choisir une option...</option>
                                        <option value="domicile">Livraison à domicile (+ <?= $frais_livraison ?> dh)</option>
                                        <option value="recuperation">Récupération sur place</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="addressContainer" style="display: none;">
                                    <label for="address" class="form-label">Adresse de livraison</label>
                                    <textarea class="form-control" id="address" name="address_de_livraison" rows="3"></textarea>
                                </div>
                                
                                <div class="card mb-4 mt-4">
                                    <div class="card-body">
                                        <h5 class="card-title">Résumé de la réservation</h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Prix par jour:</span>
                                            <span><?= htmlspecialchars($annonce['prix_journalier']) ?> dh</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Nombre de jours:</span>
                                            <span id="nbJours">0</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2" id="fraisLivraisonSection" style="display: none;">
                                            <span>Frais de livraison:</span>
                                            <span><?= $frais_livraison ?> dh</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span><strong>Total:</strong></span>
                                            <span class="total-price" id="prixTotal">0 dh</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-4">
                                        <a href="../IHM/produits.php" class="btn btn-secondary w-100">
                                            <i class="fas fa-arrow-left me-2"></i>Retour
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="reset" class="btn btn-warning w-100" id="btnAnnuler">
                                            <i class="fas fa-times-circle me-2"></i>Annuler
                                        </button>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn submit-btn w-100" <?= !$objet_disponible ? 'disabled' : '' ?>>
                                            <i class="fas fa-check-circle me-2"></i>Confirmer
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="py-4" style="background-color: #87CEEB; color: white;">
        <div class="container text-center">
            <p><i class="fas fa-baby-carriage me-2"></i> Miniloc - Location d'objets pour bébés</p>
            <div class="mt-3">
                <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dates de l'annonce
            const dateDebutAnnonce = new Date('<?= $date_debut_annonce ?>');
            const dateFinAnnonce = new Date('<?= $date_fin_annonce ?>');            
            // Dates non disponibles
            const datesNonDisponibles = <?= $dates_non_disponibles_json ?>;           
            // Frais de livraison
            const fraisLivraison = <?= $frais_livraison ?>;           
            // Configuration pour flatpickr
            const configCalendrier = {
                locale: 'fr',
                dateFormat: 'Y-m-d',
                minDate: dateDebutAnnonce,
                maxDate: dateFinAnnonce,
                disable: datesNonDisponibles,
                disableMobile: "true",
                onChange: calculeTotal
            };
            
            // Initialiser les calendriers
            const dateDebutPicker = flatpickr('#date_debut', {
                ...configCalendrier,
                onChange: function(selectedDates, dateStr) {
                    // Mettre à jour la date minimale pour le calendrier de fin
                    dateFinPicker.set('minDate', dateStr);
                    calculeTotal();
                }
            });
            
            const dateFinPicker = flatpickr('#date_fin', {
                ...configCalendrier,
                onChange: function(selectedDates, dateStr) {
                    // Mettre à jour la date maximale pour le calendrier de début
                    dateDebutPicker.set('maxDate', dateStr);
                    calculeTotal();
                }
            });
            
            // Option de livraison
            const optionLivraison = document.getElementById('option_livraison');
            const addressContainer = document.getElementById('addressContainer');
            const addressField = document.getElementById('address');
            const fraisLivraisonSection = document.getElementById('fraisLivraisonSection');
            
            optionLivraison.addEventListener('change', function() {
                if (this.value === 'domicile') {
                    addressContainer.style.display = 'block';
                    addressField.setAttribute('required', 'true');
                    fraisLivraisonSection.style.display = 'flex';
                } else {
                    addressContainer.style.display = 'none';
                    addressField.removeAttribute('required');
                    fraisLivraisonSection.style.display = 'none';
                }
                calculeTotal();
            });
            
            // Calculer le total
            function calculeTotal() {
                const dateDebut = document.getElementById('date_debut').value;
                const dateFin = document.getElementById('date_fin').value;
                const optionLivraisonValue = optionLivraison.value;
                
                if (dateDebut && dateFin) {
                    const debut = new Date(dateDebut);
                    const fin = new Date(dateFin);
                    const diffTime = Math.abs(fin - debut);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 pour inclure le jour de début
                    
                    const prixJournalier = <?= $annonce['prix_journalier'] ?>;
                    let total = diffDays * prixJournalier;
                    
                    // Ajouter les frais de livraison si l'option domicile est sélectionnée
                    if (optionLivraisonValue === 'domicile') {
                        total += fraisLivraison;
                    }
                    
                    document.getElementById('nbJours').textContent = diffDays;
                    document.getElementById('prixTotal').textContent = total + ' dh';
                }
            }
            
            // Vérifier si l'objet est disponible
            const isObjetDisponible = <?= $objet_disponible ? 'true' : 'false' ?>;
            
            // Réinitialiser le formulaire
            document.getElementById('btnAnnuler').addEventListener('click', function() {
                dateDebutPicker.clear();
                dateFinPicker.clear();
                optionLivraison.value = '';
                addressContainer.style.display = 'none';
                fraisLivraisonSection.style.display = 'none';
                document.getElementById('nbJours').textContent = '0';
                document.getElementById('prixTotal').textContent = '0 dh';
            });
            
            // Valider le formulaire avant soumission
            document.getElementById('reservationForm').addEventListener('submit', function(e) {
                // Si l'objet n'est pas disponible, empêcher la soumission du formulaire
                if (!isObjetDisponible) {
                    e.preventDefault();
                    alert("Cet objet est actuellement loué et n'est pas disponible à la réservation.");
                    return;
                }
                
                const dateDebut = document.getElementById('date_debut').value;
                const dateFin = document.getElementById('date_fin').value;
                const optionLivraisonValue = optionLivraison.value;
                const address = document.getElementById('address').value;
                
                if (!dateDebut || !dateFin) {
                    e.preventDefault();
                    alert("Veuillez sélectionner des dates de réservation");
                    return;
                }
                
                if (!optionLivraisonValue) {
                    e.preventDefault();
                    alert("Veuillez choisir une option de livraison");
                    return;
                }
                
                if (optionLivraisonValue === 'domicile' && !address.trim()) {
                    e.preventDefault();
                    alert("Veuillez entrer une adresse de livraison");
                    return;
                }
            });
        });
    </script>
</body>
</html>