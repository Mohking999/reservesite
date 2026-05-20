<?php
require 'config.php';
requireUser();

// Stats
$total_salles       = $pdo->query("SELECT COUNT(*) FROM gestion")->fetchColumn();
$total_reservations = $pdo->query("SELECT COUNT(*) FROM reservation")->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservation WHERE id_user = ?");
$stmt->execute([$_SESSION['user_id']]);
$mes_res_count = $stmt->fetchColumn();

// Last 5 reservations of the user
$stmt = $pdo->prepare("
    SELECT r.*, g.nom AS salle_nom, g.capacite
    FROM reservation r
    JOIN gestion g ON r.id_salle = g.id
    WHERE r.id_user = ?
    ORDER BY r.date DESC, r.heure_debut DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$prochaines = $stmt->fetchAll();

// Featured rooms (first 3)
$salles = $pdo->query("SELECT * FROM gestion LIMIT 3")->fetchAll();

$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('home') ?> — GestionSalles</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="index.php" class="navbar-brand">
            <div class="logo-icon">🏢</div>
            GestionSalles
        </a>
        <ul class="navbar-nav">
            <li><a href="index.php" class="active">🏠 <?= __('home') ?></a></li>
            <li><a href="salles.php">🚪 <?= __('rooms') ?></a></li>
            <li><a href="reservation.php">📅 <?= __('reserve') ?></a></li>
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

    <!-- HERO -->
    <div class="animate" style="margin-bottom:32px;">
        <p style="color:var(--muted); font-size:0.85rem; margin-bottom:4px;">👋 <?= __('hello') ?>,</p>
        <h1 style="font-size:2rem; margin-bottom:6px;">
            <?= htmlspecialchars($_SESSION['user_nom']) ?> !
        </h1>
        <p style="color:var(--light); font-size:0.92rem;"><?= __('manage_reservations') ?></p>
    </div>

    <!-- STATS -->
    <div class="grid-3 animate animate-delay-1" style="margin-bottom:28px;">
        <div class="stat-card">
            <div class="stat-icon blue">🚪</div>
            <div>
                <div class="stat-value"><?= $total_salles ?></div>
                <div class="stat-label"><?= __('available_rooms') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📅</div>
            <div>
                <div class="stat-value"><?= $mes_res_count ?></div>
                <div class="stat-label"><?= __('my_reservations') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan">📊</div>
            <div>
                <div class="stat-value"><?= $total_reservations ?></div>
                <div class="stat-label"><?= __('total_reservations') ?></div>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="card animate animate-delay-2" style="margin-bottom:28px;">
        <div class="card-title">⚡ <?= __('quick_actions') ?></div>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <a href="reservation.php" class="btn btn-primary">📅 <?= __('new_reservation') ?></a>
            <a href="salles.php" class="btn btn-outline">🚪 <?= __('view_rooms') ?></a>
            <a href="mes_reservations.php" class="btn btn-outline">📋 <?= __('my_reservations') ?></a>
        </div>
    </div>

    <!-- RECENT RESERVATIONS -->
    <div class="animate animate-delay-3" style="margin-bottom:40px;">
        <div class="page-header">
            <h1 style="font-size:1.2rem;">📋 <?= __('latest_reservations') ?></h1>
        </div>

        <?php if (empty($prochaines)): ?>
            <div class="table-wrap">
                <div class="empty-state">
                    <span class="icon">📅</span>
                    <h3><?= __('no_reservations') ?></h3>
                    <p><?= __('no_reservations_desc') ?></p>
                    <br>
                    <a href="reservation.php" class="btn btn-primary"><?= __('reserve_a_room') ?></a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><?= __('room') ?></th>
                            <th><?= __('capacity') ?></th>
                            <th><?= __('date') ?></th>
                            <th><?= __('start_time') ?></th>
                            <th><?= __('end_time') ?></th>
                            <th><?= __('action') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prochaines as $r):
                            $future = $r['date'] >= date('Y-m-d');
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['salle_nom']) ?></strong></td>
                            <td><?= (int)$r['capacite'] ?> <?= __('people') ?></td>
                            <td><?= date('d/m/Y', strtotime($r['date'])) ?></td>
                            <td><span class="badge badge-info"><?= substr($r['heure_debut'], 0, 5) ?></span></td>
                            <td><span class="badge badge-info"><?= substr($r['heure_fin'], 0, 5) ?></span></td>
                            <td>
                                <?php if ($future): ?>
                                <!-- CSRF-protected POST form for cancellation -->
                                <form method="POST" action="mes_reservations.php"
                                      onsubmit="return confirm('<?= __('confirm_cancel') ?>')"
                                      style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                    <input type="hidden" name="annuler" value="<?= (int)$r['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">🗑️ <?= __('cancel') ?></button>
                                </form>
                                <?php else: ?>
                                    <span style="color:var(--muted); font-size:0.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- FEATURED ROOMS -->
    <div class="page-header">
        <h1 style="font-size:1.2rem;">🚪 <?= __('available_rooms') ?></h1>
    </div>
    <div class="grid-3">
        <?php foreach ($salles as $s): ?>
        <div class="salle-card">
            <div class="salle-card-header">
                <div class="salle-icon">🏛️</div>
                <div>
                    <div class="salle-name"><?= htmlspecialchars($s['nom']) ?></div>
                    <div style="font-size:0.75rem; color:var(--muted);">ID: <?= $s['id'] ?></div>
                </div>
            </div>
            <div class="salle-body">
                <div class="salle-info">👥 <?= (int)$s['capacite'] ?> <?= __('people') ?></div>
                <div class="salle-desc"><?= htmlspecialchars($s['description'] ?? __('available_rooms')) ?></div>
            </div>
            <div class="salle-footer">
                <a href="reservation.php?salle=<?= (int)$s['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;">📅 <?= __('reserve') ?></a>
                <a href="salles.php" class="btn btn-outline btn-sm"><?= __('details') ?></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<script src="script.js"></script>
</body>
</html>