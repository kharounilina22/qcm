<?php
//  Gestion des utilisateurs : voir, bloquer, promouvoir, supprimer
//  Accès réservé aux admins uniquement
session_start();
include('config/db.php');

// Sécurité : rediriger si non connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Sécurité : accès refusé si pas admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$msg_ok  = '';
$msg_err = '';

// ACTION : Supprimer un utilisateur
if (isset($_POST['supprimer'])) {
    $uid = (int)$_POST['uid'];
    if ($uid == $_SESSION['user_id']) {
        $msg_err = "Vous ne pouvez pas supprimer votre propre compte.";
    } else {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        $msg_ok = "Utilisateur supprimé avec succès.";
    }
}
// ACTION : Bloquer / Débloquer un utilisateur

if (isset($_POST['bloquer'])) {
    $uid = (int)$_POST['uid'];
    if ($uid == $_SESSION['user_id']) {
        $msg_err = "Vous ne pouvez pas bloquer votre propre compte.";
    } else {
        // Récupérer le statut actuel du compte
        $req = $pdo->prepare("SELECT bloque FROM users WHERE id = ?");
        $req->execute([$uid]);
        $bloque_actuel = (int)$req->fetchColumn();

        // Inverser le statut (0 → 1 ou 1 → 0)
        $nouveau = $bloque_actuel ? 0 : 1;
        $pdo->prepare("UPDATE users SET bloque = ? WHERE id = ?")->execute([$nouveau, $uid]);
        $msg_ok = $nouveau ? "Utilisateur bloqué." : "Utilisateur débloqué.";
    }
}
// ACTION : Promouvoir un étudiant en admin

if (isset($_POST['promouvoir'])) {
    $uid = (int)$_POST['uid'];
    $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$uid]);
    $msg_ok = "Utilisateur promu administrateur.";
}
// ACTION : Rétrograder un admin en étudiant

if (isset($_POST['retrograder'])) {
    $uid = (int)$_POST['uid'];
    if ($uid == $_SESSION['user_id']) {
        $msg_err = "Vous ne pouvez pas vous rétrograder vous-même.";
    } else {
        $pdo->prepare("UPDATE users SET role = 'etudiant' WHERE id = ?")->execute([$uid]);
        $msg_ok = "Utilisateur rétrogradé en étudiant.";
    }
}
// RECHERCHE d'utilisateurs

