<?php 
include_once('../Traitement/traitement_produits.php');

$annonces = getToutesLesAnnonces();

$coords_villes = [
    'Casablanca' => [33.589886, -7.603869],
    'Rabat' => [34.020882, -6.841650],
    'Marrakech' => [31.6295, -7.9811],
    'Fès' => [34.033333, -5.000000],
    'Tanger' => [35.7673, -5.7997],
    'Agadir' => [30.4278, -9.5981],
    'Meknès' => [33.8938, -5.5473],
    'Oujda' => [34.6824, -1.9073],
    'Kenitra' => [34.2615, -6.5800],
    'Tetouan' => [35.5785, -5.3686],
    'Safi' => [32.2991, -9.2339],
    'Khouribga' => [33.5634, -6.8899],
    'Beni Mellal' => [32.3373, -6.3498],
    'El Jadida' => [33.2549, -8.5071],
    'Nador' => [35.1684, -2.9339],
    'Ksar El Kebir' => [35.0226, -5.9117],
    'Larache' => [35.1736, -6.1597],
    'Settat' => [33.0000, -7.6167],
    'Taza' => [34.2133, -4.0150],
    'Ouarzazate' => [30.9333, -6.9167],
    'Inezgane' => [30.3914, -9.6003],
    'Al Hoceima' => [35.2500, -3.9333],
    'Guelmim' => [28.9857, -10.0571],
    'Tan-Tan' => [28.4414, -11.1033],
    'Zagora' => [30.3200, -5.8400],
    'Errachidia' => [31.9311, -4.4194]
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte des Villes - Miniloc</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root {
            --rose: #FFD1DC;
            --bleu-ciel: #87CEEB;
            --blanc: #FFFFFF;
        }

        body {
            background-color: var(--blanc);
        }

        h2 {
            color: var(--bleu-ciel);
        }

       #map {
    height: 500px;
    border-radius: 15px;
    margin-bottom: 50px;
}

.map-controls {
    position: absolute;
    top: 80px;
    right: 30px;
    z-index: 1000;
}

.map-btn {
    background-color: var(--bleu-ciel); /* Couleur de fond */
    color: #c2185b; 
    border: none;
    border-radius: 50%;
    padding: 10px 14px;
    font-size: 18px;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.map-btn:hover {
    background-color: #5ab9e6;
    color: #880e4f; /* Couleur de l’icône au survol */
}


footer {
            background-color: var(--bleu-ciel);
            color: white;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container my-5 position-relative">
    <h2 class="text-center mb-4">Carte des annonces</h2>
    <div id="map"></div>
    <div class="map-controls">
        <button class="map-btn" id="resetView" title="Réinitialiser la vue">↻</button>
    </div>
</div>
 <button id="backToTop" title="Retour en haut">↑</button>
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
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const annonces = <?= json_encode($annonces) ?>;
    const coordsVilles = <?= json_encode($coords_villes) ?>;

    // Regrouper les annonces par ville
    const annoncesParVille = {};

    annonces.forEach(annonce => {
        const ville = annonce.ville || 'Ville inconnue';
        if (!annoncesParVille[ville]) annoncesParVille[ville] = [];
        annoncesParVille[ville].push(annonce);
    });

    const map = L.map('map').setView([31.7917, -7.0926], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Pour chaque ville, ajouter un marqueur
    Object.entries(annoncesParVille).forEach(([ville, annoncesVille]) => {
        let lat = null, lng = null;

        // Chercher coordonnées dans les annonces (si plusieurs annonces avec coords différentes, on prend la première trouvée)
        for (const annonce of annoncesVille) {
            if (annonce.latitude && annonce.longitude) {
                lat = annonce.latitude;
                lng = annonce.longitude;
                break;
            }
        }

        // Sinon fallback coordonnées de la ville dans le tableau
        if ((!lat || !lng) && coordsVilles[ville]) {
            lat = coordsVilles[ville][0];
            lng = coordsVilles[ville][1];
        }

        if (lat && lng) {
            const marker = L.marker([parseFloat(lat), parseFloat(lng)]).addTo(map);

            // Construire le contenu HTML de la popup avec la liste des annonces
            let popupContent = `<strong>${ville}</strong><br><ul style="padding-left: 20px;">`;
            annoncesVille.forEach(a => {
                popupContent += `<li><a href="detailsAnnonce.php?id=${a.id}">${a.objet_nom}</a></li>`;
            });
            popupContent += `</ul>`;

            marker.bindPopup(popupContent);
        } else {
            console.warn(`Pas de coordonnées pour la ville: ${ville}`);
        }
    });
    // Bouton de réinitialisation de la vue
    document.getElementById('resetView').addEventListener('click', () => {
        map.setView([31.7917, -7.0926], 6);
    });


</script>
</body>
</html>
