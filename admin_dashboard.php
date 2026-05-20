<?php
require 'config.php';
requireAdmin();

$success = "";
$erreur  = "";

// ===== ADD ROOM =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom      = trim($_POST['nom']         ?? '');
    $capacite = (int)($_POST['capacite']   ?? 0);
    $desc     = trim($_POST['description'] ?? '');

    if (empty($nom) || $capacite < 1) {
        $erreur = $lang === 'ar' ? "الاسم والسعة مطلوبان." : "Le nom et la capacité sont obligatoires.";
    } elseif (mb_strlen($nom) > 100) {
        $erreur = $lang === 'ar' ? "الاسم طويل جداً." : "Le nom est trop long.";
    } else {
        $pdo->prepare("INSERT INTO gestion (nom, capacite, description) VALUES (?, ?, ?)")
            ->execute([$nom, $capacite, $desc]);
        $success = $lang === 'ar' ? "تمت إضافة القاعة بنجاح!" : "Salle ajoutée avec succès !";
    }
}

// ===== EDIT ROOM =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $id       = (int)$_POST['id'];
    $nom      = trim($_POST['nom']         ?? '');
    $capacite = (int)($_POST['capacite']   ?? 0);
    $desc     = trim($_POST['description'] ?? '');

    if (empty($nom) || $capacite < 1) {
        $erreur = $lang === 'ar' ? "الاسم والسعة مطلوبان." : "Le nom et la capacité sont obligatoires.";
    } else {
        $pdo->prepare("UPDATE gestion SET nom=?, capacite=?, description=? WHERE id=?")
            ->execute([$nom, $capacite, $desc, $id]);
        $success = $lang === 'ar' ? "تم تعديل القاعة بنجاح!" : "Salle modifiée avec succès !";
    }
}

// ===== DELETE ROOM =====
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    $pdo->prepare("DELETE FROM reservation WHERE id_salle = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM gestion WHERE id = ?")->execute([$id]);
    $success = $lang === 'ar' ? "تم حذف القاعة." : "Salle supprimée.";
}

// ===== DELETE USER =====
if (isset($_GET['del_user'])) {
    $id = (int)$_GET['del_user'];
    $check = $pdo->prepare("SELECT role FROM authentification WHERE id = ?");
    $check->execute([$id]);
    $target = $check->fetch();
    if ($target && $target['role'] === 'user') {
        $pdo->prepare("DELETE FROM reservation WHERE id_user = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM authentification WHERE id = ?")->execute([$id]);
        $success = $lang === 'ar' ? "تم حذف المستخدم." : "Utilisateur supprimé.";
    } else {
        $erreur = $lang === 'ar' ? "لا يمكن حذف هذا المستخدم." : "Impossible de supprimer cet utilisateur.";
    }
}

// ===== ADD ADMIN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $nom   = trim($_POST['admin_nom']   ?? '');
    $email = trim($_POST['admin_email'] ?? '');
    $mdp   = trim($_POST['admin_mdp']   ?? '');

    if (empty($nom) || empty($email) || empty($mdp)) {
        $erreur = $lang === 'ar' ? "جميع الحقول مطلوبة." : "Tous les champs sont requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = $lang === 'ar' ? "تنسيق البريد الإلكتروني غير صالح." : "Format d'email invalide.";
    } elseif (strlen($mdp) < 4) {
        $erreur = $lang === 'ar' ? "يجب أن تحتوي كلمة المرور على 4 أحرف على الأقل." : "Le mot de passe doit contenir au moins 4 caractères.";
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM authentification WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $erreur = $lang === 'ar' ? "البريد الإلكتروني موجود بالفعل." : "Cet email existe déjà.";
            } else {
                $pdo->prepare("INSERT INTO authentification (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'admin')")
                    ->execute([$nom, $email, password_hash($mdp, PASSWORD_BCRYPT)]);
                $success = $lang === 'ar' ? "تمت إضافة المشرف بنجاح!" : "Administrateur ajouté avec succès !";
            }
        } catch (Exception $e) {
            $erreur = $lang === 'ar' ? "خطأ أثناء إضافة المشرف." : "Erreur lors de l'ajout de l'administrateur.";
        }
    }
}

