<?php
/**
 * Learn Way - Barre latérale dynamique
 */
$currentUrl = $_SERVER['PHP_SELF'];
$role = $_SESSION['user_role'] ?? '';
$fullName = ($_SESSION['user_first_name'] ?? '') . ' ' . ($_SESSION['user_last_name'] ?? '');
?>
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-logo">
        <span class="logo-text">Learn Way<span class="logo-dot">.</span></span>
    </div>
    
    <ul class="sidebar-menu">
        <?php if ($role === 'promoteur'): ?>
            <li class="sidebar-item <?= strpos($currentUrl, 'promoteur.php') !== false ? 'active' : '' ?>">
                <a href="/dashboards/promoteur.php">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z" />
                    </svg>
                    <span>Tableau de bord</span>
                </a>
            </li>
        <?php elseif ($role === 'enseignant'): ?>
            <li class="sidebar-item <?= strpos($currentUrl, 'enseignant.php') !== false ? 'active' : '' ?>">
                <a href="/dashboards/enseignant.php">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>Mes Cours</span>
                </a>
            </li>
        <?php elseif ($role === 'etudiant'): ?>
            <li class="sidebar-item <?= strpos($currentUrl, 'etudiant.php') !== false ? 'active' : '' ?>">
                <a href="/dashboards/etudiant.php">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>Espace Apprenant</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
    
    <div class="sidebar-footer">
        <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['user_first_name'] ?? 'U', 0, 1) . substr($_SESSION['user_last_name'] ?? 'N', 0, 1)) ?>
        </div>
        <div class="user-info">
            <span class="user-name" title="<?= htmlspecialchars($fullName) ?>"><?= htmlspecialchars($fullName) ?></span>
            <span class="user-role"><?= htmlspecialchars($role === 'promoteur' ? 'Promoteur' : ($role === 'enseignant' ? 'Enseignant' : 'Étudiant')) ?></span>
        </div>
    </div>
</aside>
