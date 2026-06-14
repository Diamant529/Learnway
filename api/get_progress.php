<?php
/**
 * Learn Way - API : Récupérer la progression d'un étudiant (JSON)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$moduleId  = intval($_GET['module_id'] ?? 0);
$studentId = intval($_GET['student_id'] ?? $_SESSION['user_id']); // Étudiant peut uniquement voir le sien

// Un étudiant ne peut consulter que sa propre progression
if ($_SESSION['user_role'] === 'etudiant' && $studentId !== intval($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès refusé.']);
    exit;
}

if ($moduleId <= 0) {
    echo json_encode(['success' => false, 'error' => 'module_id manquant.']);
    exit;
}

try {
    // Récupérer toutes les leçons du module avec le statut de leur évaluation
    $stmt = $pdo->prepare('
        SELECT l.id as lesson_id, l.title as lesson_title,
               e.id as evaluation_id
        FROM lessons l
        JOIN courses c ON l.course_id = c.id
        LEFT JOIN evaluations e ON l.id = e.lesson_id
        WHERE c.module_id = :module_id
        ORDER BY c.id, l.position
    ');
    $stmt->execute(['module_id' => $moduleId]);
    $lessons = $stmt->fetchAll();

    if (empty($lessons)) {
        echo json_encode(['success' => true, 'progress' => 0, 'lessons' => []]);
        exit;
    }

    $totalProgress = 0;
    $lessonData = [];

    foreach ($lessons as $lesson) {
        if (empty($lesson['evaluation_id'])) {
            // Pas d'évaluation → 100 % par défaut
            $lessonPct = 100.0;
        } else {
            $stmtA = $pdo->prepare('
                SELECT MAX(percentage) as best
                FROM evaluation_attempts
                WHERE student_id = :sid AND evaluation_id = :eid
            ');
            $stmtA->execute(['sid' => $studentId, 'eid' => $lesson['evaluation_id']]);
            $best = $stmtA->fetchColumn();
            $lessonPct = ($best !== null && $best !== false) ? round(floatval($best), 1) : 0.0;
        }

        $totalProgress += $lessonPct;
        $lessonData[] = [
            'lesson_id'     => $lesson['lesson_id'],
            'lesson_title'  => $lesson['lesson_title'],
            'evaluation_id' => $lesson['evaluation_id'],
            'progress'      => $lessonPct,
        ];
    }

    $globalProgress = round($totalProgress / count($lessons), 1);

    // Vérifier si un certificat existe déjà
    $stmtCert = $pdo->prepare('SELECT certificate_number FROM certifications WHERE student_id = :sid AND module_id = :mid');
    $stmtCert->execute(['sid' => $studentId, 'mid' => $moduleId]);
    $cert = $stmtCert->fetch();

    echo json_encode([
        'success'          => true,
        'progress'         => $globalProgress,
        'lessons'          => $lessonData,
        'has_certificate'  => (bool) $cert,
        'certificate_number' => $cert ? $cert['certificate_number'] : null,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur : ' . $e->getMessage()]);
}
