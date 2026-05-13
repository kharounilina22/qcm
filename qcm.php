<?php
//  Affiche les 10 questions et gère la soumission des réponses
//  Fonctionnalités : minuteur 10 min, anti-triche, auto-submit
session_start();
include('config/db.php');

// Rediriger si non connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
// ÉTAPE 1 : Traitement de la soumission des réponses

if (isset($_POST['soumettre'])) {
    $attempt_id = (int)$_POST['attempt_id'];

    // Sécurité : vérifier que cette tentative appartient bien à cet utilisateur
    $req = $pdo->prepare("SELECT * FROM attempts WHERE id = ? AND user_id = ? AND statut = 'en_cours'");
    $req->execute([$attempt_id, $user_id]);
    $tentative = $req->fetch();

    if ($tentative) {
        // Récupérer toutes les questions de cette tentative
        $req_q = $pdo->prepare("SELECT * FROM attempt_questions WHERE attempt_id = ?");
        $req_q->execute([$attempt_id]);
        $aq_list = $req_q->fetchAll();

        $score = 0; // Compteur de bonnes réponses

        foreach ($aq_list as $aq) {
            // Récupérer la réponse donnée par l'utilisateur (ou NULL si pas répondu)
            $rep_user = isset($_POST['q_' . $aq['question_id']]) ? (int)$_POST['q_' . $aq['question_id']] : null;

            // Récupérer la bonne réponse depuis la table questions
            $req_bonne = $pdo->prepare("SELECT bonne_reponse FROM questions WHERE id = ?");
            $req_bonne->execute([$aq['question_id']]);
            $bonne = (int)$req_bonne->fetchColumn();

            // Vérifier si la réponse est correcte
            $est_correcte = ($rep_user === $bonne) ? 1 : 0;
            if ($est_correcte) $score++;

            // Enregistrer la réponse de l'utilisateur
            $pdo->prepare("UPDATE attempt_questions SET reponse_utilisateur = ?, est_correcte = ? WHERE id = ?")
                ->execute([$rep_user, $est_correcte, $aq['id']]);
        }

        // Calculer le score sur 20
        $score_sur_20 = round($score * 20 / 10, 2);

        // Statut : 'invalide' si triche détectée, sinon 'termine'
        $statut = isset($_POST['triche']) && $_POST['triche'] == '1' ? 'invalide' : 'termine';

        // Mettre à jour la tentative avec le score final
        $pdo->prepare("UPDATE attempts SET score = ?, score_sur_20 = ?, statut = ?, date_fin = NOW() WHERE id = ?")
            ->execute([$score, $score_sur_20, $statut, $attempt_id]);

        // Supprimer l'id de tentative de la session
        unset($_SESSION['attempt_id']);

        // Rediriger vers la page des résultats
        header("Location: resultats.php?id=$attempt_id");
        exit;
    }
}
// ÉTAPE 2 : Vérifier s'il y a une tentative en cours
$tentative_en_cours = null;
if (isset($_SESSION['attempt_id'])) {
    $req = $pdo->prepare("SELECT * FROM attempts WHERE id = ? AND user_id = ? AND statut = 'en_cours'");
    $req->execute([$_SESSION['attempt_id'], $user_id]);
    $tentative_en_cours = $req->fetch();
}
// ÉTAPE 3 : Créer une nouvelle tentative si besoin

if (!$tentative_en_cours) {
    // Sélectionner 10 questions aléatoires depuis la base
    $questions = $pdo->query("SELECT * FROM questions ORDER BY RAND() LIMIT 10")->fetchAll();

    if (count($questions) < 10) {
        die("<div class='container py-5 text-center'><div class='alert alert-warning'>Pas assez de questions dans la base. Contactez l'administrateur.</div></div>");
    }

    // Créer la tentative en base de données
    $req = $pdo->prepare("INSERT INTO attempts (user_id, statut, date_debut) VALUES (?, 'en_cours', NOW())");
    $req->execute([$user_id]);
    $attempt_id = $pdo->lastInsertId(); // Récupérer l'ID généré automatiquement

    $_SESSION['attempt_id'] = $attempt_id;

    // Associer les 10 questions à cette tentative
    foreach ($questions as $q) {
        $pdo->prepare("INSERT INTO attempt_questions (attempt_id, question_id) VALUES (?, ?)")
            ->execute([$attempt_id, $q['id']]);
    }

    $_SESSION['attempt_start'] = time();
    header("Location: qcm.php");
    exit;
}
// ÉTAPE 4 : Afficher les questions de la tentative

$attempt_id = $tentative_en_cours['id'];

// Récupérer les questions avec leurs détails
$req_q = $pdo->prepare("
    SELECT q.*
    FROM attempt_questions aq
    JOIN questions q ON aq.question_id = q.id
    WHERE aq.attempt_id = ?
");
$req_q->execute([$attempt_id]);
$questions = $req_q->fetchAll();

// Calculer le temps restant (10 minutes = 600 secondes)
$temps_debut   = strtotime($tentative_en_cours['date_debut']);
$temps_restant = max(0, 600 - (time() - $temps_debut));

include('includes/header.php');
?>
<style>
/* ---- Styles spécifiques à la page QCM ---- */

.question-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
}

