<?php
// ============================================================
//  config.php — database connection settings
// ============================================================

// ── إعدادات قاعدة البيانات ──────────────────────────────────
if (in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)) {
    // بيئة محلية
    $host     = "localhost";
    $port     = 3306;
    $dbname   = "gestionlesall";
    $username = "root";
    $password = "";
} else {
    // Set these in the deployment environment; never store production credentials in source.
    $host     = getenv('DB_HOST') ?: '';
    $portValue = getenv('DB_PORT') ?: '';
    $dbname   = getenv('DB_NAME') ?: '';
    $username = getenv('DB_USER') ?: '';
    $password = getenv('DB_PASSWORD') ?: '';
    $port     = filter_var($portValue, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 65535],
    ]);

    if ($host === '' || $dbname === '' || $username === '' || $password === '' || $port === false) {
        error_log('Database configuration error: set DB_HOST, DB_PORT, DB_NAME, DB_USER, and DB_PASSWORD.');
        http_response_code(500);
        die("Une erreur est survenue. Veuillez réessayer plus tard.");
    }
}

// ── الاتصال بقاعدة البيانات ──────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
} catch (PDOException $e) {
    // في production: لا تعرض تفاصيل الخطأ للمستخدم
    if (in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)) {
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
