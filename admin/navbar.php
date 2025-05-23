<nav class="navbar">
    <div class="nav-section">
        <div class="logo"><i class="fa-solid fa-baby"></i> BabyShop</div>
        <ul class="nav-links">
             <li><a href="../admin/tableau_de_bord_admin.php"><i class="fa-solid fa-gift"></i> Annonces</a></li>
            <li><a href="../admin/gestion_commentaires.php"><i class="fa-solid fa-comments"></i> Commentaires</a></li>
            <li><a href="../admin/Utilisateur_admin.php"><i class="fa-solid fa-user"></i> Utilisateurs</a></li>
        
        </ul>
    </div>

    <div class="auth-buttons">
        <?php
        if (isset($_SESSION['admin_id'])) {
            
            echo '<a href="../Traitement/deconnexion.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>';
        } else{
            echo '<a href="../IHM/connexion_admin.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Se connecter</a>';
        }
        ?>
    </div>
</nav>

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

    .auth-buttons {
        display: flex;
        gap: 15px;
        margin-left: auto;
    }

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
        color: #2196f3;
    }

    .signup {
        background-color: #e91e63;
        color: #fff;
    }

    .login {
        border: 1px solid #2196f3;
        color: #2196f3;
    }

    .logout {
        color: #e91e63 !important;
        border-color: #e91e63;
    }

    .btn-switch, .devenir-role {
        border-color: #2196F3;
        color: #2196F3 !important;
    }

    @media (max-width: 1200px) {
        .navbar {
            padding: 15px 20px;
        }

        .nav-links {
            display: none;
        }

        .auth-buttons {
            flex: 1;
        }
    }
</style>
