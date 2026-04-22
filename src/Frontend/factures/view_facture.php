 <?php
// src/Frontend/factures/view_facture.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index_facture.php');
    exit();
}

// Récupérer infos facture
$stmt = $pdo->prepare("SELECT * FROM factures WHERE id = ?");
$stmt->execute([$id]);
$facture = $stmt->fetch();

if (!$facture) {
    header('Location: index_facture.php');
    exit();
}

// Récupérer les commandes liées
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.date_commande,
        c.total_ttc,
        c.statut,
        CONCAT(cl.prenom, ' ', cl.nomc) as client_nom,
        cl.tel as client_tel,
        cl.adresse as client_adresse,
        cl.id as client_id,
        GROUP_CONCAT(CONCAT(p.nomp, ' (x', d.quantite, ')') SEPARATOR ', ') as produits
    FROM commandes c
    LEFT JOIN clients cl ON c.client_id = cl.id
    LEFT JOIN detail_commande d ON c.id = d.commande_id
    LEFT JOIN produits p ON d.produit_id = p.id
    WHERE c.facture_id = ?
    GROUP BY c.id
    ORDER BY c.date_commande DESC
");
$stmt->execute([$id]);
$commandes = $stmt->fetchAll();

// Total
$total_ttc = array_sum(array_column($commandes, 'total_ttc'));

// Infos client (première commande)
$client = !empty($commandes) ? $commandes[0] : null;
$client_id = $client ? $client['client_id'] : 0;

