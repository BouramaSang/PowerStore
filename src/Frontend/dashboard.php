 <?php
// src/Frontend/dashboard.php
require_once '../config/app.php';
requireAuth();

$pdo = getPDO();
$user_id = $_SESSION['user_id'];
$is_super_admin = isSuperAdmin();

 // ========== STATISTIQUES ==========
$total_clients = $pdo->query("SELECT COUNT(*) as total FROM clients")->fetch(PDO::FETCH_ASSOC)['total'];
$total_produits = $pdo->query("SELECT COUNT(*) as total FROM produits")->fetch(PDO::FETCH_ASSOC)['total'];
$total_commandes = $pdo->query("SELECT COUNT(*) as total FROM commandes")->fetch(PDO::FETCH_ASSOC)['total'];
$total_factures = $pdo->query("SELECT COUNT(*) as total FROM factures")->fetch(PDO::FETCH_ASSOC)['total'];

$total_sales = $pdo->query("SELECT SUM(total_ttc) as total FROM commandes WHERE statut = 'livree'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$total_expenses = $pdo->query("SELECT SUM(c.total_ttc) as total FROM commandes c JOIN factures f ON c.facture_id = f.id WHERE f.etatf = 0")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$total_profit = $total_sales - $total_expenses;
// Nombre de produits (pour la carte en bas)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits");
$total_produits = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre de clients (pour la carte en bas)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
$total_clients_stats = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
// ========== GRAPHIQUE Ventes vs Achats (Sales vs Purchase) ==========
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(date_commande, '%Y-%m') as mois,
        SUM(total_ttc) as total_ventes,
        COUNT(*) as nb_commandes
    FROM commandes
    WHERE statut = 'livree'
    GROUP BY DATE_FORMAT(date_commande, '%Y-%m')
    ORDER BY mois DESC
    LIMIT 6
");
$sales_data = $stmt->fetchAll();
$sales_data = array_reverse($sales_data);

$sales_labels = [];
$sales_values = [];
$purchase_values = [];
foreach ($sales_data as $d) {
    $sales_labels[] = date('M Y', strtotime($d['mois'] . '-01'));
    $sales_values[] = $d['total_ventes'];
    $purchase_values[] = $d['nb_commandes'] * 100000; // Approximation pour l'exemple
}

// ========== GRAPHIQUE CLIENTS (Customer Overview) ==========
// First time vs Return customers
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT client_id) as total_clients,
           SUM(CASE WHEN nb_commandes = 1 THEN 1 ELSE 0 END) as first_time,
           SUM(CASE WHEN nb_commandes > 1 THEN 1 ELSE 0 END) as return_customers
    FROM (
        SELECT client_id, COUNT(*) as nb_commandes
        FROM commandes
        GROUP BY client_id
    ) as client_stats
");
$customer_stats = $stmt->fetch();
$first_time = $customer_stats['first_time'] ?? 0;
$return_customers = $customer_stats['return_customers'] ?? 0;
$total_customers = $customer_stats['total_clients'] ?? 0;

