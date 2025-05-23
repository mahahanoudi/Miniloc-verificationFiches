<?php
session_start();

include_once('../Traitement/traitement_page_acceuil.php');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miniloc - Location d'objets pour bébés</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --rose: #FFD1DC;
            --bleu-ciel: #87CEEB;
            --beige: #F5F5DC;
            --blanc: #FFFFFF;
        }

        /* Navbar personnalisée */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 50px;
            background-color: var(--blanc);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--bleu-ciel);
            margin-right: 40px;
        }

        .nav-section {
            display: flex;
            align-items: center;
            gap: 40px;
            flex-grow: 1;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--bleu-ciel);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--rose);
        }

        .search-bar {
            flex: 0 1 400px;
            margin: 0 20px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 8px 15px 8px 35px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
        }

        .search-bar i {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: #aaa;
        }

        .auth-buttons {
            display: flex;
            gap: 15px;
            margin-left: auto;
        }

        .auth-buttons a {
            padding: 8px 20px;
            border-radius: 15px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .signup {
            background-color: var(--rose);
            color: #333;
        }

        .login {
            border: 1px solid var(--bleu-ciel);
            color: var(--bleu-ciel);
        }

        @media (max-width: 1200px) {
            .navbar {
                padding: 15px 20px;
            }

            .nav-links {
                display: none;
            }

            .search-bar {
                flex: 1;
            }
        }



        .hero-section {

            background-size: cover;
            padding: 100px 0;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--rose);
            border: none;
            color: #333;
        }

        .btn-primary:hover {
            background-color: var(--bleu-ciel);
        }

        .card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s;
            background-color: var(--blanc);
        }

        .card:hover {
            transform: translateY(-10px);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .category-badge {
            background-color: var(--bleu-ciel);
            color: white;
        }

        .card-rating {
            background-color: #FFF9C4;
            color: #FFA000;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .reserve-btn {
            background-color: #FFB6C1;
            /* Couleur rose bébé */
            color: #fff;
            border: none;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .reserve-btn:hover {
            background-color: #FF9AAC;
            /* Rose plus foncé au survol */
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            color: #fff;
        }


        .hero-section {
            display: flex;
            align-items: center;
            min-height: 70vh;
            padding: 0;
            background:rgba(255, 236, 255, 0.46);
        }

        .content-left {
            flex: 1;
            padding-right: 150px;
        }

        .brand-title {
            font-size: 3.5rem;
            color: #e91e63;
            margin-bottom: 1.1rem;
            font-weight: 700;
        }

        .subtitle {
            font-size: 2.2rem;
            color: #333;
            margin-bottom: 1.1rem;
            font-weight: 300;
        }

        .description {
            font-size: 1.1rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.4rem;
            max-width: 500px;
        }

        .cta-button {
            display: inline-block;
            padding: 1rem 2.5rem;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 500;
            transition: transform 0.3s ease;
        }

        .cta-button:hover {
            transform: translateY(-3px);
        }

        .circle-image {
            border-radius: 50%;
            position: absolute;
            background-size: cover;
            background-position: center;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .image-container {
            flex: 1;
            position: relative;
            height: 450px;
            overflow: hidden; /* Cache les parties qui débordent */
            padding-right: 40px;
        }

        .medium-circle {
            width: 350px;
            height: 350px;
            right: 50px; /* Déborde sur la droite */
            top: 40px; /* Déborde en haut */
            z-index: 1;
        }

        .small-circle {
            width: 220px;
            height: 220px;
            left: 50px; /* Positionné à gauche */
            bottom: 20px; /* Positionné en bas */
            z-index: 2;
        }

        @media (max-width: 768px) {
            .hero-section {
                flex-direction: column;
                text-align: center;
                padding: 4rem 5%;
            }

            .content-left {
                padding-right: 0;
                margin-top: 3rem;
            }

            .image-container {
                width: 100%;
                height: 400px;
            }

            
            .medium-circle {
                right: -50px;
                top: -50px;
                width: 350px;
                height: 350px;
            }

            .small-circle {
                left: 20px;
                bottom: 20px;
                width: 180px;
                height: 180px;
            }
        }

        footer {
            background-color: var(--bleu-ciel);
            color: white;
        }

    </style>
</head>
<body>
<?php include ('navbar.php'); ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="content-left">
            <h1 class="brand-title">MINILOC</h1>
            <h2 class="subtitle">L'univers de bébé...<br>en location!</h2>
            <p class="description">Des solutions simples pour les<br>premiers pas.</p>
        </div>

        <div class="image-container">
            <!-- Remplacer par vos images -->
            <div class="circle-image small-circle " style="background-image: url('../photos/IMAGE2.jpg')"></div>
            <div class="circle-image medium-circle" style="background-image: url('../photos/IMAGE1.png')"></div>
        </div>
    </section>

    <!-- Catégories -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5" style="color: var(--bleu-ciel);">Catégories populaires</h2>
            <div class="row">
            <div class="col-md-3 mb-4 text-center">
           <a href="../IHM/poussettes.php?categorie=Poussettes" style="text-decoration: none; color: inherit;">
           <div class="p-4 rounded" style="background-color: var(--rose);">
            <i class="fas fa-baby-carriage fa-3x mb-3"></i>
            <h5>Poussettes</h5>
              </div>
            </a>
             </div>

             <div class="col-md-3 mb-4 text-center">
           <a href="../IHM/Berceaux.php?categorie=Berceaux" style="text-decoration: none; color: inherit;">
           <div class="p-4 rounded" style="background-color: var(--bleu-ciel); color: white;">
            <i class="fas fa-bed fa-3x mb-3"></i>
            <h5>Berceaux</h5>
              </div>
            </a>
             </div>

               
                
             <div class="col-md-3 mb-4 text-center">
   <a href="../IHM/vetements.php?categorie=Vêtements" style="text-decoration: none; color: inherit;">
   <div class="p-4 rounded" style="background-color: var(--rose);">
      <i class="fas fa-tshirt fa-3x mb-3"></i>
      <h5>Vêtements</h5>
   </div>
   </a>
</div>

<div class="col-md-3 mb-4 text-center">
   <a href="../IHM/jouets.php?categorie=Jouets" style="text-decoration: none; color: inherit;">
   <div class="p-4 rounded" style="background-color: var(--bleu-ciel); color: white;">
      <i class="fas fa-baby fa-3x mb-3"></i>
      <h5>Jouets</h5>
   </div>
   </a>
</div>

            </div>
        </div>
    </section>

    <section class="py-5" style="background-color: var(--blanc);">
    <div class="container">
        <h2 class="text-center mb-5" style="color: var(--bleu-ciel);" >Annonces Premium</h2>
        <div class="row">
            <?php foreach($annonces as $annonce): ?>
            <div class="col-md-4 mb-4" id="poussette-card" style="cursor: pointer;">
            <a href="../IHM/detailsAnnonce.php?id=<?= urlencode($annonce['id']) ?><?= isset($annonce['note_moyenne']) && $annonce['note_moyenne'] !== null ? '&note=' . urlencode($annonce['note_moyenne']) : '' ?>" style="text-decoration: none; color: inherit;">
                <div class="card h-100">
                   <img src="../uploads/<?= htmlspecialchars($annonce['image_url'] ?? 'images.png') ?>"  class="card-img-top" alt="<?php echo htmlspecialchars($annonce['objet_nom']); ?>">
                    <div class="card-body">
                        <span class="badge category-badge mb-2"><?php echo htmlspecialchars($annonce['categorie_nom']); ?></span>
                        <h5 class="card-title"><?php echo htmlspecialchars($annonce['objet_nom']); ?></h5>
                        <p class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($annonce['ville']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold" style="color: var(--bleu-ciel);"><?php echo htmlspecialchars($annonce['prix_journalier']); ?>dh/jour</span>
                            <?php if (!is_null($annonce['note_moyenne'])): ?>
                                <span class="card-rating">
                                <i class="fas fa-star"></i>
                                <?= number_format($annonce['note_moyenne'], 1) ?>
                                </span>
                            <?php else: ?>
                                <span class="card-rating" style="background-color: #e9ecef; color: #6c757d;">
                                    <i ></i> Pas noté
                                </span>
                            <?php endif; ?>
                        </div>
                        <div>
                        <a href="../IHM/formulaire_reservation.php?annonce_id=<?= htmlspecialchars($annonce['id']) ?>" class="btn w-100 reserve-btn">
            <i class="fas fa-calendar-check me-2"></i>Réserver
            </a>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
                <a href="../IHM/produits.php" class="btn btn-primary px-4"><i class="fas fa-list"></i> Voir toutes les annonces</a>
            </div>
    </div>


    <!--- Dialogues ---->
    

</div>
    </section>
    <!-- Footer -->
    <footer class="py-4">
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