<?php
// config/app.php
session_start();

// Configuration de l'application
define('APP_NAME', 'Système de Facturation');
define('APP_URL', 'http://localhost/facturation');
define('APP_TIMEZONE', 'Africa/Bamako');

date_default_timezone_set(APP_TIMEZONE);

// Chargement automatique des classes
spl_autoload_register(function ($class) {
    $prefix = 'Facturation\\';
    $base_dir = __DIR__ . '/../';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Inclusion de la connexion PDO
require_once __DIR__ . '/database.php';

// Fonctions d'authentification globales
function getPDO() {
    return \Facturation\Config\Database::getInstance()->getConnection();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'super_admin');
}

function isSuperAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'super_admin';
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function requireSuperAdmin() {
    requireAuth();
    if (!isSuperAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
    // Récupérer les catégories rejetées pour l'utilisateur connecté
function getRejectedCategories($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as approved_by_name
        FROM categories c
        LEFT JOIN users u ON c.approved_by = u.id
        WHERE c.created_by = ? AND c.status = 'rejected'
        ORDER BY c.id DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
}