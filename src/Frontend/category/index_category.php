<?php
// src/Frontend/category/index_category.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Vérifier si les colonnes existent
$columns = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_COLUMN);
$has_status = in_array('status', $columns);
$has_image = in_array('image', $columns);
$has_created_by = in_array('created_by', $columns);

// Récupérer les catégories rejetées
$rejected_categories = [];
if ($has_status) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as approved_by_name
        FROM categories c
        LEFT JOIN users u ON c.approved_by = u.id
        WHERE c.created_by = ? AND c.status = 'rejected'
        ORDER BY c.id DESC
    ");
    $stmt->execute([$user_id]);
    $rejected_categories = $stmt->fetchAll();
}

// Récupérer toutes les catégories
$sql = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM produits WHERE categorie_id = c.id) as total_produits,
           (SELECT COALESCE(SUM(prix), 0) FROM produits WHERE categorie_id = c.id) as total_value
    FROM categories c
    GROUP BY c.id
    ORDER BY c.id ASC
";

$stmt = $pdo->query($sql);
$categories = $stmt->fetchAll();

if (empty($categories)) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $stmt->fetchAll();
    
    foreach ($categories as &$cat) {
        $stmtProd = $pdo->prepare("SELECT COUNT(*) as total FROM produits WHERE categorie_id = ?");
        $stmtProd->execute([$cat['id']]);
        $cat['total_produits'] = $stmtProd->fetch()['total'];
        
        $stmtVal = $pdo->prepare("SELECT COALESCE(SUM(prix), 0) as total FROM produits WHERE categorie_id = ?");
        $stmtVal->execute([$cat['id']]);
        $cat['total_value'] = $stmtVal->fetch()['total'];
    }
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Categories - InApp Inventory Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="apple-touch-icon" sizes="180x180" href="../../assets/images/favicon_io/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../assets/images/favicon_io/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon_io/favicon-16x16.png">
  <link rel="manifest" href="../../assets/images/favicon_io/site.webmanifest">
  <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/style.css">
  
  <style>
    :root {
      --primary-color: #E66239;
      --primary-light: rgba(230, 98, 57, 0.1);
    }

    /* Notification Orange Alert - En haut à droite */
    .alert-notification {
      position: fixed;
      top: 80px;
      right: 20px;
      z-index: 9999;
      max-width: 380px;
      width: 100%;
    }
    
    .alert-popup {
      background: #fff3e0;
      border-left: 4px solid #ff9800;
      border-radius: 8px;
      padding: 14px 16px;
      margin-bottom: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      animation: slideInRight 0.3s ease;
      position: relative;
    }
    
    @keyframes slideInRight {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    @keyframes slideOutRight {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }
    
    .alert-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
    }
    
    .alert-icon {
      background: #ff9800;
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .alert-icon i {
      color: white;
      font-size: 14px;
    }
    
    .alert-title {
      font-weight: 700;
      color: #e65100;
      font-size: 14px;
      flex: 1;
    }
    
    .alert-close {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
      color: #ff9800;
      padding: 0 4px;
      transition: color 0.2s;
    }
    
    .alert-close:hover {
      color: #e65100;
    }
    
    .alert-message {
      font-size: 13px;
      color: #5d4037;
      margin-bottom: 8px;
      padding-left: 38px;
    }
    
    .alert-category-name {
      font-weight: 600;
      color: #e65100;
    }
    
    .alert-reason {
      background: #fff8e1;
      padding: 8px 10px;
      border-radius: 6px;
      font-size: 12px;
      color: #bf360c;
      margin-top: 8px;
      margin-bottom: 10px;
      margin-left: 38px;
      border-left: 2px solid #ff9800;
    }
    
    .alert-actions {
      display: flex;
      gap: 12px;
      padding-left: 38px;
    }
    
    .alert-btn {
      font-size: 12px;
      text-decoration: none;
      padding: 4px 12px;
      border-radius: 4px;
      transition: all 0.2s;
      font-weight: 500;
    }
    
    .alert-btn-edit {
      background: #ff9800;
      color: white;
    }
    
    .alert-btn-edit:hover {
      background: #f57c00;
      color: white;
    }
    
    .alert-btn-delete {
      background: #ef5350;
      color: white;
    }
    
    .alert-btn-delete:hover {
      background: #e53935;
      color: white;
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

    .category-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
    }

    .category-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      transition: all 0.25s ease;
      border: 1px solid #f0f0f5;
      position: relative;
    }

    .category-card:hover {
      box-shadow: 0 8px 24px rgba(230, 98, 57, 0.12);
      border-color: var(--primary-color);
    }

    .category-image {
      height: 160px;
      overflow: hidden;
      position: relative;
    }

    .category-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .category-card:hover .category-image img {
      transform: scale(1.05);
    }

    .category-badge {
      position: absolute;
      top: 12px;
      right: 12px;
      background: rgba(255,255,255,0.95);
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
      color: #333;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      backdrop-filter: blur(4px);
    }

    .status-badge {
      position: absolute;
      top: 12px;
      left: 12px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 4px;
      background: rgba(255,255,255,0.95);
      backdrop-filter: blur(4px);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .status-pending {
      background: #fff3cd;
      color: #856404;
    }
    
    .status-approved {
      background: #d4edda;
      color: #155724;
    }
    
    .status-rejected {
      background: #f8d7da;
      color: #721c24;
    }

    .category-content {
      padding: 16px;
    }

    .category-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
    }

    .category-icon {
      width: 40px;
      height: 40px;
      background: var(--primary-light);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-color);
    }

    .category-icon i {
      font-size: 22px;
    }

    .category-info {
      flex: 1;
    }

    .category-name {
      font-size: 16px;
      font-weight: 600;
      color: #1a1a2e;
      margin-bottom: 4px;
    }

    .category-count {
      font-size: 12px;
      color: #888;
    }

    .category-stats {
      display: flex;
      gap: 16px;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #f0f0f5;
    }

    .stat {
      flex: 1;
    }

    .stat-value {
      font-size: 16px;
      font-weight: 600;
      color: #1a1a2e;
    }

    .stat-label {
      font-size: 11px;
      color: #999;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .category-actions {
      position: absolute;
      bottom: 16px;
      right: 16px;
      display: flex;
      gap: 6px;
      opacity: 0;
      transform: translateY(10px);
      transition: all 0.25s ease;
    }

    .category-card:hover .category-actions {
      opacity: 1;
      transform: translateY(0);
    }

    .action-btn {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: white;
      border: 1px solid #e0e0e8;
      color: #666;
      cursor: pointer;
      transition: all 0.2s ease;
      font-size: 14px;
      text-decoration: none;
    }

    .action-btn.view:hover {
      background: var(--primary-color);
      border-color: var(--primary-color);
      color: white;
    }

    .action-btn.edit:hover {
      background: #f39c12;
      border-color: #f39c12;
      color: white;
    }

    .action-btn.delete:hover {
      background: #e74c3c;
      border-color: #e74c3c;
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
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }

    .filter-tab {
      padding: 6px 14px;
      background: #f8f9fa;
      border-radius: 8px;
      font-size: 13px;
      color: #666;
      cursor: pointer;
      transition: all 0.2s ease;
      border: 1px solid transparent;
    }

    .filter-tab:hover,
    .filter-tab.active {
      background: var(--primary-light);
      color: var(--primary-color);
      border-color: var(--primary-color);
    }

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

    @media (max-width: 768px) {
      .category-grid {
        grid-template-columns: 1fr;
      }
      .alert-notification {
        left: 20px;
        right: 20px;
        max-width: calc(100% - 40px);
      }
    }
  </style>
