<?php
// admin_super/dashboard.php
require_once '../config/app.php';
requireSuperAdmin();

$page_title = 'Dashboard Super Admin';
include '../includes/sidebar_super_admin.php';

$pdo = getPDO();

// ============================================
// STATISTIQUES GLOBALES
// ============================================

// Nombre total d'utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['users'] = $stmt->fetch()['total'];

// Nombre d'utilisateurs actifs
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
$stats['users_active'] = $stmt->fetch()['total'];

// Nombre d'utilisateurs inactifs
$stats['users_inactive'] = $stats['users'] - $stats['users_active'];

// Nombre de catégories
$stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
$stats['categories'] = $stmt->fetch()['total'];

// Nombre de produits
$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits");
$stats['products'] = $stmt->fetch()['total'];

// Valeur totale du stock
$stmt = $pdo->query("SELECT SUM(prix) as total FROM produits");
$stats['stock_value'] = $stmt->fetch()['total'] ?? 0;

// Nombre de super admins
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'super_admin'");
$stats['super_admins'] = $stmt->fetch()['total'];

// Nombre d'admins
$stats['admins'] = $stats['users'] - $stats['super_admins'];

// ============================================
// DONNÉES RÉCENTES
// ============================================

// Derniers utilisateurs inscrits
$stmt = $pdo->query("
    SELECT id, username, email, role, created_at, is_active
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_users = $stmt->fetchAll();

// Dernières catégories ajoutées
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as produits_count
    FROM categories c
    LEFT JOIN produits p ON c.id = p.categorie_id
    GROUP BY c.id
    ORDER BY c.id DESC 
    LIMIT 5
");
$recent_categories = $stmt->fetchAll();

// Derniers produits ajoutés avec leur catégorie
$stmt = $pdo->query("
    SELECT p.*, c.nomcat 
    FROM produits p
    LEFT JOIN categories c ON p.categorie_id = c.id
    ORDER BY p.id DESC 
    LIMIT 5
");
$recent_products = $stmt->fetchAll();

// Top 5 produits les plus chers
$stmt = $pdo->query("
    SELECT p.*, c.nomcat 
    FROM produits p
    LEFT JOIN categories c ON p.categorie_id = c.id
    ORDER BY p.prix DESC 
    LIMIT 5
");
$top_products = $stmt->fetchAll();
?>

<style>
    /* Stats Grid Modern */
    .stats-dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card-modern {
        background: white;
        border-radius: 15px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .stat-card-modern:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .stat-left {
        flex: 1;
    }
    
    .stat-left h4 {
        font-size: 12px;
        color: #666;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-number-large {
        font-size: 28px;
        font-weight: bold;
        color: #2a5298;
    }
    
    .stat-sub {
        font-size: 11px;
        color: #999;
        margin-top: 5px;
    }
    
    .stat-icon-modern {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    /* Section Title */
    .section-title {
        font-size: 18px;
        font-weight: 600;
        margin: 25px 0 15px 0;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-title i {
        color: #2a5298;
    }
    
    /* Table Styles */
    .table-responsive-modern {
        overflow-x: auto;
    }
    
    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .modern-table th {
        padding: 12px;
        text-align: left;
        background: #f8f9fa;
        color: #555;
        font-weight: 600;
        font-size: 12px;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .modern-table td {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .modern-table tr:hover {
        background: #f8f9ff;
    }
    
    /* Badges */
    .badge-role-super {
        background: #ff4757;
        color: white;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
    }
    
    .badge-role-admin {
        background: #00d2d3;
        color: white;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
    }
    
    .badge-active {
        background: #d4edda;
        color: #155724;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
    }
    
    .badge-inactive {
        background: #f8d7da;
        color: #721c24;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
    }
    
    /* Price Tag */
    .price-tag-dash {
        font-weight: bold;
        color: #28a745;
    }
    
    /* Quick Actions */
    .quick-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    
    .quick-action-btn {
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .quick-action-btn:hover {
        transform: translateY(-2px);
    }
    
    /* Welcome Section */
    .welcome-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 25px;
        color: white;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .welcome-text h2 {
        font-size: 24px;
        margin-bottom: 5px;
    }
    
    .welcome-text p {
        opacity: 0.9;
        font-size: 14px;
    }
    
    .date-info {
        background: rgba(255,255,255,0.2);
        padding: 10px 20px;
        border-radius: 10px;
        text-align: center;
    }
    
    .date-info .day {
        font-size: 20px;
        font-weight: bold;
    }
    
    .date-info .date {
        font-size: 12px;
        opacity: 0.9;
    }
</style>

<!-- Welcome Section -->
<div class="welcome-section">
    <div class="welcome-text">
        <h2>Bonjour, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Super Admin') ?> !</h2>
        <p>Bienvenue dans votre espace d'administration. Voici un aperçu de votre plateforme.</p>
    </div>
    <div class="date-info">
        <div class="day"><?= date('d') ?></div>
        <div class="date"><?= date('F Y') ?></div>
    </div>
</div>

<!-- Statistiques -->
<div class="stats-dashboard-grid">
    <div class="stat-card-modern" onclick="window.location.href='users/index.php'">
        <div class="stat-left">
            <h4><i class="fas fa-users"></i> Utilisateurs</h4>
            <div class="stat-number-large"><?= $stats['users'] ?></div>
            <div class="stat-sub">
                <?= $stats['users_active'] ?> actifs | <?= $stats['users_inactive'] ?> inactifs
            </div>
        </div>
        <div class="stat-icon-modern" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <i class="fas fa-users"></i>
        </div>
    </div>
    
    <div class="stat-card-modern" onclick="window.location.href='users/index.php'">
        <div class="stat-left">
            <h4><i class="fas fa-crown"></i> Super Admins</h4>
            <div class="stat-number-large"><?= $stats['super_admins'] ?></div>
            <div class="stat-sub"><?= $stats['admins'] ?> Admins</div>
        </div>
        <div class="stat-icon-modern" style="background: linear-gradient(135deg, #ff4757 0%, #ff6b81 100%);">
            <i class="fas fa-crown"></i>
        </div>
    </div>
    
    <div class="stat-card-modern" onclick="window.location.href='categories/index.php'">
        <div class="stat-left">
            <h4><i class="fas fa-tags"></i> Catégories</h4>
            <div class="stat-number-large"><?= $stats['categories'] ?></div>
            <div class="stat-sub">Organisation du catalogue</div>
        </div>
        <div class="stat-icon-modern" style="background: linear-gradient(135deg, #00d2d3 0%, #48dbfb 100%);">
            <i class="fas fa-tags"></i>
        </div>
    </div>
    
    <div class="stat-card-modern" onclick="window.location.href='products/index.php'">
        <div class="stat-left">
            <h4><i class="fas fa-box"></i> Produits</h4>
            <div class="stat-number-large"><?= $stats['products'] ?></div>
            <div class="stat-sub">Valeur: <?= number_format($stats['stock_value'], 0, ',', ' ') ?> FCFA</div>
        </div>
        <div class="stat-icon-modern" style="background: linear-gradient(135deg, #28a745 0%, #34ce57 100%);">
            <i class="fas fa-box"></i>
        </div>
    </div>
</div>

<!-- Section: Dernières activités -->
<div class="section-title">
    <i class="fas fa-history"></i> Dernières activités
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <!-- Derniers utilisateurs -->
    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-user-plus"></i> Derniers utilisateurs</span>
            <a href="users/index.php" class="btn btn-primary btn-sm">Voir tout →</a>
        </div>
        <div class="table-responsive-modern">
            <?php if(count($recent_users) > 0): ?>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_users as $user): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                            <small style="color:#999;"><?= htmlspecialchars($user['email']) ?></small>
                        </td>
                        <td>
                            <?php if($user['role'] == 'super_admin'): ?>
                                <span class="badge-role-super">👑 Super Admin</span>
                            <?php else: ?>
                                <span class="badge-role-admin">👤 Admin</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($user['is_active']): ?>
                                <span class="badge-active">✅ Actif</span>
                            <?php else: ?>
                                <span class="badge-inactive">❌ Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">Aucun utilisateur</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Derniers produits -->
    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-box"></i> Derniers produits</span>
            <a href="products/index.php" class="btn btn-primary btn-sm">Voir tout →</a>
        </div>
        <div class="table-responsive-modern">
            <?php if(count($recent_products) > 0): ?>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_products as $product): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($product['nomp']) ?></strong></td>
                        <td>
                            <?php if($product['nomcat']): ?>
                                <span style="background:#e7f3ff; padding:3px 10px; border-radius:20px; font-size:11px;">
                                    <?= htmlspecialchars($product['nomcat']) ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#999;">Non catégorisé</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="price-tag-dash"><?= number_format($product['prix'], 0, ',', ' ') ?> FCFA</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">Aucun produit</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Section: Top produits -->
<div class="section-title">
    <i class="fas fa-chart-line"></i> Produits les plus chers
</div>

<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <span><i class="fas fa-trophy"></i> Top 5 des produits par prix</span>
        <a href="products/index.php" class="btn btn-primary btn-sm">Gérer les produits →</a>
    </div>
    <div class="table-responsive-modern">
        <?php if(count($top_products) > 0): ?>
        <table class="modern-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach($top_products as $product): ?>
                <tr>
                    <td style="width: 50px;">
                        <?php if($rank == 1): ?>
                            🥇
                        <?php elseif($rank == 2): ?>
                            🥈
                        <?php elseif($rank == 3): ?>
                            🥉
                        <?php else: ?>
                            #<?= $rank ?>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($product['nomp']) ?></strong></td>
                    <td><?= htmlspecialchars($product['nomcat'] ?? 'Non catégorisé') ?></td>
                    <td><span class="price-tag-dash"><?= number_format($product['prix'], 0, ',', ' ') ?> FCFA</span></td>
                    <td>
                        <a href="products/edit.php?id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                <?php $rank++; endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; color: #999; padding: 40px;">Aucun produit disponible</p>
        <?php endif; ?>
    </div>
</div>

<!-- Actions rapides -->
<div class="section-title">
    <i class="fas fa-bolt"></i> Actions rapides
</div>

<div class="card">
    <div class="quick-actions">
        <a href="users/add.php" class="quick-action-btn" style="background: #2a5298; color: white;">
            <i class="fas fa-user-plus"></i> Ajouter un utilisateur
        </a>
        <a href="categories/add.php" class="quick-action-btn" style="background: #00d2d3; color: white;">
            <i class="fas fa-tag"></i> Ajouter une catégorie
        </a>
        <a href="products/add.php" class="quick-action-btn" style="background: #28a745; color: white;">
            <i class="fas fa-box"></i> Ajouter un produit
        </a>
        <a href="users/index.php" class="quick-action-btn" style="background: #ffc107; color: #333;">
            <i class="fas fa-users"></i> Gérer les utilisateurs
        </a>
        <a href="products/index.php" class="quick-action-btn" style="background: #6c757d; color: white;">
            <i class="fas fa-boxes"></i> Gérer les produits
        </a>
    </div>
</div>

<!-- Informations système -->




<?php include '../includes/footer.php'; ?>