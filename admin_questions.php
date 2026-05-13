<?php
//  CRUD : ajouter, modifier, supprimer des questions
//  Accès réservé aux admins uniquement
session_start();
include('config/db.php');

// Sécurité : rediriger si non connecté ou pas admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
$msg_ok  = '';
$msg_err = '';
// ACTION : Supprimer une question
if (isset($_POST['supprimer'])) {
    $qid = (int)$_POST['qid'];
    $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$qid]);
    $msg_ok = "Question supprimée.";
}
// ACTION : Ajouter une question
if (isset($_POST['ajouter'])) {
    $question = trim($_POST['question']);
    $r1       = trim($_POST['reponse1']);
    $r2       = trim($_POST['reponse2']);
    $r3       = trim($_POST['reponse3']);
    $r4       = trim($_POST['reponse4']);
    $bonne    = (int)$_POST['bonne_reponse'];
    $cat      = trim($_POST['categorie']) ?: 'Général';

    // Vérifier que tous les champs sont remplis
    if (!$question || !$r1 || !$r2 || !$r3 || !$r4 || !in_array($bonne, [1,2,3,4])) {
        $msg_err = "Tous les champs sont obligatoires et la bonne réponse doit être entre 1 et 4.";
    } else {
        $pdo->prepare("
            INSERT INTO questions (question, reponse1, reponse2, reponse3, reponse4, bonne_reponse, categorie)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$question, $r1, $r2, $r3, $r4, $bonne, $cat]);
        $msg_ok = "Question ajoutée avec succès !";
    }
}
// ACTION : Modifier une question
if (isset($_POST['modifier'])) {
    $qid      = (int)$_POST['qid'];
    $question = trim($_POST['question']);
    $r1       = trim($_POST['reponse1']);
    $r2       = trim($_POST['reponse2']);
    $r3       = trim($_POST['reponse3']);
    $r4       = trim($_POST['reponse4']);
    $bonne    = (int)$_POST['bonne_reponse'];
    $cat      = trim($_POST['categorie']) ?: 'Général';

    $pdo->prepare("
        UPDATE questions
        SET question = ?, reponse1 = ?, reponse2 = ?, reponse3 = ?,
            reponse4 = ?, bonne_reponse = ?, categorie = ?
        WHERE id = ?
    ")->execute([$question, $r1, $r2, $r3, $r4, $bonne, $cat, $qid]);
    $msg_ok = "Question modifiée avec succès.";
}
// LISTE DES QUESTIONS avec pagination et filtres
$page       = max(1, (int)($_GET['page'] ?? 1));
$par_page   = 15; // Nombre de questions par page
$offset     = ($page - 1) * $par_page;
$search     = trim($_GET['q'] ?? '');
$cat_filter = trim($_GET['cat'] ?? '');

// Construction de la clause WHERE selon les filtres
$where  = "WHERE 1=1";
$params = [];
if ($search) {
    $where   .= " AND question LIKE ?";
    $params[] = "%$search%";
}
if ($cat_filter) {
    $where   .= " AND categorie = ?";
    $params[] = $cat_filter;
}
// Compter le total pour la pagination
$total_req = $pdo->prepare("SELECT COUNT(*) FROM questions $where");
$total_req->execute($params);
$total = (int)$total_req->fetchColumn();
$total_pages = max(1, ceil($total / $par_page));

// Récupérer les questions de la page courante
$req = $pdo->prepare("SELECT * FROM questions $where ORDER BY id DESC LIMIT $par_page OFFSET $offset");
$req->execute($params);
$questions = $req->fetchAll();

// Liste des catégories pour le filtre
$categories = $pdo->query("SELECT DISTINCT categorie FROM questions ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);

// Question à modifier (si on clique sur "Modifier")
$edit_question = null;
if (isset($_GET['edit'])) {
    $req_e = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $req_e->execute([(int)$_GET['edit']]);
    $edit_question = $req_e->fetch();
}

include('includes/header.php');
?>

<style>
.badge-etudiant { background:#dbeafe; color:#1d4ed8; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:600; }
</style>

<div class="container-fluid py-4 px-4">

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">
                <i class="bi bi-question-circle me-2"></i>Gestion des Questions
            </h4>
            <span class="text-muted" style="font-size:14px;"><?= $total ?> question(s) dans la base</span>
        </div>
        <div class="d-flex gap-2">
            <a href="admin.php" class="btn btn-outline-secondary">
                <i class="bi bi-people me-2"></i>Utilisateurs
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouter">
                <i class="bi bi-plus-circle me-2"></i>Ajouter une question
            </button>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($msg_ok):  ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg_ok) ?></div><?php endif; ?>
    <?php if ($msg_err): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($msg_err) ?></div><?php endif; ?>

    <!-- Filtres de recherche -->
    <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
        <input type="text" name="q" class="form-control"
               placeholder="Rechercher dans les questions..."
               value="<?= htmlspecialchars($search) ?>"
               style="max-width:300px;">
        <select name="cat" class="form-select" style="max-width:180px;">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $cat_filter === $c ? 'selected' : '' ?>>
                <?= htmlspecialchars($c) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary">
            <i class="bi bi-search me-1"></i>Filtrer
        </button>
        <?php if ($search || $cat_filter): ?>
        <a href="admin_questions.php" class="btn btn-outline-secondary">Réinitialiser</a>
        <?php endif; ?>
    </form>

    <!-- Tableau des questions -->
    <div class="card">
        <table class="table table-hover mb-0">
            <thead style="background:#f8fafc;">
                <tr>
                    <th class="ps-4" style="width:45%;">Question</th>
                    <th>Catégorie</th>
                    <th>Bonne réponse</th>
                    <th class="pe-4 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $q): ?>
                <tr>
                    <!-- Question (tronquée si trop longue) -->
                    <td class="ps-4" style="vertical-align:middle;">
                        <div style="max-width:400px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                             title="<?= htmlspecialchars($q['question']) ?>">
                            <?= htmlspecialchars($q['question']) ?>
                        </div>
                    </td>

                    <!-- Catégorie -->
                    <td style="vertical-align:middle;">
                        <span class="badge-etudiant"><?= htmlspecialchars($q['categorie']) ?></span>
                    </td>

                    <!-- Bonne réponse -->
                    <td style="vertical-align:middle;">
                        <?php $labels = ['A','B','C','D']; ?>
                        <span style="font-weight:700; color:#16a34a;">
                            <?= $labels[$q['bonne_reponse'] - 1] ?>
                        </span>
                        —
                        <span class="text-muted" style="font-size:13px;">
                            <?= htmlspecialchars(substr($q['reponse'.$q['bonne_reponse']], 0, 40)) ?>
                            <?= strlen($q['reponse'.$q['bonne_reponse']]) > 40 ? '...' : '' ?>
                        </span>
                    </td>

                    <!-- Actions -->
                    <td style="vertical-align:middle; text-align:right;" class="pe-4">
                        <!-- Bouton modifier : ouvre la modal avec les données pré-remplies -->
                        <a href="?edit=<?= $q['id'] ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $cat_filter ? '&cat='.urlencode($cat_filter) : '' ?>"
                           class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil me-1"></i>Modifier
                        </a>
                        <!-- Bouton supprimer avec confirmation -->
                        <form method="POST" style="display:inline;"
                              onsubmit="return confirm('Supprimer cette question définitivement ?')">
                            <input type="hidden" name="qid" value="<?= $q['id'] ?>">
                            <button type="submit" name="supprimer" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Supprimer
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link"
                   href="?page=<?= $p ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $cat_filter ? '&cat='.urlencode($cat_filter) : '' ?>">
                    <?= $p ?>
                </a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>

</div>
<!-- MODAL : Ajouter une question  -->
<div class="modal fade" id="modalAjouter" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-plus-circle me-2"></i>Ajouter une question
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Question *</label>
                        <textarea name="question" class="form-control" rows="2" required
                                  placeholder="Saisir la question..."></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Réponse A *</label>
                            <input type="text" name="reponse1" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Réponse B *</label>
                            <input type="text" name="reponse2" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Réponse C *</label>
                            <input type="text" name="reponse3" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Réponse D *</label>
                            <input type="text" name="reponse4" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Bonne réponse *</label>
                            <select name="bonne_reponse" class="form-select" required>
                                <option value="1">A — Réponse A</option>
                                <option value="2">B — Réponse B</option>
                                <option value="3">C — Réponse C</option>
                                <option value="4">D — Réponse D</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Catégorie</label>
                            <input type="text" name="categorie" class="form-control"
                                   placeholder="Ex: HTML, SQL, PHP..."
                                   list="cat-list">
                            <datalist id="cat-list">
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="ajouter" class="btn btn-primary fw-semibold">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL : Modifier une question   -->
<!-- (s'ouvre si ?edit=id dans URL)  -->
<?php if ($edit_question): ?>
<div class="modal fade show" id="modalModifier" tabindex="-1" style="display:block; background:rgba(0,0,0,.5);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil me-2"></i>Modifier la question
                </h5>
                <a href="admin_questions.php" class="btn-close"></a>
            </div>
            <form method="POST">
                <input type="hidden" name="qid" value="<?= $edit_question['id'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Question *</label>
                        <textarea name="question" class="form-control" rows="2" required><?= htmlspecialchars($edit_question['question']) ?></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Réponse A *</label>
                            <input type="text" name="reponse1" class="form-control" value="<?= htmlspecialchars($edit_question['reponse1']) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Réponse B *</label>
                            <input type="text" name="reponse2" class="form-control" value="<?= htmlspecialchars($edit_question['reponse2']) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Réponse C *</label>
                            <input type="text" name="reponse3" class="form-control" value="<?= htmlspecialchars($edit_question['reponse3']) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Réponse D *</label>
                            <input type="text" name="reponse4" class="form-control" value="<?= htmlspecialchars($edit_question['reponse4']) ?>" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Bonne réponse *</label>
                            <select name="bonne_reponse" class="form-select" required>
                                <?php for ($r = 1; $r <= 4; $r++): ?>
                                <option value="<?= $r ?>" <?= $edit_question['bonne_reponse'] == $r ? 'selected' : '' ?>>
                                    <?= ['A','B','C','D'][$r-1] ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Catégorie</label>
                            <input type="text" name="categorie" class="form-control"
                                   value="<?= htmlspecialchars($edit_question['categorie']) ?>"
                                   list="cat-list2">
                            <datalist id="cat-list2">
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="admin_questions.php" class="btn btn-outline-secondary">Annuler</a>
                    <button type="submit" name="modifier" class="btn btn-primary fw-semibold">
                        <i class="bi bi-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include('includes/footer.php'); ?>