<?php
include 'connexion.php';



function insererUtilisateur($nom, $prenom, $email, $mot_de_passe,
                            $role, $CIN, $img_profil, $img_cin_front, $img_cin_back,
                            $address, $est_client, $est_partenaire) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO utilisateur
        (nom, prenom, email, mot_de_passe, role, CIN, img_profil, img_cin_front, img_cin_back, address, est_client, est_partenaire)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Exécution de la requête
    $success = $stmt->execute([
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
    ]);

    // Retourne l'ID du nouvel utilisateur si succès, sinon false
    return $success ? $conn->lastInsertId() : false;
}

function getUtilisateurParEmail($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}



?>


