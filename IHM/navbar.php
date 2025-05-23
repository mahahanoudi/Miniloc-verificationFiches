<?php

require_once '../BD/connexion.php';

if (!isset($_SESSION['user_id'])) {
    $isConnected = false;
} else {
    $isConnected = true;
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT * FROM utilisateur WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $isConnected = false;
    }
}

// Fonction utilitaire pour afficher image ou placeholder
function imgOrPlaceholder($img, $alt = 'Image', $width = '100%') {
    if ($img && trim($img) !== '') {
        return '<img src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($alt) . '" style="width:' . $width . '; border-radius:8px; object-fit:cover;">';
    } else {
        return '<div style="width:' . $width . '; height:150px; background:#ddd; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#666;">Pas d\'image</div>';
    }
}
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar">
    <a href="/IHM/index.php" class="logo">MINILOC</a>
    <div class="nav-left">
    <div class="logo"><i class="fa-solid fa-baby"></i> BabyShop</div>
    <?php if ($isConnected): ?>
<button class="profile-btn" id="toggleProfileMenu">
    <img src="<?= htmlspecialchars($user['img_profil'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($user['prenom'] . ' ' . $user['nom']) . '&background=e91e63&color=fff') ?>" alt="avatar" class="avatar" />
    <span><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></span>
    <i class="fa-solid fa-chevron-down"></i>
</button>
<?php endif; ?>

</div>

        <ul class="nav-links">

            <?php
            if (isset($_SESSION['user_id'])) {
                // Rôle CLIENT
                if ($_SESSION['role'] === 'client') {
                    echo '<li><a href="../Traitement/traitement_index.php"><i class="fa-solid fa-heart"></i> Acceuil</a></li>';
                    echo '<li><a href="../IHM/produits.php"><i class="fas fa-bullhorn"></i> Annonces</a></li>';
                }
                // Rôle PROPRIETAIRE
                elseif ($_SESSION['role'] === 'proprietaire') {
                    echo '<li><a href="../IHM/espace_partenaire.php"><i class="fa-solid fa-heart"></i> Acceuil</a></li>';
                    echo '<li><a href="../IHM/liste_annonces.php"><i class="fas fa-bullhorn"></i> Mes Annonces</a></li>';
                }
            } else {
                // Liens par défaut si non connecté
                echo '<li><a href="../Traitement/traitement_index.php"><i class="fa-solid fa-heart"></i> Acceuil</a></li>';
                echo '<li><a href="../IHM/produits.php"><i class="fa-solid fa-gift"></i> Annonces</a></li>';
            }
            ?>

        </ul>

        <div class="auth-buttons">
            <?php

            if (isset($_SESSION['user_id'])) {
                // Connexion établie, on récupère les rôles
                $isClient = $_SESSION['is_client'] ?? 0;
                $isPartenaire = $_SESSION['is_partenaire'] ?? 0;

                // S'il n'est que client
                if ($isClient && !$isPartenaire) {
                    echo '<a href="#" class="btn devenir-role" data-role="partenaire"><i class="fa-solid fa-briefcase"></i> Devenir partenaire</a>';
                } elseif (!$isClient && $isPartenaire) {
                    echo '<a href="#" class="btn devenir-role" data-role="client"><i class="fa-solid fa-user"></i> Devenir client</a>';
                } elseif ($isClient && $isPartenaire) {
                    // Détermination du libellé dynamique
                    $currentRole = $_SESSION['role'];
                    $targetRole = ($currentRole === 'client') ? 'Partenaire' : 'Client';
                    $targetIcon = ($currentRole === 'client') ? 'fa-repeat' : 'fa-repeat';

                    echo '<a href="../Traitement/switch_role.php" class="btn btn-switch">';
                    echo '<i class="fas fa-repeat ' . $targetIcon . ' me-2"></i>';
                    echo  $targetRole;
                    echo '</a>';
                }
                // Bouton de déconnexion
                echo '<a href="../Traitement/deconnexion.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>';
            } else {
                // Utilisateur non connecté
                echo '<a href="../IHM/connexion_admin.php" style="color: #333; text-decoration: none; font-weight: 500; padding-bottom: 2px; border-bottom: 2px solid transparent; transition: 0.3s;" onmouseover="this.style.borderBottom=\'2px solid #007bff\'" onmouseout="this.style.borderBottom=\'2px solid transparent\'">Espace admin</a>';
                echo '<a href="../IHM/inscription.php" style="background-color: #e91e63; color: #fff; padding: 8px 20px; border-radius: 15px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-user-plus"></i> S\'inscrire</a>';
                echo '<a href="../IHM/connexion.php" class="login"><i class="fa-solid fa-right-to-bracket"></i> Connexion</a>';
            }
            ?>


        </div>

    </nav>
    <!-- Modal Conditions -->
    <div class="modal fade" id="conditionsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="conditionsTitle">Conditions Générales</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="conditionsContent">
                    <p>Veuillez lire et accepter nos conditions générales avant de continuer.</p>
                    <div id="lienConditions" style="margin-bottom: 10px;">
                        <!-- Le lien va être ajouté ici dynamiquement -->
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="acceptConditions">
                        <label class="form-check-label" for="acceptConditions">
                            J'accepte les conditions générales
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" id="confirmConditions">Accepter</button>
                </div>
            </div>
        </div>
    </div>
    <?php if ($isConnected): ?>
       

