<?php
/**
 * Learn Way - API : Soumettre une évaluation QCM et calculer le score (JSON/AJAX)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

// Réservé aux étudiants
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'etudiant') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès refusé.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

verifyCSRFToken();

// Lire le corps JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST; // fallback form data
}

$evaluationId = intval($input['evaluation_id'] ?? 0);
$answers      = $input['answers'] ?? []; // Tableau [ question_id => option_id ]
$moduleId     = intval($input['module_id'] ?? 0);
$studentId    = intval($_SESSION['user_id']);

if ($evaluationId <= 0 || $moduleId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants.']);
    exit;
}

// Vérifier que l'étudiant est inscrit au module
$stmtEnroll = $pdo->prepare('SELECT 1 FROM enrollments WHERE student_id = :sid AND module_id = :mid');
$stmtEnroll->execute(['sid' => $studentId, 'mid' => $moduleId]);
if (!$stmtEnroll->fetch()) {
    echo json_encode(['success' => false, 'error' => "Vous n'êtes pas inscrit à ce module."]);
    exit;
}

try {
    // Récupérer toutes les questions + bonnes réponses
    $stmtQ = $pdo->prepare('
        SELECT q.id as question_id, q.points,
               o.id as option_id, o.is_correct
        FROM questions q
        JOIN options o ON o.question_id = q.id
        WHERE q.evaluation_id = :eid
    ');
    $stmtQ->execute(['eid' => $evaluationId]);
    $rows = $stmtQ->fetchAll();

    if (empty($rows)) {
        echo json_encode(['success' => false, 'error' => "Cette évaluation ne contient aucune question."]);
        exit;
    }

    // Grouper par question
    $questionsData = [];
    foreach ($rows as $row) {
        $qid = $row['question_id'];
        if (!isset($questionsData[$qid])) {
            $questionsData[$qid] = ['points' => $row['points'], 'correct_options' => []];
        }
        if ($row['is_correct']) {
            $questionsData[$qid]['correct_options'][] = intval($row['option_id']);
        }
    }

    // Calculer le score
    $scoreObtained = 0;
    $maxScore = 0;
    $feedback = [];

    foreach ($questionsData as $qid => $qdata) {
        $maxScore += $qdata['points'];
        $givenOptionId = intval($answers[$qid] ?? 0);
        $isCorrect = in_array($givenOptionId, $qdata['correct_options']);
        if ($isCorrect) {
            $scoreObtained += $qdata['points'];
        }
        $feedback[$qid] = [
            'correct' => $isCorrect,
            'correct_options' => $qdata['correct_options'],
        ];
    }

    $percentage = ($maxScore > 0) ? round(($scoreObtained / $maxScore) * 100, 2) : 0;

    // Enregistrer la tentative (toujours, y compris si déjà tenté — garder l'historique)
    $stmtInsert = $pdo->prepare('
        INSERT INTO evaluation_attempts (student_id, evaluation_id, score_obtained, max_score, percentage)
        VALUES (:sid, :eid, :score, :max, :pct)
    ');
    $stmtInsert->execute([
        'sid'   => $studentId,
        'eid'   => $evaluationId,
        'score' => $scoreObtained,
        'max'   => $maxScore,
        'pct'   => $percentage,
    ]);

    // Recalculer la progression globale du module
    $stmtLessons = $pdo->prepare('
        SELECT l.id as lesson_id, e.id as evaluation_id
        FROM lessons l JOIN courses c ON l.course_id = c.id
        LEFT JOIN evaluations e ON l.id = e.lesson_id
        WHERE c.module_id = :mid
    ');
    $stmtLessons->execute(['mid' => $moduleId]);
    $allLessons = $stmtLessons->fetchAll();

    $totalPct = 0;
    foreach ($allLessons as $al) {
        if (empty($al['evaluation_id'])) {
            $totalPct += 100;
        } else {
            $stmtBest = $pdo->prepare('SELECT MAX(percentage) FROM evaluation_attempts WHERE student_id = :sid AND evaluation_id = :eid');
            $stmtBest->execute(['sid' => $studentId, 'eid' => $al['evaluation_id']]);
            $best = $stmtBest->fetchColumn();
            $totalPct += ($best !== null) ? floatval($best) : 0;
        }
    }
    $globalProgress = round($totalPct / count($allLessons), 1);

    // Générer un certificat si 100 % et non encore émis
    $certificateGenerated = false;
    $certificateNumber    = null;

    if ($globalProgress >= 100) {
        $stmtExist = $pdo->prepare('SELECT certificate_number FROM certifications WHERE student_id = :sid AND module_id = :mid');
        $stmtExist->execute(['sid' => $studentId, 'mid' => $moduleId]);
        $existingCert = $stmtExist->fetch();

        if (!$existingCert) {
            $certNum = sprintf(
                'LW-%s-%d-%d-%s',
                date('Ymd'),
                $studentId,
                $moduleId,
                strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))
            );
            $sigHash = hash('sha256', $studentId . '-' . $moduleId . '-' . $certNum);
            $stmtCert = $pdo->prepare('INSERT INTO certifications (certificate_number, student_id, module_id, signature_hash) VALUES (:num, :sid, :mid, :hash)');
            $stmtCert->execute(['num' => $certNum, 'sid' => $studentId, 'mid' => $moduleId, 'hash' => $sigHash]);
            $certificateGenerated = true;
            $certificateNumber    = $certNum;
        } else {
            $certificateNumber = $existingCert['certificate_number'];
        }
    }

    echo json_encode([
        'success'               => true,
        'score_obtained'        => $scoreObtained,
        'max_score'             => $maxScore,
        'percentage'            => $percentage,
        'feedback'              => $feedback,
        'module_progress'       => $globalProgress,
        'certificate_generated' => $certificateGenerated,
        'certificate_number'    => $certificateNumber,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur : ' . $e->getMessage()]);
}
