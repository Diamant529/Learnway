<?php
/**
 * Learn Way - Action d'inscription
 */
require_once __DIR__ . '/../includes/auth.php';

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /register.php');
    exit;
}

// Vérifier le jeton CSRF
verifyCSRFToken();

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'etudiant';

// Validation des données obligatoires
if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    $_SESSION['register_error'] = "Veuillez remplir tous les champs obligatoires.";
    header('Location: /register.php');
    exit;
}

// Validation du format d'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = "Format d'adresse email invalide.";
    header('Location: /register.php');
    exit;
}

// Validation de la taille du mot de passe
if (strlen($password) < 6) {
    $_SESSION['register_error'] = "Le mot de passe doit contenir au moins 6 caractères.";
    header('Location: /register.php');
    exit;
}

// Restriction stricte des rôles enregistrables publiquement (le rôle promoteur est interdit)
if ($role !== 'etudiant' && $role !== 'enseignant') {
    $_SESSION['register_error'] = "Rôle invalide.";
    header('Location: /register.php');
    exit;
}

try {
    // Vérifier si l'adresse email existe déjà
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        $_SESSION['register_error'] = "Cette adresse email est déjà enregistrée.";
        header('Location: /register.php');
        exit;
    }
    
    // Hashage sécurisé du mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $pdo->beginTransaction();

    // Insertion dans la base de données
    $stmt = $pdo->prepare('INSERT INTO users (email, password, first_name, last_name, role) VALUES (:email, :password, :first_name, :last_name, :role)');
    $stmt->execute([
        'email' => $email,
        'password' => $hashedPassword,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'role' => $role
    ]);
    
    $userId = $pdo->lastInsertId();

    // Si c'est un enseignant, on l'affecte automatiquement à tous les modules existants
    if ($role === 'enseignant') {
        $stmtMods = $pdo->query('SELECT id FROM modules');
        $modules = $stmtMods->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($modules)) {
            $stmtAssign = $pdo->prepare('INSERT INTO module_teachers (module_id, teacher_id) VALUES (:mid, :tid)');
            foreach ($modules as $mid) {
                $stmtAssign->execute(['mid' => $mid, 'tid' => $userId]);
            }
        }
    }

    $pdo->commit();
    
    $_SESSION['register_success'] = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
    header('Location: /index.php');
    exit;
    
} catch (PDOException $e) {
    $_SESSION['register_error'] = "Une erreur système est survenue lors de l'enregistrement.";
    header('Location: /register.php');
    exit;
}
