<?php
// ============================================================
//  config.php — Production ready (ezyro.com)
//  ⚠️  لا ترفع هذا الملف على GitHub أو أي مكان عام
// ============================================================

// ── إعدادات قاعدة البيانات ──────────────────────────────────
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // بيئة محلية
    $host     = "localhost";
    $dbname   = "gestionlesall";
    $username = "root";
    $password = "";
} else {
    // بيئة الإنتاج — ezyro.com
    $host     = "sql305.ezyro.com";
    $dbname   = "ezyro_41948910_gestionlesall";
    $username = "ezyro_41948910";
    $password = "YOUR_VPANEL_PASSWORD"; // ← ضع كلمة سر الـ vPanel هنا
}

// ── الاتصال بقاعدة البيانات ──────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
} catch (PDOException $e) {
    // في production: لا تعرض تفاصيل الخطأ للمستخدم
    if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
        die("Erreur DB : " . $e->getMessage());
    } else {
        error_log("DB Connection Error: " . $e->getMessage());
        die("Une erreur est survenue. Veuillez réessayer plus tard.");
    }
}

// ── Session آمنة ─────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly'  => true,
        'cookie_samesite'  => 'Lax',
        'cookie_secure'    => isset($_SERVER['HTTPS']), // HTTPS تلقائياً
        'gc_maxlifetime'   => 3600, // ساعة واحدة
    ]);
}

// ── إعداد اللغة ──────────────────────────────────────────────
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'ar'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang         = $_SESSION['lang'] ?? 'fr';
$translations = require __DIR__ . '/lang.php';

function __($key): string {
    global $lang, $translations;
    return $translations[$lang][$key] ?? $translations['fr'][$key] ?? $key;
}

// ── ثوابت عامة ───────────────────────────────────────────────
define('ADMIN_EMAIL', 'mohasbt77@gmail.com');

// ── حماية CSRF ───────────────────────────────────────────────
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): void {
    if (empty($token)
        || empty($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        die("Erreur de sécurité : Token CSRF invalide. <a href='javascript:history.back()'>Retour</a>");
    }
}

// ── Helpers للصلاحيات ────────────────────────────────────────
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

function requireUser(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        header('Location: admin_dashboard.php');
        exit;
    }
}
