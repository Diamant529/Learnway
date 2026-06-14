<?php
/**
 * Learn Way - API de statistiques (Promoteur uniquement)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

// Vérifier les droits du promoteur
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'promoteur') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès interdit.']);
    exit;
}

try {
    // 1. Nombre total d'étudiants
    $stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "etudiant"');
    $totalStudents = intval($stmt->fetchColumn());

    // 2. Nombre total d'enseignants
    $stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "enseignant"');
    $totalTeachers = intval($stmt->fetchColumn());

    // 3. Nombre total de modules
    $stmt = $pdo->query('SELECT COUNT(*) FROM modules');
    $totalModules = intval($stmt->fetchColumn());

    // 4. Nombre total de certificats émis
    $stmt = $pdo->query('SELECT COUNT(*) FROM certifications');
    $totalCertificates = intval($stmt->fetchColumn());

    // 5. Répartition des étudiants par module (Données pour graphiques ou listes)
    $stmt = $pdo->query('
        SELECT m.title as label, COUNT(e.student_id) as value 
        FROM modules m 
        LEFT JOIN enrollments e ON m.id = e.module_id 
        GROUP BY m.id
    ');
    $studentsPerModule = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'total_modules' => $totalModules,
            'total_certificates' => $totalCertificates,
            'distribution' => $studentsPerModule
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données : ' . $e->getMessage()]);
}
