<?php
require 'config.php';
requireAdmin();

$id_salle = (int)($_GET['id'] ?? 0);
if ($id_salle <= 0) {
    header('Location: admin_dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM gestion WHERE id = ?");
$stmt->execute([$id_salle]);
$salle = $stmt->fetch();
if (!$salle) {
    header('Location: admin_dashboard.php');
    exit;
}

// Filters — use whitelist for status to prevent SQL injection
$filter_date   = trim($_GET['date']   ?? '');
$filter_search = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? '';
$allowed_statuses = ['future', 'past', 'today', ''];
if (!in_array($filter_status, $allowed_statuses, true)) {
    $filter_status = '';
}

$today = date('Y-m-d');
$now   = date('H:i:s');

$where  = ["r.id_salle = ?"];
$params = [$id_salle];

if ($filter_date !== '') {
    $where[]  = "r.date = ?";
    $params[] = $filter_date;
}

// Use parameterised date comparisons instead of direct interpolation
if ($filter_status === 'future') {
    $where[]  = "(r.date > ? OR (r.date = ? AND r.heure_fin > ?))";
    $params[] = $today;
    $params[] = $today;
    $params[] = $now;
} elseif ($filter_status === 'past') {
    $where[]  = "(r.date < ? OR (r.date = ? AND r.heure_fin <= ?))";
    $params[] = $today;
    $params[] = $today;
    $params[] = $now;
} elseif ($filter_status === 'today') {
    $where[]  = "r.date = ?";
    $params[] = $today;
}

if ($filter_search !== '') {
    $like     = '%' . $filter_search . '%';
    $where[]  = "(a.nom LIKE ? OR a.email LIKE ?)";
    $params[] = $like;
    $params[] = $like;
}

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT r.*, a.nom AS user_nom, a.email AS user_email
    FROM reservation r
    JOIN authentification a ON r.id_user = a.id
    WHERE $whereSQL
    ORDER BY r.date ASC, r.heure_debut ASC
");
$stmt->execute($params);
$reservations = $stmt->fetchAll();

// Stats for this room
$stats_stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(r.date >= ?) AS futures,
        SUM(r.date < ?)  AS passees,
        SUM(r.date = ?)  AS today_count,
        COUNT(DISTINCT r.id_user) AS unique_users
    FROM reservation r
    WHERE r.id_salle = ?
");
$stats_stmt->execute([$today, $today, $today, $id_salle]);
$stats = $stats_stmt->fetch();

// Unique users for this room
$uu_stmt = $pdo->prepare("
    SELECT a.id, a.nom, a.email,
           COUNT(r.id) AS nb_reservations,
           MIN(r.date) AS premiere_res,
           MAX(r.date) AS derniere_res
    FROM reservation r
    JOIN authentification a ON r.id_user = a.id
    WHERE r.id_salle = ?
    GROUP BY a.id, a.nom, a.email
    ORDER BY nb_reservations DESC
");
$uu_stmt->execute([$id_salle]);
$unique_users = $uu_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= __('details') ?> — <?= htmlspecialchars($salle['nom']) ?> — GestionSalles</title>
<link rel="stylesheet" href="style.css">
<style>
/* SALLE DETAILS SPECIFIC */
.topbar{background:rgba(10,11,15,.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 36px;position:sticky;top:0;z-index:100;}
[data-theme="light"] .topbar { background:rgba(255,255,255,.9); }
.topbar-inner{max-width:1300px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;height:62px;gap:20px;}
.topbar-left{display:flex;align-items:center;gap:14px;}
.back-btn{display:inline-flex;align-items:center;gap:8px;padding:7px 16px;background:var(--surface2);border:1px solid var(--border2);border-radius:8px;color:var(--light);font-size:.84rem;font-weight:500;transition:all .2s;}
.back-btn:hover{border-color:var(--accent);color:var(--accent);}
.topbar-title{font-family:var(--font-h);font-size:1rem;font-weight:700;}
.topbar-right{display:flex;align-items:center;gap:10px;}
.room-header{background:linear-gradient(135deg,rgba(108,99,255,.12),rgba(0,212,170,.06));border:1px solid rgba(108,99,255,.2);border-radius:20px;padding:32px;margin-bottom:28px;display:flex;align-items:center;gap:28px;}
.room-icon-big{width:72px;height:72px;border-radius:18px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:36px;flex-shrink:0;}
.room-info h1{font-family:var(--font-h);font-size:1.7rem;font-weight:800;margin-bottom:6px;}
.room-info p{color:var(--muted);font-size:.9rem;}
.room-meta{display:flex;gap:12px;margin-top:12px;flex-wrap:wrap;}
.meta-tag{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:600;background:rgba(255,255,255,.06);border:1px solid var(--border2);}
[data-theme="light"] .meta-tag { background:rgba(0,0,0,.04); border:1px solid rgba(0,0,0,.1); }
.filters-card{background:var(--surface);backdrop-filter:var(--glass-blur);border:1px solid var(--border);border-radius:var(--r);padding:20px 24px;margin-bottom:24px;}
.filters-row{display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;}
.filter-group{display:flex;flex-direction:column;gap:6px;}
.filter-label{font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;}
.filter-control{padding:9px 13px;background:var(--bg);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-family:var(--font);font-size:.85rem;outline:none;transition:border-color .2s;}
.filter-control:focus{border-color:var(--accent);}
.main-layout{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;}
@media(max-width:1000px){.main-layout{grid-template-columns:1fr;}}
.user-cell{display:flex;align-items:center;gap:10px;}
.user-ava{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;color:#fff;flex-shrink:0;}
.user-name{font-weight:600;font-size:.85rem;}
.user-email{font-size:.75rem;color:var(--muted);}
.badge-today{background:rgba(0,212,170,.12);color:#00d4aa;border:1px solid rgba(0,212,170,.25);}
.badge-amber{background:rgba(245,158,11,.1);color:var(--amber);border:1px solid rgba(245,158,11,.25);}
.badge-past{background:rgba(122,125,142,.1);color:var(--muted);}
.status-dot{display:inline-block;width:6px;height:6px;border-radius:50%;}
.user-card{background:var(--surface);backdrop-filter:var(--glass-blur);border:1px solid var(--border);border-radius:var(--r);padding:16px;margin-bottom:12px;transition:border-color .2s;}
.user-card:hover{border-color:rgba(108,99,255,.3);}
.user-card-top{display:flex;align-items:center;gap:10px;margin-bottom:12px;}
.user-card-ava{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.88rem;color:#fff;flex-shrink:0;}
.user-card-name{font-weight:600;font-size:.88rem;}
.user-card-email{font-size:.75rem;color:var(--muted);}
.user-card-stats{display:flex;gap:10px;}
.user-stat-box{flex:1;background:var(--surface2);border-radius:8px;padding:10px;text-align:center;}
.user-stat-box .v{font-family:var(--font-h);font-size:1.1rem;font-weight:800;}
.user-stat-box .l{font-size:.68rem;color:var(--muted);margin-top:2px;}
.action-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.anim-1{animation-delay:.05s;}.anim-2{animation-delay:.1s;}.anim-3{animation-delay:.15s;}
@media print {
  .topbar,.filters-card,.action-bar,.btn,.logout-btn,.users-sidebar{display:none!important;}
  .main-layout{grid-template-columns:1fr!important;}
  body{background:#fff!important;color:#000!important;}
  .table-wrap{border:1px solid #ccc!important;}
  th,td{color:#000!important;border-color:#ddd!important;}
  .room-header{background:#f5f5f5!important;}
}
</style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-inner">
    <div class="topbar-left">
      <a href="admin_dashboard.php" class="back-btn"><?= $lang === 'ar' ? '→ رجوع' : '← Retour' ?></a>
      <span class="topbar-title">🏛️ <?= htmlspecialchars($salle['nom']) ?></span>
    </div>
    <div class="topbar-right">
      <div style="display:flex; gap: 10px;">
          <a href="?id=<?= $id_salle ?>&lang=<?= $lang === 'fr' ? 'ar' : 'fr' ?>" class="lang-toggle" style="text-decoration:none; display:flex; align-items:center;">
              <?= $lang === 'fr' ? 'AR' : 'FR' ?>
          </a>
          <button id="theme-toggle" class="theme-toggle">🌙</button>
      </div>
      <div class="user-pill">
        <div class="ava"><?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?></div>
        <?= htmlspecialchars($_SESSION['user_nom']) ?>
      </div>
      <a href="logout.php" class="logout-btn">🚪 <?= __('logout') ?></a>
    </div>
  </div>
</header>

<div class="wrapper">

  <!-- ROOM HEADER -->
  <div class="room-header anim">
    <div class="room-icon-big">🏛️</div>
    <div class="room-info">
      <h1><?= htmlspecialchars($salle['nom']) ?></h1>
      <p><?= htmlspecialchars($salle['description'] ?? ($lang === 'ar' ? 'قاعة اجتماعات' : 'Salle de réunion')) ?></p>
      <div class="room-meta">
        <span class="meta-tag">👥 <?= (int)$salle['capacite'] ?> <?= __('people') ?></span>
        <span class="meta-tag">🆔 ID <?= $salle['id'] ?></span>
        <span class="meta-tag" style="color:var(--accent2);">📅 <?= $stats['total'] ?? 0 ?> <?= $lang === 'ar' ? 'حجز إجمالي' : 'réservation(s) au total' ?></span>
      </div>
    </div>
  </div>

  <!-- STATS ROW -->
  <div class="stats-row anim anim-1">
    <div class="stat">
      <div class="val"><?= $stats['total'] ?? 0 ?></div>
      <div class="lbl"><?= __('total') ?></div>
    </div>
    <div class="stat">
      <div class="val" style="color:var(--green);"><?= $stats['futures'] ?? 0 ?></div>
      <div class="lbl"><?= __('upcoming') ?></div>
    </div>
    <div class="stat">
      <div class="val" style="color:var(--muted);"><?= $stats['passees'] ?? 0 ?></div>
      <div class="lbl"><?= __('past') ?></div>
    </div>
    <div class="stat">
      <div class="val" style="color:var(--amber);"><?= $stats['today_count'] ?? 0 ?></div>
      <div class="lbl"><?= __('today') ?></div>
    </div>
    <div class="stat">
      <div class="val" style="color:var(--accent);"><?= $stats['unique_users'] ?? 0 ?></div>
      <div class="lbl"><?= __('unique_people') ?></div>
    </div>
  </div>

  <!-- FILTERS -->
  <div class="filters-card anim anim-1">
    <form method="GET" action="">
      <input type="hidden" name="id" value="<?= $id_salle ?>">
      <div class="filters-row">
        <!-- Search -->
        <div class="filter-group" style="flex:1;min-width:180px;">
          <label class="filter-label">🔍 <?= __('search') ?></label>
          <input type="text" name="search" class="filter-control"
                 placeholder="<?= $lang === 'ar' ? 'الاسم أو البريد...' : 'Nom ou email...' ?>"
                 value="<?= htmlspecialchars($filter_search) ?>">
        </div>
        <!-- Date -->
        <div class="filter-group">
          <label class="filter-label">📆 <?= __('date') ?></label>
          <input type="date" name="date" class="filter-control"
                 value="<?= htmlspecialchars($filter_date) ?>">
        </div>
        <!-- Status -->
        <div class="filter-group">
          <label class="filter-label">🔖 <?= __('status') ?></label>
          <select name="status" class="filter-control">
            <option value=""><?= __('all') ?></option>
            <option value="today"  <?= $filter_status === 'today'  ? 'selected' : '' ?>><?= __('today') ?></option>
            <option value="future" <?= $filter_status === 'future' ? 'selected' : '' ?>><?= __('upcoming') ?></option>
            <option value="past"   <?= $filter_status === 'past'   ? 'selected' : '' ?>><?= __('past') ?></option>
          </select>
        </div>
        <!-- Buttons -->
        <div class="filter-group" style="flex-direction:row;gap:8px;">
          <button type="submit" class="btn btn-primary"><?= __('filter') ?></button>
          <a href="salle_details.php?id=<?= $id_salle ?>" class="btn btn-ghost">✕ <?= __('reset') ?></a>
        </div>
      </div>
    </form>
  </div>

  <!-- MAIN LAYOUT -->
  <div class="main-layout">

    <!-- LEFT: RESERVATIONS TABLE -->
    <div>
      <div class="sec-header anim anim-2">
        <span class="sec-title">
          📋 <?= $lang === 'ar' ? 'الحجوزات' : 'Réservations' ?>
          <?php if ($filter_search || $filter_date || $filter_status): ?>
            <span style="font-size:.78rem;color:var(--accent);font-family:var(--font);font-weight:400;">— <?= $lang === 'ar' ? 'تصفية' : 'filtrées' ?></span>
          <?php endif; ?>
        </span>
        <div class="action-bar">
          <span class="badge badge-info"><?= count($reservations) ?> <?= $lang === 'ar' ? 'نتيجة' : 'résultat(s)' ?></span>
          <button onclick="exportCSV()" class="btn btn-ghost btn-sm">⬇️ CSV</button>
          <button onclick="window.print()" class="btn btn-ghost btn-sm">🖨️ <?= __('print') ?></button>
        </div>
      </div>

      <?php if (empty($reservations)): ?>
        <div class="table-wrap">
          <div class="empty">
            <div class="ico">🔍</div>
            <p><?= __('no_results') ?></p>
          </div>
        </div>
      <?php else: ?>
        <div class="table-wrap anim anim-3" id="reservations-table">
          <table id="res-table">
            <thead>
              <tr>
                <th><?= __('user') ?></th>
                <th><?= __('date') ?></th>
                <th><?= __('start_time') ?></th>
                <th><?= __('end_time') ?></th>
                <th><?= __('duration') ?></th>
                <th><?= __('status') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reservations as $r):
                $is_today = $r['date'] === $today;
                $is_past  = $r['date'] < $today || ($r['date'] === $today && $r['heure_fin'] <= $now);
                $is_now   = $r['date'] === $today && $r['heure_debut'] <= $now && $r['heure_fin'] > $now;
                $debut_ts  = strtotime($r['heure_debut']);
                $fin_ts    = strtotime($r['heure_fin']);
                $duree_min = max(0, ($fin_ts - $debut_ts) / 60);
                $duree_str = $duree_min >= 60
                    ? floor($duree_min / 60) . 'h' . ($duree_min % 60 ? sprintf('%02d', $duree_min % 60) : '')
                    : $duree_min . 'min';
              ?>
              <tr>
                <td>
                  <div class="user-cell">
                    <div class="user-ava"><?= strtoupper(substr($r['user_nom'], 0, 1)) ?></div>
                    <div>
                      <div class="user-name"><?= htmlspecialchars($r['user_nom']) ?></div>
                      <div class="user-email"><?= htmlspecialchars($r['user_email']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <strong><?= date('d/m/Y', strtotime($r['date'])) ?></strong>
                  <?php if ($is_today): ?>
                    <br><span style="font-size:.7rem;color:var(--accent2);"><?= __('today') ?></span>
                  <?php endif; ?>
                </td>
                <td><span class="badge badge-info"><?= substr($r['heure_debut'], 0, 5) ?></span></td>
                <td><span class="badge badge-info"><?= substr($r['heure_fin'], 0, 5) ?></span></td>
                <td style="color:var(--muted);font-size:.82rem;"><?= $duree_str ?></td>
                <td>
                  <?php if ($is_now): ?>
                    <span class="badge badge-today">
                      <span class="status-dot" style="background:var(--accent2);"></span><?= __('in_progress') ?>
                    </span>
                  <?php elseif ($is_past): ?>
                    <span class="badge badge-past"><?= __('past') ?></span>
                  <?php elseif ($is_today): ?>
                    <span class="badge badge-amber">
                      <span class="status-dot" style="background:var(--amber);"></span><?= __('tonight') ?>
                    </span>
                  <?php else: ?>
                    <span class="badge badge-green"><?= __('upcoming') ?></span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: UNIQUE USERS -->
    <div class="users-sidebar">
      <div class="sec-header">
        <span class="sec-title">👤 <?= $lang === 'ar' ? 'الأشخاص' : 'Personnes' ?></span>
        <span class="badge badge-info"><?= count($unique_users) ?></span>
      </div>

      <?php if (empty($unique_users)): ?>
        <div class="table-wrap">
          <div class="empty">
            <div class="ico">👤</div>
            <p><?= $lang === 'ar' ? 'لا يوجد أشخاص.' : 'Aucune personne.' ?></p>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($unique_users as $u): ?>
        <div class="user-card anim">
          <div class="user-card-top">
            <div class="user-card-ava"><?= strtoupper(substr($u['nom'], 0, 1)) ?></div>
            <div>
              <div class="user-card-name"><?= htmlspecialchars($u['nom']) ?></div>
              <div class="user-card-email"><?= htmlspecialchars($u['email']) ?></div>
            </div>
          </div>
          <div class="user-card-stats">
            <div class="user-stat-box">
              <div class="v" style="color:var(--accent);"><?= $u['nb_reservations'] ?></div>
              <div class="l"><?= $lang === 'ar' ? 'الحجوزات' : 'Réservations' ?></div>
            </div>
            <div class="user-stat-box">
              <div class="v" style="color:var(--accent2);font-size:.85rem;margin-top:4px;">
                <?= date('d/m', strtotime($u['derniere_res'])) ?>
              </div>
              <div class="l"><?= $lang === 'ar' ? 'الأخيرة' : 'Dernière' ?></div>
            </div>
          </div>
          <div style="margin-top:10px;">
            <a href="salle_details.php?id=<?= $id_salle ?>&search=<?= urlencode($u['nom']) ?>"
               class="btn btn-ghost btn-sm" style="width:100%;">
              🔍 <?= $lang === 'ar' ? 'عرض حجوزاته' : 'Voir ses réservations' ?>
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="script.js"></script>
<script>
function exportCSV() {
  const table = document.getElementById('res-table');
  if (!table) return;
  const rows = Array.from(table.querySelectorAll('tr'));
  const csv  = rows.map(row =>
    Array.from(row.querySelectorAll('th, td'))
      .map(c => '"' + c.innerText.replace(/\n/g, ' ').replace(/"/g, '""').trim() + '"')
      .join(',')
  ).join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href     = URL.createObjectURL(blob);
  link.download = 'salle_<?= $salle['id'] ?>_reservations.csv';
  link.click();
}

// Highlight currently-active rows
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('tbody tr').forEach(row => {
    if (row.querySelector('.badge-today')) {
      row.style.background = 'rgba(0,212,170,0.04)';
    }
  });
});
</script>
</body>
</html>