// ========== TOP SELLING PRODUCTS ==========
$stmt = $pdo->query("
    SELECT p.nomp, SUM(dc.quantite) as qte_vendue, SUM(dc.quantite * dc.prix_unitaire) as total_vendu
    FROM detail_commande dc
    JOIN produits p ON dc.produit_id = p.id
    JOIN commandes c ON dc.commande_id = c.id
    WHERE c.statut = 'livree'
    GROUP BY p.id, p.nomp
    ORDER BY total_vendu DESC
    LIMIT 5
");
$top_products = $stmt->fetchAll();

// ========== LOW STOCK PRODUCTS ==========
$stmt = $pdo->query("
    SELECT id, nomp, quantite 
    FROM produits 
    WHERE quantite < 10
    ORDER BY quantite ASC
    LIMIT 5
");
$low_stock = $stmt->fetchAll();

// ========== RECENT SALES ==========
$stmt = $pdo->query("
    SELECT c.id, c.total_ttc, c.date_commande, c.statut,
           CONCAT(cl.prenom, ' ', cl.nomc) as client_nom,
           p.nomp as produit_nom
    FROM commandes c
    LEFT JOIN clients cl ON c.client_id = cl.id
    LEFT JOIN detail_commande dc ON c.id = dc.commande_id
    LEFT JOIN produits p ON dc.produit_id = p.id
    GROUP BY c.id
    ORDER BY c.date_commande DESC
    LIMIT 5
");
$recent_sales = $stmt->fetchAll();

$page_title = 'Dashboard';
include '../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PowerStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- ApexCharts -->
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
        .bg-primary-light { background: rgba(230, 98, 57, 0.1); }
        .bg-success-light { background: rgba(16, 185, 129, 0.1); }
        .bg-info-light { background: rgba(59, 130, 246, 0.1); }
        .bg-warning-light { background: rgba(245, 158, 11, 0.1); }
    </style>
</head>
<body>
<!-- ============================================= -->
<!-- TOPBAR AVEC TOGGLE SIDEBAR -->
<!-- ============================================= -->
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
<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        
        <!-- Titre -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="fs-3 mb-1">Dashboard</h1>
                <p class="text-secondary">Bienvenue, <?= htmlspecialchars($_SESSION['user_name']) ?> !</p>
            </div>
        </div>

        <!-- Cartes statistiques -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-12">
                <div class="card p-4 bg-primary-light border border-primary border-opacity-25 rounded-3">
                    <div class="d-flex gap-3">
                        <div class="icon-shape bg-primary text-white rounded-2">
                            <i class="ti ti-report-analytics fs-4"></i>
                        </div>
                        <div>
                            <h2 class="mb-3 fs-6">Chiffre d'affaires</h2>
                            <h3 class="fw-bold mb-0"><?= number_format($total_sales, 0, ',', ' ') ?> FCFA</h3>
                            <p class="text-primary mb-0 small">Total des ventes</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-12">
                <div class="card p-4 bg-success-light border border-success border-opacity-25 rounded-3">
                    <div class="d-flex gap-3">
                        <div class="icon-shape bg-success text-white rounded-2">
                            <i class="ti ti-repeat fs-4"></i>
                        </div>
                        <div>
                            <h2 class="mb-3 fs-6">Total commandes</h2>
                             <h3 class="fw-bold mb-0"><?= number_format($total_commandes, 0, ',', ' ') ?></h3>
                            <p class="text-success mb-0 small">Commandes passées</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-12">
                <div class="card p-4 bg-info-light border border-info border-opacity-25 rounded-3">
                    <div class="d-flex gap-3">
                        <div class="icon-shape bg-info text-white rounded-2">
                            <i class="ti ti-currency-dollar fs-4"></i>
                        </div>
                        <div>
                            <h2 class="mb-3 fs-6">Factures impayées</h2>
                            <h3 class="fw-bold mb-0"><?= number_format($total_expenses, 0, ',', ' ') ?> FCFA</h3>
                            <p class="text-info mb-0 small">À encaisser</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-12">
                <div class="card p-4 bg-warning-light border border-warning border-opacity-25 rounded-3">
                    <div class="d-flex gap-3">
                        <div class="icon-shape bg-warning text-white rounded-2">
                            <i class="ti ti-notes fs-4"></i>
                        </div>
                        <div>
                            <h2 class="mb-3 fs-6">Bénéfice net</h2>
                            <h3 class="fw-bold mb-0"><?= number_format($total_profit, 0, ',', ' ') ?> FCFA</h3>
                            <p class="text-warning mb-0 small">CA - Impayés</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cartes supplémentaires -->
        <div class="row g-3 mb-4">
            <div class="col-lg-4 col-12">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between border-bottom pb-5 mb-3">
                            <div>
                                <h3 class="fw-bold h4"><?= number_format($total_sales, 0, ',', ' ') ?> FCFA</h3>
                                <span>Total Profit</span>
                            </div>
                            <div>
                                <i class="ti ti-layers-subtract fs-1 text-primary"></i>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small">
                            <div class="text-muted">Ventes totales</div>
                            <div><a href="commandes/index_commande.php" class="link-primary text-decoration-underline">Voir</a></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between border-bottom pb-5 mb-3">
                            <div>
                                <h3 class="fw-bold h4"><?= number_format($total_expenses, 0, ',', ' ') ?> FCFA</h3>
                                <span>Total à encaisser</span>
                            </div>
                            <div>
                                <i class="ti ti-credit-card fs-1 text-danger"></i>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small">
                            <div class="text-muted">Factures impayées</div>
                            <div><a href="factures/index_facture.php" class="link-primary text-decoration-underline">Voir</a></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between border-bottom pb-5 mb-3">
                            <div>
                                <h3 class="fw-bold h4"><?= number_format($total_sales - $total_expenses, 0, ',', ' ') ?> FCFA</h3>
                                <span>Bénéfice net</span>
                            </div>
                            <div>
                                <i class="ti ti-cash-banknote fs-1 text-warning"></i>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small">
                            <div class="text-muted">Après impayés</div>
                            <div><a href="#" class="link-primary text-decoration-underline">Détails</a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center bg-transparent px-4 py-3">
                        <h3 class="h5 mb-0">Ventes mensuelles</h3>
                        <div>
                            <select class="form-select form-select-sm" id="yearSelect">
                                <option selected>Cette année</option>
                                <option>Ce mois</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div id="salesChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center bg-transparent px-4 py-3">
                        <h3 class="h5 mb-0">Aperçu clients</h3>
                        <div>
                            <select class="form-select form-select-sm">
                                <option selected>Global</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <h3 class="h6">Clients</h3>
                        <div class="row align-items-center">
                            <div class="col-sm-6">
                                <div id="customerChart"></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="row">
                                    <div class="col-6 border-end">
                                        <div class="text-center">
                                            <h2 class="mb-1"><?= $first_time ?></h2>
                                            <p class="text-success mb-2">Nouveaux</p>
                                            <span class="badge bg-success"><i class="ti ti-arrow-up-left me-1"></i><?= round(($first_time / max($total_customers, 1)) * 100) ?>%</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center">
                                            <h2 class="mb-1"><?= $return_customers ?></h2>
                                            <p class="text-warning mb-2">Fidèles</p>
                                            <span class="badge bg-success"><i class="ti ti-arrow-up-left me-1"></i><?= round(($return_customers / max($total_customers, 1)) * 100) ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row text-center border-top mt-4 pt-4">
                            <div class="col-4 border-end">
                                <h3 class="fw-bold mb-2"><?= number_format($total_produits, 0, ',', ' ') ?></h3>
<small class="text-secondary">Produits</small>
                            </div>
                            <div class="col-4 border-end">
                                <h3 class="fw-bold mb-2"><?= number_format($total_customers, 0, ',', ' ') ?></h3>
                                <small class="text-secondary">Clients</small>
                            </div>
                            <div class="col-4">
 <h3 class="fw-bold mb-2"><?= number_format($total_commandes, 0, ',', ' ') ?></h3>
                                <small class="text-secondary">Commandes</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Selling, Low Stock, Recent Sales -->
        <div class="row g-3">
            <!-- Top Selling Products -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
                        <h4 class="mb-0 h5">Top produits</h4>
                        <button class="btn btn-sm btn-outline-secondary">
                            <i class="ti ti-calendar"></i> Annuel
                        </button>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach($top_products as $p): ?>
                        <li class="list-group-item d-flex align-items-center gap-3">
                            <div class="flex-grow-1">
                                <p class="mb-1"><?= htmlspecialchars($p['nomp']) ?></p>
                                <div class="d-flex align-items-center gap-2 text-muted">
                                    <small class="fw-semibold"><?= number_format($p['total_vendu'] / max($p['qte_vendue'], 1), 0, ',', ' ') ?> FCFA</small>
                                    <small>•</small>
                                    <small><?= $p['qte_vendue'] ?> unités</small>
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary">Top</span>
                        </li>
                        <?php endforeach; ?>
                        <?php if(count($top_products) == 0): ?>
                        <li class="list-group-item text-center py-4 text-secondary">Aucune donnée</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Low Stock Products -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
                        <h4 class="mb-0 h5">Stock faible</h4>
                        <a href="produits/index_produit.php" class="small text-primary text-decoration-underline">Voir tout</a>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach($low_stock as $p): ?>
                        <li class="list-group-item d-flex align-items-center gap-3">
                            <div class="flex-grow-1">
                                <p class="mb-1"><?= htmlspecialchars($p['nomp']) ?></p>
                                
                            </div>
                            <div class="d-flex flex-column gap-0 align-items-center">
                                <span class="fw-semibold text-primary"><?= $p['quantite'] ?></span>
                                <small class="text-muted">En stock</small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <?php if(count($low_stock) == 0): ?>
                        <li class="list-group-item text-center py-4 text-success">✅ Tous les stocks sont bons</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Recent Sales -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
                        <h4 class="mb-0 h5">Dernières ventes</h4>
                        <button class="btn btn-sm btn-outline-secondary">
                            <i class="ti ti-calendar-event"></i> Récent
                        </button>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach($recent_sales as $sale): ?>
                        <li class="list-group-item d-flex align-items-center gap-3">
                            <div class="flex-grow-1">
                                <p class="mb-1"><?= htmlspecialchars($sale['client_nom']) ?></p>
                                <div class="d-flex align-items-center gap-2 text-muted">
                                    <!-- <small class="fw-semibold">#<?= $sale['id'] ?></small> -->
                                    <small>•</small>
                                    <small><?= $sale['produit_nom'] ?? '-' ?></small>
                                </div>
                            </div>
                            <span class="badge bg-success-subtle text-success"><?= number_format($sale['total_ttc'], 0, ',', ' ') ?> FCFA</span>
                        </li>
                        <?php endforeach; ?>
                        <?php if(count($recent_sales) == 0): ?>
                        <li class="list-group-item text-center py-4 text-secondary">Aucune vente récente</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Graphique des ventes mensuelles
var salesOptions = {
    series: [{
        name: 'Ventes (FCFA)',
        data: <?= json_encode($sales_values) ?>
    }],
    chart: {
        type: 'area',
        height: 350,
        toolbar: { show: false },
        zoom: { enabled: false }
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
    xaxis: {
        categories: <?= json_encode($sales_labels) ?>,
        labels: { style: { fontSize: '12px' } }
    },
    yaxis: {
        labels: {
            formatter: function(val) {
                return val.toLocaleString('fr-FR') + ' FCFA';
            }
        }
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

// Graphique des clients (donut)
var customerOptions = {
    series: [<?= $first_time ?>, <?= $return_customers ?>],
    chart: { type: 'donut', height: 200 },
    labels: ['Nouveaux clients', 'Clients fidèles'],
    colors: ['#10b981', '#f59e0b'],
    legend: { position: 'bottom' },
    dataLabels: { enabled: false },
    plotOptions: {
        pie: {
            donut: { size: '65%' }
        }
    }
};

var customerChart = new ApexCharts(document.querySelector("#customerChart"), customerOptions);
customerChart.render();
</script>

<script>
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