<?php
// ============================================================
//  PAGE RÉSULTATS — resultats.php
//  Affiche le score et la correction détaillée après un QCM
// ============================================================
session_start();
include('config/db.php');

// Rediriger si non connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Récupérer l'id de la tentative depuis l'URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Sécurité : on vérifie que cette tentative appartient bien à l'utilisateur connecté
$req = $pdo->prepare("SELECT * FROM attempts WHERE id = ? AND user_id = ?");
$req->execute([$id, $_SESSION['user_id']]);
$tentative = $req->fetch();

// Si la tentative n'existe pas, rediriger vers l'historique
if (!$tentative) {
    header("Location: historique.php");
    exit;
}

// Récupérer le détail des réponses avec les questions
$req_q = $pdo->prepare("
    SELECT
        aq.reponse_utilisateur,
        aq.est_correcte,
        q.question,
        q.reponse1, q.reponse2, q.reponse3, q.reponse4,
        q.bonne_reponse,
        q.categorie
    FROM attempt_questions aq
    JOIN questions q ON aq.question_id = q.id
    WHERE aq.attempt_id = ?
");
$req_q->execute([$id]);
$details = $req_q->fetchAll();

// Données du score
$score  = $tentative['score'];
$sur_20 = $tentative['score_sur_20'];
$statut = $tentative['statut'];

// Couleur et mention selon le score
if ($sur_20 >= 14) {
    $couleur = '#16a34a'; $mention = 'Très bien';
} elseif ($sur_20 >= 10) {
    $couleur = '#d97706'; $mention = 'Passable';
} else {
    $couleur = '#dc2626'; $mention = 'Insuffisant';
}

include('includes/header.php');
?>

<div class="container py-5" style="max-width: 800px;">

    <!-- ===== RÉSUMÉ DU SCORE ===== -->
    <div class="card p-4 mb-4 text-center">

        <?php if ($statut === 'invalide'): ?>
        <div class="alert alert-danger mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>QCM invalidé</strong> — Changement d'onglet détecté pendant l'examen.
        </div>
        <?php endif; ?>

        <!-- Score en gros -->
        <div style="font-size: 4.5rem; font-weight: 900; color: <?= $couleur ?>; line-height: 1;">
            <?= $sur_20 ?>/20
        </div>
        <div style="font-size: 1.2rem; color: <?= $couleur ?>; font-weight: 700; margin: 8px 0;">
            <?= $mention ?>
        </div>
        <p class="text-muted mb-3"><?= $score ?> bonne(s) réponse(s) sur 10 questions</p>

        <!-- Barre de progression -->
        <div class="progress" style="height: 12px; border-radius: 10px;">
            <div class="progress-bar" style="width: <?= $score * 10 ?>%; background: <?= $couleur ?>; border-radius: 10px; transition: width 1s;"></div>
        </div>
    </div>

    <!-- ===== CORRECTION DÉTAILLÉE ===== -->
    <h5 class="fw-bold mb-3">
        <i class="bi bi-list-check me-2"></i>Correction détaillée
    </h5>

    <?php foreach ($details as $i => $d): ?>
    <?php
    $rep_labels = ['A', 'B', 'C', 'D'];
    $bonneRep   = $d['bonne_reponse'];     // La bonne réponse (1, 2, 3 ou 4)
    $userRep    = $d['reponse_utilisateur']; // La réponse de l'étudiant
    $correcte   = $d['est_correcte'];       // 1 = correct, 0 = faux
    ?>
    <div class="card mb-3" style="border-left: 4px solid <?= $correcte ? '#22c55e' : '#ef4444' ?>;">
        <div class="card-body">

            <!-- En-tête de la question -->
            <div class="d-flex align-items-start justify-content-between mb-2">
                <div style="flex:1;">
                    <span style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:1px;">
                        Question <?= $i + 1 ?> — <?= htmlspecialchars($d['categorie']) ?>
                    </span>
                    <p class="fw-semibold mt-1 mb-2" style="color:#1e293b;">
                        <?= htmlspecialchars($d['question']) ?>
                    </p>
                </div>
                <span style="font-size: 1.5rem; margin-left: 12px;">
                    <?= $correcte ? '✅' : '❌' ?>
                </span>
            </div>

            <!-- Les 4 réponses avec code couleur -->
            <?php for ($r = 1; $r <= 4; $r++): ?>
            <?php
            $isBonne = ($r == $bonneRep);
            $isUser  = ($r == $userRep);

            // Couleur de fond : vert = bonne réponse, rouge = mauvaise réponse choisie
            if ($isBonne) {
                $style = 'background:#f0fdf4; border-color:#86efac;';
            } elseif ($isUser && !$isBonne) {
                $style = 'background:#fef2f2; border-color:#fca5a5;';
            } else {
                $style = '';
            }
            ?>
            <div style="display:flex; align-items:center; gap:10px; padding:8px 12px;
                        border:2px solid #e2e8f0; border-radius:8px; margin-bottom:6px; <?= $style ?>">
                <!-- Lettre (A, B, C, D) -->
                <span style="width:26px; height:26px; border-radius:50%; background:#e2e8f0;
                             display:flex; align-items:center; justify-content:center;
                             font-size:12px; font-weight:700; flex-shrink:0;">
                    <?= $rep_labels[$r - 1] ?>
                </span>
                <!-- Texte de la réponse -->
                <span style="flex:1;"><?= htmlspecialchars($d['reponse'.$r]) ?></span>
                <!-- Icône si bonne réponse ou mauvaise réponse choisie -->
                <?php if ($isBonne): ?>
                    <i class="bi bi-check-circle-fill text-success ms-auto"></i>
                <?php elseif ($isUser && !$isBonne): ?>
                    <i class="bi bi-x-circle-fill text-danger ms-auto"></i>
                <?php endif; ?>
            </div>
            <?php endfor; ?>

            <!-- Si l'étudiant n'a pas répondu -->
            <?php if (!$userRep): ?>
            <small class="text-muted">
                <i class="bi bi-dash-circle me-1"></i>Sans réponse
            </small>
            <?php endif; ?>

        </div>
    </div>
    <?php endforeach; ?>

    <!-- Boutons d'action -->
    <div class="d-flex gap-3 mt-4 flex-wrap">
        <a href="qcm.php" class="btn btn-primary fw-semibold">
            <i class="bi bi-arrow-repeat me-2"></i>Repasser le QCM
        </a>
        <a href="historique.php" class="btn btn-outline-secondary fw-semibold">
            <i class="bi bi-clock-history me-2"></i>Voir mon historique
        </a>
        <a href="index.php" class="btn btn-outline-secondary fw-semibold">
            <i class="bi bi-house me-2"></i>Accueil
        </a>
    </div>

</div>

<?php include('includes/footer.php'); ?>