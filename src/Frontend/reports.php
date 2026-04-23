 <?php
// src/Frontend/reports.php
require_once '../config/app.php';
requireAuth();

$pdo = getPDO();
$is_super_admin = isSuperAdmin();

// ========== STATISTIQUES ==========
// Total Revenue (Chiffre d'affaires des commandes livrées)
$stmt = $pdo->query("SELECT SUM(total_ttc) as total FROM commandes WHERE statut = 'livree'");
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Produits vendus (quantité totale)
$stmt = $pdo->query("SELECT SUM(quantite) as total FROM detail_commande dc JOIN commandes c ON dc.commande_id = c.id WHERE c.statut = 'livree'");
$products_sold = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Stock faible (quantite < 5 et > 0)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits WHERE quantite < 5 AND quantite > 0");
$low_stock = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Rupture de stock (quantite = 0)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits WHERE quantite = 0");
$out_of_stock = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// ========== GRAPHIQUE VENTES MENSUELLES ==========
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(date_commande, '%Y-%m') as mois,
        SUM(total_ttc) as total_ventes
    FROM commandes
    WHERE statut = 'livree'
    GROUP BY DATE_FORMAT(date_commande, '%Y-%m')
    ORDER BY mois ASC
");
$sales_data = $stmt->fetchAll();

$sales_labels = [];
$sales_values = [];
foreach ($sales_data as $d) {
    $sales_labels[] = date('M Y', strtotime($d['mois'] . '-01'));
    $sales_values[] = $d['total_ventes'];
}

// Calcul de l'évolution par rapport au mois dernier
$last_month_sales = count($sales_values) > 1 ? $sales_values[count($sales_values) - 2] : 0;
$current_month_sales = count($sales_values) > 0 ? end($sales_values) : 0;
$evolution = $last_month_sales > 0 ? round(($current_month_sales - $last_month_sales) / $last_month_sales * 100) : 0;

