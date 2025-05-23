<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
}
?>
<style>
    /* Styles communs conservés */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Comic Neue', cursive;
    }

    body {
        background: #f8f9fa;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        background-image: linear-gradient(to bottom right, #b3e5fc, #f8bbd0);
    }

    .header {
        width: 100%;
        padding: 20px;
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .logo {
        font-size: 2.5rem;
        color: #2196F3;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .form-container {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        margin-top: 50px;
        margin-bottom: 30px;
        width: 90%;
        max-width: 500px;
        position: relative;
        overflow: hidden;
    }

    /* Styles spécifiques à l'inscription */
    h2 {
        color: #2196F3;
        margin-bottom: 30px;
        text-align: center;
        font-size: 2rem;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="file"] {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s ease;
        margin: 8px 0;
    }

    input:focus {
        outline: none;
        border-color: #2196F3;
        box-shadow: 0 0 8px rgba(33, 150, 243, 0.2);
    }

    .conditions {
        margin: 20px 0;
        padding: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        max-height: 200px;
        overflow-y: auto;
        background: #f8f9fa;
    }

    .role-selection {
        margin: 15px 0;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
    }

    .role-selection label {
        display: inline-flex;
        align-items: center;
        margin-right: 20px;
        color: #616161;
    }

    input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin-right: 8px;
        accent-color: #2196F3;
    }

    button[type="submit"] {
        width: 100%;
        padding: 15px;
        background: #2196F3;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        font-weight: bold;
        letter-spacing: 1px;
    }

    button[type="submit"]:hover {
        background: #1976D2;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(33, 150, 243, 0.3);
    }

    .file-upload {
        display: flex;
        /* Ajouter cette propriété obligatoire pour flexbox */
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        margin: 15px 0;
        padding: 15px;
        border: 2px dashed #e0e0e0;
        border-radius: 10px;
        text-align: left;
        /* Remplacer 'center' par 'left' */
    }

    .file-upload label {
        font-weight: bold;
        margin-bottom: 0;
        /* Retirer la marge négative */
    }

    .file-upload input[type="file"] {
        display: block;
        width: 100%;
    }

    .baby-icons {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin: 30px 0;
    }

    .baby-icons img {
        width: 50px;
        opacity: 0.3;
    }

    #acceptBox {
        margin: 20px 0;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        text-align: center;
    }

    @media (max-width: 480px) {
        .form-container {
            padding: 30px;
        }

        h2 {
            font-size: 1.8rem;
        }
    }
</style>
<link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="header">
        <div class="logo">
            <i class="fas fa-baby-carriage"></i>
            MiniLoc
        </div>
    </div>

    <div class="form-container">
        <h2>Inscription 👶</h2>


        <form action="../Traitement/traitement_inscription.php" method="POST" enctype="multipart/form-data">
            <!-- Les champs existants avec le même style -->
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="prenom" placeholder="Prénom" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
            <input type="text" name="CIN" placeholder="CIN" required>
            <input type="text" name="address" placeholder="Adresse" required>

            <div class="role-selection">
                <label>
                    <input type="checkbox" name="roles[]" value="client" id="clientCheck"> Client
                </label>
                <label>
                    <input type="checkbox" name="roles[]" value="proprietaire" id="partenaireCheck"> Partenaire
                </label>
            </div>

            <div id="dynamicConditions"></div>

            <div class="file-upload">
                <label>image profil</label>
                <input type="file" name="img_profil" accept="image/*">
                <label>image CIN front</label>
                <input type="file" name="img_cin_front" accept="image/*" required>
                <label>image CIN back</label>
                <input type="file" name="img_cin_back" accept="image/*" required>
            </div>

            <div id="acceptBox">
                <label>
                    <input type="checkbox" name="accept_conditions" required>
                    J'accepte les conditions
                </label>
            </div>

            <button type="submit">
                <i class="fas fa-baby"></i> S'inscrire
            </button>
        </form>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const clientCheck = document.getElementById('clientCheck');
            const partenaireCheck = document.getElementById('partenaireCheck');
            const container = document.getElementById('dynamicConditions');

            function updateConditions() {
                const selected = [];
                if (clientCheck.checked) selected.push('client');
                if (partenaireCheck.checked) selected.push('partenaire');

                container.innerHTML = '';

                selected.forEach(role => {
                    fetch(../IHM/conditions_${role}.php)
                        .then(response => response.text())
                        .then(data => {
                            const div = document.createElement('div');
                            div.className = 'conditions';
                            div.innerHTML = data;
                            container.appendChild(div);
                        });
                });

                document.querySelector('input[name="accept_conditions"]').required = selected.length > 0;
            }

            clientCheck.addEventListener('change', updateConditions);
            partenaireCheck.addEventListener('change', updateConditions);
        });
    </script>
</body>

</html>