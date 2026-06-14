<?php
/**
 * Learn Way - Action de gestion des modules (Promoteur uniquement)
 */
require_once __DIR__ . '/../includes/auth.php';

// Sécurité : Réservé uniquement au promoteur
requireRole('promoteur');

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboards/promoteur.php');
    exit;
}

// Vérifier le jeton CSRF
verifyCSRFToken();

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($title)) {
                $_SESSION['action_error'] = "Le titre du module est requis.";
                header('Location: /dashboards/promoteur.php');
                exit;
            }
            
            $stmt = $pdo->prepare('INSERT INTO modules (title, description, created_by) VALUES (:title, :description, :created_by)');
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'created_by' => $_SESSION['user_id']
            ]);
            
            $_SESSION['action_success'] = "Le module a été créé avec succès.";
            break;
            
        case 'edit':
            $id = intval($_POST['module_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if ($id <= 0 || empty($title)) {
                $_SESSION['action_error'] = "Données invalides pour la modification.";
                header('Location: /dashboards/promoteur.php');
                exit;
            }
            
            $stmt = $pdo->prepare('UPDATE modules SET title = :title, description = :description WHERE id = :id');
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'id' => $id
            ]);
            
            $_SESSION['action_success'] = "Le module a été modifié avec succès.";
            break;
            
        case 'delete':
            $id = intval($_POST['module_id'] ?? 0);
            
            if ($id <= 0) {
                $_SESSION['action_error'] = "Identifiant de module invalide.";
                header('Location: /dashboards/promoteur.php');
                exit;
            }
            
            $stmt = $pdo->prepare('DELETE FROM modules WHERE id = :id');
            $stmt->execute(['id' => $id]);
            
            $_SESSION['action_success'] = "Le module a été supprimé avec succès.";
            break;
            
        case 'assign_teacher':
            $moduleId = intval($_POST['module_id'] ?? 0);
            $teacherId = intval($_POST['teacher_id'] ?? 0);
            
            if ($moduleId <= 0 || $teacherId <= 0) {
                $_SESSION['action_error'] = "Veuillez sélectionner un module et un enseignant valides.";
                header('Location: /dashboards/promoteur.php');
                exit;
            }
            
            // Vérifier que l'enseignant existe et a bien le rôle d'enseignant
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role = "enseignant"');
            $stmt->execute(['id' => $teacherId]);
            if (!$stmt->fetch()) {
                $_SESSION['action_error'] = "L'utilisateur sélectionné n'est pas un enseignant valide.";
                header('Location: /dashboards/promoteur.php');
                exit;
            }
            
            // Insérer l'attribution (ignorer si déjà attribué)
            $stmt = $pdo->prepare('INSERT IGNORE INTO module_teachers (module_id, teacher_id) VALUES (:module_id, :teacher_id)');
            $stmt->execute([
                'module_id' => $moduleId,
                'teacher_id' => $teacherId
            ]);
            
            $_SESSION['action_success'] = "L'enseignant a été attribué au module avec succès.";
            break;
            
        case 'unassign_teacher':
            $moduleId = intval($_POST['module_id'] ?? 0);
            $teacherId = intval($_POST['teacher_id'] ?? 0);
            
            if ($moduleId <= 0 || $teacherId <= 0) {
                $_SESSION['action_error'] = "Paramètres d'attribution invalides.";
                header('Location: /dashboards/promoteur.php');
                exit;
            }
            
            $stmt = $pdo->prepare('DELETE FROM module_teachers WHERE module_id = :module_id AND teacher_id = :teacher_id');
            $stmt->execute([
                'module_id' => $moduleId,
                'teacher_id' => $teacherId
            ]);
            
            $_SESSION['action_success'] = "L'attribution a été retirée avec succès.";
            break;
            
        default:
            $_SESSION['action_error'] = "Action non reconnue.";
            break;
    }
} catch (PDOException $e) {
    $_SESSION['action_error'] = "Erreur de base de données : " . $e->getMessage();
}

header('Location: /dashboards/promoteur.php');
exit;
