<?php
require 'config.php';

// Already logged in → redirect
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$erreur  = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom   = trim($_POST['nom']           ?? '');
    $email = trim($_POST['email']         ?? '');
    $mdp   = trim($_POST['mot_de_passe']  ?? '');
    $mdp2  = trim($_POST['mot_de_passe2'] ?? '');

    if (empty($nom) || empty($email) || empty($mdp)) {
        $erreur = __('fill_all_fields');
    } elseif (mb_strlen($nom) > 100) {
        $erreur = "Le nom est trop long (max 100 caractères).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = __('invalid_email');
    } elseif ($mdp !== $mdp2) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($mdp) < 4) {
        $erreur = __('password_min_4');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM authentification WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $erreur = __('email_exists');
            } else {
                $mdp_hash = password_hash($mdp, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO authentification (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'user')")
                    ->execute([$nom, $email, $mdp_hash]);
                $success = __('account_created');
            }
        } catch (Exception $e) {
            $erreur = "Une erreur s'est produite lors de l'inscription.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('register') ?> — GestionSalles</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-footer { margin-top: 24px; text-align: center; color: var(--muted); font-size: 0.9rem; }
        .auth-footer a { color: var(--accent); font-weight: 600; }
    </style>
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
            <span class="icon">🏢</span>
            <h1><?= __('create_account') ?></h1>
            <p>Rejoignez GestionSalles</p>
        </div>

        <?php if ($erreur): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($success) ?>
                <br><a href="login.php" style="color:inherit; font-weight:700; margin-top:6px; display:inline-block;">→ <?= __('login') ?></a>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" novalidate>

            <div class="form-group">
                <label class="form-label" for="nom">👤 <?= __('name') ?></label>
                <input type="text" id="nom" name="nom" class="form-control"
                       placeholder="<?= __('name') ?>"
                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                       autocomplete="name" required>
            </div>

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
                           placeholder="Minimum 4 caractères"
                           autocomplete="new-password" required>
                    <button type="button" class="password-toggle"
                            onclick="togglePassword('mot_de_passe', this)" aria-label="Afficher/masquer">👁️</button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="mot_de_passe2">🔒 Confirmer le mot de passe</label>
                <div class="password-input-group">
                    <input type="password" id="mot_de_passe2" name="mot_de_passe2" class="form-control"
                           placeholder="Répétez le mot de passe"
                           autocomplete="new-password" required>
                    <button type="button" class="password-toggle"
                            onclick="togglePassword('mot_de_passe2', this)" aria-label="Afficher/masquer">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
                ✨ <?= __('create_account') ?>
            </button>

        </form>
        <?php endif; ?>

        <div class="auth-footer">
            <a href="login.php"><?= __('already_account') ?></a>
        </div>

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