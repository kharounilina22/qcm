<?php
// ============================================================
//  En-tête commun à toutes les pages
//  Inclus en haut de chaque fichier PHP
// ============================================================

// On vérifie si l'utilisateur est connecté
$est_connecte = isset($_SESSION['user_id']);
$est_admin    = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizMaster - QCM Informatique L1</title>

    <!-- Bootstrap 5 CSS (via CDN, pas besoin de télécharger) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* Police Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
        }

        /* Barre de navigation */
        .navbar {
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            color: #2563eb !important;
        }

        .navbar-brand i {
            color: #2563eb;
        }

        /* Bouton principal bleu */
        .btn-primary {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }

        /* Lien actif dans la navigation */
        .nav-link.active {
            color: #2563eb !important;
            font-weight: 600;
        }

        /* Cartes avec ombre légère */
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border-radius: 12px;
        }

        /* Pied de page */
        footer {
            background: #1e293b;
            color: #94a3b8;
            padding: 20px 0;
            text-align: center;
            margin-top: 60px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<!-- ===== BARRE DE NAVIGATION ===== -->
<nav class="navbar navbar-expand-lg sticky-top mb-4">
    <div class="container">

        <!-- Logo / Nom du site -->
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-book-half me-2"></i>QuizMaster
        </a>

        <!-- Bouton menu mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Liens de navigation -->
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto align-items-center gap-1">

                <?php if ($est_connecte): ?>
                    <!-- Menus pour utilisateur connecté -->
                    <li class="nav-item">
                        <a class="nav-link" href="qcm.php">
                            <i class="bi bi-play-circle me-1"></i>Passer un QCM
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="historique.php">
                            <i class="bi bi-clock-history me-1"></i>Mon historique
                        </a>
                    </li>

                    <?php if ($est_admin): ?>
                        <!-- Menus admin uniquement -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-shield-check me-1"></i>Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="admin.php">
                                    <i class="bi bi-people me-2"></i>Utilisateurs
                                </a></li>
                                <li><a class="dropdown-item" href="admin_questions.php">
                                    <i class="bi bi-question-circle me-2"></i>Questions
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Nom de l'utilisateur + déconnexion -->
                    <li class="nav-item">
                        <span class="nav-link text-muted">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger btn-sm" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                        </a>
                    </li>

                <?php else: ?>
                    <!-- Menus pour visiteur non connecté -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Connexion</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm" href="register.php">Inscription</a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<!-- Le contenu de la page commence ici -->
