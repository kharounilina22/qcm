<?php
//  PAGE DE CONNEXION — login.php
session_start();
include('config/db.php');

// Si connecté
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error   = '';
$success = '';

// Message inscription réussie
if (isset($_GET['inscrit'])) {
    $success = "Inscription réussie ! Connectez-vous pour accéder à votre espace.";
}
// Traitement connexion
if (isset($_POST['login'])) {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Vérification utilisateur
    $req = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $req->execute([$email]);

    $user = $req->fetch();

    // Vérification mot de passe
    if ($user && password_verify($password, $user['mot_de_passe'])) {

        // Vérifier si bloqué
        if ($user['bloque'] == 1) {
            $error = "Votre compte est bloqué. Contactez l'administrateur.";
        } else {
            // Session
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

<style>
    body{
        background: linear-gradient(135deg, #0f172a, #1e3a8a);
        min-height: 100vh;
        font-family: 'Segoe UI', sans-serif;
    }

    .login-container{
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 15px;
    }

    .login-card{
        width: 100%;
        max-width: 430px;
        background: white;
        border-radius: 22px;
        padding: 40px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.20);
        animation: fadeIn 0.5s ease;
    }
    @keyframes fadeIn{
        from{
            opacity: 0;
            transform: translateY(20px);
        }
        to{
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-title{
        font-size: 30px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 8px;
    }

    .login-subtitle{
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
        font-size: 15px;
        transition: 0.3s;
    }

    .form-control:focus{
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37,99,235,0.15);
    }

    .btn-login{
        width: 100%;
        height: 52px;
        border: none;
        border-radius: 14px;
        background: #0f172a;
        color: white;
        font-size: 16px;
        font-weight: 600;
        transition: 0.3s;
    }

    .btn-login:hover{
        background: #020617;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.25);
    }

    .alert{
        border-radius: 12px;
    }

    .register-link{
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
    }

    .register-link:hover{
        text-decoration: underline;
    }

    .small-text{
        font-size: 14px;
        color: #64748b;
    }

    @media(max-width: 576px){

        .login-card{
            padding: 30px 22px;
        }

    }

</style>
<!-- ===== PAGE LOGIN ===== -->
<div class="login-container">

    <div class="login-card">

        <!-- TITRE -->
        <div class="text-center mb-4">

            <h2 class="login-title">Connexion</h2>

            <p class="login-subtitle">
                Entrez vos identifiants pour accéder à votre espace
            </p>

        </div>
        <!-- ALERTES -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- FORMULAIRE -->

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Adresse email
                </label>

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
            <div class="mb-4">

                <label class="form-label">
                    Mot de passe
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
            <button type="submit" name="login" class="btn-login">

                <i class="bi bi-box-arrow-in-right me-2"></i>

                Se connecter

            </button>

        </form>
        <!-- FOOTER -->
        <div class="text-center mt-4">

            <span class="small-text">
                Pas encore de compte ?
            </span>

            <a href="register.php" class="register-link">
                S'inscrire
            </a>
        </div>
    </div>
</div>
<?php include('includes/footer.php'); ?>