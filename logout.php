<?php
// ============================================================
//  DÉCONNEXION — logout.php
//  Détruit la session et redirige vers l'accueil
// ============================================================
session_start();
session_destroy(); // Supprime toutes les données de session
header("Location: index.php");
exit;