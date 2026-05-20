<?php
require 'config.php';
requireUser();

$salles = $pdo->query("SELECT * FROM gestion ORDER BY nom ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('rooms') ?> — GestionSalles</title>
    <link rel="stylesheet" href="style.css">
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
            <li><a href="salles.php" class="active">🚪 <?= __('rooms') ?></a></li>
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

    <div class="page-header animate">
        <h1>🚪 <?= __('rooms') ?></h1>
        <p><?= $lang === 'ar' ? 'اطلع على جميع القاعات المتاحة واحجز بنقرة واحدة' : 'Consultez toutes les salles disponibles et réservez en un clic' ?></p>
    </div>

    <div style="margin-bottom:20px;" class="animate animate-delay-1">
        <span class="badge badge-info">
            <?= count($salles) ?> <?= __('room') ?>(s)
        </span>
    </div>

    <?php if (empty($salles)): ?>
        <div class="table-wrap">
            <div class="empty-state">
                <span class="icon">🚪</span>
                <h3><?= $lang === 'ar' ? 'لا توجد قاعات متاحة' : 'Aucune salle disponible' ?></h3>
                <p><?= $lang === 'ar' ? 'لم يقم المشرف بإضافة قاعات بعد.' : 'L\'administrateur n\'a pas encore ajouté de salles.' ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="grid-3 animate animate-delay-2">
            <?php foreach ($salles as $s):
                $pct = min(100, ((int)$s['capacite'] / 50) * 100);
            ?>
            <div class="salle-card glass-card">
                <div class="salle-card-header">
                    <div class="salle-icon">🏛️</div>
                    <div>
                        <div class="salle-name"><?= htmlspecialchars($s['nom']) ?></div>
                        <div style="font-size:0.74rem; color:var(--muted); margin-top:2px;">ID: <?= $s['id'] ?></div>
                    </div>
                </div>

                <div class="salle-body">
                    <div class="salle-info">👥 <?= __('capacity') ?> : <strong><?= (int)$s['capacite'] ?> <?= __('people') ?></strong></div>
                    <div class="salle-desc">
                        <?= htmlspecialchars($s['description'] ?? ($lang === 'ar' ? 'لا يوجد وصف متاح.' : 'Aucune description disponible.')) ?>
                    </div>

                    <!-- Capacity bar -->
                    <div style="margin-top:14px;">
                        <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--muted); margin-bottom:6px;">
                            <span><?= __('capacity') ?></span>
                            <span><?= (int)$s['capacite'] ?> <?= __('people') ?></span>
                        </div>
                        <div style="height:4px; background:var(--border); border-radius:2px; overflow:hidden;">
                            <div style="height:100%; width:<?= $pct ?>%;
                                        background:linear-gradient(90deg, var(--accent), var(--accent2));
                                        border-radius:2px; transition: width 0.6s ease;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="salle-footer">
                    <a href="reservation.php?salle=<?= (int)$s['id'] ?>"
                       class="btn btn-primary btn-sm" style="flex:1; text-align:center;">
                        📅 <?= __('reserve') ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script src="script.js"></script>
</body>
</html>