<?php
// ============================================================
//  PAGE HISTORIQUE — historique.php
//  Affiche toutes les tentatives passées de l'utilisateur
// ============================================================
session_start();
include('config/db.php');

// Rediriger si non connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer toutes les tentatives terminées ou invalidées de cet utilisateur
$req = $pdo->prepare("
    SELECT * FROM attempts
    WHERE user_id = ? AND statut IN ('termine', 'invalide')
    ORDER BY date_debut DESC
");
$req->execute([$user_id]);
$tentatives = $req->fetchAll();

// Calculer les statistiques personnelles
$nb_total   = count($tentatives);
$nb_valides = 0;
$scores     = [];

foreach ($tentatives as $t) {
    if ($t['statut'] === 'termine') {
        $nb_valides++;
        $scores[] = $t['score_sur_20'];
    }
}

// Moyenne et meilleur score (en évitant la division par zéro)
$moyenne  = $nb_valides > 0 ? round(array_sum($scores) / $nb_valides, 2) : 0;
$meilleur = $nb_valides > 0 ? max($scores) : 0;

include('includes/header.php');
?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-bold mb-0">
            <i class="bi bi-clock-history me-2"></i>Mon historique
        </h4>
        <a href="qcm.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Nouveau QCM
        </a>
    </div>

    <!-- ===== STATISTIQUES PERSONNELLES ===== -->
    <div class="row g-3 mb-5">

        <div class="col-6 col-md-3">
            <div class="card p-3 text-center">
                <div style="font-size: 1.8rem; font-weight: 800; color: #2563eb;"><?= $nb_total ?></div>
                <div class="text-muted" style="font-size:13px;">QCM passés</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card p-3 text-center">
                <div style="font-size: 1.8rem; font-weight: 800; color: #16a34a;"><?= $nb_valides ?></div>
                <div class="text-muted" style="font-size:13px;">QCM valides</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card p-3 text-center">
                <div style="font-size: 1.8rem; font-weight: 800; color: <?= $moyenne >= 10 ? '#16a34a' : '#dc2626' ?>;">
                    <?= $moyenne ?>/20
                </div>
                <div class="text-muted" style="font-size:13px;">Moyenne</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card p-3 text-center">
                <div style="font-size: 1.8rem; font-weight: 800; color: #16a34a;"><?= $meilleur ?>/20</div>
                <div class="text-muted" style="font-size:13px;">Meilleur score</div>
            </div>
        </div>

    </div>

    <!-- ===== TABLEAU DES TENTATIVES ===== -->
    <?php if ($nb_total === 0): ?>
        <div class="card p-5 text-center">
            <div style="font-size: 3rem; margin-bottom: 16px;">📋</div>
            <p class="text-muted mb-3">Vous n'avez pas encore passé de QCM.</p>
            <a href="qcm.php" class="btn btn-primary">
                <i class="bi bi-play-circle me-2"></i>Passer mon premier QCM
            </a>
        </div>
    <?php else: ?>

    <div class="card">
        <table class="table table-hover mb-0">
            <thead style="background:#f8fafc;">
                <tr>
                    <th class="ps-4">Date</th>
                    <th>Score</th>
                    <th>Statut</th>
                    <th class="pe-4">Correction</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tentatives as $t): ?>
                <?php
                // Choisir la couleur selon le score
                if ($t['score_sur_20'] >= 14)     $couleur = '#16a34a';
                elseif ($t['score_sur_20'] >= 10)  $couleur = '#d97706';
                else                               $couleur = '#dc2626';
                ?>
                <tr>
                    <td class="ps-4" style="vertical-align:middle;">
                        <!-- Formater la date en français -->
                        <?= date('d/m/Y à H\hi', strtotime($t['date_debut'])) ?>
                    </td>
                    <td style="vertical-align:middle;">
                        <span style="font-weight:700; color:<?= $couleur ?>;"><?= $t['score_sur_20'] ?>/20</span>
                        <span class="text-muted" style="font-size:13px;"> — <?= $t['score'] ?>/10</span>
                    </td>
                    <td style="vertical-align:middle;">
                        <?php if ($t['statut'] === 'invalide'): ?>
                            <span class="badge bg-danger">Invalidé</span>
                        <?php else: ?>
                            <span class="badge bg-success">Validé</span>
                        <?php endif; ?>
                    </td>
                    <td class="pe-4" style="vertical-align:middle;">
                        <a href="resultats.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Voir
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>

</div>

<?php include('includes/footer.php'); ?>
