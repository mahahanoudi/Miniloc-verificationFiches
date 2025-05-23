<?php
session_start();
include '../BD/connexion.php';
include 'objetTraitement.php';

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si l'utilisateur est connecté et est un propriétaire
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'proprietaire') {
    $_SESSION['error'] = "Vous devez être connecté en tant que propriétaire pour effectuer cette action.";
    header('Location: ../IHM/form_objet.php');
    exit();
}

// Log des données reçues
error_log("Données POST reçues : " . print_r($_POST, true));
error_log("Données FILES reçues : " . print_r($_FILES, true));

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérifier les champs requis
        $required_fields = ['nom', 'categorie_id', 'description', 'ville', 'prix_journalier'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Le champ $field est requis");
            }
        }

        $nom = trim($_POST['nom']);
        $categorie_id = (int)$_POST['categorie_id'];
        $description = trim($_POST['description']);
        $ville = trim($_POST['ville']);
        $prix_journalier = (float)$_POST['prix_journalier'];
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;

        // Validation des données
        if (strlen($nom) < 3) {
            throw new Exception("Le nom de l'objet doit contenir au moins 3 caractères");
        }
        if ($prix_journalier <= 0) {
            throw new Exception("Le prix journalier doit être supérieur à 0");
        }

        // Vérifier si c'est une modification
        if ($id) {
            // Vérifier que l'objet appartient bien au propriétaire
            $stmt = $conn->prepare("SELECT * FROM objet WHERE id = ? AND proprietaire_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $objet = $stmt->fetch();

            if (!$objet) {
                throw new Exception("Vous n'êtes pas autorisé à modifier cet objet");
            }

            // Mise à jour de l'objet
            $stmt = $conn->prepare("
                UPDATE objet 
                SET nom = ?, categorie_id = ?, description = ?, ville = ?, prix_journalier = ?
                WHERE id = ? AND proprietaire_id = ?
            ");
            
            $result = $stmt->execute([
                $nom, $categorie_id, $description, $ville, $prix_journalier,
                $id, $_SESSION['user_id']
            ]);

            if (!$result) {
                $error = $stmt->errorInfo();
                throw new Exception("Erreur lors de la modification de l'objet : " . $error[2]);
            }

            $objet_id = $id;
        } else {
            // Création d'un nouvel objet
            $stmt = $conn->prepare("
    INSERT INTO objet (nom, categorie_id, description, ville, prix_journalier, proprietaire_id, etat)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$result = $stmt->execute([
    $nom, $categorie_id, $description, $ville, $prix_journalier, $_SESSION['user_id'], 'non_loue'
]);


            if (!$result) {
                $error = $stmt->errorInfo();
                throw new Exception("Erreur lors de la création de l'objet : " . $error[2]);
            }

            $objet_id = $conn->lastInsertId();
        }

        // Traitement des images
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Impossible de créer le dossier d'upload");
                }
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['images']['name'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Vérifier l'extension
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($file_ext, $allowed_ext)) {
                        continue;
                    }

                    // Vérifier la taille du fichier (max 5MB)
                    if ($_FILES['images']['size'][$key] > 5 * 1024 * 1024) {
                        continue;
                    }

                    // Générer un nom unique
                    $new_file_name = uniqid() . '.' . $file_ext;
                    $file_path = $upload_dir . $new_file_name;

                    // Déplacer le fichier
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        // Ajouter l'image dans la base de données
                        $stmt = $conn->prepare("INSERT INTO image (objet_id, url) VALUES (?, ?)");
                        if (!$stmt->execute([$objet_id, $new_file_name])) {
                            // Si l'insertion échoue, supprimer le fichier
                            unlink($file_path);
                            throw new Exception("Erreur lors de l'enregistrement de l'image");
                        }
                    } else {
                        throw new Exception("Erreur lors du téléchargement de l'image");
                    }
                }
            }
        }

        // Suppression des images
        if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $image_id) {
                // Vérifier que l'image appartient bien à l'objet
                $stmt = $conn->prepare("
                    SELECT url FROM image 
                    WHERE id = ? AND objet_id = ?
                ");
                $stmt->execute([$image_id, $objet_id]);
                if ($image = $stmt->fetch()) {
                    // Supprimer le fichier
                    $file_path = $upload_dir . $image['url'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    // Supprimer l'entrée dans la base de données
                    $stmt = $conn->prepare("DELETE FROM image WHERE id = ?");
                    $stmt->execute([$image_id]);
                }
            }
        }

        $_SESSION['success'] = "Objet " . ($id ? "modifié" : "créé") . " avec succès !";
        header('Location: ../IHM/liste_objets.php');
        exit();

    } catch (Exception $e) {
        error_log("Erreur dans traitement_objet.php : " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header('Location: ../IHM/form_objet.php' . ($id ? "?id=$id" : ""));
        exit();
    }
}

// Si on arrive ici, c'est qu'il y a eu une erreur
$_SESSION['error'] = "Une erreur est survenue lors du traitement de votre demande. Veuillez réessayer.";
header('Location: ../IHM/form_objet.php');
exit();
?> 