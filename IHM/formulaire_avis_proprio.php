<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'proprietaire') {
    die('Accès non autorisé.');
}

include_once('../BD/connexion.php');

// Vérification et récupération de la réservation
if (!isset($_GET['reservation_id']) || !is_numeric($_GET['reservation_id'])) {
    die('Réservation invalide.');
}

$reservation_id = (int)$_GET['reservation_id'];
$proprietaire_id = $_SESSION['user_id'];

// Requête sécurisée avec vérification du propriétaire
$stmt = $conn->prepare("SELECT
    r.*,
    o.id AS objet_id,
    o.nom AS objet_nom,
    o.description,
    a.proprietaire_id,
    u.prenom AS client_prenom,
    u.nom AS client_nom,
    u.img_profil AS client_img,
    i.url AS objet_image
FROM reservation r
JOIN annonce a ON r.annonce_id = a.id
JOIN objet o ON a.objet_id = o.id
JOIN utilisateur u ON r.client_id = u.id
LEFT JOIN image i ON o.id = i.objet_id
WHERE r.id = :reservation_id
AND a.proprietaire_id = :proprietaire_id
GROUP BY o.id");

$stmt->execute([
    ':reservation_id' => $reservation_id,
    ':proprietaire_id' => $proprietaire_id
]);

$reservation = $stmt->fetch();

if (!$reservation) {
    die("Réservation introuvable ou vous n'êtes pas autorisé.");
}

// Vérifier si une évaluation existe déjà pour cette réservation
$stmt_check = $conn->prepare("SELECT COUNT(*) FROM evaluation_partenaire WHERE reservation_id = :res_id AND partenaire_id = :proprio_id");
$stmt_check->execute([
    ':res_id' => $reservation_id,
    ':proprio_id' => $proprietaire_id
]);
$evaluation_exists = (int)$stmt_check->fetchColumn() > 0;

// Traitement du formulaire
$success = '';
$error = '';

if ($evaluation_exists) {
    $error = 'Vous avez déjà évalué cette réservation.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = (int)$_POST['note'];
    $commentaire = htmlspecialchars(trim($_POST['commentaire']));

    try {
        $conn->beginTransaction();

        // Insertion de l'évaluation dans la table evaluation_partenaire
        $stmt = $conn->prepare("INSERT INTO evaluation_partenaire
            (partenaire_id, client_id, note, commentaire, date, reservation_id)
            VALUES (:partenaire_id, :client_id, :note, :comment, NOW(), :res_id)");

        $stmt->execute([
            ':partenaire_id' => $proprietaire_id,
            ':client_id' => $reservation['client_id'],
            ':note' => $note,
            ':comment' => $commentaire,
            ':res_id' => $reservation_id
        ]);

        $conn->commit();
        $success = 'Évaluation enregistrée avec succès !';
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
    <title>Évaluation du client - <?= htmlspecialchars($reservation['objet_nom']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .client-card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .client-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
        }
        .objet-img {
            height: 200px;
            object-fit: cover;
            border-radius: 15px;
        }
        .rating-stars .bi-star-fill {
            color: #ffd700;
            font-size: 1.5em;
        }
        .form-check-input:checked + .form-check-label .bi-star-fill {
            transform: scale(1.2);
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-8 mx-auto">
            <!-- En-tête -->
            <div class="text-center mb-5">
                <h1 class="display-5 mb-3">Évaluer le client</h1>
                <p class="lead">Merci de partager votre expérience avec <?= htmlspecialchars($reservation['client_prenom']) ?></p>
            </div>

            <!-- Carte client -->
            <div class="card client-card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-4">
                        <img src="<?= htmlspecialchars($reservation['client_img'] ?: 'https://ui-avatars.com/api/?name='.urlencode($reservation['client_prenom'].'+'.$reservation['client_nom'])) ?>"
                             class="client-img"
                             alt="Photo de <?= htmlspecialchars($reservation['client_prenom']) ?>">
                        <div>
                            <h2 class="mb-1"><?= htmlspecialchars($reservation['client_prenom'].' '.$reservation['client_nom']) ?></h2>
                            <p class="text-muted mb-0">Client depuis le <?= date('d/m/Y', strtotime($reservation['date_debut'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails objet -->
            <div class="card client-card mb-4">
                <div class="card-body">
                    <h3 class="mb-3">Objet loué</h3>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <?php if(!empty($reservation['objet_image'])): ?>
                                <img src="../uploads/<?= $reservation['objet_image'] ?>"
                                     class="objet-img w-100"
                                     alt="<?= htmlspecialchars($reservation['objet_nom']) ?>">
                            <?php else: ?>
                                <div class="objet-img bg-secondary d-flex align-items-center justify-content-center">
                                    <span class="text-white">Image non disponible</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h4><?= htmlspecialchars($reservation['objet_nom']) ?></h4>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($reservation['description'])) ?></p>
                            <div class="d-flex gap-3">
                                <div>
                                    <span class="fw-bold">Début:</span>
                                    <?= date('d/m/Y', strtotime($reservation['date_debut'])) ?>
                                </div>
                                <div>
                                    <span class="fw-bold">Fin:</span>
                                    <?= date('d/m/Y', strtotime($reservation['date_fin'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
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
            <div class="card client-card">
                <div class="card-body">
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
                            <label for="commentaire" class="form-label">Commentaire</label>
                            <textarea class="form-control"
                                      name="commentaire"
                                      rows="5"
                                      placeholder="Décrivez votre expérience avec ce client..."
                                      required></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send me-2"></i>Soumettre l'évaluation
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