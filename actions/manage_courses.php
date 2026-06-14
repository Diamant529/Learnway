<?php
/**
 * Learn Way - Action de gestion des cours (Enseignant uniquement)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('enseignant');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboards/enseignant.php');
    exit;
}
verifyCSRFToken();

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $moduleId = intval($_POST['module_id'] ?? 0);

            if (empty($title) || $moduleId <= 0) {
                $_SESSION['action_error'] = "Titre et module sont requis.";
                header('Location: /dashboards/enseignant.php'); exit;
            }
            // Vérifier que l'enseignant est bien affecté à ce module
            $stmt = $pdo->prepare('SELECT 1 FROM module_teachers WHERE module_id = :mid AND teacher_id = :tid');
            $stmt->execute(['mid' => $moduleId, 'tid' => $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                $_SESSION['action_error'] = "Vous n'êtes pas affecté à ce module.";
                header('Location: /dashboards/enseignant.php'); exit;
            }
            $stmt = $pdo->prepare('INSERT INTO courses (title, description, module_id, created_by) VALUES (:title, :description, :module_id, :created_by)');
            $stmt->execute(['title' => $title, 'description' => $description, 'module_id' => $moduleId, 'created_by' => $_SESSION['user_id']]);
            $_SESSION['action_success'] = "Cours créé avec succès.";
            break;

        case 'edit':
            $id = intval($_POST['course_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if ($id <= 0 || empty($title)) { $_SESSION['action_error'] = "Données invalides."; header('Location: /dashboards/enseignant.php'); exit; }
            // Vérifier la propriété du cours
            $stmt = $pdo->prepare('SELECT 1 FROM courses WHERE id = :id AND created_by = :uid');
            $stmt->execute(['id' => $id, 'uid' => $_SESSION['user_id']]);
            if (!$stmt->fetch()) { $_SESSION['action_error'] = "Vous n'avez pas les droits sur ce cours."; header('Location: /dashboards/enseignant.php'); exit; }
            $stmt = $pdo->prepare('UPDATE courses SET title = :title, description = :description WHERE id = :id');
            $stmt->execute(['title' => $title, 'description' => $description, 'id' => $id]);
            $_SESSION['action_success'] = "Cours modifié avec succès.";
            break;

        case 'delete':
            $id = intval($_POST['course_id'] ?? 0);
            if ($id <= 0) { $_SESSION['action_error'] = "Identifiant invalide."; header('Location: /dashboards/enseignant.php'); exit; }
            $stmt = $pdo->prepare('SELECT 1 FROM courses WHERE id = :id AND created_by = :uid');
            $stmt->execute(['id' => $id, 'uid' => $_SESSION['user_id']]);
            if (!$stmt->fetch()) { $_SESSION['action_error'] = "Vous n'avez pas les droits sur ce cours."; header('Location: /dashboards/enseignant.php'); exit; }
            $stmt = $pdo->prepare('DELETE FROM courses WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $_SESSION['action_success'] = "Cours supprimé avec succès.";
            break;

        default:
            $_SESSION['action_error'] = "Action non reconnue.";
    }
} catch (PDOException $e) {
    $_SESSION['action_error'] = "Erreur base de données : " . $e->getMessage();
}

header('Location: /dashboards/enseignant.php');
exit;
