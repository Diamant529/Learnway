<?php
/**
 * Learn Way - Script de déconnexion
 */
require_once __DIR__ . '/config/database.php';

// Vider toutes les variables de session
$_SESSION = [];

// Détruire le cookie de session si nécessaire
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil
header("Location: /index.php");
exit;
