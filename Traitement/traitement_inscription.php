
<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
}
include '../BD/utilisateurBD.php'; // Assure-toi que cette inclusion est correcte
  // Vérifie également cette inclusion

// Vérification que les champs sont bien envoyés et non vides
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = isset($_POST['nom']) ? $_POST['nom'] : '';
    $prenom = isset($_POST['prenom']) ? $_POST['prenom'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $mot_de_passe = isset($_POST['mot_de_passe']) ? password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT) : '';
    $CIN = isset($_POST['CIN']) ? $_POST['CIN'] : '';
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $roles = isset($_POST['roles']) ? $_POST['roles'] : [];

    // Vérification que l'utilisateur a accepté les conditions générales
    if (!isset($_POST['accept_conditions'])) {
        echo "Vous devez accepter les conditions générales pour vous inscrire.";
        exit;
    }

    // Si les champs requis sont vides, renvoyer un message d'erreur
    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($CIN) || empty($address)) {
        echo "Tous les champs sont obligatoires.";
        exit;
    }

    // Récupérer les rôles sélectionnés
    $est_client = in_array('client', $roles) ? 1 : 0;
    $est_partenaire = in_array('proprietaire', $roles) ? 1 : 0;

    // Si aucun rôle n'est sélectionné, définir un rôle par défaut (par exemple 'client')
    $role = 'client';
    if (count($roles) == 1) {
        $role = $roles[0];
    }

    // Fonction pour uploader les images
    function uploadImage($fileInputName, $destinationFolder = "../uploads/")
    {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === 0) {
            $tmp = $_FILES[$fileInputName]['tmp_name'];
            $name = uniqid() . "_" . basename($_FILES[$fileInputName]['name']);
            $dest = $destinationFolder . $name;
            if (!file_exists($destinationFolder)) {
                mkdir($destinationFolder, 0777, true);
            }
            move_uploaded_file($tmp, $dest);
            return $dest;
        } else {
            return null; // Si aucun fichier n'est téléchargé
        }
    }

    // Enregistrer les images dans un dossier spécifique
    $img_profil = uploadImage('img_profil');
    $img_cin_front = uploadImage('img_cin_front');
    $img_cin_back = uploadImage('img_cin_back');

    // Vérification de la connexion à la base de données
    if (!$conn) {
        echo "Erreur de connexion à la base de données.";
        exit;
    }

    // Appel de la fonction pour insérer l'utilisateur dans la base de données
    $success = insererUtilisateur(
        $nom,
        $prenom,
        $email,
        $mot_de_passe,
        $role,
        $CIN,
        $img_profil,
        $img_cin_front,
        $img_cin_back,
        $address,
        $est_client,
        $est_partenaire
    );

    // Si l'inscription est réussie
    if ($success) {
        // Récupérer TOUTES les infos du nouvel utilisateur
        $utilisateur = getUtilisateurParEmail($email); // Utilisez votre fonction existante
        
        // Créer la session
        $_SESSION['user_id'] = $utilisateur['id'];
        $_SESSION['user_email'] = $utilisateur['email'];
        $_SESSION['role'] = $utilisateur['role'];
        $_SESSION['is_client'] = $utilisateur['est_client'];
        $_SESSION['is_partenaire'] = $utilisateur['est_partenaire'];

        header("Location: traitement_index.php"); // Redirection immédiate
        exit;
    } else {
        echo "Erreur lors de l'inscription.";
    }
}
