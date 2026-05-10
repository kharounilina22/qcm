<?php
// ============================================================
//  PAGE D'INSCRIPTION — register.php
// ============================================================
session_start();
include('config/db.php');

// Si déjà connecté, rediriger vers l'accueil
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

// Traitement du formulaire d'inscription
if (isset($_POST['register'])) {
    $nom      = trim($_POST['nom']);
    $prenom   = trim($_POST['prenom']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    // Vérifications
    if (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier si l'email est déjà utilisé
        $req_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $req_check->execute([$email]);

        if ($req_check->rowCount() > 0) {
            $error = "Cet email est déjà utilisé. Essayez de vous connecter.";
        } else {
            // Hacher le mot de passe avec PHP
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Insérer le nouvel utilisateur en base de données
            $req = $pdo->prepare("INSERT INTO users (nom, prenom, email, mot_de_passe, role, bloque) VALUES (?, ?, ?, ?, 'etudiant', 0)");
            $req->execute([$nom, $prenom, $email, $hash]);

            // Rediriger vers la page de connexion avec un message de succès
            header("Location: login.php?inscrit=1");
            exit;
        }
    }
}

include('includes/header.php');
?>

<!-- ===== FORMULAIRE D'INSCRIPTION ===== -->
<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
    <div class="card p-4 p-md-5" style="width: 100%; max-width: 480px;">

        <div class="text-center mb-4">
            <i class="bi bi-person-plus" style="font-size: 2.5rem; color: #2563eb;"></i>
            <h2 class="fw-bold mt-2 mb-1">Inscription</h2>
            <p class="text-muted" style="font-size: 14px;">Créez votre compte pour commencer à vous entraîner</p>
        </div>

        <!-- Message d'erreur -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form method="POST">
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Prénom *</label>
                    <input type="text" name="prenom" class="form-control" placeholder="Jean"
                           value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Nom *</label>
                    <input type="text" name="nom" class="form-control" placeholder="Dupont"
                           value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email *</label>
                <input type="email" name="email" class="form-control" placeholder="jean.dupont@univ.fr"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Mot de passe * <small class="text-muted">(6 caractères minimum)</small></label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Confirmer le mot de passe *</label>
                <input type="password" name="confirm" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" name="register" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-person-check me-2"></i>Créer mon compte
            </button>
        </form>

        <p class="text-center mt-3 mb-0" style="font-size: 14px;">
            Déjà un compte ?
            <a href="login.php" class="text-primary fw-semibold">Se connecter</a>
        </p>
    </div>
</div>

<?php include('includes/footer.php'); ?>
