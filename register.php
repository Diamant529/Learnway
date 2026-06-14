<?php
/**
 * Learn Way - Page d'Inscription
 */
require_once __DIR__ . '/includes/auth.php';

// Redirection si déjà connecté
if (!empty($_SESSION['user_id'])) {
    redirectToDashboard($_SESSION['user_role']);
}

$error = $_SESSION['register_error'] ?? null;
unset($_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Créez votre compte sur Learn Way pour accéder aux cours en ligne ou publier vos modules de formation.">
    <title>Learn Way - Inscription</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/glassmorphism.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at 10% 20%, rgba(10, 25, 47, 1) 0%, rgba(17, 34, 64, 1) 90%);
            padding: 2rem 1rem;
            position: relative;
            overflow-y: auto;
        }
        
        .register-wrapper {
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 10;
        }
        
        .register-card {
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .register-logo {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            font-family: var(--font-display);
        }
        
        .register-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }
        
        .name-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-actions {
            margin-top: 1.5rem;
        }
        
        .login-link {
            display: block;
            margin-top: 1.25rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .login-link a {
            color: var(--color-accent);
            font-weight: 600;
        }
        
        @media (max-width: 576px) {
            .name-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Points lumineux ambiants (Glow effects) -->
    <div class="glow-dot" style="top: -50px; right: -50px;"></div>
    <div class="glow-dot glow-purple" style="bottom: -50px; left: -50px;"></div>

    <div class="register-wrapper">
        <div class="card glass-panel register-card">
            <h1 class="register-logo text-gradient">Créer un compte<span style="color: var(--color-accent)">.</span></h1>
            <p class="register-subtitle">Rejoignez la communauté Learn Way</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" style="text-align: left;">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="/actions/auth_register.php" method="POST">
                <?php csrfInput(); ?>
                
                <div class="name-row">
                    <div class="form-group" style="text-align: left;">
                        <label for="first_name" class="form-label">Prénom</label>
                        <input type="text" name="first_name" id="first_name" class="form-control glass-input" placeholder="Ex: Pierre" required>
                    </div>
                    
                    <div class="form-group" style="text-align: left;">
                        <label for="last_name" class="form-label">Nom</label>
                        <input type="text" name="last_name" id="last_name" class="form-control glass-input" placeholder="Ex: Dupont" required>
                    </div>
                </div>
                
                <div class="form-group" style="text-align: left;">
                    <label for="email" class="form-label">Adresse Email</label>
                    <input type="email" name="email" id="email" class="form-control glass-input" placeholder="nom@exemple.com" required autocomplete="username">
                </div>
                
                <div class="form-group" style="text-align: left;">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control glass-input" placeholder="Minimum 6 caractères" required minlength="6" autocomplete="new-password">
                </div>
                
                <div class="form-group" style="text-align: left;">
                    <label for="role" class="form-label">Qui êtes-vous ?</label>
                    <select name="role" id="role" class="form-control glass-input" required>
                        <option value="etudiant">Étudiant (Suivre des cours & évaluations)</option>
                        <option value="enseignant">Enseignant (Créer des cours & évaluations)</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-filled" style="width: 100%;">S'inscrire</button>
                </div>
            </form>
            
            <span class="login-link">
                Déjà un compte ? <a href="/index.php">Se connecter</a>
            </span>
        </div>
    </div>
</body>
</html>
