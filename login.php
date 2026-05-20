<?php
require 'config.php';

// Si déjà connecté → rediriger selon le rôle
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . (($_SESSION['user_role'] ?? '') === 'admin' ? 'admin_dashboard.php' : 'index.php'));
    exit;
}

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = trim($_POST['mot_de_passe'] ?? '');

    if (empty($email) || empty($mdp)) {
        $erreur = __('fill_all_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = __('invalid_email');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM authentification WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Use a constant-time comparison to avoid timing attacks
            $valid = false;
            if ($user) {
                if (password_verify($mdp, $user['mot_de_passe'])) {
                    $valid = true;
                } elseif ($user['mot_de_passe'] === $mdp) {
                    // Plain-text password from initial import — rehash immediately
                    $valid = true;
                    $pdo->prepare("UPDATE authentification SET mot_de_passe = ? WHERE id = ?")
                        ->execute([password_hash($mdp, PASSWORD_BCRYPT), $user['id']]);
                }
            } else {
                // Dummy check to prevent timing-based user enumeration
                password_verify($mdp, '$2y$10$dummyhashfortimingnormalization');
            }

            if ($valid && $user) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['user_nom'] = $user['nom'];

                // Determine role
                if ($user['role'] === 'admin' || $email === ADMIN_EMAIL) {
                    $_SESSION['user_role'] = 'admin';
                    // Ensure DB reflects admin role
                    $pdo->prepare("UPDATE authentification SET role = 'admin' WHERE id = ?")
                        ->execute([$user['id']]);
                } else {
                    $_SESSION['user_role'] = 'user';
                }

                header('Location: ' . ($_SESSION['user_role'] === 'admin' ? 'admin_dashboard.php' : 'index.php'));
                exit;
            } else {
                $erreur = __('incorrect_login');
            }
        } catch (Exception $e) {
            $erreur = "Une erreur s'est produite. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('login') ?> — GestionSalles</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div style="position: absolute; top: 20px; right: 20px; display: flex; gap: 10px; z-index: 100;">
    <a href="?lang=<?= $lang === 'fr' ? 'ar' : 'fr' ?>" class="lang-toggle" style="text-decoration: none; display: flex; align-items: center;">
        <?= $lang === 'fr' ? 'AR' : 'FR' ?>
    </a>
    <button id="theme-toggle" class="theme-toggle">🌙</button>
</div>

<div class="auth-wrapper">
    <div class="auth-card animate">

        <div class="auth-logo">
            <span class="icon">🔐</span>
            <h1><?= __('login') ?></h1>
            <p><?= __('access_space') ?></p>
        </div>

        <?php if ($erreur): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>

            <div class="form-group">
                <label class="form-label" for="email">📧 <?= __('email') ?></label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="votre@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       autocomplete="email" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="mot_de_passe">🔒 <?= __('password') ?></label>
                <div class="password-input-group">
                    <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control"
                           placeholder="<?= __('password') ?>"
                           autocomplete="current-password" required>
                    <button type="button" class="password-toggle"
                            onclick="togglePassword('mot_de_passe', this)" aria-label="Afficher/masquer le mot de passe">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
                🚀 <?= __('login') ?>
            </button>

        </form>

        <div class="divider">ou</div>

        <a href="register.php" class="btn btn-outline btn-full">
            ✨ <?= __('create_account') ?>
        </a>

    </div>
</div>

<script src="script.js"></script>
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? '🙈' : '👁️';
}
</script>

</body>
</html>