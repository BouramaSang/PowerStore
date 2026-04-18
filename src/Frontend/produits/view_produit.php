<?php
// src/Frontend/produits/view_produit.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: index_produit.php?error=ID invalide');
    exit();
}

// Récupérer le produit avec ses informations
$stmt = $pdo->prepare("
    SELECT p.*, c.nomcat as categorie_nom, u.username as createur_nom
    FROM produits p
    LEFT JOIN categories c ON p.categorie_id = c.id
    LEFT JOIN users u ON p.created_by = u.id
    WHERE p.id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index_produit.php?error=Produit non trouvé');
    exit();
}

// Calculer la valeur totale du stock pour ce produit
$total_value = ($product['prix'] ?? 0) * ($product['quantite'] ?? 0);

$page_title = 'Détails du produit';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['nomp']) ?> - InApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .product-detail-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .product-image {
            width: 100%;
            height: 300px;
            object-fit: contain;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .product-info {
            padding: 30px;
        }
        
        .product-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 10px;
        }
        
        .product-price {
            font-size: 24px;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .product-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
            font-size: 14px;
        }
        
        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .stock-high {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .stock-low {
            background: #f8d7da;
            color: #721c24;
        }
        
        .stock-stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .stock-stat-item {
            flex: 1;
            text-align: center;
        }
        
        .stock-stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }
        
        .stock-stat-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        .product-description {
            margin-top: 20px;
        }
        
        .product-description h4 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .product-description p {
            color: #555;
            line-height: 1.6;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 10px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 10px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
        }
        
        .badge-category {
            background: #e7f3ff;
            color: #2196f3;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        
        .divider {
            margin: 20px 0;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>

<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        <div class="product-detail-container">
            <div class="product-card">
                <div class="row g-0">
                    <div class="col-md-5">
                        <?php if($product['image']): ?>
                            <img src="../../<?= htmlspecialchars($product['image']) ?>" class="product-image" alt="<?= htmlspecialchars($product['nomp']) ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/400x300?text=Pas+d'image" class="product-image" alt="Image par défaut">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-7">
                        <div class="product-info">
                            <h1 class="product-title"><?= htmlspecialchars($product['nomp']) ?></h1>
                            <div class="product-price"><?= number_format($product['prix'], 0, ',', ' ') ?> FCFA</div>
                            
                            <div class="product-meta">
                                <div class="meta-item">
                                    <i class="ti ti-id"></i>
                                    <span>ID: #<?= $product['id'] ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="ti ti-category"></i>
                                    <span>Catégorie: <?= htmlspecialchars($product['categorie_nom'] ?? 'Non catégorisé') ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="ti ti-user"></i>
                                    <span>Créé par: <?= htmlspecialchars($product['createur_nom'] ?? 'Inconnu') ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="ti ti-calendar"></i>
                                    <span>Créé le: <?= date('d/m/Y à H:i', strtotime($product['created_at'])) ?></span>
                                </div>
                                <?php if($product['updated_at']): ?>
                                <div class="meta-item">
                                    <i class="ti ti-edit"></i>
                                    <span>Modifié le: <?= date('d/m/Y à H:i', strtotime($product['updated_at'])) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Section Stock -->
                            <div class="stock-stats">
                                <div class="stock-stat-item">
                                    <div class="stock-stat-value"><?= $product['quantite'] ?? 0 ?></div>
                                    <div class="stock-stat-label">Unités en stock</div>
                                </div>
                                <div class="stock-stat-item">
                                    <div class="stock-stat-value"><?= number_format($total_value, 0, ',', ' ') ?> FCFA</div>
                                    <div class="stock-stat-label">Valeur du stock</div>
                                </div>
                                <div class="stock-stat-item">
                                    <div class="stock-stat-value">
                                        <?php 
                                        $stock = $product['quantite'] ?? 0;
                                        if($stock <= 0): ?>
                                            <span class="stock-badge stock-low">
                                                <i class="ti ti-alert-triangle"></i> Rupture
                                            </span>
                                        <?php elseif($stock < 5): ?>
                                            <span class="stock-badge stock-medium">
                                                <i class="ti ti-alert-circle"></i> Stock faible
                                            </span>
                                        <?php else: ?>
                                            <span class="stock-badge stock-high">
                                                <i class="ti ti-check"></i> Stock suffisant
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="stock-stat-label">Statut</div>
                                </div>
                            </div>
                            
                            <?php if($product['description']): ?>
                            <div class="product-description">
                                <h4>Description</h4>
                                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="divider"></div>
                            
                            <div class="action-buttons">
                                <a href="edit_produit.php?id=<?= $product['id'] ?>" class="btn-edit">
                                    <i class="ti ti-edit"></i> Modifier
                                </a>
                                <a href="index_produit.php" class="btn-back">
                                    <i class="ti ti-arrow-left"></i> Retour
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>