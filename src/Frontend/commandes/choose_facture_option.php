<?php
// src/Frontend/commandes/choose_facture_option.php
require_once '../../config/app.php';
requireAdmin();

session_start();

// Vérifier qu'on vient bien de update_status
if (!isset($_SESSION['pending_livraison'])) {
    header('Location: index_commande.php');
    exit();
}

$pdo = getPDO();
$pending = $_SESSION['pending_livraison'];
$commande_id = $pending['commande_id'];
$client_id = $pending['client_id'];
$client_nom = $pending['client_nom'];
$total_ttc = $pending['total_ttc'];

// Récupérer les factures impayées de ce client
$stmt = $pdo->prepare("
    SELECT f.id, f.nomf, SUM(c.total_ttc) as total, COUNT(c.id) as nb_commandes
    FROM factures f
    LEFT JOIN commandes c ON f.id = c.facture_id
    WHERE c.client_id = ? AND f.etatf = 0
    GROUP BY f.id
");
$stmt->execute([$client_id]);
$factures_existantes = $stmt->fetchAll();

$page_title = 'Option de facturation';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choix de la facture | PowerStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root { --primary: #E66239; --success: #10b981; --warning: #f59e0b; }
        .choice-card { cursor: pointer; transition: all 0.3s; border: 2px solid #e2e8f0; border-radius: 16px; padding: 24px; height: 100%; }
        .choice-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .choice-card.selected { border-color: var(--primary); background: #fef4f0; }
        .choice-icon { font-size: 48px; margin-bottom: 16px; }
        .btn-primary-custom { background: var(--primary); border: none; padding: 12px 30px; border-radius: 12px; color: white; font-weight: 500; }
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
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- En-tête -->
                <div class="text-center mb-5">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fa-solid fa-truck fa-2x"></i>
                    </div>
                    <h1 class="fs-2 fw-bold">Confirmation de livraison</h1>
                    <p class="text-secondary">Commande #<?= $commande_id ?> - <strong><?= htmlspecialchars($client_nom) ?></strong> - <?= number_format($total_ttc, 0, ',', ' ') ?> FCFA</p>
                </div>
                
                <!-- Message d'info -->
                <div class="alert alert-info mb-4">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    Comment souhaitez-vous gérer la facture pour cette livraison ?
                </div>
                
                <!-- Options -->
                <div class="row g-4 mb-4">
                    
                    <!-- Option 1 : Ajouter à une facture existante -->
                    <div class="col-md-6">
                        <div class="choice-card" data-option="existing">
                            <div class="text-center">
                                <div class="choice-icon">
                                    <i class="fa-solid fa-layer-group" style="color: var(--warning);"></i>
                                </div>
                                <h4 class="fw-bold">Ajouter à une facture existante</h4>
                                <p class="text-secondary">Regrouper avec d'autres commandes du même client</p>
                            </div>
                            
                            <?php if(count($factures_existantes) > 0): ?>
                                <div class="mt-3">
                                    <label class="form-label fw-semibold">Sélectionner une facture :</label>
                                    <select class="form-select" id="existing_facture_id">
                                        <option value="">-- Choisir une facture --</option>
                                        <?php foreach($factures_existantes as $f): ?>
                                            <option value="<?= $f['id'] ?>">
                                                <?= htmlspecialchars($f['nomf']) ?> - <?= number_format($f['total'], 0, ',', ' ') ?> FCFA (<?= $f['nb_commandes'] ?> commande(s))
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mt-3 mb-0 small">
                                    <i class="fa-solid fa-exclamation-triangle"></i>
                                    Aucune facture impayée existante pour ce client.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Option 2 : Nouvelle facture -->
                    <div class="col-md-6">
                        <div class="choice-card" data-option="new">
                            <div class="text-center">
                                <div class="choice-icon">
                                    <i class="fa-solid fa-file-invoice" style="color: var(--success);"></i>
                                </div>
                                <h4 class="fw-bold">Créer une nouvelle facture</h4>
                                <p class="text-secondary">Facture séparée pour cette commande uniquement</p>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Option 3 : Livrer sans facture -->
                <div class="text-center mb-4">
                    <button class="btn btn-link text-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#noInvoiceOption">
                        <i class="fa-solid fa-chevron-down"></i> Options avancées
                    </button>
                </div>
                
                <div class="collapse" id="noInvoiceOption">
                    <div class="card border-warning mb-4">
                        <div class="card-body text-center">
                            <i class="fa-solid fa-clock fa-2x mb-2 text-warning"></i>
                            <h5>Livrer sans générer de facture</h5>
                            <p class="text-secondary small">La commande sera marquée comme livrée mais aucune facture ne sera créée.<br>
                            Vous pourrez la facturer plus tard manuellement.</p>
                            <button class="btn btn-outline-warning" id="livrerSansFactureBtn">
                                <i class="fa-solid fa-truck"></i> Livrer sans facture
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Boutons d'action -->
                <div class="d-flex justify-content-between gap-3 mt-4">
                    <a href="view_commande.php?id=<?= $commande_id ?>" class="btn btn-secondary px-4">
                        <i class="fa-solid fa-times"></i> Annuler
                    </a>
                    <button class="btn btn-primary-custom px-5" id="confirmBtn" disabled>
                        <i class="fa-solid fa-check-circle"></i> Confirmer la livraison
                    </button>
                </div>
                
            </div>
        </div>
        
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let selectedOption = null;
let selectedFactureId = null;

// Gestion du clic sur les cartes
document.querySelectorAll('.choice-card').forEach(card => {
    card.addEventListener('click', function(e) {
        if(e.target.closest('select')) return;
        
        document.querySelectorAll('.choice-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        selectedOption = this.dataset.option;
        
        if(selectedOption === 'existing') {
            selectedFactureId = document.getElementById('existing_facture_id').value;
        } else {
            selectedFactureId = null;
        }
        
        updateConfirmButton();
    });
});

// Gestion du changement de select
document.getElementById('existing_facture_id')?.addEventListener('change', function() {
    if(selectedOption === 'existing') {
        selectedFactureId = this.value;
        updateConfirmButton();
    }
});

// Gestion du bouton "Livrer sans facture"
document.getElementById('livrerSansFactureBtn')?.addEventListener('click', function() {
    window.location.href = `process_livraison.php?option=none&commande_id=<?= $commande_id ?>`;
});

function updateConfirmButton() {
    const btn = document.getElementById('confirmBtn');
    
    if(selectedOption === 'existing' && selectedFactureId) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Ajouter à la facture et livrer';
    } else if(selectedOption === 'new') {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Créer nouvelle facture et livrer';
    } else {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Confirmer la livraison';
    }
}

document.getElementById('confirmBtn')?.addEventListener('click', function() {
    if(selectedOption === 'existing' && selectedFactureId) {
        window.location.href = `process_livraison.php?option=existing&commande_id=<?= $commande_id ?>&facture_id=${selectedFactureId}`;
    } else if(selectedOption === 'new') {
        window.location.href = `process_livraison.php?option=new&commande_id=<?= $commande_id ?>`;
    }
});
</script>
</body>
</html>