// ===== DELETE ADMIN =====
if (isset($_GET['del_admin'])) {
    $id = (int)$_GET['del_admin'];
    if ($id === (int)$_SESSION['user_id']) {
        $erreur = $lang === 'ar' ? "لا يمكنك حذف نفسك." : "Vous ne pouvez pas vous supprimer vous-même.";
    } else {
        $pdo->prepare("DELETE FROM reservation WHERE id_user = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM authentification WHERE id = ?")->execute([$id]);
        $success = $lang === 'ar' ? "تم حذف المشرف." : "Administrateur supprimé.";
    }
}

// Fetch data
$salles = $pdo->query("SELECT * FROM gestion ORDER BY id DESC")->fetchAll();

$res_counts = [];
foreach ($pdo->query("
    SELECT id_salle, COUNT(*) AS total_res, COUNT(DISTINCT id_user) AS unique_users
    FROM reservation GROUP BY id_salle
")->fetchAll() as $rc) {
    $res_counts[$rc['id_salle']] = $rc;
}

$users  = $pdo->query("SELECT * FROM authentification WHERE role = 'user'  ORDER BY id ASC")->fetchAll();
$admins = $pdo->query("SELECT * FROM authentification WHERE role = 'admin' ORDER BY id ASC")->fetchAll();
$reservations = $pdo->query("
    SELECT r.*, g.nom AS salle_nom, a.nom AS user_nom
    FROM reservation r
    JOIN gestion g ON r.id_salle = g.id
    JOIN authentification a ON r.id_user = a.id
    ORDER BY r.date DESC, r.heure_debut DESC
")->fetchAll();

$nb_salles = count($salles);
$nb_users  = count($users);
$nb_admins = count($admins);
$nb_res    = count($reservations);

$salle_edit = null;
if (isset($_GET['modifier'])) {
    $st = $pdo->prepare("SELECT * FROM gestion WHERE id = ?");
    $st->execute([(int)$_GET['modifier']]);
    $salle_edit = $st->fetch();
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — GestionSalles</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <a href="admin_dashboard.php" class="brand">
      <span class="dot"></span> GestionSalles
    </a>
  </div>
  <div class="sidebar-label"><?= $lang === 'ar' ? 'التنقل' : 'Navigation' ?></div>
  <nav>
    <a href="#" class="active tab-link" data-tab="salles"><span class="ico">🚪</span> <?= __('rooms') ?></a>
    <a href="#" class="tab-link" data-tab="reservations"><span class="ico">📅</span> <?= $lang === 'ar' ? 'الحجوزات' : 'Réservations' ?></a>
    <a href="#" class="tab-link" data-tab="users"><span class="ico">👥</span> <?= __('users') ?></a>
    <a href="#" class="tab-link" data-tab="admins"><span class="ico">⚡</span> <?= __('admins') ?></a>
  </nav>
  <div class="sidebar-bottom">
    <div style="display:flex; justify-content:space-between; margin-bottom: 10px;">
        <a href="?lang=<?= $lang === 'fr' ? 'ar' : 'fr' ?>" class="lang-toggle" style="text-decoration:none; display:flex; align-items:center;">
            <?= $lang === 'fr' ? 'AR' : 'FR' ?>
        </a>
        <button id="theme-toggle" class="theme-toggle">🌙</button>
    </div>
    <div class="user-pill">
      <div class="ava"><?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?></div>
      <div class="info">
        <div class="name"><?= htmlspecialchars($_SESSION['user_nom']) ?></div>
        <div class="role">⚡ <?= __('admin') ?></div>
      </div>
    </div>
    <a href="logout.php" class="logout-btn">🚪 <?= __('logout') ?></a>
  </div>
</aside>

<!-- MAIN -->
<main class="main">

  <div style="margin-bottom:28px;" class="anim">
    <p style="color:var(--muted);font-size:.82rem;margin-bottom:4px;"><?= __('dashboard') ?></p>
    <h1 style="font-family:var(--font-h);font-size:1.7rem;font-weight:800;"><?= $lang === 'ar' ? 'الإدارة' : 'Administration' ?> ⚡</h1>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success anim">✅ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($erreur): ?>
    <div class="alert alert-danger anim">⚠️ <?= htmlspecialchars($erreur) ?></div>
  <?php endif; ?>

  <!-- STATS -->
  <div class="stats-row anim">
    <div class="stat">
      <div class="ico blue">🚪</div>
      <div><div class="val"><?= $nb_salles ?></div><div class="lbl"><?= __('rooms') ?></div></div>
    </div>
    <div class="stat">
      <div class="ico green">📅</div>
      <div><div class="val"><?= $nb_res ?></div><div class="lbl"><?= $lang === 'ar' ? 'الحجوزات' : 'Réservations' ?></div></div>
    </div>
    <div class="stat">
      <div class="ico cyan">👥</div>
      <div><div class="val"><?= $nb_users ?></div><div class="lbl"><?= __('users') ?></div></div>
    </div>
    <div class="stat">
      <div class="ico red">⚡</div>
      <div><div class="val"><?= $nb_admins ?></div><div class="lbl"><?= __('admins') ?></div></div>
    </div>
  </div>

  <!-- TABS -->
  <div class="page-tabs anim">
    <button class="tab-btn active" data-tab="salles">🚪 <?= __('rooms') ?></button>
    <button class="tab-btn" data-tab="reservations">📅 <?= $lang === 'ar' ? 'الحجوزات' : 'Réservations' ?></button>
    <button class="tab-btn" data-tab="users">👥 <?= __('users') ?></button>
    <button class="tab-btn" data-tab="admins">⚡ <?= __('admins') ?></button>
  </div>

  <!-- ===== PANEL: SALLES ===== -->
  <div class="panel active anim" id="panel-salles">
    <div class="salles-layout">

      <!-- FORM -->
      <div class="form-card glass-card">
        <?php if ($salle_edit): ?>
          <h3>✏️ <?= $lang === 'ar' ? 'تعديل القاعة' : 'Modifier la salle' ?></h3>
          <form method="POST">
            <input type="hidden" name="id" value="<?= (int)$salle_edit['id'] ?>">
            <div class="form-group">
              <label class="form-label"><?= __('name') ?></label>
              <input type="text" name="nom" class="form-control"
                     value="<?= htmlspecialchars($salle_edit['nom']) ?>" required maxlength="100">
            </div>
            <div class="form-group">
              <label class="form-label"><?= __('capacity') ?></label>
              <input type="number" name="capacite" class="form-control"
                     min="1" value="<?= (int)$salle_edit['capacite'] ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label"><?= __('description') ?></label>
              <textarea name="description" class="form-control"><?= htmlspecialchars($salle_edit['description'] ?? '') ?></textarea>
            </div>
            <button type="submit" name="modifier" class="btn btn-warning btn-full">💾 <?= __('save') ?></button>
            <a href="admin_dashboard.php" class="btn btn-ghost btn-full" style="margin-top:8px;">✕ <?= __('cancel') ?></a>
          </form>
        <?php else: ?>
          <h3>➕ <?= $lang === 'ar' ? 'إضافة قاعة' : 'Ajouter une salle' ?></h3>
          <form method="POST">
            <div class="form-group">
              <label class="form-label"><?= __('name') ?></label>
              <input type="text" name="nom" class="form-control" placeholder="<?= $lang === 'ar' ? 'مثال: قاعة أ' : 'Ex: Salle A' ?>" required maxlength="100">
            </div>
            <div class="form-group">
              <label class="form-label"><?= __('capacity') ?></label>
              <input type="number" name="capacite" class="form-control" placeholder="<?= $lang === 'ar' ? 'مثال: 30' : 'Ex: 30' ?>" min="1" required>
            </div>
            <div class="form-group">
              <label class="form-label"><?= __('description') ?></label>
              <textarea name="description" class="form-control" placeholder="<?= $lang === 'ar' ? 'وصف اختياري...' : 'Description optionnelle...' ?>"></textarea>
            </div>
            <button type="submit" name="ajouter" class="btn btn-success btn-full">➕ <?= __('add') ?></button>
          </form>
        <?php endif; ?>
      </div>

      <!-- TABLE SALLES -->
      <div>
        <div class="sec-header">
          <span class="sec-title"><?= $lang === 'ar' ? 'قائمة القاعات' : 'Liste des salles' ?></span>
          <span class="badge badge-info"><?= $nb_salles ?> <?= __('room') ?>(s)</span>
        </div>
        <?php if (empty($salles)): ?>
          <div class="table-wrap"><div class="empty"><div class="ico">🚪</div><p><?= $lang === 'ar' ? 'لا توجد قاعات. أضف واحدة.' : 'Aucune salle. Ajoutez-en une.' ?></p></div></div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th><?= __('name') ?></th><th><?= __('capacity') ?></th><th><?= __('description') ?></th><th><?= $lang === 'ar' ? 'المسجلين' : 'Inscrits' ?></th><th><?= __('action') ?></th></tr></thead>
            <tbody>
              <?php foreach ($salles as $s): ?>
              <tr>
                <td style="color:var(--muted)"><?= $s['id'] ?></td>
                <td><strong><?= htmlspecialchars($s['nom']) ?></strong></td>
                <td><span class="badge badge-info">👥 <?= (int)$s['capacite'] ?></span></td>
                <td style="color:var(--muted);font-size:.82rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  <?= htmlspecialchars(substr($s['description'] ?? '—', 0, 40)) ?>
                </td>
                <td>
                  <?php $rc = $res_counts[$s['id']] ?? ['total_res' => 0, 'unique_users' => 0]; ?>
                  <a href="salle_details.php?id=<?= $s['id'] ?>"
                     style="display:flex;flex-direction:column;gap:2px;">
                    <span class="badge" style="background:rgba(0,212,170,.1);color:#00d4aa;border:1px solid rgba(0,212,170,.2);">
                      👤 <?= $rc['unique_users'] ?> <?= __('people') ?>.
                    </span>
                    <span style="font-size:.7rem;color:var(--muted);margin-top:2px;">
                      <?= $rc['total_res'] ?> <?= $lang === 'ar' ? 'حجز' : 'réserv.' ?>
                    </span>
                  </a>
                </td>
                <td>
                  <div class="actions">
                    <a href="salle_details.php?id=<?= $s['id'] ?>"
                       class="btn btn-sm"
                       style="background:rgba(0,212,170,.12);color:#00d4aa;border:1px solid rgba(0,212,170,.25);">
                       👥 <?= $lang === 'ar' ? 'المسجلين' : 'Inscrits' ?>
                    </a>
                    <a href="admin_dashboard.php?modifier=<?= $s['id'] ?>#panel-salles"
                       class="btn btn-warning btn-sm">✏️ <?= __('edit') ?></a>
                    <a href="admin_dashboard.php?supprimer=<?= $s['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('<?= $lang === 'ar' ? 'حذف هذه القاعة وجميع حجوزاتها؟' : 'Supprimer cette salle et toutes ses réservations ?' ?>')">
                       🗑️
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ===== PANEL: RÉSERVATIONS ===== -->
  <div class="panel" id="panel-reservations">
    <div class="sec-header">
      <span class="sec-title"><?= $lang === 'ar' ? 'كل الحجوزات' : 'Toutes les réservations' ?></span>
      <span class="badge badge-info"><?= $nb_res ?> <?= $lang === 'ar' ? 'حجز' : 'réservation(s)' ?></span>
    </div>
    <?php if (empty($reservations)): ?>
      <div class="table-wrap"><div class="empty"><div class="ico">📅</div><p><?= __('no_reservations') ?></p></div></div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th><?= __('user') ?></th><th><?= __('room') ?></th><th><?= __('date') ?></th><th><?= __('start_time') ?></th><th><?= __('end_time') ?></th><th><?= __('status') ?></th></tr>
        </thead>
        <tbody>
          <?php foreach ($reservations as $r):
            $past = $r['date'] < date('Y-m-d');
          ?>
          <tr>
            <td style="color:var(--muted)"><?= $r['id'] ?></td>
            <td><strong><?= htmlspecialchars($r['user_nom']) ?></strong></td>
            <td><?= htmlspecialchars($r['salle_nom']) ?></td>
            <td><?= date('d/m/Y', strtotime($r['date'])) ?></td>
            <td><span class="badge badge-info"><?= substr($r['heure_debut'], 0, 5) ?></span></td>
            <td><span class="badge badge-info"><?= substr($r['heure_fin'], 0, 5) ?></span></td>
            <td>
              <?php if ($past): ?>
                <span class="badge" style="background:rgba(122,125,142,.1);color:var(--muted);"><?= __('past') ?></span>
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

  <!-- ===== PANEL: UTILISATEURS ===== -->
  <div class="panel" id="panel-users">
    <div class="sec-header">
      <span class="sec-title"><?= __('registered_users') ?></span>
      <span class="badge badge-info"><?= $nb_users ?> <?= __('user') ?>(s)</span>
    </div>
    <?php if (empty($users)): ?>
      <div class="table-wrap"><div class="empty"><div class="ico">👥</div><p><?= $lang === 'ar' ? 'لا يوجد مستخدمين.' : 'Aucun utilisateur.' ?></p></div></div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th><?= __('name') ?></th><th><?= __('email') ?></th><th><?= __('role') ?></th><th><?= __('action') ?></th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td style="color:var(--muted)"><?= $u['id'] ?></td>
            <td><strong><?= htmlspecialchars($u['nom']) ?></strong></td>
            <td style="color:var(--muted);font-size:.83rem;"><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge badge-user">👤 <?= __('user') ?></span></td>
            <td>
              <a href="admin_dashboard.php?del_user=<?= $u['id'] ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('<?= $lang === 'ar' ? 'حذف هذا المستخدم وجميع حجوزاته؟' : 'Supprimer cet utilisateur et toutes ses réservations ?' ?>')">
                 🗑️ <?= __('delete') ?>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ===== PANEL: ADMINISTRATEURS ===== -->
  <div class="panel" id="panel-admins">
    <div class="sec-header">
      <span class="sec-title"><?= __('admin_management') ?></span>
      <span class="badge badge-admin"><?= $nb_admins ?> <?= __('admin') ?>(s)</span>
    </div>

    <!-- Add admin form -->
    <div class="form-card glass-card" style="margin-bottom:28px;max-width:460px;">
      <h3>➕ <?= __('add_admin') ?></h3>
      <form method="POST">
        <div class="form-group">
          <label class="form-label"><?= __('name') ?></label>
          <input type="text" name="admin_nom" class="form-control" placeholder="<?= $lang === 'ar' ? 'مثال: أحمد' : 'Ex: Ahmed' ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label"><?= __('email') ?></label>
          <input type="email" name="admin_email" class="form-control" placeholder="admin@example.com" required>
        </div>
        <div class="form-group">
          <label class="form-label"><?= __('password') ?></label>
          <input type="password" name="admin_mdp" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" name="add_admin" class="btn btn-primary btn-full">➕ <?= __('add_admin') ?></button>
      </form>
    </div>

    <!-- Admins table -->
    <div class="sec-header">
      <span class="sec-title"><?= $lang === 'ar' ? 'قائمة المشرفين' : 'Liste des administrateurs' ?></span>
      <span class="badge badge-admin"><?= $nb_admins ?></span>
    </div>
    <?php if (empty($admins)): ?>
      <div class="table-wrap"><div class="empty"><div class="ico">⚡</div><p><?= $lang === 'ar' ? 'لا يوجد مشرفين.' : 'Aucun administrateur.' ?></p></div></div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th><?= __('name') ?></th><th><?= __('email') ?></th><th><?= __('status') ?></th><th><?= __('action') ?></th></tr></thead>
        <tbody>
          <?php foreach ($admins as $a): ?>
          <tr>
            <td style="color:var(--muted)"><?= $a['id'] ?></td>
            <td><strong><?= htmlspecialchars($a['nom']) ?></strong></td>
            <td style="color:var(--muted);font-size:.83rem;"><?= htmlspecialchars($a['email']) ?></td>
            <td>
              <?php if ((int)$a['id'] === (int)$_SESSION['user_id']): ?>
                <span class="badge" style="background:rgba(0,212,170,.12);color:#06b6d4;border:1px solid rgba(0,212,170,.25);">
                  👤 <?= __('you_current') ?>
                </span>
              <?php else: ?>
                <span class="badge badge-admin">⚡ <?= __('admin') ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ((int)$a['id'] !== (int)$_SESSION['user_id']): ?>
                <a href="admin_dashboard.php?del_admin=<?= $a['id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('<?= $lang === 'ar' ? 'حذف هذا المشرف؟' : 'Supprimer cet administrateur ?' ?>')">
                   🗑️ <?= __('delete') ?>
                </a>
              <?php else: ?>
                <span style="color:var(--muted);font-size:.78rem;">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</main>

<script src="script.js"></script>
<script>
const btns   = document.querySelectorAll('.tab-btn');
const panels = document.querySelectorAll('.panel');
const links  = document.querySelectorAll('.tab-link');

function activateTab(tab) {
  btns.forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
  panels.forEach(p => p.classList.toggle('active', p.id === 'panel-' + tab));
  links.forEach(a => a.classList.toggle('active', a.dataset.tab === tab));
}

btns.forEach(b => b.addEventListener('click', () => activateTab(b.dataset.tab)));
links.forEach(a => a.addEventListener('click', e => { e.preventDefault(); activateTab(a.dataset.tab); }));

<?php if ($salle_edit): ?>activateTab('salles');<?php endif; ?>
</script>
</body>
</html>