<?php
/**
 * Learn Way - Page de Connexion / Accueil
 */
require_once __DIR__ . '/includes/auth.php';

// Redirection si déjà connecté
if (!empty($_SESSION['user_id'])) {
    redirectToDashboard($_SESSION['user_role']);
}

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$success = $_SESSION['register_success'] ?? null;
unset($_SESSION['register_success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bienvenue sur Learn Way - Plateforme d'apprentissage en ligne moderne. Connectez-vous à votre espace personnel.">
    <title>Learn Way - Connexion</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/glassmorphism.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at 10% 20%, rgba(10, 25, 47, 1) 0%, rgba(17, 34, 64, 1) 90%);
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-wrapper {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 10;
        }
        
        .login-card {
            padding: 3rem 2.5rem;
            text-align: center;
        }
        
        .login-logo {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            font-family: var(--font-display);
        }
        
        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 2.5rem;
        }
        
        .form-actions {
            margin-top: 2rem;
        }
        
        .register-link {
            display: block;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .register-link a {
            color: var(--color-accent);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Points lumineux ambiants (Glow effects) -->
    <div class="glow-dot" style="top: -50px; left: -50px;"></div>
    <div class="glow-dot glow-purple" style="bottom: -50px; right: -50px;"></div>

    <div class="login-wrapper">
        <div class="card glass-panel login-card">
            <h1 class="login-logo text-gradient">Learn Way<span style="color: var(--color-accent)">.</span></h1>
            <p class="login-subtitle">Votre portail d'apprentissage intelligent</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <form action="/actions/auth_login.php" method="POST">
                <?php csrfInput(); ?>
                
                <div class="form-group" style="text-align: left;">
                    <label for="email" class="form-label">Adresse Email</label>
                    <input type="email" name="email" id="email" class="form-control glass-input" placeholder="nom@exemple.com" required autocomplete="username">
                </div>
                
                <div class="form-group" style="text-align: left;">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control glass-input" placeholder="••••••••" required autocomplete="current-password">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-filled" style="width: 100%;">Se connecter</button>
                </div>
            </form>
            
            <span class="register-link">
                Pas encore de compte ? <a href="/register.php">Créer un compte</a>
            </span>
        </div>
    </div>
</body>
</html>
