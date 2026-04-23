 <?php
// src/Frontend/clients/view_client.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index_client.php');
    exit();
}

// Récupérer le client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: index_client.php');
    exit();
}

// Récupérer les commandes avec leur total
$stmt = $pdo->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM detail_commande WHERE commande_id = c.id) as nb_articles
    FROM commandes c
    WHERE c.client_id = ?
    ORDER BY c.date_commande DESC
");
$stmt->execute([$id]);
$commandes = $stmt->fetchAll();

// Calculs statistiques CORRECTS
$total_commandes = count($commandes);
$total_achats = array_sum(array_column($commandes, 'total_ttc'));
 // Récupérer les produits avec BON calcul (regrouper UNIQUEMENT par produit)
$stmt = $pdo->prepare("
    SELECT 
        p.id, 
        p.nomp,
        SUM(dc.quantite) as quantite_totale,
        SUM(dc.quantite * dc.prix_unitaire) as montant_total,
        ROUND(AVG(dc.prix_unitaire)) as prix_moyen
    FROM detail_commande dc
    JOIN produits p ON dc.produit_id = p.id
    JOIN commandes c ON dc.commande_id = c.id
    WHERE c.client_id = ?
    GROUP BY p.id, p.nomp
    ORDER BY montant_total DESC
");
$stmt->execute([$id]);
$produits = $stmt->fetchAll();
$total_produits = count($produits);

$page_title = $client['prenom'] . ' ' . $client['nomc'];
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | PowerStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root { --primary: #E66239; --success: #10b981; --warning: #f59e0b; --border: #e2e8f0; }
        .info-card { background: #f8fafc; border-radius: 20px; padding: 24px; margin-bottom: 24px; border-left: 4px solid var(--primary); }
        .stat-card { background: white; border-radius: 16px; padding: 20px; text-align: center; border: 1px solid var(--border); transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .stat-value { font-size: 32px; font-weight: 700; color: var(--primary); }
        .stat-label { color: #64748b; font-size: 13px; margin-top: 5px; }
        .section-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--border); }
        .btn-outline-custom { background: white; border: 2px solid var(--primary); color: var(--primary); border-radius: 12px; padding: 8px 20px; text-decoration: none; transition: all 0.2s; }
        .btn-outline-custom:hover { background: var(--primary); color: white; }
        .card-custom { background: white; border-radius: 20px; border: 1px solid var(--border); overflow: hidden; margin-bottom: 24px; }
        .badge-paid { background: var(--success); color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px; }
        .badge-pending { background: var(--warning); color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px; }
        @media (max-width: 768px) {
            .info-card { padding: 16px; }
            .stat-value { font-size: 24px; }
            .stat-card { padding: 12px; }
            .btn-outline-custom, .btn-warning, .btn-danger { padding: 6px 14px; font-size: 13px; }
            .table-responsive { font-size: 13px; }
        }
    </style>
</head>
<body>

<!-- TO PBAR COMPLETE comme dans index_produit.php -->
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
        
        <!-- En-tête avec bouton Modifier VISIBLE -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="fs-3 fw-bold mb-1">
                    <i class="fa-solid fa-user me-2" style="color: var(--primary);"></i>
                    <?= htmlspecialchars($client['prenom'] . ' ' . $client['nomc']) ?>
                </h1>
                <p class="text-secondary mb-0 small">Fiche client détaillée</p>
            </div>
            <div class="d-flex gap-2">
                <a href="edit_client.php?id=<?= $id ?>" class="btn" style="background: var(--warning); color: white; padding: 8px 20px; border-radius: 12px;">
                    <i class="fa-solid fa-pen"></i> Modifier
                </a>
                <a href="index_client.php" class="btn-outline-custom">
                    <i class="fa-solid fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <!-- Cartes statistiques -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-value"><?= $total_commandes ?></div>
                    <div class="stat-label">Commandes</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($total_achats, 0, ',', ' ') ?></div>
                    <div class="stat-label">Total achats (FCFA)</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-value"><?= $total_produits ?></div>
                    <div class="stat-label">Produits différents</div>
                </div>
            </div>
        </div>

        <!-- Informations client -->
        <div class="info-card">
            <h5 class="section-title"><i class="fa-solid fa-address-card me-2" style="color: var(--primary);"></i>Coordonnées</h5>
            <div class="row">
                <div class="col-md-6 mb-2"><strong>Nom complet :</strong><br><?= htmlspecialchars($client['prenom'] . ' ' . ($client['nomc'] ?? '')) ?></div>
                <div class="col-md-6 mb-2"><strong>Téléphone :</strong><br><?= htmlspecialchars($client['tel'] ?? 'Non renseigné') ?></div>
                <div class="col-md-6 mb-2"><strong>Email :</strong><br><?= htmlspecialchars($client['email'] ?? 'Non renseigné') ?></div>
                <div class="col-md-6 mb-2"><strong>Adresse :</strong><br><?= htmlspecialchars($client['adresse'] ?? 'Non renseignée') ?></div>
            </div>
        </div>

        <!-- Commandes avec leur TOTAL -->
        <div class="card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="fa-solid fa-history me-2" style="color: var(--primary);"></i>Commandes</h5>
                <a href="../commandes/create_commande.php?client_id=<?= $id ?>" class="btn btn-sm" style="background: var(--success); color: white;">
                    <i class="fa-solid fa-plus"></i> Nouvelle commande
                </a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N°</th>
                            <th>Date</th>
                            <th>Articles</th>
                            <th class="text-end">Total</th>
                            <th>Statut</th>
                            <th>Facture</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($total_commandes > 0): ?>
                            <?php foreach($commandes as $cmd): ?>
                            <tr>
                                <td><strong>#<?= $cmd['id'] ?></strong></td>
                                <td><?= date('d/m/Y', strtotime($cmd['date_commande'])) ?></td>
                                <td><?= $cmd['nb_articles'] ?? 0 ?> article(s)</td>
                                <td class="text-end fw-bold"><?= number_format($cmd['total_ttc'], 0, ',', ' ') ?> FCFA</span>
                                <td>
                                    <?php if($cmd['statut'] == 'livree'): ?>
                                        <span class="badge-paid"><i class="fa-solid fa-check"></i> Livrée</span>
                                    <?php elseif($cmd['statut'] == 'en_attente'): ?>
                                        <span class="badge-pending"><i class="fa-solid fa-clock"></i> En attente</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Annulée</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($cmd['facture_id']): ?>
                                        <a href="../factures/view_facture.php?id=<?= $cmd['facture_id'] ?>" class="btn btn-sm btn-outline-danger" title="Voir facture">
                                            <i class="fa-regular fa-file-pdf"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4">Aucune commande</span></td>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Produits avec BONS CALCULS -->
        <?php if($total_produits > 0): ?>
        <div class="card-custom">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fa-solid fa-boxes me-2" style="color: var(--primary);"></i>Produits achetés</h5>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produit</th>
                            <th class="text-end">Prix unitaire</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-end">Total dépensé</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $verif_total = 0;
                        foreach($produits as $p): 
                            $verif_total += $p['montant_total'];
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['nomp']) ?></strong></span>
                            <td class="text-end"><?= number_format($p['prix_moyen'], 0, ',', ' ') ?> FCFA</span>
                            <td class="text-center"><?= $p['quantite_totale'] ?>x</span>
                            <td class="text-end fw-bold text-primary"><?= number_format($p['montant_total'], 0, ',', ' ') ?> FCFA</span>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">TOTAL GÉNÉRAL :</span>
                            <td class="text-end fw-bold fs-5" style="color: var(--primary);"><?= number_format($total_achats, 0, ',', ' ') ?> FCFA</span>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Suppression -->
        <div class="mt-4 d-flex justify-content-end">
            <a href="delete_client.php?id=<?= $id ?>" class="btn btn-danger" onclick="return confirm('⚠️ Supprimer ce client ?\n\nToutes ses commandes seront également supprimées.')">
                <i class="fa-solid fa-trash"></i> Supprimer le client
            </a>
        </div>
        
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Gestion toggle sidebar (comme dans tous les fichiers)
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