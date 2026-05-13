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

            // Hacher le mot de passe
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Insertion utilisateur
            $req = $pdo->prepare("
                INSERT INTO users (nom, prenom, email, mot_de_passe, role, bloque)
                VALUES (?, ?, ?, ?, 'etudiant', 0)
            ");

            $req->execute([$nom, $prenom, $email, $hash]);

            header("Location: login.php?inscrit=1");
            exit;
        }
    }
}

include('includes/header.php');
?>

<style>
    body{
        background: linear-gradient(135deg, #0f172a, #1e3a8a);
        min-height: 100vh;
        font-family: 'Segoe UI', sans-serif;
    }

    .register-container{
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 15px;
    }

    .register-card{
        width: 100%;
        max-width: 470px;
        background: #ffffff;
        border-radius: 22px;
        padding: 40px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.20);
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn{
        from{
            opacity:0;
            transform: translateY(20px);
        }
        to{
            opacity:1;
            transform: translateY(0);
        }
    }
    .register-title{
        font-size: 30px;
        font-weight: 700;
        color: #0f172a;
        margin-top: 18px;
    }

    .register-subtitle{
        color: #64748b;
        font-size: 14px;
        margin-bottom: 30px;
    }

    .form-label{
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .form-control{
        height: 52px;
        border-radius: 14px;
        border: 1px solid #cbd5e1;
        padding-left: 15px;
        transition: 0.3s;
        font-size: 15px;
    }

    .form-control:focus{
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37,99,235,0.15);
    }

    .btn-register{
        height: 52px;
        border: none;
        border-radius: 14px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        font-weight: 600;
        font-size: 16px;
        transition: 0.3s;
    }

    .btn-register:hover{
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(37,99,235,0.25);
    }

    .alert{
        border-radius: 12px;
    }

    .login-link{
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
    }

    .login-link:hover{
        text-decoration: underline;
    }

    .small-text{
        font-size: 14px;
        color: #64748b;
    }

    @media(max-width: 576px){
        .register-card{
            padding: 30px 22px;
        }
    }
</style>

<!-- ===== PAGE INSCRIPTION ===== -->

<div class="register-container">

    <div class="register-card">

        <!-- HEADER -->
        <div class="text-center">
            <h2 class="register-title">Créer un compte</h2>

            <p class="register-subtitle">
                Rejoignez la plateforme et commencez votre entraînement
            </p>
        </div>

        <!-- ERREUR -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- FORMULAIRE -->
        <form method="POST">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Prénom</label>

                    <input
                        type="text"
                        name="prenom"
                        class="form-control"
                        placeholder="Jean"
                        value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                        required
                    >
                </div>
                     <br>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nom</label>

                    <input
                        type="text"
                        name="nom"
                        class="form-control"
                        placeholder="Dupont"
                        value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                        required
                    >
                </div>
                <br>
            </div>

            <div class="mb-3">
                <label class="form-label">Adresse email</label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="exemple@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>
<br>
            <div class="mb-3">
                <label class="form-label">
                    Mot de passe
                    <small class="text-muted">(8 caractères minimum)</small>
                </label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="••••••••"
                    required
                >
            </div>
            <br>
            <div class="mb-4">
                <label class="form-label">Confirmer le mot de passe</label>

                <input
                    type="password"
                    name="confirm"
                    class="form-control"
                    placeholder="••••••••"
                    required
                >
            </div>
<br>
            <button type="submit" name="register" class="btn-register w-100">
                <i class="bi bi-person-check-fill me-2"></i>
                Créer mon compte
            </button>

        </form>

        <!-- FOOTER -->
        <div class="text-center mt-4">

            <span class="small-text">
                Déjà un compte ?
            </span>

            <a href="login.php" class="login-link">
                Se connecter
            </a>

        </div>

    </div>

</div>

<?php include('includes/footer.php'); ?>