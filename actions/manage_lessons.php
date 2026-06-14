<?php
/**
 * Learn Way - Action de gestion des leçons, uploads et QCM (Enseignant uniquement)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('enseignant');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboards/enseignant.php');
    exit;
}
verifyCSRFToken();

$action = $_POST['action'] ?? '';
$uploadDir = __DIR__ . '/../assets/uploads/';

/**
 * Gère l'upload sécurisé d'un fichier
 * @param string $fieldName  Clé dans $_FILES
 * @param string $type       'pdf' | 'video'
 * @return string|null       Chemin relatif stocké en BDD, null si pas de fichier
 */
function handleUpload(string $fieldName, string $type): ?string {
    global $uploadDir;
    if (empty($_FILES[$fieldName]['name'])) return null;

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Erreur lors du téléversement du fichier ($fieldName).");
    }

    $allowedMimes = ($type === 'pdf')
        ? ['application/pdf']
        : ['video/mp4', 'video/quicktime', 'video/webm'];

    $maxSize = ($type === 'pdf') ? 10 * 1024 * 1024 : 50 * 1024 * 1024;

    if ($file['size'] > $maxSize) {
        $limit = ($type === 'pdf') ? '10 Mo' : '50 Mo';
        throw new RuntimeException("Le fichier dépasse la limite autorisée ($limit).");
    }

    // Vérification du type MIME réel (sécurité côté serveur)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    if (!in_array($realMime, $allowedMimes)) {
        throw new RuntimeException("Type de fichier non autorisé ($realMime).");
    }

    $ext = ($type === 'pdf') ? 'pdf' : pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid('lw_', true) . '.' . strtolower($ext);
    $subDir = ($type === 'pdf') ? 'pdf/' : 'videos/';
    $destPath = $uploadDir . $subDir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException("Impossible de déplacer le fichier téléversé.");
    }

    return 'assets/uploads/' . $subDir . $newName;
}