.question-num {
    color: #2563eb;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.question-text {
    font-size: 1.05rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 16px;
}

/* Style des options de réponse */
.option-label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}

.option-label:hover {
    border-color: #2563eb;
    background: #eff6ff;
}

/* Quand une réponse est sélectionnée */
input[type=radio]:checked + .option-label {
    border-color: #2563eb;
    background: #eff6ff;
}

/* Barre du minuteur en haut */
.timer-bar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 0;
}

.timer-display {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e293b;
}

/* Animation quand le temps est presque écoulé */
.timer-danger {
    color: #dc2626 !important;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.5; }
}

/* Bannière d'alerte anti-triche */
.anticheat-banner {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 10px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: none; /* Cachée par défaut, visible si triche détectée */
}
</style>

<!-- ===== BARRE MINUTEUR ===== -->
<div class="timer-bar">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="fw-semibold text-muted">QCM en cours</span>
            — <?= count($questions) ?> questions · 10 minutes
        </div>
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock text-muted"></i>
            <div class="timer-display" id="timer">10:00</div>
        </div>
    </div>
</div>

<!-- ===== CONTENU DU QCM ===== -->
<div class="container py-4" style="max-width: 800px;">

    <!-- Message anti-triche (s'affiche si l'étudiant change d'onglet) -->
    <div class="anticheat-banner" id="triche-banner">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>⚠️ Alerte anti-triche :</strong>
        Vous avez quitté la fenêtre d'examen. Cette infraction a été enregistrée.
    </div>

    <!-- Formulaire QCM -->
    <form method="POST" id="qcm-form">
        <!-- Champs cachés -->
        <input type="hidden" name="attempt_id" value="<?= $attempt_id ?>">
        <input type="hidden" name="triche" id="triche-flag" value="0">

        <!-- Affichage de chaque question -->
        <?php foreach ($questions as $i => $q): ?>
        <div class="question-card">
            <div class="question-num">Question <?= $i + 1 ?> / <?= count($questions) ?></div>
            <div class="question-text"><?= htmlspecialchars($q['question']) ?></div>

            <!-- Les 4 réponses possibles -->
            <?php for ($r = 1; $r <= 4; $r++): ?>
            <div>
                <input type="radio"
                       name="q_<?= $q['id'] ?>"
                       value="<?= $r ?>"
                       id="q<?= $q['id'] ?>r<?= $r ?>"
                       style="display:none;">
                <label for="q<?= $q['id'] ?>r<?= $r ?>" class="option-label">
                    <!-- Lettre A, B, C ou D -->
                    <span style="width:28px; height:28px; border-radius:50%; background:#e2e8f0;
                                 display:flex; align-items:center; justify-content:center;
                                 font-size:13px; font-weight:700; flex-shrink:0;">
                        <?= ['A','B','C','D'][$r-1] ?>
                    </span>
                    <?= htmlspecialchars($q['reponse'.$r]) ?>
                </label>
            </div>
            <?php endfor; ?>
        </div>
        <?php endforeach; ?>

        <!-- Bouton de soumission -->
        <div class="card p-4 text-center">
            <p class="text-muted mb-3">
                Vérifiez bien vos réponses avant de soumettre.
                <strong>Vous ne pourrez pas revenir en arrière.</strong>
            </p>
            <button type="submit" name="soumettre" class="btn btn-primary btn-lg px-5 fw-bold">
                <i class="bi bi-send me-2"></i>Soumettre mes réponses
            </button>
        </div>
    </form>
</div>

<!-- ===== JAVASCRIPT : MINUTEUR + ANTI-TRICHE ===== -->
<script>
// Temps restant envoyé depuis PHP
let secondes = <?= $temps_restant ?>;
let triched = false; // A-t-on détecté une triche ?

// Fonction qui met à jour l'affichage du minuteur chaque seconde
function updateTimer() {
    const minutes = Math.floor(secondes / 60);
    const secs    = secondes % 60;
    const el      = document.getElementById('timer');

    // Affichage au format MM:SS
    el.textContent = String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');

    // Passer en rouge quand il reste moins d'1 minute
    if (secondes <= 60) {
        el.classList.add('timer-danger');
    }

    // Temps écoulé : soumettre automatiquement le formulaire
    if (secondes <= 0) {
        document.getElementById('triche-flag').value = triched ? '1' : '0';
        document.getElementById('qcm-form').submit();
        return;
    }

    secondes--;
}

// Démarrer le minuteur immédiatement puis toutes les secondes
updateTimer();
setInterval(updateTimer, 1000);

// ===== ANTI-TRICHE : Détection de changement d'onglet =====
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // L'étudiant a changé d'onglet !
        triched = true;
        document.getElementById('triche-flag').value = '1';
        document.getElementById('triche-banner').style.display = 'block';
    }
});

// Désactiver le clic droit (empêche copier-coller via menu contextuel)
document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
});

// Désactiver le copier-coller
document.addEventListener('copy',  function(e) { e.preventDefault(); });
document.addEventListener('paste', function(e) { e.preventDefault(); });
</script>

<?php include('includes/footer.php'); ?>
