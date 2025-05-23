<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si les fichiers requis existent
$required_files = [
    '../BD/connexion.php',
    '../Traitement/objetTraitement.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        die("Erreur : Le fichier $file est manquant. Veuillez vérifier l'installation.");
    }
}

include '../BD/connexion.php';
include '../Traitement/objetTraitement.php';

// Vérifier si l'utilisateur est connecté et est un propriétaire
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'proprietaire') {
    $_SESSION['error'] = "Vous devez être connecté en tant que propriétaire pour accéder à cette page.";
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$objet = null;
$images = [];

// Si on modifie un objet existant
if (isset($_GET['id'])) {
    $objet = getObjetById($_GET['id']);
    if ($objet) {
        $images = getImagesObjet($objet['id']);
    }
}

// Récupérer les catégories
try {
    $stmt = $conn->query("SELECT * FROM categorie ORDER BY nom");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des catégories : " . $e->getMessage();
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $objet ? 'Modifier' : 'Ajouter'; ?> un Objet - MiniLoc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .image-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin: 5px;
            border: 2px solid var(--primary-color);
        }

        .image-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .image-wrapper {
            position: relative;
            display: inline-block;
        }

        .remove-image {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            line-height: 25px;
            text-align: center;
            cursor: pointer;
            font-size: 14px;
        }

        .remove-image:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="espace_partenaire.php">MiniLoc</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="espace_partenaire.php"><i class="fas fa-home"></i> Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="liste_annonces.php"><i class="fas fa-list"></i> Mes Annonces</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="form-container">
            <h2 class="mb-4"><?php echo $objet ? 'Modifier' : 'Ajouter'; ?> un Objet</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="../Traitement/traitement_objet.php" method="POST" enctype="multipart/form-data" id="objetForm">
                <?php if ($objet): ?>
                    <input type="hidden" name="id" value="<?php echo $objet['id']; ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="nom" class="form-label">Nom de l'objet</label>
                    <input type="text" class="form-control" id="nom" name="nom" required
                           value="<?php echo $objet ? htmlspecialchars($objet['nom']) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label for="categorie_id" class="form-label">Catégorie</label>
                    <select class="form-select" id="categorie_id" name="categorie_id" required>
                        <option value="">Sélectionnez une catégorie</option>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?php echo $categorie['id']; ?>"
                                    <?php echo ($objet && $objet['categorie_id'] == $categorie['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categorie['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $objet ? htmlspecialchars($objet['description']) : ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="ville" class="form-label">Ville</label>
                    <input type="text" class="form-control" id="ville" name="ville" required
                           value="<?php echo $objet ? htmlspecialchars($objet['ville']) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label for="prix_journalier" class="form-label">Prix journalier (DHS)</label>
                    <input type="number" class="form-control" id="prix_journalier" name="prix_journalier" 
                           min="0" step="0.01" required
                           value="<?php echo $objet ? htmlspecialchars($objet['prix_journalier']) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label for="images" class="form-label">Images de l'objet</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                    <div id="image-preview" class="mt-2 d-flex flex-wrap gap-2">
                        <?php if ($objet): 
                            $images = getImagesObjet($objet['id']);
                            foreach ($images as $image): ?>
                                <div class="image-container position-relative" data-image-id="<?php echo $image['id']; ?>">
                                    <img src="../uploads/<?php echo htmlspecialchars($image['url']); ?>" 
                                         class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                            onclick="supprimerImage(<?php echo $image['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endforeach;
                        endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="liste_objets.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $objet ? 'Modifier' : 'Ajouter'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prévisualisation des images
        document.getElementById('images').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            const files = e.target.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'image-container position-relative';
                        div.innerHTML = `
                            <img src="${e.target.result}" class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" onclick="this.parentElement.remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        preview.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                }
            }
        });

        // Suppression d'une image
        function supprimerImage(imageId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                fetch('../Traitement/traitement_image.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: image_id=${imageId}
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const imageContainer = document.querySelector([data-image-id="${imageId}"]);
                        if (imageContainer) {
                            imageContainer.remove();
                        }
                    } else {
                        alert(data.message || 'Erreur lors de la suppression de l\'image');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue');
                });
            }
        }

        // Gestion de la soumission du formulaire
        document.getElementById('objetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validation des champs requis
            const requiredFields = ['nom', 'categorie_id', 'description', 'ville', 'prix_journalier'];
            for (const field of requiredFields) {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    alert(Le champ ${input.previousElementSibling.textContent} est requis);
                    input.focus();
                    return;
                }
            }

            // Soumettre le formulaire directement
            this.submit();
        });
    </script>
</body>
</html>