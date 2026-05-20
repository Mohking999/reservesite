<?php
// ⚠️ احذف هذا الملف بعد التأكد من الاتصال!
$host     = "sql305.ezyro.com";
$dbname   = "ezyro_41948910_gestionlesall";
$username = "ezyro_41948910";
$password = "YOUR_VPANEL_PASSWORD"; // ← نفس كلمة سر الـ vPanel

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion réussie !";
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
