<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

//  PAGE D'ACCUEIL — index.php
//  Affiche les statistiques et les boutons de navigation

session_start();
include('config/db.php');
include('includes/header.php');

// On compte les données pour afficher les statistiques
$nb_questions  = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$nb_etudiants  = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'etudiant'")->fetchColumn();
$nb_tentatives = $pdo->query("SELECT COUNT(*) FROM attempts WHERE statut = 'termine'")->fetchColumn();
?>

<!-- ===== BANNIÈRE HERO ===== -->
<div style="background: linear-gradient(135deg, #1e40af, #2563eb, #3b82f6); padding: 80px 0; color: white; text-align: center;">
    <div class="container">
        <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 16px;">
            Préparez vos examens pour spécialitée Developpement WEB<br>
            <span style="color: #93c5fd;">avec rigueur</span>
        </h1>
        <p style="font-size: 1.1rem; opacity: .9; max-width: 580px; margin: 0 auto 32px;">
            QuizLicence2 est votre plateforme d'entraînement pour l'informatique L1.
            Des QCM chronométrés avec correction immédiate.
        </p>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <!-- Boutons pour visiteur non connecté -->
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="login.php" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </a>
                <a href="register.php" class="btn btn-light btn-lg px-4 fw-bold" style="color: #1e40af;">
                    <i class="bi bi-person-plus me-2"></i>S'inscrire
                </a>
            </div>
        <?php else: ?>
            <!-- Bouton pour utilisateur connecté -->
            <a href="qcm.php" class="btn btn-light btn-lg px-5 fw-bold" style="color: #1e40af;">
                <i class="bi bi-play-circle-fill me-2"></i>Lancer le QCM
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ===== CARTES DE FONCTIONNALITÉS ===== -->
<div class="container py-5">
    <div class="row g-4 mb-5">

        <div class="col-md-4">
            <div class="card p-4 text-center h-100">
                <div style="font-size: 2.5rem; color: #2563eb; margin-bottom: 12px;">
                    <i class="bi bi-clock"></i>
                </div>
                <h5 class="fw-bold">Chronométré</h5>
                <p class="text-muted">10 minutes pour répondre à 10 questions. Conditions réelles d'examen.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4 text-center h-100">
                <div style="font-size: 2.5rem; color: #2563eb; margin-bottom: 12px;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h5 class="fw-bold">Anti-triche</h5>
                <p class="text-muted">Détection de changement d'onglet et soumission automatique à la fin du temps.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4 text-center h-100">
                <div style="font-size: 2.5rem; color: #2563eb; margin-bottom: 12px;">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <h5 class="fw-bold">Corrections</h5>
                <p class="text-muted">Retours détaillés question par question pour comprendre vos erreurs.</p>
            </div>
        </div>

    </div>

    <!-- ===== STATISTIQUES ===== -->
    <h5 class="fw-bold text-center mb-4">La plateforme en chiffres</h5>
    <div class="row g-4 text-center">

        <div class="col-4">
            <div class="card p-3">
                <div style="font-size: 2rem; font-weight: 800; color: #2563eb;"><?= $nb_questions ?></div>
                <div class="text-muted">Questions disponibles</div>
            </div>
        </div>

        <div class="col-4">
            <div class="card p-3">
                <div style="font-size: 2rem; font-weight: 800; color: #2563eb;"><?= $nb_etudiants ?></div>
                <div class="text-muted">Étudiants inscrits</div>
            </div>
        </div>

        <div class="col-4">
            <div class="card p-3">
                <div style="font-size: 2rem; font-weight: 800; color: #2563eb;"><?= $nb_tentatives ?></div>
                <div class="text-muted">QCM passés</div>
            </div>
        </div>

    </div>
</div>

<?php include('includes/footer.php'); ?>