</head>

<body>
  <div id="overlay" class="overlay"></div>
  
  <!-- Notifications Orange Alert - En haut à droite -->
  <div class="alert-notification" id="alertNotification">
    <?php foreach($rejected_categories as $rejected): ?>
    <div class="alert-popup" id="alert-<?= $rejected['id'] ?>">
      <div class="alert-header">
        <div class="alert-icon">
          <i class="ti ti-alert-triangle"></i>
        </div>
        <div class="alert-title">⚠️ CATÉGORIE REJETÉE</div>
        <button class="alert-close" onclick="closeAlert(<?= $rejected['id'] ?>)">&times;</button>
      </div>
      <div class="alert-message">
        La catégorie <span class="alert-category-name">"<?= htmlspecialchars($rejected['nomcat']) ?>"</span> a été rejetée par le Super Admin.
      </div>
      <?php if($rejected['rejection_reason']): ?>
      <div class="alert-reason">
        <i class="ti ti-message"></i> Raison : <?= htmlspecialchars($rejected['rejection_reason']) ?>
      </div>
      <?php endif; ?>
      <div class="alert-actions">
        <a href="edit_category.php?id=<?= $rejected['id'] ?>" class="alert-btn alert-btn-edit">
          <i class="ti ti-edit"></i> Modifier
        </a>
        <a href="delete_category.php?id=<?= $rejected['id'] ?>" class="alert-btn alert-btn-delete" onclick="return confirm('Supprimer cette catégorie ?')">
          <i class="ti ti-trash"></i> Supprimer
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

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
            <?php if(count($rejected_categories) > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger mt-2 ms-n2">
              <?= count($rejected_categories) ?>
            </span>
            <?php endif; ?>
          </a>
          <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-0">
            <ul class="list-unstyled p-0 m-0">
              <?php if(count($rejected_categories) > 0): ?>
                <?php foreach($rejected_categories as $rejected): ?>
                <li class="p-3 border-bottom">
                  <div class="d-flex gap-3">
                    <div class="flex-grow-1 small">
                      <p class="mb-0 fw-medium text-danger">Catégorie rejetée</p>
                      <p class="mb-1 text-secondary"><?= htmlspecialchars($rejected['nomcat']) ?></p>
                    </div>
                  </div>
                </li>
                <?php endforeach; ?>
              <?php else: ?>
              <li class="p-3 text-center">
                <p class="mb-0 text-secondary small">Aucune notification</p>
              </li>
              <?php endif; ?>
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
      
      <!-- En-tête -->
      <div class="page-header">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1 class="page-title">
              <i class="ti ti-category me-2" style="color: var(--primary-color);"></i>
              Catégories
            </h1>
            <p class="text-secondary mb-0 small">Gérez vos catégories de produits</p>
          </div>
          <div class="col-md-6">
            <div class="d-flex gap-3 justify-content-md-end mt-3 mt-md-0">
              <div class="search-bar flex-grow-1" style="max-width: 280px;">
                <i class="ti ti-search text-secondary me-2"></i>
                <input type="text" id="searchInput" class="form-control-plaintext" placeholder="Rechercher..." onkeyup="filterCategories()">
                <button onclick="filterCategories()"><i class="ti ti-search"></i></button>
              </div>
              <a href="create_category.php" class="btn-primary-custom">
                <i class="ti ti-plus"></i>
                <span class="d-none d-sm-inline">Nouvelle catégorie</span>
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

      <!-- Grille de catégories -->
      <div class="category-grid" id="categoryGrid">
        <?php if(count($categories) > 0): ?>
          <?php 
          $displayed_ids = [];
          foreach($categories as $cat): 
            if (in_array($cat['id'], $displayed_ids)) continue;
            $displayed_ids[] = $cat['id'];
          ?>
          <div class="category-card <?= ($has_status && $cat['status'] == 'rejected') ? 'rejected' : '' ?>" 
               data-name="<?= strtolower(htmlspecialchars($cat['nomcat'])) ?>" 
               data-id="<?= $cat['id'] ?>">
            
            <div class="category-badge">
              <?= $cat['total_produits'] ?? 0 ?> produits
            </div>
            
            <?php if($has_status): ?>
            <div class="status-badge <?= $cat['status'] == 'pending' ? 'status-pending' : ($cat['status'] == 'rejected' ? 'status-rejected' : 'status-approved') ?>">
              <?php if($cat['status'] == 'pending'): ?>
                <i class="ti ti-clock"></i> En attente
              <?php elseif($cat['status'] == 'approved'): ?>
                <i class="ti ti-check"></i> Validée
              <?php elseif($cat['status'] == 'rejected'): ?>
                <i class="ti ti-x"></i> Rejetée
              <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="category-image">
              <?php if($has_image && !empty($cat['image'])): ?>
                <img src="../../<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['nomcat']) ?>">
              <?php else: ?>
                <img src="https://images.pexels.com/photos/47261/pexels-photo-47261.jpeg?auto=compress&cs=tinysrgb&w=600" alt="<?= htmlspecialchars($cat['nomcat']) ?>">
              <?php endif; ?>
            </div>
            <div class="category-content">
              <div class="category-header">
                <div class="category-icon">
                  <i class="ti ti-category"></i>
                </div>
                <div class="category-info">
                  <div class="category-name"><?= htmlspecialchars($cat['nomcat']) ?></div>
                  <div class="category-count">ID: #<?= $cat['id'] ?></div>
                </div>
              </div>
              <div class="category-stats">
                <div class="stat">
                  <div class="stat-value"><?= number_format($cat['total_produits'] ?? 0, 0, ',', ' ') ?></div>
                  <div class="stat-label">Produits</div>
                </div>
                <div class="stat">
                  <div class="stat-value"><?= number_format($cat['total_value'] ?? 0, 0, ',', ' ') ?> FCFA</div>
                  <div class="stat-label">Valeur</div>
                </div>
              </div>
            </div>
            <div class="category-actions">
              <a href="view_category.php?id=<?= $cat['id'] ?>" class="action-btn view" title="Voir">
                <i class="ti ti-eye"></i>
              </a>
              <a href="edit_category.php?id=<?= $cat['id'] ?>" class="action-btn edit" title="Modifier">
                <i class="ti ti-edit"></i>
              </a>
              <a href="delete_category.php?id=<?= $cat['id'] ?>" class="action-btn delete" title="Supprimer" 
                 onclick="return confirm('⚠️ Supprimer la catégorie &quot;<?= htmlspecialchars($cat['nomcat']) ?>&quot; ?\n\nCette action est irréversible.')">
                <i class="ti ti-trash"></i>
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center py-5" style="width: 100%;">
            <i class="ti ti-category" style="font-size: 64px; color: #ddd;"></i>
            <h4 class="mt-3">Aucune catégorie</h4>
            <p class="text-secondary">Créez votre première catégorie en cliquant sur "Nouvelle catégorie"</p>
            <a href="create_category.php" class="btn-primary-custom mt-2" style="display: inline-flex;">
              <i class="ti ti-plus"></i> Créer une catégorie
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

  <script>
    function filterCategories() {
      const searchInput = document.getElementById('searchInput');
      const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
      const cards = document.querySelectorAll('.category-card');
      let visibleCount = 0;
      
      cards.forEach(card => {
        const name = card.getAttribute('data-name') || '';
        const matchesSearch = name.includes(searchValue);
        
        if (matchesSearch) {
          card.style.display = '';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });
      
      const grid = document.getElementById('categoryGrid');
      const existingEmptyMsg = document.getElementById('emptyFilterMessage');
      
      if (visibleCount === 0 && document.querySelectorAll('.category-card').length > 0) {
        if (!existingEmptyMsg) {
          const emptyMsg = document.createElement('div');
          emptyMsg.id = 'emptyFilterMessage';
          emptyMsg.className = 'text-center py-5';
          emptyMsg.style.width = '100%';
          emptyMsg.innerHTML = `
            <i class="ti ti-search" style="font-size: 64px; color: #ddd;"></i>
            <h4 class="mt-3">Aucun résultat</h4>
            <p class="text-secondary">Aucune catégorie ne correspond à votre recherche</p>
          `;
          grid.appendChild(emptyMsg);
        }
      } else if (existingEmptyMsg) {
        existingEmptyMsg.remove();
      }
    }
    
    function closeAlert(id) {
      const alert = document.getElementById('alert-' + id);
      if (alert) {
        alert.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => alert.remove(), 300);
      }
    }
    
    // Auto-fermeture après 10 secondes
    setTimeout(() => {
      const alerts = document.querySelectorAll('.alert-popup');
      alerts.forEach(alert => {
        setTimeout(() => {
          if (alert && alert.parentNode) {
            alert.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => alert.remove(), 300);
          }
        }, 10000);
      });
    }, 1000);
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../assets/js/main.js" type="module"></script>
</body>
</html>