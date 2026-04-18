<?php
// includes/sidebar_super_admin.php
// Vérifier que l'utilisateur est connecté et est super admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: /inapp-1.0.0/src/login.php');
    exit();
}

// Pour la navigation active
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Connexion PDO pour compter les catégories en attente
$pdo = getPDO();
$stmt = $pdo->query("SELECT COUNT(*) as pending FROM categories WHERE status = 'pending'");
$pending_count = $stmt->fetch()['pending'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - <?= APP_NAME ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }

        /* Sidebar Super Admin */
        .sidebar-super {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            transition: all 0.3s;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-super::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-super::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }

        .sidebar-super::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 5px;
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h3 {
            font-size: 22px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 11px;
            opacity: 0.8;
        }

        .user-info-sidebar {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 40px;
        }

        .user-name-sidebar {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-role-sidebar {
            font-size: 12px;
            opacity: 0.8;
        }

        .badge-super {
            background: #ff4757;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
            display: inline-block;
            margin-top: 5px;
        }

        /* Badge pour les notifications */
        .nav-badge {
            background: #ffc107;
            color: #333;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: bold;
            margin-left: auto;
        }

        .nav-badge-pending {
            background: #ffc107;
            color: #333;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-section {
            padding: 0 20px;
            margin-bottom: 25px;
        }

        .nav-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.6;
            margin-bottom: 12px;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 10px;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            padding-left: 25px;
        }

        .nav-link.active {
            background: rgba(255,255,255,0.2);
        }

        .nav-link i {
            width: 25px;
            margin-right: 15px;
            font-size: 18px;
        }

        .nav-link span {
            font-size: 14px;
        }

        /* Main Content */
        .main-content-super {
            margin-left: 280px;
            min-height: 100vh;
        }

        .top-bar-super {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 24px;
            color: #333;
        }

        .page-title {
            font-size: 24px;
            color: #333;
            font-weight: 600;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        .content-wrapper {
            padding: 30px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .card-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e0e0e0;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        /* Buttons */
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #2a5298;
            color: white;
        }

        .btn-primary:hover {
            background: #1e3c72;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #2a5298;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #2a5298;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        /* Alerts */
        .alert {
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar-super {
                left: -280px;
            }
            .sidebar-super.active {
                left: 0;
            }
            .main-content-super {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
            }
            .content-wrapper {
                padding: 15px;
            }
            .page-title {
                font-size: 18px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar-super" id="sidebar">
        <div class="sidebar-header">
            <h3><?= APP_NAME ?></h3>
            <p>Panel Super Admin</p>
        </div>
        
        <div class="user-info-sidebar">
            <div class="user-avatar">
                <i class="fas fa-crown"></i>
            </div>
            <div class="user-name-sidebar"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Super Admin') ?></div>
            <div class="user-role-sidebar">
                <span class="badge-super">👑 Super Admin</span>
            </div>
        </div>
        
        <div class="sidebar-nav">
            <!-- Dashboard -->
            <div class="nav-section">
                <div class="nav-section-title">Tableau de bord</div>
                <div class="nav-item">
                    <a href="/inapp-1.0.0/src/admin_super/dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>
            
            <!-- Gestion des utilisateurs -->
            <div class="nav-section">
                <div class="nav-section-title">👥 Utilisateurs</div>
                <div class="nav-item">
                    <a href="/inapp-1.0.0/src/admin_super/users/index.php" class="nav-link <?= $current_dir == 'users' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        <span>Liste des utilisateurs</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/inapp-1.0.0/src/admin_super/users/add.php" class="nav-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Ajouter un utilisateur</span>
                    </a>
                </div>
            </div>
            
            <!-- Gestion des catégories -->
            <div class="nav-section">
                <div class="nav-section-title">🏷️ Catégories</div>
                <div class="nav-item">
                    <a href="/inapp-1.0.0/src/admin_super/categories/index.php" class="nav-link <?= $current_dir == 'categories' && $current_page != 'pending.php' ? 'active' : '' ?>">
                        <i class="fas fa-tags"></i>
                        <span>Liste des catégories</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/inapp-1.0.0/src/admin_super/categories/add.php" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>Ajouter une catégorie</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/inapp-1.0.0/src/admin_super/categories/pending.php" class="nav-link <?= $current_page == 'pending.php' ? 'active' : '' ?>">
                        <i class="fas fa-clock"></i>
                        <span>Validation en attente</span>
                        <?php if($pending_count > 0): ?>
                            <span class="nav-badge nav-badge-pending"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            
            <!-- Gestion des produits -->
            <div class="nav-section">
                <div class="nav-section-title">📦 Produits</div>
                <div class="nav-item">
                    <a href="/inapp-1.0.0/src/admin_super/products/index.php" class="nav-link <?= $current_dir == 'products' ? 'active' : '' ?>">
                        <i class="fas fa-box"></i>
                        <span>Liste des produits</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/inapp-1.0.0/src/admin_super/products/add.php" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>Ajouter un produit</span>
                    </a>
                </div>
            </div>
            
            <!-- Statistiques -->
            <div class="nav-section">
                <div class="nav-section-title">📊 Analyses</div>
                <div class="nav-item">
                    <a href="/inapp-1.0.0/src/admin_super/statistiques/index.php" class="nav-link <?= $current_dir == 'statistiques' ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Statistiques</span>
                    </a>
                </div>
            </div>
            
            <!-- Déconnexion -->
            <div class="nav-section">
                <div class="nav-item">
                    <a href="/inapp-1.0.0/src/logout.php" class="nav-link" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="main-content-super">
        <div class="top-bar-super">
            <div class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </div>
            <div class="page-title"><?= $page_title ?? 'Super Admin Panel' ?></div>
            <div class="top-bar-right">
                <span><i class="fas fa-clock"></i> <?= date('d/m/Y H:i') ?></span>
                <a href="/inapp-1.0.0/src/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
        <div class="content-wrapper">

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

// Fermer le sidebar au clic en dehors sur mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.querySelector('.menu-toggle');
    if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
        if (menuToggle && !menuToggle.contains(event.target) && !sidebar.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    }
});
</script>