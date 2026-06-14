<?php
/**
 * Learn Way - En-tête HTML commun
 */
require_once __DIR__ . '/auth.php';

// Définir le titre de la page par défaut s'il n'est pas fourni
$pageTitle = $pageTitle ?? 'Apprentissage en ligne';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Learn Way - Plateforme d'apprentissage en ligne moderne, professionnelle et intuitive pour étudiants et enseignants.">
    <title>Learn Way - <?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Liens CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/glassmorphism.css">
    
    <!-- Jeton CSRF pour AJAX -->
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
</head>
<body>
    <?php if (!empty($_SESSION['user_id'])): ?>
    <div class="app-container">
        <!-- Barre latérale incluse séparément -->
        <?php include_once __DIR__ . '/sidebar.php'; ?>
        
        <!-- Barre de navigation supérieure -->
        <header class="navbar glass-navbar">
            <button class="menu-toggle" id="sidebarToggle" aria-label="Menu principal">
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <h1 class="page-title text-gradient"><?= htmlspecialchars($pageTitle) ?></h1>
            
            <div class="navbar-actions">
                <div class="user-avatar" title="<?= htmlspecialchars($_SESSION['user_email']) ?>">
                    <?= strtoupper(substr($_SESSION['user_first_name'], 0, 1) . substr($_SESSION['user_last_name'], 0, 1)) ?>
                </div>
                <a href="/logout.php" class="btn-logout" aria-label="Se déconnecter">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Déconnexion</span>
                </a>
            </div>
        </header>
        
        <!-- Contenu principal -->
        <main class="main-content">
    <?php endif; ?>
