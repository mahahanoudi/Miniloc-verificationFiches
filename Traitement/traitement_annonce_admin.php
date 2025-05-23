<?php

include_once '../BD/connexion.php';

function getAnnoncesFiltrees($ville = '', $categorie = '', $prix_interval = '', $note_min = '') {
    global $conn;

    $sql = "
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
    WHERE a.visibility = FALSE
    ";
 $params=[];
  

 if (!empty($ville)) {
    $sql .= " AND o.ville LIKE :ville";
    $params[':ville'] = "%$ville%";
}

if (!empty($categorie)) {
    $sql .= " AND c.id = :categorie";
    $params[':categorie'] = $categorie;
}

if (!empty($prix_interval)) {
    if ($prix_interval === '60+') {
        $sql .= " AND o.prix_journalier >= :prix_min";
        $params[':prix_min'] = 60;
    } else {
        list($min, $max) = explode('-', $prix_interval);
        $sql .= " AND o.prix_journalier BETWEEN :prix_min AND :prix_max";
        $params[':prix_min'] = $min;
        $params[':prix_max'] = $max;
    }
}

$sql .= " GROUP BY a.id";

    if (!empty($note_min)) {
        $sql .= " HAVING note_moyenne >= :note_min AND note_moyenne < :note_max";
        $params[':note_min'] = $note_min;
        $params[':note_max'] = $note_min + 1;
    }

    $sql .= " ORDER BY est_actuellement_premium DESC, a.date_publication DESC";

    $query = $conn->prepare($sql);
    $query->execute($params);

    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getVillesDisponibles() {
    global $conn;
    return $conn->query("SELECT DISTINCT ville FROM objet ORDER BY ville")->fetchAll(PDO::FETCH_COLUMN);
}

function getCategoriesDisponibles() {
    global $conn;
    return $conn->query("SELECT id, nom FROM categorie ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
}

function getPrixOptionsDisponibles() {
    global $conn;
    return $conn->query("SELECT DISTINCT prix_journalier FROM objet ORDER BY prix_journalier")->fetchAll(PDO::FETCH_COLUMN);
}

function getToutesLesAnnonces() {
    global $conn; // Ajoute cette ligne pour utiliser la connexion existante
    $sql ="
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
WHERE a.visibility = FALSE
GROUP BY a.id
ORDER BY est_actuellement_premium DESC, a.date_publication DESC
";
     $stmt = $conn->prepare($sql);
     $stmt->execute();
     return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



$ville = $_GET['ville'] ?? '';
$categorie = $_GET['categorie_id'] ?? '';
$prix_interval = $_GET['prix_interval'] ?? '';
$note_min = $_GET['note_min'] ?? '';
$voir_tout = $_GET['voir_tout'] ?? '';

if (!empty($ville) || !empty($categorie) || !empty($prix_interval) || !empty($note_min)) {
    // Si on utilise des filtres de recherche
    $annonces = getAnnoncesFiltrees($ville, $categorie, $prix_interval, $note_min);
    
} else {
    // Sinon on affiche seulement les annonces Premium
    $annonces = getToutesLesAnnonces();
    
}



// Tu peux aussi récupérer des listes pour les filtres
$villes = getVillesDisponibles();
$categories = getCategoriesDisponibles();
$prix_options = getPrixOptionsDisponibles();
?>
