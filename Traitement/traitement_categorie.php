<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once '../BD/connexion.php';

function getAnnoncesParCategorie($categorie) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT a.*, c.nom AS categorie_nom, o.nom AS objet_nom, o.ville, o.prix_journalier, i.url AS image_url, AVG(e.note) AS note_moyenne
        FROM annonce a
        JOIN objet o ON a.objet_id = o.id
        JOIN categorie c ON o.categorie_id = c.id
        LEFT JOIN image i ON o.id = i.objet_id
        LEFT JOIN evaluation e ON e.objet_id = o.id
        WHERE c.nom = ?
        GROUP BY a.id, c.nom, o.nom, o.ville, o.prix_journalier, i.url
        ORDER BY a.date_publication DESC
    ");
    $stmt->execute([$categorie]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$categorie = $_GET['categorie'] ?? null;

if ($categorie) {
    $annonces = getAnnoncesParCategorie($categorie);
}
?>