// RÉCUPÉRATION SIMPLIFIÉE des commandes disponibles
$commandes_disponibles = [];
if ($facture['etatf'] == 0 && $client_id > 0) {
    // Version simplifiée pour tester
    $stmt = $pdo->prepare("
        SELECT id, date_commande, total_ttc
        FROM commandes
        WHERE client_id = ?
          AND statut = 'livree'
          AND (facture_id IS NULL OR facture_id = 0)
          AND id NOT IN (SELECT commande_id FROM detail_commande WHERE commande_id IN (SELECT id FROM commandes WHERE facture_id = ?))
    ");
    $stmt->execute([$client_id, $id]);
    $commandes_disponibles = $stmt->fetchAll();
    
    // SI toujours vide, on prend TOUTES les commandes livrées du client (debug)
    if (empty($commandes_disponibles)) {
        $stmt = $pdo->prepare("
            SELECT id, date_commande, total_ttc
            FROM commandes
            WHERE client_id = ? AND statut = 'livree' AND (facture_id IS NULL OR facture_id = 0)
        ");
        $stmt->execute([$client_id]);
        $commandes_disponibles = $stmt->fetchAll();
    }
}

$page_title = 'Facture #' . $facture['nomf'];
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
        :root { --primary: #E66239; --success: #10b981; --danger: #ef4444; --border: #e2e8f0; }
        .info-card { background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 24px; border-left: 4px solid var(--primary); }
        .status-badge-paid { background: var(--success); color: white; padding: 6px 14px; border-radius: 30px; font-size: 13px; display: inline-block; }
        .status-badge-unpaid { background: var(--danger); color: white; padding: 6px 14px; border-radius: 30px; font-size: 13px; display: inline-block; }
        .btn-primary-custom { background: var(--primary); border: none; padding: 10px 24px; border-radius: 12px; color: white; transition: all 0.2s; }
        .btn-primary-custom:hover { background: #d5542e; transform: translateY(-2px); }
        .btn-outline-custom { background: white; border: 2px solid var(--primary); color: var(--primary); border-radius: 12px; padding: 8px 20px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-add-commande { background: #f59e0b; color: white; border: none; padding: 6px 16px; border-radius: 8px; font-size: 13px; }
        .btn-add-commande:hover { background: #e67e22; }
        @media (max-width: 768px) { 
            .btn-group-vertical-custom { display: flex; flex-direction: column; gap: 10px; } 
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
            <li><a class="btn btn-light btn-icon btn-sm rounded-circle" href="#"><i class="ti ti-bell"></i></a></li>
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
        
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fa-solid fa-check-circle me-2"></i>
                <?php if($_GET['success'] == 'added'): ?>
                    ✓ Commande ajoutée avec succès !
                <?php elseif($_GET['success'] == 'removed'): ?>
                    ✓ Commande retirée avec succès !
                <?php elseif($_GET['success'] == 'paid'): ?>
                    ✓ Facture marquée comme payée !
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="fs-3 fw-bold mb-1">
                    <i class="fa-solid fa-file-invoice me-2" style="color: var(--primary);"></i>
                    Facture <?= htmlspecialchars($facture['nomf']) ?>
                </h1>
                <p class="text-secondary mb-0 small">Détail de la facture et commandes associées</p>
            </div>
            <a href="index_facture.php" class="btn-outline-custom">
                <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
        </div>

        <!-- Info Facture + Client -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="mb-3"><i class="fa-solid fa-file-invoice me-2" style="color: var(--primary);"></i>Informations facture</h5>
                    <p class="mb-2"><strong>N° Facture :</strong> <?= htmlspecialchars($facture['nomf']) ?></p>
                    <p class="mb-2"><strong>Date d'émission :</strong> <?= date('d/m/Y H:i', strtotime($facture['datef'])) ?></p>
                    <p class="mb-0">
                        <strong>Statut :</strong> 
                        <span class="status-badge-<?= $facture['etatf'] == 1 ? 'paid' : 'unpaid' ?>">
                            <?= $facture['etatf'] == 1 ? '✓ Payée' : '⚠️ Impayée' ?>
                        </span>
                    </p>
                </div>
            </div>
            <?php if($client): ?>
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="mb-3"><i class="fa-solid fa-user me-2" style="color: var(--primary);"></i>Client</h5>
                    <p class="mb-2"><strong><?= htmlspecialchars($client['client_nom']) ?></strong></p>
                    <p class="mb-2"><i class="fa-solid fa-phone me-2 text-secondary"></i> <?= htmlspecialchars($client['client_tel'] ?? 'Non renseigné') ?></p>
                    <p class="mb-0"><i class="fa-solid fa-location-dot me-2 text-secondary"></i> <?= htmlspecialchars($client['client_adresse'] ?? 'Non renseignée') ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Commandes -->
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">
                    <i class="fa-solid fa-boxes me-2" style="color: var(--primary);"></i> 
                    Commandes regroupées
                    <span class="badge bg-secondary ms-2"><?= count($commandes) ?> commande(s)</span>
                </h5>
                
                <!-- BOUTON AJOUTER - Visible si facture impayée ET commandes disponibles -->
                <?php if($facture['etatf'] == 0): ?>
                    <?php if(count($commandes_disponibles) > 0): ?>
                        <button class="btn btn-add-commande" data-bs-toggle="modal" data-bs-target="#addCommandeModal">
                            <i class="fa-solid fa-plus"></i> Ajouter une commande
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled title="Aucune commande disponible">
                            <i class="fa-solid fa-plus"></i> Ajouter (aucune dispo)
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° Commande</th>
                            <th>Date</th>
                            <th>Produits</th>
                            <th class="text-end">Montant</th>
                            <?php if($facture['etatf'] == 0): ?>
                                <th class="text-center" style="width: 60px;">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($commandes) > 0): ?>
                            <?php foreach($commandes as $cmd): ?>
                            <tr>
                                <td><strong>#<?= $cmd['id'] ?></strong></td>
                                <td><?= date('d/m/Y', strtotime($cmd['date_commande'])) ?></td>
                                <td><?= htmlspecialchars($cmd['produits'] ?? '-') ?></td>
                                <td class="text-end fw-bold"><?= number_format($cmd['total_ttc'], 0, ',', ' ') ?> FCFA</td>
                                <?php if($facture['etatf'] == 0): ?>
                                <td class="text-center">
                                    <a href="remove_commande.php?facture_id=<?= $id ?>&commande_id=<?= $cmd['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Retirer cette commande de la facture ?\nLe montant total sera recalculé.')"
                                       title="Retirer de la facture">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $facture['etatf'] == 0 ? 5 : 4 ?>" class="text-center py-5">
                                    <i class="fa-solid fa-inbox fa-2x mb-2 d-block text-secondary"></i>
                                    Aucune commande associée à cette facture
                                    <?php if($facture['etatf'] == 0 && count($commandes_disponibles) > 0): ?>
                                        <br><br>
                                        <button class="btn btn-sm btn-add-commande" data-bs-toggle="modal" data-bs-target="#addCommandeModal">
                                            <i class="fa-solid fa-plus"></i> Ajouter une commande
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold fs-5">TOTAL :</td>
                            <td class="text-end fw-bold fs-4" style="color: var(--primary);">
                                <?= number_format($total_ttc, 0, ',', ' ') ?> FCFA
                            </td>
                            <?php if($facture['etatf'] == 0): ?>
                            <td></td>
                            <?php endif; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-content-end gap-3 flex-wrap">
            <a href="print_facture.php?id=<?= $id ?>" class="btn-primary-custom" target="_blank">
                <i class="fa-solid fa-print"></i> Imprimer la facture
            </a>
            <?php if($facture['etatf'] == 0 && $total_ttc > 0): ?>
                <a href="update_facture_status.php?id=<?= $id ?>&status=paid" 
                   class="btn" style="background: var(--success); color: white; padding: 10px 24px; border-radius: 12px; text-decoration: none;"
                   onclick="return confirm('Confirmer l\'encaissement de <?= number_format($total_ttc, 0, ',', ' ') ?> FCFA ?\n\nCette action est irréversible.')">
                    <i class="fa-solid fa-money-bill-wave"></i> Encaisser et marquer payée
                </a>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal Ajouter commande -->
<div class="modal fade" id="addCommandeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header" style="background: #f59e0b; color: white; border-radius: 20px 20px 0 0;">
                <h5 class="modal-title"><i class="fa-solid fa-plus"></i> Ajouter une commande à la facture</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="add_commande_to_facture.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="facture_id" value="<?= $id ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sélectionner une commande</label>
                        <select name="commande_id" class="form-select" required style="border-radius: 10px;">
                            <option value="">-- Choisir une commande --</option>
                            <?php foreach($commandes_disponibles as $cmd): ?>
                                <option value="<?= $cmd['id'] ?>">
                                    #<?= $cmd['id'] ?> - <?= date('d/m/Y', strtotime($cmd['date_commande'])) ?> - <?= number_format($cmd['total_ttc'], 0, ',', ' ') ?> FCFA
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fa-solid fa-info-circle"></i>
                        Seules les commandes <strong>livrées</strong> et <strong>non encore facturées</strong> du même client apparaissent ici.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Annuler</button>
                    <button type="submit" class="btn" style="background: #f59e0b; color: white; border-radius: 10px;">
                        <i class="fa-solid fa-check"></i> Ajouter à la facture
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>