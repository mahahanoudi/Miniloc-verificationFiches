<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}
include_once('../BD/connexion.php');
include('../Traitement/traitement_commentaires.php');
$user_id = $_SESSION['user_id'];
$aujourdhui = date('Y-m-d');

// Récupérer toutes les réservations sur MES annonces
$query = "
SELECT r.*, a.id as annonce_id, a.proprietaire_id, o.nom as objet_nom, u.nom as client_nom, u.email as client_email, r.client_id
FROM reservation r
JOIN annonce a ON r.annonce_id = a.id
JOIN objet o ON a.objet_id = o.id
JOIN utilisateur u ON r.client_id = u.id
WHERE a.proprietaire_id = :user_id
ORDER BY r.date_debut DESC

";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$reservations = $stmt->fetchAll();
$client_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($client_id > 0) {
    $commentaires = getCommentairesSurClient($client_id);
    $client = getClientInfo($client_id);
} else {
    $commentaires = [];
    $client = null;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivre vos annonces - Miniloc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container py-5">
    <h2>Réservations reçues pour vos annonces</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Objet</th>
                <th>Client</th>
                <th>Période</th>
                <th>Option livraison</th>
                <th>Adresse</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($reservations as $res): ?>
            <tr>
                <td><?= htmlspecialchars($res['objet_nom']) ?></td>
                <td><?= htmlspecialchars($res['client_nom']) ?>
 
                <a href="#" data-id="<?= urlencode($res['client_id']) ?>" data-bs-toggle="modal" data-bs-target="#partenaireModal">Voir le profil du partenaire</a> 
                <td><?= htmlspecialchars($res['date_debut']) ?> au <?= htmlspecialchars($res['date_fin']) ?></td>
                <td><?= htmlspecialchars($res['option_de_livraison'] ?? '') ?></td>
                <td><?= nl2br(htmlspecialchars($res['address_de_livraison'] ?? '')) ?></td>
                <td>
                    <?php
                    switch ($res['statut']) {
                        case 'en_attente':
                            echo '<span class="badge bg-warning text-dark">En attente</span>';
                            break;
                        case 'confirmee':
                            echo '<span class="badge bg-success">Confirmée</span>';
                            break;
                        case 'rejete':
                            echo '<span class="badge bg-danger">Rejetée</span>';
                            break;
                        case 'terminee':
                            echo '<span class="badge bg-info">Terminée</span>';
                            break;
                        default:
                            echo htmlspecialchars($res['statut']);
                    }
                    ?>
                </td>
                <td>
                    <?php if ($res['statut'] == 'en_attente'): ?>
                        <form method="post" action="../Traitement/traitement_reservation_action.php" style="display:inline;">
                            <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                            <button type="submit" name="action" value="confirmer" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Confirmer
                            </button>
                        </form>

                        <!-- Bouton pour ouvrir modal de rejet -->
                        <button type="button" 
                                class="btn btn-danger btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalRejet" 
                                data-reservation-id="<?= $res['id'] ?>"
                                data-objet-nom="<?= htmlspecialchars($res['objet_nom'], ENT_QUOTES) ?>"
                                data-client-nom="<?= htmlspecialchars($res['client_nom'], ENT_QUOTES) ?>">
                            <i class="fas fa-times"></i> Rejeter
                        </button>
                    <?php elseif ($res['statut'] == 'confirmee' && $res['date_fin'] <= $aujourdhui): ?>
                        <form method="post" action="../Traitement/traitement_reservation_action.php" style="display:inline;">
                            <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                            <button type="submit" name="action" value="terminer" class="btn btn-primary btn-sm">
                                <i class="fas fa-flag-checkered"></i> Terminer
                            </button>
                        </form>
                    <?php elseif ($res['statut'] == 'terminee'): ?>
                        <span class="badge bg-info">Terminée</span>
                    <?php else: ?>
                        <em>Aucune action</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Bootstrap pour saisir le message de rejet -->
<div class="modal fade" id="modalRejet" tabindex="-1" aria-labelledby="modalRejetLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="../Traitement/traitement_reservation_action.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalRejetLabel">Rejeter la réservation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" name="reservation_id" id="modalReservationId" value="">
          <input type="hidden" name="action" value="rejeter">

          <div class="mb-3">
            <label for="message_rejet" class="form-label">Message de justification <span class="text-danger">*</span></label>
            <textarea class="form-control" id="message_rejet" name="message_rejet" rows="5" placeholder="Expliquez pourquoi vous rejetez cette réservation..." required></textarea>
          </div>
          <div class="alert alert-info" id="infoReservation" style="display:none;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-danger">Envoyer le rejet</button>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="partenaireModal" tabindex="-1" aria-labelledby="partenaireModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="partenaireModalLabel">Fiche du client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body" id="modal-content-dynamique">
      <p>Chargement...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('partenaireModal');
  modal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const userId = button.getAttribute('data-id');
    
    // Appel AJAX pour charger les données dynamiquement
    fetch(`../Traitement/traitement_commentaire_client.php?id=${userId}`)

      .then(response => response.text())
      .then(html => {
        document.getElementById('modal-content-dynamique').innerHTML = html;
      })
      .catch(err => {
        document.getElementById('modal-content-dynamique').innerHTML = '<div class="alert alert-danger">Erreur de chargement.</div>';
      });
  });
});
</script>
<script>
// Script pour injecter les données dans le modal quand on clique sur "Rejeter"
var modalRejet = document.getElementById('modalRejet');
modalRejet.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var reservationId = button.getAttribute('data-reservation-id');
    var objetNom = button.getAttribute('data-objet-nom');
    var clientNom = button.getAttribute('data-client-nom');

    // Remplir le champ caché reservation_id
    modalRejet.querySelector('#modalReservationId').value = reservationId;

    // Afficher une info dans le modal (optionnel)
    var infoDiv = modalRejet.querySelector('#infoReservation');
    infoDiv.style.display = 'block';
    
    infoDiv.innerHTML = `<strong>Réservation pour :</strong> <em>${objetNom}</em><br><strong>Client :</strong> ${clientNom}`;
});
</script>
</body>
</html>