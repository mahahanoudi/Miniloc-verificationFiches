<?php

if (!isset($_GET['reservation_id']) || !is_numeric($_GET['reservation_id'])) {
    die('Réservation invalide.');
}
$reservation_id = (int)$_GET['reservation_id'];

include_once('../BD/connexion.php');

// Requête optimisée avec jointure d'image
$stmt = $conn->prepare("SELECT
    r.*,
    o.id AS objet_id,
    o.nom AS objet_nom,
    o.description,
    o.prix_journalier,
    a.proprietaire_id,
    i.url AS image_url
FROM reservation r
JOIN annonce a ON r.annonce_id = a.id
JOIN objet o ON a.objet_id = o.id
LEFT JOIN image i ON o.id = i.objet_id
WHERE r.id = :id
GROUP BY o.id");
$stmt->execute([':id' => $reservation_id]);
$res = $stmt->fetch();

if (!$res) {
    die('Réservation introuvable.');
}

// Vérifier si une évaluation existe déjà pour cette réservation
$stmt_check = $conn->prepare("SELECT COUNT(*) FROM evaluation WHERE reservation_id = :res_id AND evaluateur_id = :client_id");
$stmt_check->execute([
    ':res_id' => $reservation_id,
    ':client_id' => $res['client_id']
]);
$evaluation_exists = (int)$stmt_check->fetchColumn() > 0;

// Traitement du formulaire
$success = '';
$error = '';

if ($evaluation_exists) {
    $error = 'Vous avez déjà évalué cette réservation.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = (int)$_POST['note'];
    $commentaire = htmlspecialchars($_POST['commentaire']);

    try {
        $conn->beginTransaction();

        // Insertion de l'évaluation
        $stmt = $conn->prepare("INSERT INTO evaluation
            (objet_id, evaluateur_id, evalue_id, note, commentaire, date, reservation_id)
            VALUES (:objet_id, :client_id, :proprio_id, :note, :comment, NOW(), :res_id)");

        $stmt->execute([
            ':objet_id' => $res['objet_id'],
            ':client_id' => $res['client_id'],
            ':proprio_id' => $res['proprietaire_id'],
            ':note' => $note,
            ':comment' => $commentaire,
            ':res_id' => $reservation_id
        ]);

        $conn->commit();
        $success = 'Merci pour votre évaluation !';
    } catch (Exception $e) {
        $conn->rollBack();
        die("Erreur : " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Évaluation - <?= htmlspecialchars($res['objet_nom']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .objet-card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .objet-card:hover {
            transform: translateY(-5px);
        }
        .objet-img {
            height: 250px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }
        .price-badge {
            background: #e91e63;
            color: white;
            font-size: 1.2em;
            padding: 8px 20px;
            border-radius: 25px;
        }
        .rating-stars .bi-star-fill {
            color: #ffd700;
            font-size: 1.5em;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-8 mx-auto">
            <!-- Carte de l'objet -->
            <div class="card objet-card mb-4">
                <?php if(!empty($res['image_url'])): ?>
                    <img src="../uploads/<?= $res['image_url'] ?>" class="card-img-top objet-img" alt="Objet">
                <?php else: ?>
                    <div class="objet-img bg-secondary d-flex align-items-center justify-content-center">
                        <span class="text-white">Image non disponible</span>
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="card-title"><?= htmlspecialchars($res['objet_nom']) ?></h2>
                        <span class="price-badge"><?= number_format($res['prix_journalier'], 2) ?>€/jour</span>
                    </div>
                    <p class="card-text text-muted"><?= nl2br(htmlspecialchars($res['description'])) ?></p>
                </div>
            </div>

            <!-- Formulaire d'évaluation -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success text-center">
                    <h4><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></h4>
                    <p class="mb-0 mt-2">Votre évaluation a été enregistrée avec succès.</p>
                </div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-warning text-center">
                    <h4><i class="bi bi-exclamation-triangle-fill me-2"></i>Déjà évalué</h4>
                    <p class="mb-0 mt-2"><?= $error ?></p>
                </div>
            <?php else: ?>
            <div class="card objet-card">
                <div class="card-body">
                    <h3 class="mb-4">Votre expérience de location</h3>

                    <form method="post">
                        <!-- Notation par étoiles -->
                        <div class="mb-4 rating-stars">
                            <label class="form-label">Note globale</label>
                            <div class="d-flex gap-2">
                                <?php for ($i=1; $i<=5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio"
                                            name="note" id="note<?= $i ?>"
                                            value="<?= $i ?>" required>
                                        <label class="form-check-label" for="note<?= $i ?>">
                                            <i class="bi bi-star-fill"></i> <?= $i ?>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Commentaire -->
                        <div class="mb-4">
                            <label for="commentaire" class="form-label">Détails de votre expérience</label>
                            <textarea class="form-control" name="commentaire"
                                placeholder="Décrivez votre expérience avec cet objet..."
                                required></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send me-2"></i>Envoyer l'évaluation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</body>
</html>