try {
    switch ($action) {
        // ------------------------------------------------------------------
        // CRUD LEÇONS
        // ------------------------------------------------------------------
        case 'create_lesson':
            $title = trim($_POST['lesson_title'] ?? '');
            $description = trim($_POST['lesson_description'] ?? '');
            $courseId = intval($_POST['course_id'] ?? 0);
            $position = intval($_POST['position'] ?? 1);

            if (empty($title) || $courseId <= 0) {
                $_SESSION['action_error'] = "Titre et cours sont requis.";
                header('Location: /dashboards/enseignant.php'); exit;
            }
            // Vérifier la propriété du cours
            $stmt = $pdo->prepare('SELECT 1 FROM courses WHERE id = :id AND created_by = :uid');
            $stmt->execute(['id' => $courseId, 'uid' => $_SESSION['user_id']]);
            if (!$stmt->fetch()) { $_SESSION['action_error'] = "Accès refusé."; header('Location: /dashboards/enseignant.php'); exit; }

            $pdfPath = handleUpload('pdf_file', 'pdf');
            $videoPath = handleUpload('video_file', 'video');

            $stmt = $pdo->prepare('INSERT INTO lessons (title, description, pdf_path, video_path, position, course_id) VALUES (:title, :description, :pdf_path, :video_path, :position, :course_id)');
            $stmt->execute([
                'title' => $title, 'description' => $description,
                'pdf_path' => $pdfPath, 'video_path' => $videoPath,
                'position' => $position, 'course_id' => $courseId
            ]);
            $_SESSION['action_success'] = "Leçon créée avec succès.";
            break;

        case 'edit_lesson':
            $id = intval($_POST['lesson_id'] ?? 0);
            $title = trim($_POST['lesson_title'] ?? '');
            $description = trim($_POST['lesson_description'] ?? '');
            $position = intval($_POST['position'] ?? 1);

            if ($id <= 0 || empty($title)) { $_SESSION['action_error'] = "Données invalides."; header('Location: /dashboards/enseignant.php'); exit; }
            // Vérifier la propriété via le cours
            $stmt = $pdo->prepare('SELECT l.pdf_path, l.video_path FROM lessons l JOIN courses c ON l.course_id = c.id WHERE l.id = :id AND c.created_by = :uid');
            $stmt->execute(['id' => $id, 'uid' => $_SESSION['user_id']]);
            $existing = $stmt->fetch();
            if (!$existing) { $_SESSION['action_error'] = "Accès refusé."; header('Location: /dashboards/enseignant.php'); exit; }

            $pdfPath = handleUpload('pdf_file', 'pdf') ?? $existing['pdf_path'];
            $videoPath = handleUpload('video_file', 'video') ?? $existing['video_path'];

            $stmt = $pdo->prepare('UPDATE lessons SET title = :title, description = :description, pdf_path = :pdf_path, video_path = :video_path, position = :position WHERE id = :id');
            $stmt->execute(['title' => $title, 'description' => $description, 'pdf_path' => $pdfPath, 'video_path' => $videoPath, 'position' => $position, 'id' => $id]);
            $_SESSION['action_success'] = "Leçon modifiée avec succès.";
            break;

        case 'delete_lesson':
            $id = intval($_POST['lesson_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT l.id FROM lessons l JOIN courses c ON l.course_id = c.id WHERE l.id = :id AND c.created_by = :uid');
            $stmt->execute(['id' => $id, 'uid' => $_SESSION['user_id']]);
            if (!$stmt->fetch()) { $_SESSION['action_error'] = "Accès refusé."; header('Location: /dashboards/enseignant.php'); exit; }
            $pdo->prepare('DELETE FROM lessons WHERE id = :id')->execute(['id' => $id]);
            $_SESSION['action_success'] = "Leçon supprimée avec succès.";
            break;

        // ------------------------------------------------------------------
        // GESTION DES ÉVALUATIONS (QCM)
        // ------------------------------------------------------------------
        case 'create_evaluation':
            $lessonId = intval($_POST['lesson_id'] ?? 0);
            $evalTitle = trim($_POST['eval_title'] ?? '');
            if ($lessonId <= 0 || empty($evalTitle)) { $_SESSION['action_error'] = "Données invalides."; header('Location: /dashboards/enseignant.php'); exit; }
            // Vérifier la propriété
            $stmt = $pdo->prepare('SELECT 1 FROM lessons l JOIN courses c ON l.course_id = c.id WHERE l.id = :lid AND c.created_by = :uid');
            $stmt->execute(['lid' => $lessonId, 'uid' => $_SESSION['user_id']]);
            if (!$stmt->fetch()) { $_SESSION['action_error'] = "Accès refusé."; header('Location: /dashboards/enseignant.php'); exit; }
            $stmt = $pdo->prepare('INSERT INTO evaluations (title, lesson_id) VALUES (:title, :lesson_id)');
            $stmt->execute(['title' => $evalTitle, 'lesson_id' => $lessonId]);
            $_SESSION['action_success'] = "Évaluation créée. Ajoutez des questions maintenant.";
            break;

        case 'add_question':
            $evalId = intval($_POST['evaluation_id'] ?? 0);
            $questionText = trim($_POST['question_text'] ?? '');
            $points = intval($_POST['points'] ?? 1);
            $options = $_POST['options'] ?? [];
            $correctIdx = intval($_POST['correct_index'] ?? -1);

            if ($evalId <= 0 || empty($questionText) || count($options) < 2) {
                $_SESSION['action_error'] = "La question doit avoir un texte et au moins 2 options.";
                header('Location: /dashboards/enseignant.php'); exit;
            }
            // Vérifier la propriété
            $stmt = $pdo->prepare('SELECT 1 FROM evaluations ev JOIN lessons l ON ev.lesson_id = l.id JOIN courses c ON l.course_id = c.id WHERE ev.id = :eid AND c.created_by = :uid');
            $stmt->execute(['eid' => $evalId, 'uid' => $_SESSION['user_id']]);
            if (!$stmt->fetch()) { $_SESSION['action_error'] = "Accès refusé."; header('Location: /dashboards/enseignant.php'); exit; }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO questions (question_text, points, evaluation_id) VALUES (:question_text, :points, :evaluation_id)');
            $stmt->execute(['question_text' => $questionText, 'points' => max(1, $points), 'evaluation_id' => $evalId]);
            $questionId = $pdo->lastInsertId();

            $stmtOpt = $pdo->prepare('INSERT INTO options (option_text, is_correct, question_id) VALUES (:text, :correct, :qid)');
            foreach ($options as $idx => $optText) {
                $optText = trim($optText);
                if (empty($optText)) continue;
                $stmtOpt->execute(['text' => $optText, 'correct' => ($idx == $correctIdx) ? 1 : 0, 'qid' => $questionId]);
            }
            $pdo->commit();
            $_SESSION['action_success'] = "Question ajoutée avec succès.";
            break;

        case 'delete_question':
            $qid = intval($_POST['question_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT 1 FROM questions q JOIN evaluations ev ON q.evaluation_id = ev.id JOIN lessons l ON ev.lesson_id = l.id JOIN courses c ON l.course_id = c.id WHERE q.id = :qid AND c.created_by = :uid');
            $stmt->execute(['qid' => $qid, 'uid' => $_SESSION['user_id']]);
            if (!$stmt->fetch()) { $_SESSION['action_error'] = "Accès refusé."; header('Location: /dashboards/enseignant.php'); exit; }
            $pdo->prepare('DELETE FROM questions WHERE id = :id')->execute(['id' => $qid]);
            $_SESSION['action_success'] = "Question supprimée.";
            break;

        default:
            $_SESSION['action_error'] = "Action non reconnue.";
    }
} catch (RuntimeException $e) {
    $_SESSION['action_error'] = $e->getMessage();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['action_error'] = "Erreur base de données : " . $e->getMessage();
}

header('Location: /dashboards/enseignant.php');
exit;
