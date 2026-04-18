<?php
// src/Frontend/category/view_category.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Récupérer l'ID de la catégorie
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id <= 0) {
    $_SESSION['error'] = "ID de catégorie invalide";
    header('Location: index_category.php');
    exit();
}

// Vérifier les colonnes existantes
$columns = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_COLUMN);
$has_image = in_array('image', $columns);
$has_status = in_array('status', $columns);
$has_created_by = in_array('created_by', $columns);
$has_approved_by = in_array('approved_by', $columns);
$has_approved_at = in_array('approved_at', $columns);
$has_rejection_reason = in_array('rejection_reason', $columns);

// Récupérer les informations de la catégorie
if ($has_status && $has_created_by) {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u.username as created_by_name, u.email as created_by_email,
               a.username as approved_by_name
        FROM categories c
        LEFT JOIN users u ON c.created_by = u.id
        LEFT JOIN users a ON c.approved_by = a.id
        WHERE c.id = ?
    ");
    $stmt->execute([$category_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
}
$category = $stmt->fetch();

if (!$category) {
    $_SESSION['error'] = "Catégorie non trouvée";
    header('Location: index_category.php');
    exit();
}

// Vérifier que l'utilisateur a le droit de voir cette catégorie
if ($user_role != 'super_admin' && $has_status && $category['status'] != 'approved' && $category['created_by'] != $user_id) {
    $_SESSION['error'] = "Vous n'avez pas le droit de voir cette catégorie";
    header('Location: index_category.php');
    exit();
}

// Récupérer les produits de cette catégorie
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM commandes WHERE produit_id = p.id) as total_commandes
    FROM produits p
    WHERE p.categorie_id = ?
    ORDER BY p.id DESC
");
$stmt->execute([$category_id]);
$products = $stmt->fetchAll();

// Calculer les statistiques
$total_products = count($products);
$total_value = 0;
foreach ($products as $product) {
    $total_value += $product['prix'];
}

$page_title = 'Détails de la catégorie';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($category['nomcat']) ?> - InApp Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="apple-touch-icon" sizes="180x180" href="../../assets/images/favicon_io/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../assets/images/favicon_io/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon_io/favicon-16x16.png">
  <link rel="manifest" href="../../assets/images/favicon_io/site.webmanifest">
  <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/style.css">
  
  <style>
    :root {
      --primary-color: #E66239;
      --primary-light: rgba(230, 98, 57, 0.1);
    }

    .breadcrumb-custom {
      background: transparent;
      padding: 0;
      margin-bottom: 20px;
    }

    .breadcrumb-custom a {
      color: var(--primary-color);
      text-decoration: none;
    }

    .category-header {
      background: white;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .category-cover {
      height: 200px;
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 20px;
    }

    .category-cover img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .category-cover-placeholder {
      height: 200px;
      background: linear-gradient(135deg, var(--primary-color) 0%, #d4552e 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 60px;
    }

    .status-badge-large {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 14px;
      border-radius: 50px;
      font-size: 13px;
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

    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
      margin-top: 20px;
    }

    .info-item {
      background: #f8f9fa;
      padding: 12px 15px;
      border-radius: 10px;
    }

    .info-label {
      font-size: 11px;
      text-transform: uppercase;
      color: #999;
      letter-spacing: 0.5px;
      margin-bottom: 5px;
    }

    .info-value {
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }

    .stats-grid-view {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 15px;
      margin-bottom: 25px;
    }

    .stat-card-view {
      background: white;
      padding: 20px;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      transition: all 0.3s;
    }

    .stat-card-view:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .stat-icon-view {
      font-size: 32px;
      color: var(--primary-color);
      margin-bottom: 10px;
    }

    .stat-number-view {
      font-size: 28px;
      font-weight: 700;
      color: #1a1a2e;
    }

    .stat-label-view {
      font-size: 12px;
      color: #888;
      margin-top: 5px;
    }

    .product-table-container {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .product-table {
      width: 100%;
      border-collapse: collapse;
    }

    .product-table th {
      background: #f8f9fa;
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 13px;
      color: #555;
      border-bottom: 2px solid #f0f0f5;
    }

    .product-table td {
      padding: 15px;
      border-bottom: 1px solid #f0f0f5;
      vertical-align: middle;
    }

    .product-table tr:hover {
      background: var(--primary-light);
    }

    .product-badge {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .product-icon {
      width: 35px;
      height: 35px;
      background: var(--primary-light);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-color);
    }

    .price-tag {
      font-weight: 700;
      color: #28a745;
    }

    .btn-back {
      background: #6c757d;
      color: white;
      padding: 8px 20px;
      border-radius: 8px;
      text-decoration: none;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-back:hover {
      background: #5a6268;
      color: white;
    }

    .btn-edit-cat {
      background: var(--primary-color);
      color: white;
      padding: 8px 20px;
      border-radius: 8px;
      text-decoration: none;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-edit-cat:hover {
      background: #d4552e;
      color: white;
    }

    .empty-products {
      text-align: center;
      padding: 60px 20px;
    }

    .empty-icon {
      font-size: 64px;
      color: #ddd;
      margin-bottom: 15px;
    }

    @media (max-width: 768px) {
      .info-grid {
        grid-template-columns: 1fr;
      }
      .stats-grid-view {
        grid-template-columns: 1fr;
      }
      .product-table {
        font-size: 12px;
      }
      .product-table th, .product-table td {
        padding: 10px;
      }
    }
  </style>
</head>

<body>
  <div id="overlay" class="overlay"></div>
  
  <!-- TOPBAR -->
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
                    <div class="text-secondary small">30分钟</div>
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

  <!-- SIDEBAR -->
  <?php include '../../sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="content" class="content py-10">
    <div class="container-fluid px-4">
      
      <!-- Breadcrumb -->
      <div class="breadcrumb-custom">
        <a href="index_category.php">
          <i class="ti ti-arrow-left"></i> Retour aux catégories
        </a>
      </div>
      
      <!-- En-tête de la catégorie -->
      <div class="category-header">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h1 class="page-title mb-2">
              <i class="ti ti-category me-2" style="color: var(--primary-color);"></i>
              <?= htmlspecialchars($category['nomcat']) ?>
            </h1>
            <?php if($has_status): ?>
              <?php if($category['status'] == 'approved'): ?>
                <span class="status-badge-large status-approved">
                  <i class="ti ti-check-circle"></i> Catégorie validée
                </span>
              <?php elseif($category['status'] == 'pending'): ?>
                <span class="status-badge-large status-pending">
                  <i class="ti ti-clock"></i> En attente de validation
                </span>
              <?php elseif($category['status'] == 'rejected'): ?>
                <span class="status-badge-large status-rejected">
                  <i class="ti ti-x-circle"></i> Catégorie rejetée
                </span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="edit_category.php?id=<?= $category['id'] ?>" class="btn-edit-cat">
              <i class="ti ti-edit"></i> Modifier la catégorie
            </a>
          </div>
        </div>
      </div>
      
      <!-- Image de couverture -->
      <div class="category-cover">
        <?php if($has_image && $category['image']): ?>
          <img src="../../<?= htmlspecialchars($category['image']) ?>" alt="<?= htmlspecialchars($category['nomcat']) ?>">
        <?php else: ?>
          <div class="category-cover-placeholder">
            <i class="ti ti-category"></i>
          </div>
        <?php endif; ?>
      </div>
      
      <!-- Informations détaillées -->
      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">
            <i class="ti ti-id"></i> ID de la catégorie
          </div>
          <div class="info-value">#<?= $category['id'] ?></div>
        </div>
        
        <?php if($has_created_by && isset($category['created_by_name'])): ?>
        <div class="info-item">
          <div class="info-label">
            <i class="ti ti-user"></i> Créée par
          </div>
          <div class="info-value"><?= htmlspecialchars($category['created_by_name']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if($has_approved_by && $category['approved_by_name'] && $category['status'] == 'approved'): ?>
        <div class="info-item">
          <div class="info-label">
            <i class="ti ti-check"></i> Validée par
          </div>
          <div class="info-value"><?= htmlspecialchars($category['approved_by_name']) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if($has_approved_at && $category['approved_at'] && $category['status'] == 'approved'): ?>
        <div class="info-item">
          <div class="info-label">
            <i class="ti ti-calendar"></i> Date de validation
          </div>
          <div class="info-value"><?= date('d/m/Y à H:i', strtotime($category['approved_at'])) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if($has_rejection_reason && $category['rejection_reason'] && $category['status'] == 'rejected'): ?>
        <div class="info-item">
          <div class="info-label">
            <i class="ti ti-message"></i> Raison du rejet
          </div>
          <div class="info-value" style="color: #dc3545;"><?= htmlspecialchars($category['rejection_reason']) ?></div>
        </div>
        <?php endif; ?>
      </div>
      
      <!-- Statistiques -->
      <div class="stats-grid-view">
        <div class="stat-card-view">
          <div class="stat-icon-view">
            <i class="ti ti-package"></i>
          </div>
          <div class="stat-number-view"><?= $total_products ?></div>
          <div class="stat-label-view">Produits dans cette catégorie</div>
        </div>
        
        <div class="stat-card-view">
          <div class="stat-icon-view">
            <i class="ti ti-moneybag"></i>
          </div>
          <div class="stat-number-view"><?= number_format($total_value, 0, ',', ' ') ?> FCFA</div>
          <div class="stat-label-view">Valeur totale du stock</div>
        </div>
        
        <div class="stat-card-view">
          <div class="stat-icon-view">
            <i class="ti ti-chart-bar"></i>
          </div>
          <div class="stat-number-view">
            <?php 
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT c.id) as total_commandes 
                                    FROM commandes c 
                                    JOIN produits p ON c.produit_id = p.id 
                                    WHERE p.categorie_id = ?");
            $stmt->execute([$category_id]);
            $total_orders = $stmt->fetch()['total_commandes'];
            echo $total_orders;
            ?>
          </div>
          <div class="stat-label-view">Commandes passées</div>
        </div>
      </div>
      
      <!-- Liste des produits -->
      <div class="product-table-container">
        <div style="padding: 20px; border-bottom: 1px solid #f0f0f5;">
          <h5 class="mb-0">
            <i class="ti ti-package" style="color: var(--primary-color);"></i>
            Produits de cette catégorie
            <span class="badge bg-secondary ms-2"><?= $total_products ?></span>
          </h5>
        </div>
        
        <?php if($total_products > 0): ?>
        <div class="table-responsive">
          <table class="product-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Produit</th>
                <th>Prix</th>
                <th>Commandes</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($products as $product): ?>
              <tr>
                <td style="width: 60px;">#<?= $product['id'] ?></td>
                <td>
                  <div class="product-badge">
                    <div class="product-icon">
                      <i class="ti ti-box"></i>
                    </div>
                    <div>
                      <div class="fw-semibold"><?= htmlspecialchars($product['nomp']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="price-tag"><?= number_format($product['prix'], 0, ',', ' ') ?> FCFA</span>
                </td>
                <td>
                  <span class="badge bg-info"><?= $product['total_commandes'] ?> commande(s)</span>
                </td>
                <td>
                  <a href="../products/view_product.php?id=<?= $product['id'] ?>" class="action-btn view" style="display: inline-flex;">
                    <i class="ti ti-eye"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="empty-products">
          <div class="empty-icon">
            <i class="ti ti-package"></i>
          </div>
          <h5>Aucun produit dans cette catégorie</h5>
          <p class="text-secondary">Cette catégorie ne contient aucun produit pour le moment.</p>
          <a href="../products/add_product.php?category_id=<?= $category['id'] ?>" class="btn-primary-custom mt-2" style="display: inline-flex;">
            <i class="ti ti-plus"></i> Ajouter un produit
          </a>
        </div>
        <?php endif; ?>
      </div>
      
      <!-- Footer -->
      <div class="row mt-4">
        <div class="col-12">
          <footer class="text-center py-3">
            <p class="mb-0 text-secondary small">
              Copyright © 2026 InApp Inventory Dashboard. Developed by 
              <a href="#" class="text-decoration-none" style="color: var(--primary-color);">CodesCandy</a> 
              • Distributed by 
              <a href="#" class="text-decoration-none" style="color: var(--primary-color);">ThemeWagon</a>
            </p>
          </footer>
        </div>
      </div>
      
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../assets/js/main.js" type="module"></script>
</body>
</html>