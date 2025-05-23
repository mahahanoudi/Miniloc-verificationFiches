<?php
if (!isset($_GET['reservation_id']) || !is_numeric($_GET['reservation_id'])) {
    die('Réservation non spécifiée ou invalide.');
}
$reservation_id = (int)$_GET['reservation_id'];

include_once('../BD/connexion.php');

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $note = filter_input(INPUT_POST, 'note', FILTER_VALIDATE_INT);
        $commentaire = filter_input(INPUT_POST, 'commentaire', FILTER_SANITIZE_STRING);
        
        if ($note && $commentaire) {
            $sql = "INSERT INTO evaluation_client (client_id, partenaire_id, note, commentaire, date, reservation_id) 
                    SELECT r.client_id, a.proprietaire_id, :note, :commentaire, CURRENT_DATE, :reservation_id
                    FROM reservation r
                    JOIN annonce a ON r.annonce_id = a.id
                    WHERE r.id = :res_id";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':note' => $note,
                ':commentaire' => $commentaire,
                ':reservation_id' => $reservation_id,
                ':res_id' => $reservation_id
            ]);
            
            $success = "Votre avis a été enregistré avec succès !";
        } else {
            $error = "Veuillez remplir tous les champs correctement.";
        }
    } catch (PDOException $e) {
        $error = "Une erreur est survenue lors de l'enregistrement.";
    }
}

$stmt = $conn->prepare("SELECT r.*, o.nom as objet_nom, u.nom as proprio_nom, u.email as proprio_email FROM reservation r
    JOIN annonce a ON r.annonce_id = a.id
    JOIN objet o ON a.objet_id = o.id
    JOIN utilisateur u ON a.proprietaire_id = u.id
    WHERE r.id = :id");
$stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
$stmt->execute();
$res = $stmt->fetch();

if (!$res) {
    die('Réservation introuvable.');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Avis sur le propriétaire</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .objet-card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
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
            <!-- En-tête -->
            <div class="text-center mb-5">
                <h1 class="display-5 mb-3">Évaluer le propriétaire</h1>
                <p class="lead">Merci de partager votre expérience avec <?= htmlspecialchars($res['proprio_nom']) ?></p>
            </div>

            <!-- Formulaire d'évaluation -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success text-center">
                    <h4><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></h4>
                    <p class="mb-0 mt-2">Votre évaluation a été enregistrée avec succès.</p>
                </div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-warning text-center">
                    <h4><i class="bi bi-exclamation-triangle-fill me-2"></i>Erreur</h4>
                    <p class="mb-0 mt-2"><?= $error ?></p>
                </div>
            <?php else: ?>
            <div class="card objet-card">
                <div class="card-body">
                    <h3 class="mb-4">Votre expérience avec le propriétaire</h3>

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
                            <textarea class="form-control" name="commentaire" id="commentaire"
                                placeholder="Décrivez votre expérience avec ce propriétaire..."
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
</body>
</html>
