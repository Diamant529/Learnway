<?php
/**
 * Learn Way - Action de connexion
 */
require_once __DIR__ . '/../includes/auth.php';

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

// Vérifier le jeton CSRF
verifyCSRFToken();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = "Veuillez remplir tous les champs.";
    header('Location: /index.php');
    exit;
}

// Rechercher l'utilisateur dans la base de données
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Authentification réussie
        // Régénérer la session pour éviter la fixation de session
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_first_name'] = $user['first_name'];
        $_SESSION['user_last_name'] = $user['last_name'];
        
        // Rediriger vers son tableau de bord
        redirectToDashboard($user['role']);
    } else {
        // Échec de l'authentification
        $_SESSION['login_error'] = "Adresse email ou mot de passe incorrect.";
        header('Location: /index.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['login_error'] = "Une erreur système est survenue. Veuillez réessayer plus tard.";
    header('Location: /index.php');
    exit;
}
