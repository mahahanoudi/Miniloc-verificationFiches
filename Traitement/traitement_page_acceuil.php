<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once '../BD/connexion.php';


function getDernieresAnnoncesPremium() {

    global $conn;
  
    
    $stmt = $conn->prepare( "
SELECT a.*, o.nom AS objet_nom, o.ville, o.prix_journalier, c.nom AS categorie_nom, 
       i.url AS image_url, e.note AS note_moyenne,
       (CASE 
            WHEN a.premium = 1 
                 AND CURDATE() <= DATE_ADD(a.date_debut_premium, INTERVAL a.duree_premium DAY) 
            THEN 1 
            ELSE 0 
        END) AS est_actuellement_premium
FROM annonce a
JOIN objet o ON a.objet_id = o.id
JOIN categorie c ON o.categorie_id = c.id
LEFT JOIN image i ON o.id = i.objet_id
LEFT JOIN evaluation e ON o.id = e.objet_id
WHERE a.statut = 'active'
  AND a.visibility = 1
  AND a.premium = 1
  AND CURDATE() <= DATE_ADD(a.date_debut_premium, INTERVAL a.duree_premium DAY)
GROUP BY a.id
ORDER BY a.date_publication DESC
LIMIT 3
");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getNombreAnnoncesActives($partenaire_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as nombre_annonces 
        FROM annonce 
        WHERE partenaire_id = :partenaire_id 
        AND statut = 'active'
    ");
    
    $stmt->execute(['partenaire_id' => $partenaire_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['nombre_annonces'];
}


$annonces = getDernieresAnnoncesPremium();

?>