$search = trim($_GET['q'] ?? '');
if ($search) {
    // LIKE est insensible à la casse en MySQL par défaut
    $req = $pdo->prepare("
        SELECT u.*,
               (SELECT COUNT(*) FROM attempts a WHERE a.user_id = u.id) AS nb_qcm
        FROM users u
        WHERE u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?
        ORDER BY u.id DESC
    ");
    $req->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $req = $pdo->query("
        SELECT u.*,
               (SELECT COUNT(*) FROM attempts a WHERE a.user_id = u.id) AS nb_qcm
        FROM users u
        ORDER BY u.id DESC
    ");
}
$users = $req->fetchAll();

// STATISTIQUES GLOBALES
$nb_etudiants = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'etudiant'")->fetchColumn();
$nb_admins    = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$nb_bloques   = $pdo->query("SELECT COUNT(*) FROM users WHERE bloque = 1")->fetchColumn();
$nb_qcm       = $pdo->query("SELECT COUNT(*) FROM attempts WHERE statut = 'termine'")->fetchColumn();
$moyenne      = $pdo->query("SELECT ROUND(AVG(score_sur_20), 2) FROM attempts WHERE statut = 'termine'")->fetchColumn();

include('includes/header.php');
?>
<style>
/* Badges de rôle et statut */
.badge-admin    { background:#ede9fe; color:#5b21b6; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:600; }
.badge-etudiant { background:#dbeafe; color:#1d4ed8; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:600; }
.badge-bloque   { background:#fee2e2; color:#991b1b; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:600; }
</style>

<div class="container py-4">

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">
                <i class="bi bi-people me-2"></i>Gestion des Utilisateurs
            </h4>
            <span class="text-muted" style="font-size:14px;">Panneau d'administration</span>
        </div>
        <a href="admin_questions.php" class="btn btn-outline-primary">
            <i class="bi bi-question-circle me-2"></i>Gérer les questions
        </a>
    </div>

    <!-- ===== STATISTIQUES GLOBALES ===== -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md">
            <div class="card p-3 text-center">
                <div style="font-size:1.8rem; font-weight:800; color:#2563eb;"><?= $nb_etudiants ?></div>
                <div class="text-muted" style="font-size:13px;">Étudiants</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card p-3 text-center">
                <div style="font-size:1.8rem; font-weight:800; color:#9333ea;"><?= $nb_admins ?></div>
                <div class="text-muted" style="font-size:13px;">Admins</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card p-3 text-center">
                <div style="font-size:1.8rem; font-weight:800; color:#dc2626;"><?= $nb_bloques ?></div>
                <div class="text-muted" style="font-size:13px;">Bloqués</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card p-3 text-center">
                <div style="font-size:1.8rem; font-weight:800; color:#16a34a;"><?= $nb_qcm ?></div>
                <div class="text-muted" style="font-size:13px;">QCM passés</div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card p-3 text-center">
                <div style="font-size:1.8rem; font-weight:800; color:#d97706;"><?= $moyenne ?? 0 ?>/20</div>
                <div class="text-muted" style="font-size:13px;">Moyenne</div>
            </div>
        </div>
    </div>

    <!-- Messages de succès / erreur -->
    <?php if ($msg_ok):  ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg_ok) ?></div><?php endif; ?>
    <?php if ($msg_err): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($msg_err) ?></div><?php endif; ?>

    <!-- Barre de recherche -->
    <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
        <input type="text" name="q" class="form-control"
               placeholder="Rechercher un utilisateur..."
               value="<?= htmlspecialchars($search) ?>"
               style="max-width:350px;">
        <button class="btn btn-outline-secondary">
            <i class="bi bi-search me-1"></i>Rechercher
        </button>
        <?php if ($search): ?>
        <a href="admin.php" class="btn btn-outline-secondary">Réinitialiser</a>
        <?php endif; ?>
    </form>

    <!-- ===== TABLEAU DES UTILISATEURS ===== -->
    <div class="card">
        <table class="table table-hover mb-0">
            <thead style="background:#f8fafc;">
                <tr>
                    <th class="ps-4">Utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>QCM</th>
                    <th>Statut</th>
                    <th class="pe-4 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <!-- Nom -->
                    <td class="ps-4" style="vertical-align:middle;">
                        <div class="fw-bold"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></div>
                    </td>

                    <!-- Email -->
                    <td style="vertical-align:middle; color:#64748b;">
                        <?= htmlspecialchars($u['email']) ?>
                    </td>

                    <!-- Rôle avec badge coloré -->
                    <td style="vertical-align:middle;">
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge-admin">Admin</span>
                        <?php else: ?>
                            <span class="badge-etudiant">Étudiant</span>
                        <?php endif; ?>
                    </td>

                    <!-- Nombre de QCM passés -->
                    <td style="vertical-align:middle; color:#64748b;">
                        <?= $u['nb_qcm'] ?> QCM
                    </td>

                    <!-- Statut du compte -->
                    <td style="vertical-align:middle;">
                        <?php if ($u['bloque'] == 1): ?>
                            <span class="badge-bloque">Bloqué</span>
                        <?php else: ?>
                            <span style="background:#dcfce7; color:#166534; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:600;">Actif</span>
                        <?php endif; ?>
                    </td>

                    <!-- Actions -->
                    <td style="vertical-align:middle; text-align:right;" class="pe-4">

                        <?php if ($u['id'] != $_SESSION['user_id']): ?>

                        <!-- Promouvoir ou rétrograder -->
                        <?php if ($u['role'] === 'etudiant'): ?>
                        <form method="POST" style="display:inline-block; margin-right:3px;">
                            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                            <button type="submit" name="promouvoir" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-up-circle me-1"></i>Admin
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:inline-block; margin-right:3px;">
                            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                            <button type="submit" name="retrograder" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-down-circle me-1"></i>Étudiant
                            </button>
                        </form>
                        <?php endif; ?>

                        <!-- Bloquer ou débloquer -->
                        <form method="POST" style="display:inline-block; margin-right:3px;">
                            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                            <button type="submit" name="bloquer"
                                    class="btn btn-sm <?= $u['bloque'] ? 'btn-outline-success' : 'btn-outline-warning' ?>">
                                <i class="bi bi-<?= $u['bloque'] ? 'unlock' : 'lock' ?> me-1"></i>
                                <?= $u['bloque'] ? 'Débloquer' : 'Bloquer' ?>
                            </button>
                        </form>

                        <!-- Supprimer -->
                        <form method="POST" style="display:inline-block;"
                              onsubmit="return confirm('⚠️ Supprimer cet utilisateur et toutes ses données ?')">
                            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                            <button type="submit" name="supprimer" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Supprimer
                            </button>
                        </form>

                        <?php else: ?>
                        <span class="text-muted" style="font-size:13px;">— votre compte —</span>
                        <?php endif; ?>

                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-2 text-muted" style="font-size:13px;">
        <?= count($users) ?> utilisateur(s) affiché(s)
    </div>
</div>

<?php include('includes/footer.php'); ?>