// ========== TOP PRODUITS ==========
$stmt = $pdo->query("
    SELECT p.id, p.nomp, p.image, 
           SUM(dc.quantite) as qte_vendue,
           SUM(dc.quantite * dc.prix_unitaire) as total_vendu
    FROM detail_commande dc
    JOIN produits p ON dc.produit_id = p.id
    JOIN commandes c ON dc.commande_id = c.id
    WHERE c.statut = 'livree'
    GROUP BY p.id, p.nomp, p.image
    ORDER BY total_vendu DESC
    LIMIT 5
");
$top_products = $stmt->fetchAll();

$page_title = 'Rapports';
include '../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports | PowerStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        .icon-shape {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }
        .product-img {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
            background: #f1f5f9;
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
    <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>
    <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>
    <div class="ms-auto">
        <ul class="list-unstyled d-flex align-items-center mb-0 gap-2">
            <li>
                <a class="btn btn-light btn-icon btn-sm rounded-circle position-relative" data-bs-toggle="dropdown" href="#">
                    <i class="ti ti-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger mt-2 ms-n2">3</span>
                </a>
            </li>
            <li class="dropdown">
                <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="../assets/images/avatar/avatar-1.jpg" alt="" class="avatar avatar-sm rounded-circle" />
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 200px;">
                    <div>
                        <div class="d-flex gap-3 align-items-center border-bottom px-3 py-3">
                            <img src="../assets/images/avatar/avatar-1.jpg" alt="" class="avatar avatar-md rounded-circle" />
                            <div>
                                <h4 class="mb-0 small fw-semibold"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin User') ?></h4>
                                <p class="mb-0 small text-secondary">@<?= htmlspecialchars($_SESSION['username'] ?? 'admin') ?></p>
                            </div>
                        </div>
                        <div class="p-3 d-flex flex-column gap-1 small">
                            <a href="#" class="text-decoration-none text-dark py-1">Mon profil</a>
                            <a href="#" class="text-decoration-none text-dark py-1">Paramètres</a>
                            <a href="../logout.php" class="text-decoration-none text-dark py-1">Déconnexion</a>
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
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="fs-3 mb-1">Rapports</h1>
                        <p class="text-secondary mb-0">Analysez vos performances et statistiques</p>
                    </div>
                    <div class="d-flex gap-2">
                        <select id="yearFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="all">Toutes les années</option>
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cartes statistiques -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h6 class="mb-4 text-secondary">Chiffre d'affaires</h6>
                        <h3 class="mb-1 fw-bold"><?= number_format($total_revenue, 0, ',', ' ') ?> FCFA</h3>
                        <p class="mb-0 text-<?= $evolution >= 0 ? 'success' : 'danger' ?> small">
                            <i class="ti ti-arrow-<?= $evolution >= 0 ? 'up' : 'down' ?>"></i>
                            <?= abs($evolution) ?>% par rapport au mois dernier
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h6 class="mb-4 text-secondary">Produits vendus</h6>
                        <h3 class="mb-1 fw-bold"><?= number_format($products_sold, 0, ',', ' ') ?></h3>
                        <p class="mb-0 text-success small">Total unités écoulées</p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h6 class="mb-4 text-secondary">Stock faible</h6>
                        <h3 class="mb-1 fw-bold"><?= $low_stock ?></h3>
                        <p class="mb-0 text-warning small">Produits avec stock &lt; 5</p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h6 class="mb-4 text-secondary">Rupture de stock</h6>
                        <h3 class="mb-1 fw-bold"><?= $out_of_stock ?></h3>
                        <p class="mb-0 text-danger small">Produits épuisés</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique des ventes -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-3 gap-2">
                            <div>
                                <h2 class="mb-0 fs-5">Aperçu des ventes</h2>
                                <p class="text-secondary small mb-0">Évolution mensuelle du chiffre d'affaires</p>
                            </div>
                        </div>
                        <div id="salesChart"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top produits -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="mb-0 fs-5">Top produits</h2>
                                <p class="text-secondary small mb-0">Les produits les plus vendus</p>
                            </div>
                        </div>

                        <div class="list-group list-group-flush">
                            <?php if(count($top_products) > 0): ?>
                                <?php foreach($top_products as $p): ?>
                                <div class="list-group-item p-3 d-flex align-items-center">
                                    <div class="me-3">
                                        <?php if(!empty($p['image']) && file_exists('../' . $p['image'])): ?>
                                            <img src="../<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['nomp']) ?>" class="product-img">
                                        <?php else: ?>
                                            <div class="product-img bg-light d-flex align-items-center justify-content-center">
                                                <i class="ti ti-package text-secondary"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($p['nomp']) ?></h6>
                                                <small class="text-secondary"><?= $p['qte_vendue'] ?> unités vendues</small>
                                            </div>
                                            <div class="text-end">
                                                <strong class="text-primary"><?= number_format($p['total_vendu'], 0, ',', ' ') ?> FCFA</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-secondary">Aucune donnée de vente disponible</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
// Graphique des ventes mensuelles
var salesOptions = {
    series: [{
        name: 'Ventes (FCFA)',
        data: <?= json_encode($sales_values) ?>
    }],
    chart: {
        type: 'area',
        height: 380,
        toolbar: { show: true },
        zoom: { enabled: true }
    },
    colors: ['#E66239'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3
        }
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    title: {
        text: 'Évolution des ventes',
        align: 'left',
        style: { fontSize: '14px', fontWeight: 'normal' }
    },
    xaxis: {
        categories: <?= json_encode($sales_labels) ?>,
        labels: { style: { fontSize: '12px' } },
        title: { text: 'Mois' }
    },
    yaxis: {
        labels: {
            formatter: function(val) {
                return val.toLocaleString('fr-FR') + ' FCFA';
            }
        },
        title: { text: 'Montant des ventes' }
    },
    tooltip: {
        y: {
            formatter: function(val) {
                return val.toLocaleString('fr-FR') + ' FCFA';
            }
        }
    }
};

var salesChart = new ApexCharts(document.querySelector("#salesChart"), salesOptions);
salesChart.render();

// Filtre par année (optionnel)
document.getElementById('yearFilter')?.addEventListener('change', function() {
    // À implémenter si besoin
    console.log('Filtre année:', this.value);
});

// Toggle sidebar
document.getElementById("toggleBtn")?.addEventListener("click", function() {
    document.querySelector(".sidebar")?.classList.toggle("active");
});
document.getElementById("mobileBtn")?.addEventListener("click", function() {
    document.querySelector(".sidebar")?.classList.toggle("active");
    document.getElementById("overlay")?.classList.add("show");
});
</script>
</body>
</html>