<?php
// src/Frontend/commandes/view_commande.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index_commande.php');
    exit();
}

// Récupérer les infos de la commande
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        CONCAT(cl.prenom, ' ', cl.nomc) as client_nom,
        cl.tel as client_tel,
        cl.email as client_email,
        cl.adresse as client_adresse,
        f.id as facture_id,
        f.nomf as facture_numero,
        f.datef as facture_date
    FROM commandes c
    LEFT JOIN clients cl ON c.client_id = cl.id
    LEFT JOIN factures f ON c.facture_id = f.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$commande = $stmt->fetch();

if (!$commande) {
    header('Location: index_commande.php');
    exit();
}

// Récupérer les produits
$stmt = $pdo->prepare("
    SELECT d.*, p.nomp as produit_nom, p.image as produit_image
    FROM detail_commande d
    LEFT JOIN produits p ON d.produit_id = p.id
    WHERE d.commande_id = ?
");
$stmt->execute([$id]);
$details = $stmt->fetchAll();

$total = 0;
foreach ($details as $d) {
    $total += $d['quantite'] * $d['prix_unitaire'];
}

$page_title = 'Détail commande #' . $id;
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail commande #<?= $id ?> | PowerStock</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root { --primary: #E66239; --success: #10b981; --danger: #ef4444; --warning: #f59e0b; --border: #e2e8f0; }
        .info-card { background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 24px; border-left: 4px solid var(--primary); }
        .status-badge { padding: 6px 14px; border-radius: 30px; font-size: 13px; font-weight: 500; display: inline-block; }
        .status-en_attente { background: var(--warning); color: white; }
        .status-livree { background: var(--success); color: white; }
        .status-annulee { background: var(--danger); color: white; }
        @media (max-width: 768px) {
            .info-card { margin-bottom: 15px; }
            .table-responsive { font-size: 13px; }
            .btn-group-vertical-custom { display: flex; flex-direction: column; gap: 10px; width: 100%; }
            .btn-group-vertical-custom a, .btn-group-vertical-custom button { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>

<nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
    <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>
    <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>
    <div class="ms-auto">
        <ul class="list-unstyled d-flex align-items-center mb-0 gap-2">
            <li><a class="btn btn-light btn-icon btn-sm rounded-circle position-relative" href="#"><i class="ti ti-bell"></i></a></li>
            <li class="dropdown">
                <a href="#" data-bs-toggle="dropdown"><img src="../../assets/images/avatar/avatar-1.jpg" alt="" class="avatar avatar-sm rounded-circle" /></a>
                <div class="dropdown-menu dropdown-menu-end p-0">
                    <div class="p-3"><a href="../../logout.php" class="text-decoration-none">Déconnexion</a></div>
                </div>
            </li>
        </ul>
    </div>
</nav>

<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        
        <?php if(isset($_GET['facture']) && $_GET['facture'] == 1 && $commande['facture_id']): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fa-solid fa-check-circle me-2"></i>
                <strong>Commande marquée comme livrée !</strong> La facture a été générée automatiquement.
                <a href="../factures/view_facture.php?id=<?= $commande['facture_id'] ?>" class="alert-link ms-2">
                    <i class="fa-solid fa-file-pdf"></i> Voir la facture
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="fs-3 fw-bold mb-1">
                    <i class="fa-solid fa-eye me-2" style="color: var(--primary);"></i>
                    Détail commande #<?= $id ?>
                </h1>
                <p class="text-secondary mb-0 small">Informations complètes de la commande</p>
            </div>
            <a href="index_commande.php" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-2"></i> Retour
            </a>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="mb-3"><i class="fa-solid fa-user me-2" style="color: var(--primary);"></i>Informations client</h5>
                    <p class="mb-1"><strong><?= htmlspecialchars($commande['client_nom'] ?? 'N/A') ?></strong></p>
                    <p class="mb-1"><i class="fa-solid fa-phone me-2 text-secondary"></i> <?= htmlspecialchars($commande['client_tel'] ?? 'Non renseigné') ?></p>
                    <p class="mb-1"><i class="fa-solid fa-envelope me-2 text-secondary"></i> <?= htmlspecialchars($commande['client_email'] ?? 'Non renseigné') ?></p>
                    <p class="mb-0"><i class="fa-solid fa-location-dot me-2 text-secondary"></i> <?= htmlspecialchars($commande['client_adresse'] ?? 'Non renseignée') ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="mb-3"><i class="fa-solid fa-info-circle me-2" style="color: var(--primary);"></i>Informations commande</h5>
                    <p class="mb-1"><strong>Date :</strong> <?= date('d/m/Y', strtotime($commande['date_commande'])) ?></p>
                    <p class="mb-1">
                        <strong>Statut :</strong> 
                        <span class="status-badge status-<?= $commande['statut'] ?>">
                            <?= $commande['statut'] === 'en_attente' ? 'En attente' : ($commande['statut'] === 'livree' ? 'Livrée' : 'Annulée') ?>
                        </span>
                    </p>
                    <p class="mb-0">
                        <strong>Facture :</strong> 
                        <?php if($commande['facture_id']): ?>
                            <a href="../factures/view_facture.php?id=<?= $commande['facture_id'] ?>" class="btn btn-sm btn-outline-danger mt-1">
                                <i class="fa-solid fa-file-pdf me-1"></i>
                                📄 Voir la facture #<?= $commande['facture_id'] ?>
                            </a>
                        <?php else: ?>
                            <span class="text-secondary">Non générée</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fa-solid fa-box me-2" style="color: var(--primary);"></i> Produits commandés</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Produit</th><th class="text-center">Prix unitaire</th><th class="text-center">Quantité</th><th class="text-end">Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($details as $d): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['produit_nom']) ?></strong></td>
                            <td class="text-center"><?= number_format($d['prix_unitaire'], 0, ',', ' ') ?> FCFA</span>
                            <td class="text-center"><?= $d['quantite'] ?></td>
                            <td class="text-end fw-bold"><?= number_format($d['quantite'] * $d['prix_unitaire'], 0, ',', ' ') ?> FCFA</span>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr><td colspan="3" class="text-end fw-bold">TOTAL :</span><td class="text-end fs-5 fw-bold" style="color: var(--primary);"><?= number_format($total, 0, ',', ' ') ?> FCFA</span></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Boutons d'action TOUJOURS VISIBLES avec confirmations adaptées -->
        <div class="mt-4 d-flex justify-content-end gap-2 flex-wrap">
            
            <?php if($commande['statut'] !== 'livree'): ?>
                <!-- Passer à livrée -->
                <a href="update_status.php?id=<?= $id ?>&status=livree" 
                   class="btn btn-success btn-sm" 
                   onclick="return confirm('Marquer cette commande comme livrée ? Une facture sera générée automatiquement.')">
                    <i class="fa-solid fa-check-circle"></i> Marquer livrée
                </a>
            <?php endif; ?>
            
            <?php if($commande['statut'] !== 'annulee'): ?>
                <!-- Passer à annulée -->
                <a href="update_status.php?id=<?= $id ?>&status=annulee"
                   class="btn btn-dangerbtn-sm"
                   onclick="<?php echo 'return confirm(\'⚠️ Annuler cette commande ?\\n\\n' . ($commande['statut'] === 'livree' ? 'Le stock sera restauré et la facture supprimée.' : '') . '\')'; ?>">
                    <i class="fa-solid fa-ban"></i> Annuler
                </a>
                <?php if(isset($_GET['added_to_facture'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa-solid fa-check-circle me-2"></i>
        <strong>Commande livrée !</strong> La commande a été ajoutée à la facture #<?= $_GET['added_to_facture'] ?>.
        <a href="../factures/view_facture.php?id=<?= $_GET['added_to_facture'] ?>" class="alert-link ms-2">
            <i class="fa-solid fa-file-invoice"></i> Voir la facture
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
            <?php endif; ?>
            
            <?php if($commande['statut'] !== 'en_attente'): ?>
                <!-- Repasser en attente -->
                <a href="update_status.php?id=<?= $id ?>&status=en_attente"
                   class="btn btn-warning btn-sm"
                   onclick="<?php echo 'return confirm(\'⚠️ Repasser cette commande en "En attente" ?\\n\\n' . ($commande['statut'] === 'livree' ? 'La facture sera supprimée.' : '') . '\')'; ?>">
                    <i class="fa-solid fa-undo"></i> Repasser en attente
                </a>
            <?php endif; ?>
            
            <!-- Modifier (toujours possible) -->
            <?php if($commande['statut'] !== 'livree'): ?>
                <a href="edit_commande.php?id=<?= $id ?>" class="btn" style="background: var(--warning); color: white;">
                    <i class="fa-solid fa-pen"></i> Modifier
                </a>
            <?php endif; ?>
            
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>