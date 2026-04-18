<?php
// src/Frontend/produits/index_produit.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$user_id = $_SESSION['user_id'];

// Récupérer les catégories pour le filtre
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nomcat");
$categories = $stmt->fetchAll();

// Récupérer tous les produits avec leurs informations
$stmt = $pdo->query("
    SELECT p.*, c.nomcat as categorie_nom, u.username as createur_nom
    FROM produits p
    LEFT JOIN categories c ON p.categorie_id = c.id
    LEFT JOIN users u ON p.created_by = u.id
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();

// Calculer les statistiques
$total_products = count($products);
$total_value = 0;
$total_quantity = 0;
$low_stock_count = 0;

foreach ($products as $product) {
    $total_value += $product['prix'] * ($product['quantite'] ?? 0);
    $total_quantity += $product['quantite'] ?? 0;
    if (($product['quantite'] ?? 0) < 5) {
        $low_stock_count++;
    }
}

// Récupérer les messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$page_title = 'Liste des produits';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Produits - InApp Inventory Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="apple-touch-icon" sizes="180x180" href="../../assets/images/favicon_io/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../assets/images/favicon_io/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon_io/favicon-16x16.png">
  <link rel="manifest" href="../../assets/images/favicon_io/site.webmanifest">
  <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
    <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm ">
      <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>

    <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2">
      <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>
    
    <div>
      <ul class="list-unstyled d-flex align-items-center mb-0 gap-1">
        <li>
          <a class="position-relative btn-icon btn-sm btn-light btn rounded-circle" data-bs-toggle="dropdown"
            aria-expanded="false" href="#" role="button">
            <i class="ti ti-bell"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger mt-2 ms-n2">
              3
            </span>
          </a>
          <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-0">
            <ul class="list-unstyled p-0 m-0">
              <li class="p-3 border-bottom">
                <div class="d-flex gap-3">
                  <img src="../../assets/images/avatar/avatar-1.jpg" alt="" class="avatar avatar-sm rounded-circle" />
                  <div class="flex-grow-1 small">
                    <p class="mb-0 fw-medium">Nouvelle commande</p>
                    <p class="mb-1 text-secondary">Commande #12345</p>
                    <div class="text-secondary small">5 minutes</div>
                  </div>
                </div>
              </li>
              <li class="p-3 border-bottom">
                <div class="d-flex gap-3">
                  <img src="../../assets/images/avatar/avatar-4.jpg" alt="" class="avatar avatar-sm rounded-circle" />
                  <div class="flex-grow-1 small">
                    <p class="mb-0 fw-medium">Nouvel utilisateur</p>
                    <p class="mb-1 text-secondary">@john_doe s'est inscrit</p>
                    <div class="text-secondary small">30 minutes</div>
                  </div>
                </div>
              </li>
              <li class="px-4 py-3 text-center">
                <a href="#" class="text-primary small fw-medium">Voir toutes les notifications</a>
              </li>
            </ul>
          </div>
        </li>
        <li class="ms-3 dropdown">
          <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="../../assets/images/avatar/avatar-1.jpg" alt="" class="avatar avatar-sm rounded-circle" />
          </a>
          <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 200px;">
            <div>
              <div class="d-flex gap-3 align-items-center border-bottom px-3 py-3">
                <img src="../../assets/images/avatar/avatar-1.jpg" alt="" class="avatar avatar-md rounded-circle" />
                <div>
                  <h4 class="mb-0 small fw-semibold"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin User') ?></h4>
                  <p class="mb-0 small text-secondary">@<?= htmlspecialchars($_SESSION['username'] ?? 'admin') ?></p>
                </div>
              </div>
              <div class="p-3 d-flex flex-column gap-1 small">
                <a href="#!" class="text-decoration-none text-dark py-1">Mon profil</a>
                <a href="#!" class="text-decoration-none text-dark py-1">Paramètres</a>
                <a href="../../logout.php" class="text-decoration-none text-dark py-1">Déconnexion</a>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </nav>

<!-- MAIN CONTENT -->
<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        
        <!-- En-tête -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="page-title">
                        <i class="ti ti-package me-2" style="color: var(--primary-color);"></i>
                        Liste des produits
                    </h1>
                    <p class="text-secondary mb-0 small">Gérez tous vos produits depuis cet interface</p>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-3 justify-content-md-end mt-3 mt-md-0">
                        <div class="search-bar flex-grow-1" style="max-width: 280px;">
                            <i class="ti ti-search text-secondary me-2"></i>
                            <input type="text" id="searchInput" class="form-control-plaintext" placeholder="Rechercher un produit..." onkeyup="filterProducts()">
                            <button onclick="filterProducts()"><i class="ti ti-search"></i></button>
                        </div>
                        <a href="create_produit.php" class="btn-primary-custom d-flex align-items-center gap-2 text-decoration-none">
                            <i class="ti ti-plus"></i>
                            <span class="d-none d-sm-inline">Nouveau produit</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="ti ti-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="ti ti-alert-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100">
                    <div class="d-flex justify-content-between align-items-center h-100">
                        <div>
                            <p class="text-secondary mb-1 small">Total produits</p>
                            <h3 class="mb-0 fw-bold"><?= $total_products ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-light rounded-circle">
                            <i class="ti ti-package" style="color: var(--primary-color); font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100">
                    <div class="d-flex justify-content-between align-items-center h-100">
                        <div>
                            <p class="text-secondary mb-1 small">Quantité totale</p>
                            <h3 class="mb-0 fw-bold"><?= number_format($total_quantity, 0, ',', ' ') ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-light rounded-circle">
                            <i class="ti ti-box" style="color: #f39c12; font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100">
                    <div class="d-flex justify-content-between align-items-center h-100">
                        <div>
                            <p class="text-secondary mb-1 small">Valeur totale stock</p>
                            <h3 class="mb-0 fw-bold"><?= number_format($total_value, 0, ',', ' ') ?> FCFA</h3>
                        </div>
                        <div class="stat-icon bg-success-light rounded-circle">
                            <i class="ti ti-currency-franc" style="color: #28a745; font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100">
                    <div class="d-flex justify-content-between align-items-center h-100">
                        <div>
                            <p class="text-secondary mb-1 small">Stock faible</p>
                            <h3 class="mb-0 fw-bold"><?= $low_stock_count ?></h3>
                        </div>
                        <div class="stat-icon bg-danger-light rounded-circle">
                            <i class="ti ti-alert-triangle" style="color: #dc3545; font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-tabs mb-4">
            <select id="categoryFilter" class="form-select" style="width: auto; display: inline-block; margin-right: 10px;">
                <option value="">Toutes les catégories</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nomcat']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <select id="sortFilter" class="form-select" style="width: auto; display: inline-block;">
                <option value="id_desc">Plus récent</option>
                <option value="id_asc">Plus ancien</option>
                <option value="name_asc">Nom (A-Z)</option>
                <option value="name_desc">Nom (Z-A)</option>
                <option value="price_asc">Prix (croissant)</option>
                <option value="price_desc">Prix (décroissant)</option>
                <option value="stock_asc">Stock (croissant)</option>
                <option value="stock_desc">Stock (décroissant)</option>
            </select>
            
            <button onclick="resetFilters()" class="btn btn-secondary btn-sm ms-2">
                <i class="ti ti-refresh"></i> Réinitialiser
            </button>
        </div>

        <!-- Tableau des produits -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="card-header" style="padding: 20px; background: white; border-bottom: 1px solid #f0f0f0;">
                <div>
                    <span><i class="ti ti-package" style="color: var(--primary-color);"></i> <strong>Liste des produits</strong></span>
                    <span style="margin-left: 10px; font-size: 12px; color: #666;">
                        <i class="ti ti-chart-line"></i> <?= $total_products ?> produit(s) au total
                    </span>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="60">Image</th>
                            <th>Nom du produit</th>
                            <th>Description</th>
                            <th>Prix (FCFA)</th>
                            <th>Quantité</th>
                            <th>Catégorie</th>
                            <th>Créé par</th>
                            <th>Date</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTable">
                        <?php if(count($products) > 0): ?>
                            <?php foreach($products as $product): ?>
                            <tr data-id="<?= $product['id'] ?>"
                                data-name="<?= strtolower(htmlspecialchars($product['nomp'])) ?>"
                                data-category="<?= $product['categorie_id'] ?>"
                                data-price="<?= $product['prix'] ?>"
                                data-stock="<?= $product['quantite'] ?? 0 ?>">
                                <td>
                                    <?php if($product['image']): ?>
                                        <img src="../../<?= htmlspecialchars($product['image']) ?>" class="product-thumbnail" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/50" class="product-thumbnail" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($product['nomp']) ?></strong></td>
                                <td>
                                    <?php if($product['description']): ?>
                                        <small class="text-secondary"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</small>
                                    <?php else: ?>
                                        <small class="text-secondary">-</small>
                                    <?php endif; ?>
                                </td>
                                <td><strong class="text-primary"><?= number_format($product['prix'], 0, ',', ' ') ?> FCFA</strong></td>
                                <td>
                                    <?php 
                                    $stock = $product['quantite'] ?? 0;
                                    if($stock <= 0): ?>
                                        <span class="badge bg-danger"><?= $stock ?> unités</span>
                                    <?php elseif($stock < 5): ?>
                                        <span class="badge bg-warning text-dark"><?= $stock ?> unités</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?= $stock ?> unités</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark"><?= htmlspecialchars($product['categorie_nom'] ?? 'Non catégorisé') ?></span></td>
                                <td><small><?= htmlspecialchars($product['createur_nom'] ?? 'Inconnu') ?></small></td>
                                <td><small><?= date('d/m/Y', strtotime($product['created_at'])) ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="view_produit.php?id=<?= $product['id'] ?>" class="btn btn-info btn-sm" title="Voir">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="edit_produit.php?id=<?= $product['id'] ?>" class="btn btn-warning btn-sm" title="Modifier">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="delete_produit.php?id=<?= $product['id'] ?>" class="btn btn-danger btn-sm" title="Supprimer" 
                                           onclick="return confirm('Supprimer ce produit ?')">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="ti ti-package-off" style="font-size: 64px; color: #ddd;"></i>
                                        <h5 class="mt-3">Aucun produit trouvé</h5>
                                        <p class="text-secondary mb-3">Commencez par ajouter votre premier produit</p>
                                        <a href="create_produit.php" class="btn-primary-custom">
                                            <i class="ti ti-plus"></i> Ajouter un produit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="row mt-4">
            <div class="col-12">
                <footer class="text-center py-3">
                    <p class="mb-0 text-secondary small">
                        Copyright © 2026 InApp Inventory Dashboard.
                    </p>
                </footer>
            </div>
        </div>
    </div>
</main>

<style>
    :root {
        --primary-color: #E66239;
        --primary-light: rgba(230, 98, 57, 0.1);
        --success-light: rgba(40, 167, 69, 0.1);
        --warning-light: rgba(243, 156, 18, 0.1);
        --danger-light: rgba(220, 53, 69, 0.1);
        --info-light: rgba(23, 162, 184, 0.1);
        --secondary-text: #6c757d;
    }

    .page-header {
        margin-bottom: 24px;
    }

    .page-title {
        font-size: 24px;
        font-weight: 600;
        color: #1a1a2e;
        margin-bottom: 4px;
    }

    .btn-primary-custom {
        background: var(--primary-color);
        border: none;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary-custom:hover {
        background: #d4552e;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(230, 98, 57, 0.3);
        color: white;
    }

    .search-bar {
        background: white;
        border-radius: 10px;
        padding: 4px 4px 4px 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1.5px solid #f0f0f5;
        display: flex;
        align-items: center;
    }

    .search-bar input {
        border: none;
        background: transparent;
        padding: 8px 0;
        font-size: 14px;
        flex: 1;
        outline: none;
    }

    .search-bar button {
        background: var(--primary-color);
        border: none;
        color: white;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 13px;
        cursor: pointer;
    }

    .filter-tabs {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
        align-items: center;
    }

    .form-select {
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        font-size: 13px;
    }

    .stat-card {
        transition: transform 0.3s ease;
        border: 1px solid #e9ecef;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    .bg-primary-light { background: var(--primary-light); }
    .bg-success-light { background: var(--success-light); }
    .bg-warning-light { background: var(--warning-light); }
    .bg-danger-light { background: var(--danger-light); }
    .bg-info-light { background: var(--info-light); }

    .alert {
        padding: 12px 16px;
        border-radius: 10px;
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

    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .product-thumbnail {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
    }

    .btn-group-sm .btn {
        padding: 4px 8px;
    }
    
    .badge {
        font-size: 11px;
        padding: 5px 10px;
    }
</style>

<script>
    function filterProducts() {
        const searchValue = document.getElementById('searchInput').value.toLowerCase();
        const categoryFilter = document.getElementById('categoryFilter').value;
        const sortValue = document.getElementById('sortFilter').value;
        const rows = document.querySelectorAll('#productsTable tr');
        
        let visibleRows = [];
        
        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const category = row.getAttribute('data-category') || '';
            const price = parseInt(row.getAttribute('data-price')) || 0;
            const id = parseInt(row.getAttribute('data-id')) || 0;
            const stock = parseInt(row.getAttribute('data-stock')) || 0;
            
            const matchSearch = name.includes(searchValue);
            const matchCategory = !categoryFilter || category === categoryFilter;
            
            if (matchSearch && matchCategory) {
                row.style.display = '';
                visibleRows.push({row, name, price, id, stock});
            } else {
                row.style.display = 'none';
            }
        });
        
        // Trier
        if (sortValue === 'name_asc') {
            visibleRows.sort((a, b) => a.name.localeCompare(b.name));
        } else if (sortValue === 'name_desc') {
            visibleRows.sort((a, b) => b.name.localeCompare(a.name));
        } else if (sortValue === 'price_asc') {
            visibleRows.sort((a, b) => a.price - b.price);
        } else if (sortValue === 'price_desc') {
            visibleRows.sort((a, b) => b.price - a.price);
        } else if (sortValue === 'stock_asc') {
            visibleRows.sort((a, b) => a.stock - b.stock);
        } else if (sortValue === 'stock_desc') {
            visibleRows.sort((a, b) => b.stock - a.stock);
        } else if (sortValue === 'id_asc') {
            visibleRows.sort((a, b) => a.id - b.id);
        } else if (sortValue === 'id_desc') {
            visibleRows.sort((a, b) => b.id - a.id);
        }
        
        // Réorganiser dans le DOM
        const tbody = document.getElementById('productsTable');
        visibleRows.forEach(item => {
            tbody.appendChild(item.row);
        });
    }
    
    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('categoryFilter').value = '';
        document.getElementById('sortFilter').value = 'id_desc';
        filterProducts();
    }
    
    document.getElementById('searchInput').addEventListener('keyup', filterProducts);
    document.getElementById('categoryFilter').addEventListener('change', filterProducts);
    document.getElementById('sortFilter').addEventListener('change', filterProducts);
</script>

<?php include '../../includes/footer.php'; ?>