<div id="profileMenu" class="profile-menu" style="display:none;">
    <button class="close-profile-menu" title="Fermer"><i class="fa-solid fa-xmark"></i></button>
    <div class="profile-header">
        <?= imgOrPlaceholder($user['img_profil'], 'Photo de profil', '100px') ?>
        <h4><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h4>
        <span class="profile-role"><i class="fa-solid fa-user-tag"></i> <?= htmlspecialchars(ucfirst($user['role'])) ?></span>
        <form method="post" style="display:inline;">
                <input type="hidden" name="toggle_notification" value="1">
                <button type="submit" class="btn btn-sm <?= $user['activate_notification'] ? 'btn-success' : 'btn-outline-secondary' ?>">
                    <?= $user['activate_notification'] ? 'Activer les notifications' : 'Désactiver les notifications' ?>
                </button>
            </form>

    </div>
    <ul class="profile-list">
        <li><i class="fa-solid fa-envelope"></i> <span><?= htmlspecialchars($user['email']) ?></span></li>
        <li><i class="fa-solid fa-id-card"></i> <span><?= htmlspecialchars($user['CIN']) ?></span></li>
        <li><i class="fa-solid fa-location-dot"></i> <span><?= nl2br(htmlspecialchars($user['address'])) ?></span></li>
        <li><i class="fa-solid fa-user"></i> <span>Client :</span> <?= $user['est_client'] ? '<i class="fa-solid fa-check text-success"></i>' : '<i class="fa-solid fa-xmark text-danger"></i>' ?></li>
        <li><i class="fa-solid fa-handshake"></i> <span>Partenaire :</span> <?= $user['est_partenaire'] ? '<i class="fa-solid fa-check text-success"></i>' : '<i class="fa-solid fa-xmark text-danger"></i>' ?></li>
    </ul>

  
    
</div>
<?php endif; ?>





</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('toggleProfileMenu');
    const menu = document.getElementById('profileMenu');
    const closeBtn = document.querySelector('.close-profile-menu');
    if (btn && menu) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        });
        document.addEventListener('click', function() { menu.style.display = 'none'; });
        menu.addEventListener('click', function(e) { e.stopPropagation(); });
        if (closeBtn) closeBtn.addEventListener('click', () => { menu.style.display = 'none'; });
    }
});
</script>



<script>
    let roleToBecome = '';

    document.querySelectorAll('.devenir-role').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            roleToBecome = this.getAttribute('data-role');

            // Remplir seulement le lien et le texte
            const lienConditions = document.getElementById('lienConditions');
            const conditionsTitle = document.getElementById('conditionsTitle');
            const acceptConditionsLabel = document.querySelector('label[for="acceptConditions"]');

            if (roleToBecome === 'client') {
                conditionsTitle.innerText = 'Devenir Client';
                lienConditions.innerHTML = '<a href="../IHM/conditions_client.php" target="_blank">Lire les conditions pour devenir client</a>';
                acceptConditionsLabel.innerText = "J'accepte les conditions générales pour devenir client";
            } else if (roleToBecome === 'partenaire') {
                conditionsTitle.innerText = 'Devenir Partenaire';
                lienConditions.innerHTML = '<a href="../IHM/conditions_partenaire.php" target="_blank">Lire les conditions pour devenir partenaire</a>';
                acceptConditionsLabel.innerText = "J'accepte les conditions générales pour devenir partenaire";
            }

            // Ouvrir le modal
            var modal = new bootstrap.Modal(document.getElementById('conditionsModal'));
            modal.show();
        });
    });

    // Validation du bouton "Accepter"
    document.getElementById('confirmConditions').addEventListener('click', function() {
        if (!document.getElementById('acceptConditions').checked) {
            alert('Veuillez accepter les conditions pour continuer.');
            return;
        }

        // Envoi AJAX
        let url = (roleToBecome === 'client') ? '../Traitement/devenir_client.php' : '../Traitement/devenir_partenaire.php';

        fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(data => {
                window.location.reload();
            })
            .catch(error => console.error('Erreur:', error));
    });
</script>
<script>
    document.querySelector('.btn-switch').addEventListener('click', function(e) {
        e.preventDefault();

        fetch(this.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Redirection immédiate + rechargement du DOM
                    window.location.href = data.redirectUrl;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Erreur:', error));
    });
</script>

</html>