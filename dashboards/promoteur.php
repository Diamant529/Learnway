<?php
/**
 * Learn Way - Tableau de bord du Promoteur
 */
$pageTitle = "Tableau de bord Promoteur";
require_once __DIR__ . '/../includes/header.php';
requireRole('promoteur');

// Messages flash
$successMsg = $_SESSION['action_success'] ?? null;
$errorMsg = $_SESSION['action_error'] ?? null;
unset($_SESSION['action_success'], $_SESSION['action_error']);

// 1. Récupération des enseignants pour le formulaire d'attribution
$stmtTeachers = $pdo->query('SELECT id, first_name, last_name, email FROM users WHERE role = "enseignant" ORDER BY last_name, first_name');
$teachers = $stmtTeachers->fetchAll();

// 2. Récupération de tous les modules
$stmtModules = $pdo->query('
    SELECT m.*, u.first_name as creator_first, u.last_name as creator_last 
    FROM modules m 
    LEFT JOIN users u ON m.created_by = u.id 
    ORDER BY m.created_at DESC
');
$modules = $stmtModules->fetchAll();

// 3. Récupération des attributions d'enseignants
$stmtAssignments = $pdo->query('
    SELECT mt.*, m.title as module_title, u.first_name, u.last_name, u.email
    FROM module_teachers mt
    JOIN modules m ON mt.module_id = m.id
    JOIN users u ON mt.teacher_id = u.id
    ORDER BY m.title, u.last_name
');
$assignments = $stmtAssignments->fetchAll();

// Helper pour calculer la progression d'un étudiant sur un module
function getStudentProgress($pdo, $studentId, $moduleId) {
    // Récupérer toutes les leçons de tous les cours du module
    $stmt = $pdo->prepare('
        SELECT l.id as lesson_id, e.id as evaluation_id
        FROM lessons l
        JOIN courses c ON l.course_id = c.id
        LEFT JOIN evaluations e ON l.id = e.lesson_id
        WHERE c.module_id = :module_id
    ');
    $stmt->execute(['module_id' => $moduleId]);
    $lessons = $stmt->fetchAll();
    
    if (count($lessons) === 0) {
        return 0; // Pas de leçon = pas de progression
    }
    
    $totalProgress = 0;
    foreach ($lessons as $lesson) {
        if (empty($lesson['evaluation_id'])) {
            $totalProgress += 100; // Pas d'évaluation = validé par défaut
        } else {
            $stmtAttempt = $pdo->prepare('
                SELECT MAX(percentage) as max_pct
                FROM evaluation_attempts
                WHERE student_id = :student_id AND evaluation_id = :evaluation_id
            ');
            $stmtAttempt->execute([
                'student_id' => $studentId,
                'evaluation_id' => $lesson['evaluation_id']
            ]);
            $maxPct = $stmtAttempt->fetchColumn();
            $totalProgress += ($maxPct !== null ? floatval($maxPct) : 0);
        }
    }
    
    return round($totalProgress / count($lessons), 1);
}

// 4. Récupération des progressions des étudiants
$stmtEnrollments = $pdo->query('
    SELECT e.student_id, e.module_id, e.enrolled_at,
           u.first_name, u.last_name, u.email,
           m.title as module_title,
           c.id as cert_id, c.certificate_number
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    JOIN modules m ON e.module_id = m.id
    LEFT JOIN certifications c ON e.student_id = c.student_id AND e.module_id = c.module_id
    ORDER BY e.enrolled_at DESC
');
$enrollments = $stmtEnrollments->fetchAll();
?>

<!-- Alertes Flash -->
<?php if ($successMsg): ?>
    <div class="alert alert-success alert-auto-dismiss glass-panel">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span><?= htmlspecialchars($successMsg) ?></span>
    </div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="alert alert-danger alert-auto-dismiss glass-panel">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span><?= htmlspecialchars($errorMsg) ?></span>
    </div>
<?php endif; ?>

<!-- Grille des statistiques (KPI) -->
<div class="stats-grid" id="statsGrid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </div>
        <div class="stat-details">
            <span class="stat-number" id="kpi-students">-</span>
            <span class="stat-label">Étudiants</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
        </div>
        <div class="stat-details">
            <span class="stat-number" id="kpi-teachers">-</span>
            <span class="stat-label">Enseignants</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
        </div>
        <div class="stat-details">
            <span class="stat-number" id="kpi-modules">-</span>
            <span class="stat-label">Modules</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
        <div class="stat-details">
            <span class="stat-number" id="kpi-certificates">-</span>
            <span class="stat-label">Certificats Émis</span>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <!-- Gestion des modules -->
    <div class="card glass-panel" style="grid-column: span 2;">
        <div class="card-header">
            <h2 class="card-title">Modules de formation</h2>
            <button class="btn btn-primary" onclick="openCreateModuleModal()">+ Créer un Module</button>
        </div>
        
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Description</th>
                        <th>Créé par</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($modules)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Aucun module créé pour le moment.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($modules as $module): ?>
                            <tr>
                                <td style="font-weight: 600; color: #fff;"><?= htmlspecialchars($module['title']) ?></td>
                                <td><?= htmlspecialchars(substr($module['description'] ?? '', 0, 80)) . (strlen($module['description'] ?? '') > 80 ? '...' : '') ?></td>
                                <td><?= htmlspecialchars($module['creator_first'] . ' ' . $module['creator_last']) ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="openEditModuleModal(<?= $module['id'] ?>, '<?= htmlspecialchars(addslashes($module['title'])) ?>', '<?= htmlspecialchars(addslashes($module['description'] ?? '')) ?>')">Modifier</button>
                                        <form action="/actions/manage_modules.php" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce module ? Tous les cours associés seront supprimés.');">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <!-- Attribution des enseignants -->
    <div class="card glass-panel">
        <div class="card-header">
            <h2 class="card-title">Attribuer un enseignant</h2>
        </div>
        
        <form action="/actions/manage_modules.php" method="POST" style="margin-bottom: 2rem;">
            <?php csrfInput(); ?>
            <input type="hidden" name="action" value="assign_teacher">
            
            <div class="form-group">
                <label for="assign_module_id" class="form-label">Module</label>
                <select name="module_id" id="assign_module_id" class="form-control glass-input" required>
                    <option value="">Sélectionnez un module...</option>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?= $module['id'] ?>"><?= htmlspecialchars($module['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="assign_teacher_id" class="form-label">Enseignant</label>
                <select name="teacher_id" id="assign_teacher_id" class="form-control glass-input" required>
                    <option value="">Sélectionnez un enseignant...</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['last_name'] . ' ' . $teacher['first_name'] . ' (' . $teacher['email'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-filled" style="width: 100%;">Attribuer</button>
        </form>
        
        <h3 style="margin-bottom: 1rem; font-size: 1rem;">Attributions actuelles</h3>
        <div class="table-responsive">
            <table class="table-custom" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Enseignant</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignments)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center;">Aucune attribution trouvée.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assignments as $assign): ?>
                            <tr>
                                <td style="color:#fff;"><?= htmlspecialchars($assign['module_title']) ?></td>
                                <td><?= htmlspecialchars($assign['first_name'] . ' ' . $assign['last_name']) ?></td>
                                <td>
                                    <form action="/actions/manage_modules.php" method="POST">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="action" value="unassign_teacher">
                                        <input type="hidden" name="module_id" value="<?= $assign['module_id'] ?>">
                                        <input type="hidden" name="teacher_id" value="<?= $assign['teacher_id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Retirer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Suivi de progression des étudiants -->
    <div class="card glass-panel">
        <div class="card-header">
            <h2 class="card-title">Suivi des étudiants</h2>
        </div>
        
        <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
            <table class="table-custom" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Module</th>
                        <th>Progression</th>
                        <th>Certificat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enrollments)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Aucun étudiant inscrit à un module.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enrollments as $enroll): 
                            $progress = getStudentProgress($pdo, $enroll['student_id'], $enroll['module_id']);
                        ?>
                            <tr>
                                <td style="color: #fff; font-weight: 500;">
                                    <?= htmlspecialchars($enroll['first_name'] . ' ' . $enroll['last_name']) ?>
                                </td>
                                <td><?= htmlspecialchars($enroll['module_title']) ?></td>
                                <td>
                                    <div class="progress-wrapper" style="margin-top: 0; min-width: 90px;">
                                        <div class="progress-info">
                                            <span style="font-size: 0.75rem;"><?= $progress ?>%</span>
                                        </div>
                                        <div class="progress-bar-container" style="height: 6px;">
                                            <div class="progress-bar <?= $progress >= 100 ? 'success' : '' ?>" style="width: <?= $progress ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($enroll['certificate_number']): ?>
                                        <span class="badge badge-success" style="font-size: 0.65rem;">Émis</span>
                                    <?php elseif ($progress >= 100): ?>
                                        <span class="badge badge-info" style="font-size: 0.65rem;">Prêt</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning" style="font-size: 0.65rem; background: rgba(245, 158, 11, 0.08);">En cours</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- =====================================================================
   MODALS DE DIALOGUE (NATIVE)
   ===================================================================== -->
<!-- Modal: Créer un Module -->
<dialog id="createModuleDialog">
    <div class="dialog-content">
        <div class="dialog-header">
            <h2 class="text-gradient">Créer un module</h2>
            <button class="dialog-close" onclick="document.getElementById('createModuleDialog').close()">&times;</button>
        </div>
        <form action="/actions/manage_modules.php" method="POST">
            <?php csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="new_module_title" class="form-label">Titre du module</label>
                <input type="text" name="title" id="new_module_title" class="form-control glass-input" required placeholder="Ex: Introduction au PHP">
            </div>
            
            <div class="form-group">
                <label for="new_module_desc" class="form-label">Description</label>
                <textarea name="description" id="new_module_desc" class="form-control glass-input" rows="4" placeholder="Décrivez le contenu du module..."></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('createModuleDialog').close()">Annuler</button>
                <button type="submit" class="btn btn-filled">Créer</button>
            </div>
        </form>
    </div>
</dialog>

<!-- Modal: Modifier un Module -->
<dialog id="editModuleDialog">
    <div class="dialog-content">
        <div class="dialog-header">
            <h2 class="text-gradient">Modifier le module</h2>
            <button class="dialog-close" onclick="document.getElementById('editModuleDialog').close()">&times;</button>
        </div>
        <form action="/actions/manage_modules.php" method="POST">
            <?php csrfInput(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="module_id" id="edit_module_id">
            
            <div class="form-group">
                <label for="edit_module_title" class="form-label">Titre du module</label>
                <input type="text" name="title" id="edit_module_title" class="form-control glass-input" required>
            </div>
            
            <div class="form-group">
                <label for="edit_module_desc" class="form-label">Description</label>
                <textarea name="description" id="edit_module_desc" class="form-control glass-input" rows="4"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editModuleDialog').close()">Annuler</button>
                <button type="submit" class="btn btn-filled">Enregistrer</button>
            </div>
        </form>
    </div>
</dialog>

<!-- Script pour l'interactivité spécifique au Promoteur -->
<script>
function openCreateModuleModal() {
    document.getElementById('createModuleDialog').showModal();
}

function openEditModuleModal(id, title, description) {
    document.getElementById('edit_module_id').value = id;
    document.getElementById('edit_module_title').value = title;
    document.getElementById('edit_module_desc').value = description;
    document.getElementById('editModuleDialog').showModal();
}

// Chargement dynamique des KPI par AJAX au chargement de la page
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const result = await AJAX.get('/api/get_statistics.php');
        if (result.success) {
            document.getElementById('kpi-students').textContent = result.stats.total_students;
            document.getElementById('kpi-teachers').textContent = result.stats.total_teachers;
            document.getElementById('kpi-modules').textContent = result.stats.total_modules;
            document.getElementById('kpi-certificates').textContent = result.stats.total_certificates;
        }
    } catch (e) {
        console.error("Impossible de charger les statistiques :", e);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
