<?php
// ============================================================
//  PAGE DE CONNEXION — login.php
// ============================================================
session_start();
include('config/db.php');

// Si l'utilisateur est déjà connecté, on le redirige vers l'accueil
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error   = '';
$success = '';

// Message si on vient de s'inscrire
if (isset($_GET['inscrit'])) {
    $success = "Inscription réussie ! Connectez-vous pour accéder à votre espace.";
}

// Traitement du formulaire de connexion
if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Recherche de l'utilisateur par email
    $req = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $req->execute([$email]);
    $user = $req->fetch();

    // Vérification du mot de passe avec password_verify()
    if ($user && password_verify($password, $user['mot_de_passe'])) {

        // Vérification si le compte est bloqué
        if ($user['bloque'] == 1) {
            $error = "Votre compte est bloqué. Contactez l'administrateur.";
        } else {
            // Connexion réussie : on enregistre les infos dans la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['prenom']  = $user['prenom'];
            $_SESSION['role']    = $user['role'];

            header("Location: index.php");
            exit;
        }
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}

include('includes/header.php');
?>

<!-- ===== FORMULAIRE DE CONNEXION ===== -->
<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
    <div class="card p-4 p-md-5" style="width: 100%; max-width: 420px;">

        <div class="text-center mb-4">
            <i class="bi bi-book-half" style="font-size: 2.5rem; color: #2563eb;"></i>
            <h2 class="fw-bold mt-2 mb-1">Connexion</h2>
            <p class="text-muted" style="font-size: 14px;">Entrez vos identifiants pour accéder à votre espace</p>
        </div>

        <!-- Message d'erreur ou de succès -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="etudiant@univ.fr"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
            </button>
        </form>

        <p class="text-center mt-3 mb-0" style="font-size: 14px;">
            Pas encore de compte ?
            <a href="register.php" class="text-primary fw-semibold">S'inscrire</a>
        </p>
    </div>
</div>

<?php include('includes/footer.php'); ?>
