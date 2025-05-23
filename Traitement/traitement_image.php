<?php
session_start();
include '../BD/connexion.php';

// Vérifier si l'utilisateur est connecté et est un propriétaire
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'proprietaire') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Traitement de l'upload d'image
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['objet_id'])) {
            throw new Exception('ID de l\'objet manquant');
        }

        if (!isset($_FILES['image'])) {
            throw new Exception('Aucune image n\'a été envoyée');
        }

        $objet_id = intval($_POST['objet_id']);
        $upload_dir = '../uploads/';

        // Vérifier si le dossier uploads existe, sinon le créer
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file = $_FILES['image'];
        $fileName = uniqid() . '_' . basename($file['name']);
        $targetPath = $upload_dir . $fileName;

        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Type de fichier non autorisé. Formats acceptés : JPG, PNG, GIF');
        }

        // Vérifier la taille du fichier (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('L\'image est trop volumineuse. Taille maximale : 5MB');
        }

        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Erreur lors de l\'upload du fichier');
        }

        // Enregistrer l'image dans la base de données
        $stmt = $conn->prepare("INSERT INTO image (url, objet_id) VALUES (?, ?)");
        $stmt->execute([$fileName, $objet_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Image uploadée avec succès',
            'image_id' => $conn->lastInsertId(),
            'url' => $fileName
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// Traitement de la suppression d'image
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        parse_str(file_get_contents("php://input"), $_DELETE);
        
        if (!isset($_DELETE['image_id'])) {
            throw new Exception('ID de l\'image manquant');
        }

        $image_id = intval($_DELETE['image_id']);

        // Récupérer l'URL de l'image
        $stmt = $conn->prepare("SELECT url FROM image WHERE id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch();

        if (!$image) {
            throw new Exception('Image non trouvée');
        }

        // Supprimer le fichier physique
        $file_path = '../uploads/' . $image['url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Supprimer l'enregistrement de la base de données
        $stmt = $conn->prepare("DELETE FROM image WHERE id = ?");
        $stmt->execute([$image_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Image supprimée avec succès'
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?> 