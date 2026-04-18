<?php
// admin_super/products/index.php
require_once '../../config/app.php';
requireSuperAdmin();

$pdo = getPDO();

// ============================================
// TRAITEMENT DES ACTIONS (AVANT AFFICHAGE)
// ============================================

// Vérifier les colonnes existantes
$columns = $pdo->query("SHOW COLUMNS FROM produits")->fetchAll(PDO::FETCH_COLUMN);
$has_quantite = in_array('quantite', $columns);
$has_description = in_array('description', $columns);
$has_image = in_array('image', $columns);

// Récupérer tous les produits avec leurs catégories
$stmt = $pdo->query("
    SELECT p.*, c.nomcat
    FROM produits p
    LEFT JOIN categories c ON p.categorie_id = c.id
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();

// Calculer les statistiques
$total_products = count($products);
$total_value = 0;
$total_quantity = 0;
$low_stock_count = 0;

foreach ($products as $product) {
    $quantity = $product['quantite'] ?? 0;
    $total_value += $product['prix'] * $quantity;
    $total_quantity += $quantity;
    if ($quantity < 5) {
        $low_stock_count++;
    }
}

// Récupérer les catégories pour le filtre
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nomcat");
$categories = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$page_title = 'Gestion des Produits';
include '../../includes/sidebar_super_admin.php';
?>

<style>
    /* Stats Cards */
    .stats-products {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-product-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s;
    }
    
    .stat-product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .stat-info h4 {
        font-size: 12px;
        color: #666;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-number {
        font-size: 28px;
        font-weight: bold;
        color: #2a5298;
    }
    
    .stat-icon-prod {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    /* Search Section */
    .search-section-prod {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .search-box-prod {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f8f9fa;
        padding: 8px 15px;
        border-radius: 50px;
        flex: 1;
        max-width: 350px;
    }
    
    .search-box-prod i {
        color: #999;
    }
    
    .search-box-prod input {
        border: none;
        background: none;
        padding: 8px 0;
        width: 100%;
        outline: none;
        font-size: 14px;
    }
    
    .filter-select {
        padding: 8px 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        background: white;
        cursor: pointer;
    }
    
    /* Table Styles */
    .table-container-prod {
        overflow-x: auto;
        border-radius: 15px;
    }
    
    .product-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 15px;
        overflow: hidden;
    }
    
    .product-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .product-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .product-table td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }
    
    .product-table tbody tr {
        transition: all 0.3s;
    }
    
    .product-table tbody tr:hover {
        background: #f8f9ff;
    }
    
    /* Product Badge */
    .product-badge {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .product-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
    }
    
    .product-info {
        display: flex;
        flex-direction: column;
    }
    
    .product-name {
        font-weight: 600;
        color: #333;
    }
    
    .price-tag {
        font-weight: bold;
        color: #28a745;
        font-size: 16px;
    }
    
    .stock-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
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
    
    .category-badge {
        display: inline-block;
        padding: 4px 12px;
        background: #e7f3ff;
        border-radius: 20px;
        font-size: 12px;
        color: #2196f3;
    }
    
    .category-badge-uncategorized {
        background: #f8d7da;
        color: #721c24;
    }
    
    .product-image-thumb {
        width: 45px;
        height: 45px;
        border-radius: 8px;
        object-fit: cover;
        background: #f8f9fa;
        border: 1px solid #e0e0e0;
    }
    
    /* Action Buttons */
    .action-buttons-prod {
        display: flex;
        gap: 8px;
        justify-content: center;
    }
    
    .btn-action-prod {
        padding: 6px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 12px;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-action-prod:hover {
        transform: translateY(-2px);
    }
    
    .btn-edit-prod {
        background: #ffc107;
        color: #333;
    }
    
    .btn-delete-prod {
        background: #dc3545;
        color: white;
    }
    
    /* Empty State */
    .empty-state-prod {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-icon-prod {
        font-size: 80px;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .product-row {
        animation: fadeInUp 0.3s ease forwards;
    }
    
    .alert {
        padding: 12px 20px;
        border-radius: 8px;
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
</style>

<!-- Statistiques Dashboard -->
<div class="stats-products">
    <div class="stat-product-card">
        <div class="stat-info">
            <h4><i class="fas fa-box"></i> Total Produits</h4>
            <div class="stat-number"><?= $total_products ?></div>
        </div>
        <div class="stat-icon-prod">
            <i class="fas fa-box"></i>
        </div>
    </div>
    
    <?php if($has_quantite): ?>
    <div class="stat-product-card">
        <div class="stat-info">
            <h4><i class="fas fa-boxes"></i> Quantité totale</h4>
            <div class="stat-number"><?= number_format($total_quantity, 0, ',', ' ') ?></div>
        </div>
        <div class="stat-icon-prod" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
            <i class="fas fa-boxes"></i>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="stat-product-card">
        <div class="stat-info">
            <h4><i class="fas fa-chart-line"></i> Valeur du stock</h4>
            <div class="stat-number"><?= number_format($total_value, 0, ',', ' ') ?> FCFA</div>
        </div>
        <div class="stat-icon-prod" style="background: linear-gradient(135deg, #28a745 0%, #34ce57 100%);">
            <i class="fas fa-chart-line"></i>
        </div>
    </div>
    
    <?php if($has_quantite): ?>
    <div class="stat-product-card">
        <div class="stat-info">
            <h4><i class="fas fa-exclamation-triangle"></i> Stock faible</h4>
            <div class="stat-number"><?= $low_stock_count ?></div>
        </div>
        <div class="stat-icon-prod" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Search Section -->
<div class="search-section-prod">
    <div class="search-box-prod">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Rechercher un produit..." onkeyup="filterProducts()">
        <i class="fas fa-times" id="clearSearch" style="cursor: pointer; display: none;" onclick="clearSearch()"></i>
    </div>
    <div>
        <select id="categoryFilter" class="filter-select" onchange="filterProducts()">
            <option value="all">Toutes les catégories</option>
            <?php foreach($categories as $cat): ?>
                <option value="<?= strtolower(htmlspecialchars($cat['nomcat'])) ?>"><?= htmlspecialchars($cat['nomcat']) ?></option>
            <?php endforeach; ?>
        </select>
        <a href="add.php" class="btn btn-success" style="margin-left: 10px;">
            <i class="fas fa-plus-circle"></i> Nouveau produit
        </a>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="card-header" style="padding: 20px; background: white; border-bottom: 1px solid #f0f0f0;">
        <div>
            <span><i class="fas fa-box" style="color: #2a5298;"></i> <strong>Liste des produits</strong></span>
            <span style="margin-left: 10px; font-size: 12px; color: #666;">
                <i class="fas fa-chart-line"></i> <?= $total_products ?> produit(s) au total
            </span>
        </div>
    </div>
    
    <?php if($success): ?>
        <div class="alert alert-success" style="margin: 20px;">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger" style="margin: 20px;">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="table-container-prod">
        <table class="product-table" id="productTable">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <?php if($has_image): ?>
                    <th style="width: 70px;">Image</th>
                    <?php endif; ?>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Prix (FCFA)</th>
                    <?php if($has_quantite): ?>
                    <th>Quantité</th>
                    <?php endif; ?>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <?php if(count($products) > 0): ?>
                    <?php foreach($products as $product): ?>
                    <tr class="product-row" 
                        data-name="<?= strtolower(htmlspecialchars($product['nomp'])) ?>"
                        data-category="<?= strtolower(htmlspecialchars($product['nomcat'] ?? '')) ?>">
                        <td style="font-weight: 600; color: #2a5298;">#<?= $product['id'] ?></td>
                        
                        <?php if($has_image): ?>
                        <td>
                            <?php if($product['image']): ?>
                                <img src="../../<?= htmlspecialchars($product['image']) ?>" class="product-image-thumb" alt="<?= htmlspecialchars($product['nomp']) ?>">
                            <?php else: ?>
                                <div class="product-image-thumb" style="display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                    <i class="fas fa-box" style="color: #ccc;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        
                        <td>
                            <div class="product-badge">
                                <div class="product-icon">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="product-info">
                                    <span class="product-name"><?= htmlspecialchars($product['nomp']) ?></span>
                                    <?php if($has_description && $product['description']): ?>
                                        <small class="text-secondary"><?= htmlspecialchars(substr($product['description'], 0, 40)) ?>...</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if($product['nomcat']): ?>
                                <span class="category-badge">
                                    <i class="fas fa-tag"></i> <?= htmlspecialchars($product['nomcat']) ?>
                                </span>
                            <?php else: ?>
                                <span class="category-badge category-badge-uncategorized">
                                    <i class="fas fa-exclamation-triangle"></i> Non catégorisé
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="price-tag">
                                <i class="fas fa-money-bill-wave"></i> <?= number_format($product['prix'], 0, ',', ' ') ?> FCFA
                            </span>
                        </td>
                        
                        <?php if($has_quantite): ?>
                        <td>
                            <?php 
                            $stock = $product['quantite'] ?? 0;
                            if($stock <= 0): ?>
                                <span class="stock-badge stock-low">
                                    <i class="fas fa-times-circle"></i> <?= $stock ?> unités
                                </span>
                            <?php elseif($stock < 5): ?>
                                <span class="stock-badge stock-medium">
                                    <i class="fas fa-exclamation-triangle"></i> <?= $stock ?> unités
                                </span>
                            <?php else: ?>
                                <span class="stock-badge stock-high">
                                    <i class="fas fa-check-circle"></i> <?= $stock ?> unités
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        
                        <td style="text-align: center;">
                            <div class="action-buttons-prod">
                                <a href="edit.php?id=<?= $product['id'] ?>" class="btn-action-prod btn-edit-prod" title="Modifier">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="delete.php?id=<?= $product['id'] ?>" class="btn-action-prod btn-delete-prod" title="Supprimer" 
                                   onclick="return confirm('Confirmer la suppression du produit &quot;<?= htmlspecialchars($product['nomp']) ?>&quot; ?')">
                                    <i class="fas fa-trash-alt"></i> Supprimer
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= ($has_image ? '7' : ($has_quantite ? '6' : '5')) ?>" style="text-align: center; padding: 60px;">
                            <div class="empty-icon-prod">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <h3>Aucun produit</h3>
                            <p style="color: #666; margin-top: 10px;">Commencez par créer votre premier produit.</p>
                            <a href="add.php" class="btn btn-success" style="margin-top: 20px;">
                                <i class="fas fa-plus-circle"></i> Créer un produit
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterProducts() {
    const searchInput = document.getElementById('searchInput');
    const searchFilter = searchInput ? searchInput.value.toLowerCase() : '';
    const categoryFilterSelect = document.getElementById('categoryFilter');
    const categoryFilter = categoryFilterSelect ? categoryFilterSelect.value.toLowerCase() : 'all';
    const rows = document.querySelectorAll('.product-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const category = row.getAttribute('data-category') || '';
        
        const matchSearch = name.includes(searchFilter);
        const matchCategory = categoryFilter === 'all' || category === categoryFilter;
        
        if (matchSearch && matchCategory) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const clearIcon = document.getElementById('clearSearch');
    if (clearIcon) {
        clearIcon.style.display = searchFilter.length > 0 ? 'inline-block' : 'none';
    }
}

function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        filterProducts();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>