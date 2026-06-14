<?php
/**
 * Learn Way - Middleware d'authentification et de rôle
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Vérifie si l'utilisateur est connecté. Si non, redirige vers l'accueil.
 */
function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Vérifie si l'utilisateur est connecté et possède un rôle autorisé.
 * @param array|string $allowedRoles Le ou les rôles autorisés ('promoteur', 'enseignant', 'etudiant')
 */
function requireRole($allowedRoles) {
    requireLogin();
    
    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        // Redirection vers le tableau de bord correspondant au rôle de l'utilisateur
        redirectToDashboard($_SESSION['user_role']);
    }
}

/**
 * Redirige l'utilisateur vers son tableau de bord par défaut
 */
function redirectToDashboard($role) {
    switch ($role) {
        case 'promoteur':
            header('Location: /dashboards/promoteur.php');
            break;
        case 'enseignant':
            header('Location: /dashboards/enseignant.php');
            break;
        case 'etudiant':
            header('Location: /dashboards/etudiant.php');
            break;
        default:
            header('Location: /index.php');
            break;
    }
    exit;
}

/**
 * Vérifie la validité d'un token CSRF envoyé dans une requête (POST ou AJAX)
 */
function verifyCSRFToken($token = null) {
    if ($token === null) {
        // Récupération automatique depuis POST ou les headers HTTP
        if (!empty($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
    }
    
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // En cas d'erreur AJAX, retourner un JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Jeton CSRF invalide.']);
            exit;
        } else {
            http_response_code(403);
            die("Erreur de sécurité : Jeton CSRF invalide ou expiré.");
        }
    }
}

/**
 * Génère un champ de formulaire caché contenant le jeton CSRF
 */
function csrfInput() {
    $token = $_SESSION['csrf_token'] ?? '';
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
