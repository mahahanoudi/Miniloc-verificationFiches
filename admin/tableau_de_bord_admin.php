<?php
session_start();

include_once('../Traitement/traitement_annonce_admin.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miniloc - Location d'objets pour bébés</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/produits.css">
    <style>
       .reserve-btn {
    background-color: #FFB6C1; /* Couleur rose bébé */
    color: #fff;
    border: none;
    border-radius: 20px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.reserve-btn:hover {
    background-color: #FF9AAC; /* Rose plus foncé au survol */
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    color: #fff;
}
    </style>
</head>
<body  style="background-color: #FFFFFF;">
    <?php include 'navbar.php'; ?>
    <?php
   
    // Vérification de la connexion de l'admin
    if (!isset($_SESSION['admin_id']) ) {
        echo '<div class="container text-center my-5">
                <div class="alert alert-danger">
                    <h4>Accès refusé</h4>
                    <p>Vous devez être connecté en tant qu’administrateur pour accéder à cette page.</p>
                    <a href="../IHM/connexion_admin.php" class="btn btn-primary mt-3">Se connecter</a>
                </div>
            </div>';
        exit(); 
    }
    ?>
    <div class="container py-5"  >
        <!-- Section de recherche -->
        <div class="search-container mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="ville" class="form-label">Ville</label>
                    <select class="form-select" id="ville" name="ville">
                        <option value="">Toutes les villes</option>
                        <?php foreach ($villes as $v): ?>
                            <option value="<?= htmlspecialchars($v) ?>" <?= $ville == $v ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                <label for="categorie_id" class="form-label">Catégorie</label>
    <select class="form-select" id="categorie_id" name="categorie_id">
        <option value="">Toutes catégories</option>
        <?php
        foreach ($categories as $cat): 
            $selected = ($_GET['categorie_id'] ?? '') == $cat['id'] ? 'selected' : '';
        ?>
            <option value="<?= htmlspecialchars($cat['id']) ?>" <?= $selected ?>>
                <?= htmlspecialchars($cat['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>
 </div>


                <div class="col-md-2">
    <label for="prix_exact" class="form-label">Prix  (dh/jour)</label>
    <select class="form-select" id="prix_interval" name="prix_interval">
        <option value="">Tous les prix</option>
        <option value="0-20" <?= ($_GET['prix_interval'] ?? '') === '0-20' ? 'selected' : '' ?>>0 - 20 dh</option>
        <option value="20-40" <?= ($_GET['prix_interval'] ?? '') === '20-40' ? 'selected' : '' ?>>20 - 40 dh</option>
        <option value="40-60" <?= ($_GET['prix_interval'] ?? '') === '40-60' ? 'selected' : '' ?>>40 - 60 dh</option>
        <option value="60+" <?= ($_GET['prix_interval'] ?? '') === '60+' ? 'selected' : '' ?>>60 dh et plus</option>
    </select>
            </div>
                <div class="col-md-2">
                    <label for="note_min" class="form-label">Note minimale</label>
                    <select class="form-select" id="note_min" name="note_min">
        <option value="">Toutes les notes</option>
        <?php
        for ($i = 1; $i <= 5; $i += 1) {
            $selected = ($note_min == $i) ? 'selected' : '';
            echo "<option value=\"$i\" $selected>$i ★ et plus</option>";
        }
        ?>
    </select></div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="search-btn w-100">
                        <i class="fas fa-search me-2"></i>Rechercher
                    </button>
                    <?php if(!empty($ville) || !empty($categorie) || !empty($prix_interval) || !empty($note_min)): ?>
                        <a href="?" class="btn reset-btn">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <!-- Ajoutez ce code juste après la balise de fermeture </div> du conteneur de recherche, avant l'affichage des filtres actifs -->
<?php if(isset($_SESSION['message_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['message_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message_success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['message_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['message_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message_error']); ?>
<?php endif; ?>
        <!-- Affichage des filtres actifs -->
        <?php if(!empty($ville) || !empty($categorie) || !empty($prix_interval) || !empty($note_min)): ?>
            <div class="active-filters">
                <?php if(!empty($ville)): ?>
                    <span class="filter-tag">
                        Ville: <?= htmlspecialchars($ville) ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['ville' => ''])) ?>" class="close text-white">&times;</a>
                    </span>
                <?php endif; ?>
                <?php if(!empty($categorie)): ?>
                    <span class="filter-tag">
                        Catégorie: <?= htmlspecialchars($categorie) ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['categorie' => ''])) ?>" class="close text-white">&times;</a>
                    </span>
                <?php endif; ?>
                <?php if (!empty($prix_interval)) : ?>
                    <span class="filter-tag">
                        Prix : <?= htmlspecialchars($prix_interval) ?>dh
                        <a href="?<?= http_build_query(array_merge($_GET, ['prix_interval' => ''])) ?>" class="close text-white">&times;</a>
                    </span>
                <?php endif; ?>
                <?php if(!empty($note_min)): ?>
                    <span class="filter-tag">
                        Note min: <?= htmlspecialchars($note_min) ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['note_min' => ''])) ?>" class="close text-white">&times;</a>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
       
        <?php if(empty($annonces)): ?>
            <div class="no-results">
                <i class="fas fa-search fa-3x"></i>
                <h4>Aucun résultat ne correspond à votre recherche</h4>
                <p class="text-muted">Essayez de modifier vos critères de recherche</p>
                <a href="?" class="btn btn-primary mt-3">
                    <i class="fas fa-undo me-2"></i>Voir toutes les annonces
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($annonces as $annonce): ?>
                    
                    <div class="col-lg-4 col-md-6 mb-4">
                    <a href="fiche_objet_admin.php?id=<?= urlencode($annonce['id']) ?><?= isset($annonce['note_moyenne']) && $annonce['note_moyenne'] !== null ? '&note=' . urlencode($annonce['note_moyenne']) : '' ?>" style="text-decoration: none; color: inherit;">


                        <div class="annonce-card">
                            <div class="card-img-container">
                                <img src="../uploads/<?= htmlspecialchars($annonce['image_url'] ?? 'images.png') ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($annonce['categorie_nom']) ?>">
                            </div>
                            <div class="card-body">
                                <span class="category-badge"><?= htmlspecialchars($annonce['categorie_nom']) ?></span>
                                <h5 class="card-title"><?= htmlspecialchars($annonce['objet_nom']) ?></h5>
                                <p class="card-location">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?= htmlspecialchars($annonce['ville']) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="card-price"><?= htmlspecialchars($annonce['prix_journalier']) ?>dh/jour</span>
                                    <?php if ($annonce['note_moyenne']): ?>
                                        <span class="card-rating">
                                            <i class="fas fa-star"></i> 
                                            <?= number_format($annonce['note_moyenne'], 1); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="card-rating" style="background-color: #e9ecef; color: #6c757d;">
                                            Pas noté
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <!-- Remplacer ceci dans la section qui affiche les boutons dans le fichier principal -->
<div class="mt-3">
    <a href="../Traitement/traitement_validation_admin.php?action=valider&id=<?= htmlspecialchars($annonce['id']) ?>" class="btn btn-primary w-100 mb-3">
        <i class="fas fa-check-circle me-2"></i>Valider
    </a>
    <a href="../Traitement/traitement_validation_admin.php?action=supprimer&id=<?= htmlspecialchars($annonce['id']) ?>" class="btn btn-danger w-100" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce?');">
        <i class="fas fa-trash me-2"></i>Supprimer
    </a>
</div>
                            </div>
                        </div>
                       </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    </div>
    <footer class="py-4"  style="background-color: #87CEEB; color: white;">
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
</body>
</html>