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
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_notification'])) {
    // Récupérer l'état actuel pour inverser
    $stmt = $conn->prepare("SELECT activate_notification FROM utilisateur WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $current = $stmt->fetchColumn();

    if ($current !== false) {
        $new_state = $current ? 0 : 1;
        $upd = $conn->prepare("UPDATE utilisateur SET activate_notification = :new_state WHERE id = :id");
        $upd->bindParam(':new_state', $new_state, PDO::PARAM_INT);
        $upd->bindParam(':id', $user_id, PDO::PARAM_INT);
        if ($upd->execute()) {
            $success = $new_state ? "Notifications activées avec succès." : "Notifications désactivées avec succès.";
        } else {
            $error = "Erreur lors de la mise à jour des notifications.";
        }
    } else {
        $error = "Utilisateur introuvable.";
    }
}

?>




<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BabyShop Navbar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 50px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #e91e63;
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
            color: #2196f3;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #e91e63;
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
            background-color: #e91e63;
            color: #fff;
        }

        .login {
            border: 1px solid #2196f3;
            color: #2196f3;
        }

        .welcome-message {
            color: #e91e63;
            /* Couleur rose coordonnée au logo */
            font-weight: 500;
            margin-right: 25px;
            font-size: 16px;
            letter-spacing: 0.5px;
            padding: 6px;
            border-radius: 20px;
            background-color: rgba(233, 30, 99, 0.1);
            /* Fond semi-transparent */
            transition: all 0.3s ease;
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


        /* Style des boutons */
        .auth-buttons a {
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: 2px solid transparent;
            background: #f8f9fa;
            color: #2196F3;
            margin-left: 10px;
        }

        /* Déconnexion */
        .logout {
            color: #e91e63 !important;
            border-color: #e91e63;
        }



        /* Boutons switch */
        .btn-switch,
        .devenir-role {
            border-color: #2196F3;
            color: #2196F3 !important;
        }

        

        
        .welcome-message {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-align: center;
    border-radius: 50px;
    background: rgba(233, 30, 99, 0.05);
    border: 1px solid rgba(233, 30, 99, 0.15);
    padding-left: 15px;
    
}
.profile-btn {
    display: flex;
    align-items: center;
    background: none;
    border: none;
    cursor: pointer;
    gap: 10px;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 20px;
    transition: background 0.2s;
}
.profile-btn:hover {
    background: #fce4ec;
}
.avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}
.profile-sidebar {
    position: fixed;
    top: 0; right: -320px;
    width: 320px;
    height: 100%;
    background: #fff;
    box-shadow: -2px 0 15px rgba(0,0,0,0.1);
    z-index: 9999;
    transition: right 0.3s;
    display: flex;
    flex-direction: column;
}
.profile-sidebar.open {
    right: 0;
}
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    background: rgba(0,0,0,0.2);
    z-index: 9998;
}
.sidebar-overlay.active {
    display: block;
}
.profile-header {
    padding: 32px 24px 16px 24px;
    text-align: center;
    border-bottom: 1px solid #eee;
    position: relative;
}
.avatar-lg {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    margin-bottom: 10px;
}
.profile-header h4 {
    margin: 0 0 8px 0;
    color: #e91e63;
}
.close-sidebar {
    position: absolute;
    top: 15px; right: 15px;
    background: none;
    border: none;
    font-size: 26px;
    color: #aaa;
    cursor: pointer;
}

.profile-menu {
    position: absolute;
    top: 65px;
    right: 25px;
    width: 340px;
    max-width: 95vw;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    padding: 28px 26px 20px 26px;
    z-index: 10000;
    overflow: auto;
    animation: fadeIn 0.3s;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px);} to { opacity: 1; transform: none;} }
.close-profile-menu {
    position: absolute;
    top: 13px; right: 13px;
    background: none;
    border: none;
    font-size: 22px;
    color: #bbb;
    cursor: pointer;
    transition: color 0.2s;
}
.close-profile-menu:hover { color: #e91e63; }
.profile-header {
    text-align: center;
    margin-bottom: 18px;
}
.profile-header img, .profile-header .avatar {
    width: 85px; height: 85px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e91e63;
    margin-bottom: 8px;
}
.profile-header h4 {
    margin: 8px 0 2px 0;
    color: #e91e63;
    font-size: 1.3rem;
    font-weight: 700;
}
.profile-role {
    font-size: 1rem;
    color: #888;
    display: block;
    margin-bottom: 4px;
}
.profile-list {
    list-style: none;
    padding: 0;
    margin: 0 0 12px 0;
}
.profile-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1rem;
    color: #444;
    margin-bottom: 10px;
}
.profile-list i {
    min-width: 22px;
    font-size: 1.08rem;
    color: #e91e63;
}
.profile-list .text-success { color: #4caf50; }
.profile-list .text-danger { color: #dc3545; }
.profile-list .text-secondary { color: #bbb; }
.notif-form {
    display: inline;
}
.notif-switch {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.25rem;
    margin-left: 8px;
    vertical-align: middle;
    transition: color 0.2s;
}
.notif-switch .fa-toggle-on { color: #4caf50; }
.notif-switch .fa-toggle-off { color: #bbb; }
.notif-switch:hover .fa-toggle-on,
.notif-switch:hover .fa-toggle-off { color: #e91e63; }

.profile-docs h5 {
    font-size: 1.07rem;
    color: #2196f3;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.doc-images {
    display: flex;
    gap: 16px;
    justify-content: center;
}
.doc-images div {
    text-align: center;
}
.doc-images img, .doc-images div > div {
    width: 90px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    margin-bottom: 3px;
}
.doc-images span {
    display: block;
    font-size: 0.93rem;
    color: #888;
    margin-bottom: 3px;
}
.logout-btn {
    margin: 18px auto 0 auto;
    display: block;
    width: 90%;
    padding: 10px 0;
    border-radius: 8px;
    font-size: 1.08rem;
    font-weight: 600;
    background: #e91e63;
    color: #fff;
    text-align: center;
    transition: background 0.2s;
    border: none;
}
.logout-btn:hover { background: #c2185b; }
@media (max-width: 500px) {
    .profile-menu { right: 2vw; left: 2vw; width: 96vw; padding: 15px 3vw; }
    .doc-images { flex-direction: column; gap: 8px; }
}

    </style>
</head>

<body>
    <nav class="navbar">
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