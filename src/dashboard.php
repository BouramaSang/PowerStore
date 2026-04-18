<?php
// dashboard.php
require_once 'config/app.php';
requireAuth();

$pdo = getPDO();

// Récupération des statistiques avec PDO
$stats = [];

// Nombre de clients
$stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
$stats['clients'] = $stmt->fetch()['total'];

// Nombre de produits
$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits");
$stats['produits'] = $stmt->fetch()['total'];

// Nombre de factures
$stmt = $pdo->query("SELECT COUNT(*) as total FROM factures");
$stats['factures'] = $stmt->fetch()['total'];

// Chiffre d'affaires total
$stmt = $pdo->query("
    SELECT SUM(p.prix) as total 
    FROM commandes c 
    JOIN produits p ON c.produit_id = p.id
");
$stats['ca'] = $stmt->fetch()['total'] ?? 0;

// Dernières commandes
$stmt = $pdo->query("
    SELECT c.id, cl.nomc, cl.prenom, p.nomp, p.prix, f.datef
    FROM commandes c
    JOIN clients cl ON c.client_id = cl.id
    JOIN produits p ON c.produit_id = p.id
    JOIN factures f ON c.facture_id = f.id
    ORDER BY f.datef DESC
    LIMIT 5
");
$recentCommands = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0 30px;
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-details {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-role {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-super-admin {
            background: #ff4757;
            color: white;
        }
        
        .badge-admin {
            background: #00d2d3;
            color: white;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        /* Welcome Card */
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .welcome-card h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #666;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #999;
            font-size: 12px;
        }
        
        /* Menu Grid */
        .section-title {
            margin: 30px 0 20px;
            color: #333;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .menu-item {
            background: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 15px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: block;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .menu-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .menu-item h4 {
            margin-bottom: 5px;
        }
        
        .menu-item p {
            font-size: 12px;
            color: #999;
        }
        
        /* Recent Commands */
        .recent-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .recent-section h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .command-list {
            overflow-x: auto;
        }
        
        .command-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .command-item:last-child {
            border-bottom: none;
        }
        
        .command-info h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .command-info p {
            color: #999;
            font-size: 12px;
        }
        
        .command-price {
            font-weight: bold;
            color: #667eea;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 0 20px;
            }
            
            .container {
                padding: 0 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <h1><?= APP_NAME ?></h1>
        </div>
        <div class="user-info">
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                <div class="user-role">
                    <span class="badge <?= isSuperAdmin() ? 'badge-super-admin' : 'badge-admin' ?>">
                        <?= isSuperAdmin() ? 'Super Admin' : 'Admin' ?>
                    </span>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <h2>Bonjour, <?= htmlspecialchars($_SESSION['user_name']) ?> !</h2>
            <p>Bienvenue dans votre espace de gestion. Voici un aperçu de votre activité.</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <h3>Clients</h3>
                <div class="stat-number"><?= number_format($stats['clients'], 0, ',', ' ') ?></div>
                <div class="stat-label">clients enregistrés</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <h3>Produits</h3>
                <div class="stat-number"><?= number_format($stats['produits'], 0, ',', ' ') ?></div>
                <div class="stat-label">produits disponibles</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <h3>Factures</h3>
                <div class="stat-number"><?= number_format($stats['factures'], 0, ',', ' ') ?></div>
                <div class="stat-label">factures émises</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <h3>Chiffre d'affaires</h3>
                <div class="stat-number"><?= number_format($stats['ca'], 0, ',', ' ') ?> FCFA</div>
                <div class="stat-label">total des ventes</div>
            </div>
        </div>
        
        <h3 class="section-title">Modules de gestion</h3>
        <div class="menu-grid">
            <a href="clients.php" class="menu-item">
                <div class="menu-icon">👥</div>
                <h4>Gestion Clients</h4>
                <p>Ajouter, modifier, supprimer des clients</p>
            </a>
            
            <a href="produits.php" class="menu-item">
                <div class="menu-icon">📦</div>
                <h4>Gestion Produits</h4>
                <p>Gérer le catalogue produits</p>
            </a>
            
            <a href="factures.php" class="menu-item">
                <div class="menu-icon">📄</div>
                <h4>Gestion Factures</h4>
                <p>Créer et gérer les factures</p>
            </a>
            
            <?php if(isSuperAdmin()): ?>
            <a href="users.php" class="menu-item">
                <div class="menu-icon">👤</div>
                <h4>Gestion Utilisateurs</h4>
                <p>Gérer les accès et permissions</p>
            </a>
            <?php endif; ?>
        </div>
        
        <?php if(!empty($recentCommands)): ?>
        <div class="recent-section">
            <h3>📋 Dernières commandes</h3>
            <div class="command-list">
                <?php foreach($recentCommands as $command): ?>
                <div class="command-item">
                    <div class="command-info">
                        <h4><?= htmlspecialchars($command['nomp']) ?></h4>
                        <p>Client: <?= htmlspecialchars($command['nomc'] . ' ' . $command['prenom']) ?> | 
                           Date: <?= date('d/m/Y H:i', strtotime($command['datef'])) ?></p>
                    </div>
                    <div class="command-price">
                        <?= number_format($command['prix'], 0, ',', ' ') ?> FCFA
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>