<?php
require 'config.php';
requireUser();

$success = "";
$erreur  = "";

$salles = $pdo->query("SELECT * FROM gestion ORDER BY nom ASC")->fetchAll();
$salle_preselect = isset($_GET['salle']) ? (int)$_GET['salle'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_salle    = (int)($_POST['id_salle']    ?? 0);
    $date        = trim($_POST['date']         ?? '');
    $heure_debut = trim($_POST['heure_debut']  ?? '');
    $heure_fin   = trim($_POST['heure_fin']    ?? '');
    $id_user     = (int)$_SESSION['user_id'];

    if (!$id_salle || !$date || !$heure_debut || !$heure_fin) {
        $erreur = __('fill_all_fields');
    } elseif ($heure_fin <= $heure_debut) {
        $erreur = __('invalid_time');
    } elseif ($date < date('Y-m-d')) {
        $erreur = __('past_date');
    } else {
        // Verify room exists
        $check = $pdo->prepare("SELECT id FROM gestion WHERE id = ?");
        $check->execute([$id_salle]);
        if (!$check->fetch()) {
            $erreur = __('room_not_found');
        } else {
            // Check for booking conflict
            $stmt = $pdo->prepare("
                SELECT id FROM reservation
                WHERE id_salle = ?
                  AND date = ?
                  AND heure_debut < ?
                  AND heure_fin   > ?
            ");
            $stmt->execute([$id_salle, $date, $heure_fin, $heure_debut]);

            if ($stmt->fetch()) {
                $erreur = "⛔ " . __('time_conflict');
            } else {
                $pdo->prepare("
                    INSERT INTO reservation (id_user, id_salle, date, heure_debut, heure_fin)
                    VALUES (?, ?, ?, ?, ?)
                ")->execute([$id_user, $id_salle, $date, $heure_debut, $heure_fin]);
                $success = __('reservation_success');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('reserve') ?> — GestionSalles</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .reservation-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 24px;
            align-items: start;
        }
        .info-stack { display: flex; flex-direction: column; gap: 16px; }
        .rules-list { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .rules-list li { display: flex; gap: 10px; font-size: 0.87rem; color: var(--light); }
        @media (max-width: 850px) {
            .reservation-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
        <a href="index.php" class="navbar-brand">
            <div class="logo-icon">🏢</div>
            GestionSalles
        </a>
        <ul class="navbar-nav">
            <li><a href="index.php">🏠 <?= __('home') ?></a></li>
            <li><a href="salles.php">🚪 <?= __('rooms') ?></a></li>
            <li><a href="reservation.php" class="active">📅 <?= __('reserve') ?></a></li>
            <li><a href="mes_reservations.php">📋 <?= __('my_reservations') ?></a></li>
        </ul>
        <div class="navbar-user">
            <a href="?lang=<?= $lang === 'fr' ? 'ar' : 'fr' ?>" class="lang-toggle">
                <?= $lang === 'fr' ? 'AR' : 'FR' ?>
            </a>
            <button id="theme-toggle" class="theme-toggle">🌙</button>
            <div class="user-badge">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?></div>
                <?= htmlspecialchars($_SESSION['user_nom']) ?>
            </div>
            <a href="logout.php" class="btn btn-outline btn-sm"><?= __('logout') ?></a>
        </div>
    </div>
</nav>

<div class="wrapper">

    <div class="page-header animate">
        <h1>📅 <?= __('reserve_a_room') ?></h1>
        <p><?= $lang === 'ar' ? 'اختر قاعة وتاريخ ووقت' : 'Choisissez une salle, une date et un créneau horaire' ?></p>
    </div>

    <div class="reservation-layout">

        <!-- FORM -->
        <div class="card glass-card animate animate-delay-1">
            <div class="card-title">📋 <?= $lang === 'ar' ? 'معلومات الحجز' : 'Informations de réservation' ?></div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success) ?>
                    <a href="mes_reservations.php" style="color:inherit; font-weight:700; margin-inline-start:8px;">→ <?= $lang === 'ar' ? 'عرض حجوزاتي' : 'Voir mes réservations' ?></a>
                </div>
            <?php endif; ?>
            <?php if ($erreur): ?>
                <div class="alert alert-danger">⚠️ <?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>

                <div class="form-group">
                    <label class="form-label" for="salleSelect">🚪 <?= __('choose_room') ?></label>
                    <select id="salleSelect" name="id_salle" class="form-control" onchange="updateSalleInfo()" required>
                        <option value="">-- <?= $lang === 'ar' ? 'تحديد قاعة' : 'Sélectionner une salle' ?> --</option>
                        <?php foreach ($salles as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"
                                data-capacite="<?= (int)$s['capacite'] ?>"
                                data-desc="<?= htmlspecialchars($s['description'] ?? '') ?>"
                                <?= ($salle_preselect == $s['id'] || (isset($_POST['id_salle']) && (int)$_POST['id_salle'] === (int)$s['id'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nom']) ?> — <?= (int)$s['capacite'] ?> <?= __('people') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="date">📆 <?= $lang === 'ar' ? 'تاريخ الحجز' : 'Date de réservation' ?></label>
                    <input type="date" id="date" name="date" class="form-control"
                           min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" required>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="heure_debut">🕐 <?= __('start_time') ?></label>
                        <input type="time" id="heure_debut" name="heure_debut" class="form-control"
                               value="<?= htmlspecialchars($_POST['heure_debut'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="heure_fin">🕕 <?= __('end_time') ?></label>
                        <input type="time" id="heure_fin" name="heure_fin" class="form-control"
                               value="<?= htmlspecialchars($_POST['heure_fin'] ?? '') ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
                    📅 <?= $lang === 'ar' ? 'تأكيد الحجز' : 'Confirmer la réservation' ?>
                </button>

            </form>
        </div>

        <!-- SIDE INFO -->
        <div class="info-stack">

            <div class="card glass-card animate animate-delay-2" id="salleInfo"
                 style="<?= $salle_preselect ? '' : 'opacity:0.45;' ?> transition: opacity .3s;">
                <div class="card-title">🏛️ <?= $lang === 'ar' ? 'القاعة المحددة' : 'Salle sélectionnée' ?></div>
                <div id="salleInfoContent">
                    <?php
                    $s_info = null;
                    if ($salle_preselect) {
                        foreach ($salles as $s) {
                            if ((int)$s['id'] === $salle_preselect) { $s_info = $s; break; }
                        }
                    }
                    if ($s_info): ?>
                        <div class="salle-info" style="margin-bottom:10px;">
                            🏷️ <strong><?= htmlspecialchars($s_info['nom']) ?></strong>
                        </div>
                        <div class="salle-info" style="margin-bottom:10px;">
                            👥 <?= __('capacity') ?> : <strong><?= (int)$s_info['capacite'] ?> <?= __('people') ?></strong>
                        </div>
                        <p style="color:var(--muted); font-size:0.84rem;">
                            <?= htmlspecialchars($s_info['description'] ?? ($lang === 'ar' ? 'لا يوجد وصف.' : 'Aucune description.')) ?>
                        </p>
                    <?php else: ?>
                        <p style="color:var(--muted); font-size:0.87rem;">
                            <?= $lang === 'ar' ? 'حدد قاعة لعرض معلوماتها.' : 'Sélectionnez une salle pour voir ses informations.' ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card glass-card animate animate-delay-3"
                 style="background:rgba(59,130,246,0.05); border-color:rgba(59,130,246,0.18);">
                <div class="card-title" style="color:#60a5fa;">📌 <?= $lang === 'ar' ? 'قواعد الحجز' : 'Règles de réservation' ?></div>
                <ul class="rules-list">
                    <li><span>✅</span><span><?= $lang === 'ar' ? 'لا يمكن حجز قاعة مرتين في نفس الوقت' : 'Une salle ne peut pas être réservée deux fois au même horaire' ?></span></li>
                    <li><span>✅</span><span><?= $lang === 'ar' ? 'يجب أن يكون وقت النهاية بعد وقت البداية' : 'L\'heure de fin doit être après l\'heure de début' ?></span></li>
                    <li><span>✅</span><span><?= $lang === 'ar' ? 'لا يمكنك الحجز لتاريخ سابق' : 'Impossible de réserver une date passée' ?></span></li>
                    <li><span>✅</span><span><?= $lang === 'ar' ? 'يجب أن تكون مسجل الدخول للحجز' : 'Vous devez être connecté pour réserver' ?></span></li>
                </ul>
            </div>

        </div>
    </div>
</div>

<script src="script.js"></script>
<script>
function updateSalleInfo() {
    const sel     = document.getElementById('salleSelect');
    const opt     = sel.options[sel.selectedIndex];
    const box     = document.getElementById('salleInfo');
    const content = document.getElementById('salleInfoContent');
    const lang    = document.documentElement.lang;

    if (!sel.value) {
        box.style.opacity = '0.45';
        content.innerHTML = `<p style="color:var(--muted); font-size:.87rem;">${lang === 'ar' ? 'حدد قاعة لعرض معلوماتها.' : 'Sélectionnez une salle pour voir ses informations.'}</p>`;
        return;
    }

    const cap  = opt.dataset.capacite;
    const desc = opt.dataset.desc || (lang === 'ar' ? 'لا يوجد وصف.' : 'Aucune description.');
    const name = opt.text.split('—')[0].trim();

    box.style.opacity = '1';
    content.innerHTML = `
        <div class="salle-info" style="margin-bottom:10px;">🏷️ <strong>${name}</strong></div>
        <div class="salle-info" style="margin-bottom:10px;">👥 ${lang === 'ar' ? 'السعة' : 'Capacité'} : <strong>${cap} ${lang === 'ar' ? 'أشخاص' : 'personnes'}</strong></div>
        <p style="color:var(--muted); font-size:.84rem;">${desc}</p>
    `;
}

window.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('salleSelect').value) updateSalleInfo();
});
</script>

</body>
</html>