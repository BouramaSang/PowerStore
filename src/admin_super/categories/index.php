<?php
// admin_super/categories/index.php
require_once '../../config/app.php';
requireSuperAdmin();

$page_title = 'Gestion des Catégories';
include '../../includes/sidebar_super_admin.php';

$pdo = getPDO();

// Vérifier les colonnes existantes
$columns = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_COLUMN);
$has_image = in_array('image', $columns);
$has_status = in_array('status', $columns);

// Récupérer toutes les catégories UNIQUES avec statistiques
$stmt = $pdo->query("
    SELECT DISTINCT c.id, c.nomcat, c.image, c.status,
           (SELECT COUNT(*) FROM produits WHERE categorie_id = c.id) as total_produits,
           (SELECT COALESCE(SUM(prix), 0) FROM produits WHERE categorie_id = c.id) as total_value
    FROM categories c 
    GROUP BY c.id
    ORDER BY c.id ASC
");
$categories = $stmt->fetchAll();

$total_categories = count($categories);

// Calculer la valeur totale du stock par catégorie (déjà fait dans la requête)
foreach ($categories as &$cat) {
    if (!isset($cat['total_value'])) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(prix), 0) as total FROM produits WHERE categorie_id = ?");
        $stmt->execute([$cat['id']]);
        $total_value = $stmt->fetch()['total'];
        $cat['total_value'] = $total_value;
    }
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<style>
    /* Stats Cards */
    .stats-categories {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-category-card {
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
    
    .stat-category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .stat-info h4 {
        font-size: 13px;
        color: #666;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-number {
        font-size: 32px;
        font-weight: bold;
        color: #2a5298;
    }
    
    .stat-icon-cat {
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
    
    /* Table Styles */
    .table-container-cat {
        overflow-x: auto;
        border-radius: 15px;
    }
    
    .category-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 15px;
        overflow: hidden;
    }
    
    .category-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .category-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .category-table td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }
    
    .category-table tbody tr {
        transition: all 0.3s;
    }
    
    .category-table tbody tr:hover {
        background: #f8f9ff;
    }
    
    /* Category Badge with Image */
    .category-badge-img {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .category-img {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        object-fit: cover;
        background: #f0f2f5;
    }
    
    .category-img-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
    }
    
    .category-info-cat {
        display: flex;
        flex-direction: column;
    }
    
    .category-name-cat {
        font-weight: 600;
        font-size: 16px;
        color: #333;
    }
    
    .category-id-cat {
        font-size: 11px;
        color: #999;
    }
    
    /* Status Badge */
    .status-badge-cat {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .status-approved {
        background: #d4edda;
        color: #155724;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }
    
    /* Products count */
    .products-count {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #e7f3ff;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        color: #2196f3;
    }
    
    .products-value {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #e8f5e9;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        color: #28a745;
    }
    
    /* Search Bar */
    .search-section-cat {
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
    
    .search-box-cat {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f8f9fa;
        padding: 8px 15px;
        border-radius: 50px;
        flex: 1;
        max-width: 350px;
    }
    
    .search-box-cat i {
        color: #999;
    }
    
    .search-box-cat input {
        border: none;
        background: none;
        padding: 8px 0;
        width: 100%;
        outline: none;
        font-size: 14px;
    }
    
    /* Empty State */
    .empty-state-cat {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-icon-cat {
        font-size: 80px;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    /* Action Buttons */
    .action-buttons-cat {
        display: flex;
        gap: 8px;
        justify-content: center;
    }
    
    .btn-action {
        padding: 8px 15px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 12px;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
    }
    
    .btn-edit-cat {
        background: #ffc107;
        color: #333;
    }
    
    .btn-edit-cat:hover {
        background: #e0a800;
    }
    
    .btn-delete-cat {
        background: #dc3545;
        color: white;
    }
    
    .btn-delete-cat:hover {
        background: #c82333;
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
    
    .category-row {
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
<div class="stats-categories">
    <div class="stat-category-card" onclick="window.location.href='index.php'">
        <div class="stat-info">
            <h4><i class="fas fa-tags"></i> Total Catégories</h4>
            <div class="stat-number"><?= $total_categories ?></div>
        </div>
        <div class="stat-icon-cat">
            <i class="fas fa-tags"></i>
        </div>
    </div>
    
    <div class="stat-category-card" onclick="window.location.href='../products/index.php'">
        <div class="stat-info">
            <h4><i class="fas fa-boxes"></i> Produits associés</h4>
            <div class="stat-number">
                <?php 
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM produits");
                $totalProducts = $stmt->fetch()['total'];
                echo $totalProducts;
                ?>
            </div>
        </div>
        <div class="stat-icon-cat" style="background: linear-gradient(135deg, #28a745 0%, #34ce57 100%);">
            <i class="fas fa-boxes"></i>
        </div>
    </div>
</div>

<!-- Search Section -->
<div class="search-section-cat">
    <div class="search-box-cat">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Rechercher une catégorie..." onkeyup="filterCategories()">
        <i class="fas fa-times" id="clearSearch" style="cursor: pointer; display: none;" onclick="clearSearch()"></i>
    </div>
    <div>
        <a href="add.php" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Nouvelle catégorie
        </a>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="card-header" style="padding: 20px; background: white; border-bottom: 1px solid #f0f0f0;">
        <div>
            <span><i class="fas fa-tags" style="color: #2a5298;"></i> <strong>Liste des catégories</strong></span>
            <span style="margin-left: 10px; font-size: 12px; color: #666;">
                <i class="fas fa-chart-line"></i> <?= $total_categories ?> catégorie(s) au total
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
    
    <div class="table-container-cat">
        <table class="category-table" id="categoryTable">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Catégorie</th>
                    <th>Produits</th>
                    <th>Valeur du stock</th>
                    <?php if($has_status): ?>
                    <th>Statut</th>
                    <?php endif; ?>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody id="categoryTableBody">
                <?php if(count($categories) > 0): ?>
                    <?php 
                    // Filtrer les doublons par ID
                    $displayed_ids = [];
                    foreach($categories as $cat): 
                        if (in_array($cat['id'], $displayed_ids)) continue;
                        $displayed_ids[] = $cat['id'];
                    ?>
                    <tr class="category-row" data-name="<?= strtolower(htmlspecialchars($cat['nomcat'])) ?>">
                        <td style="font-weight: 600; color: #2a5298;">#<?= $cat['id'] ?></td>
                        <td>
                            <div class="category-badge-img">
                                <?php if($has_image && !empty($cat['image'])): ?>
                                    <img src="../../<?= htmlspecialchars($cat['image']) ?>" class="category-img" alt="<?= htmlspecialchars($cat['nomcat']) ?>">
                                <?php else: ?>
                                    <div class="category-img-placeholder">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="category-info-cat">
                                    <span class="category-name-cat"><?= htmlspecialchars($cat['nomcat']) ?></span>
                                    <span class="category-id-cat">ID: <?= $cat['id'] ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="products-count">
                                <i class="fas fa-box"></i>
                                <?= $cat['total_produits'] ?? 0 ?> produit(s)
                            </span>
                        </td>
                        <td>
                            <span class="products-value">
                                <i class="fas fa-money-bill-wave"></i>
                                <?= number_format($cat['total_value'] ?? 0, 0, ',', ' ') ?> FCFA
                            </span>
                        </td>
                        <?php if($has_status): ?>
                        <td>
                            <?php if(isset($cat['status']) && $cat['status'] == 'approved'): ?>
                                <span class="status-badge-cat status-approved">
                                    <i class="fas fa-check-circle"></i> Validée
                                </span>
                            <?php elseif(isset($cat['status']) && $cat['status'] == 'pending'): ?>
                                <span class="status-badge-cat status-pending">
                                    <i class="fas fa-clock"></i> En attente
                                </span>
                            <?php elseif(isset($cat['status']) && $cat['status'] == 'rejected'): ?>
                                <span class="status-badge-cat status-rejected">
                                    <i class="fas fa-times-circle"></i> Rejetée
                                </span>
                            <?php else: ?>
                                <span class="status-badge-cat status-approved">
                                    <i class="fas fa-check-circle"></i> Validée
                                </span>
                            <?php endif; ?>
                          </td>
                        <?php endif; ?>
                        <td style="text-align: center;">
                            <div class="action-buttons-cat">
                                <a href="edit.php?id=<?= $cat['id'] ?>" class="btn-action btn-edit-cat" title="Modifier">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="delete.php?id=<?= $cat['id'] ?>" class="btn-action btn-delete-cat" title="Supprimer" 
                                   onclick="return confirmDelete('<?= htmlspecialchars($cat['nomcat']) ?>', <?= $cat['total_produits'] ?? 0 ?>)">
                                    <i class="fas fa-trash-alt"></i> Supprimer
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $has_status ? '6' : '5' ?>" style="text-align: center; padding: 60px;">
                            <div class="empty-state-cat">
                                <div class="empty-icon-cat">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <h3>Aucune catégorie</h3>
                                <p style="color: #666; margin-top: 10px;">Commencez par créer votre première catégorie.</p>
                                <a href="add.php" class="btn btn-success" style="margin-top: 20px;">
                                    <i class="fas fa-plus-circle"></i> Créer une catégorie
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Empty State pour recherche -->
    <div id="emptySearchState" class="empty-state-cat" style="display: none;">
        <div class="empty-icon-cat">
            <i class="fas fa-search"></i>
        </div>
        <h3>Aucun résultat trouvé</h3>
        <p style="color: #666; margin-top: 10px;">Aucune catégorie ne correspond à votre recherche.</p>
    </div>
</div>

<script>
// Search functionality
function filterCategories() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#categoryTableBody tr.category-row');
    let visibleCount = 0;
    
    if (rows.length > 0) {
        rows.forEach(row => {
            const name = row.getAttribute('data-name');
            if (name && name.includes(filter)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    const clearIcon = document.getElementById('clearSearch');
    if (clearIcon) {
        clearIcon.style.display = filter.length > 0 ? 'inline-block' : 'none';
    }
    
    const emptySearchState = document.getElementById('emptySearchState');
    const tableBody = document.getElementById('categoryTableBody');
    const table = document.getElementById('categoryTable');
    
    if (visibleCount === 0 && rows.length > 0) {
        if (emptySearchState) {
            emptySearchState.style.display = 'block';
        }
        if (tableBody) {
            tableBody.style.display = 'none';
        }
    } else {
        if (emptySearchState) {
            emptySearchState.style.display = 'none';
        }
        if (tableBody) {
            tableBody.style.display = '';
        }
    }
}

function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        filterCategories();
    }
}

function confirmDelete(categoryName, productCount) {
    let message = `⚠️ Suppression de la catégorie "${categoryName}"\n\n`;
    if (productCount > 0) {
        message += `⚠️ ATTENTION : Cette catégorie contient ${productCount} produit(s) !\n`;
        message += `La suppression entraînera la perte de ces produits.\n\n`;
    }
    message += `Êtes-vous sûr de vouloir supprimer définitivement cette catégorie ?\n\nCette action est irréversible.`;
    return confirm(message);
}
</script>

<?php include '../../includes/footer.php'; ?>