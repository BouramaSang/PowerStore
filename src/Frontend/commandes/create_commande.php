 <?php
// src/Frontend/commandes/create_commande.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$error = '';
$success = '';
$lastCommandeId = null;

$clients = $pdo->query("SELECT id, nomc, prenom, tel, email, adresse FROM clients ORDER BY nomc")->fetchAll();
$produits = $pdo->query("SELECT id, nomp, prix, quantite FROM produits ORDER BY nomp")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = (int)$_POST['client_id'];
    $date_commande = $_POST['date_commande'];
    $produits_json = $_POST['produits_json'];
    
    if ($client_id <= 0) {
        $error = "Veuillez sélectionner un client";
    } elseif (empty($produits_json)) {
        $error = "Ajoutez au moins un produit";
    } else {
        $items = json_decode($produits_json, true);
        $total = 0;
        $stock_ok = true;
        
        foreach ($items as $item) {
            $stmt = $pdo->prepare("SELECT prix, quantite FROM produits WHERE id = ?");
            $stmt->execute([$item['id']]);
            $pdt = $stmt->fetch();
            if (!$pdt) {
                $error = "Produit introuvable";
                $stock_ok = false;
                break;
            }
            if ($pdt['quantite'] < $item['qty']) {
                $error = "Stock insuffisant pour le produit (Stock: {$pdt['quantite']})";
                $stock_ok = false;
                break;
            }
            $total += $pdt['prix'] * $item['qty'];
        }
        $first_product_id = $items[0]['id'];
        if ($stock_ok && empty($error)) {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("
                    INSERT INTO commandes (client_id, date_commande, statut, total_ttc,produit_id) 
                    VALUES (?, ?, 'en_attente', ?, ?)
                ");
                $stmt->execute([$client_id, $date_commande, $total, $first_product_id]);
                $commande_id = $pdo->lastInsertId();
                
                foreach ($items as $item) {
                    $stmt = $pdo->prepare("SELECT prix FROM produits WHERE id = ?");
                    $stmt->execute([$item['id']]);
                    $prix = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO detail_commande (commande_id, produit_id, quantite, prix_unitaire) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$commande_id, $item['id'], $item['qty'], $prix]);
                    
                    $stmt = $pdo->prepare("UPDATE produits SET quantite = quantite - ? WHERE id = ?");
                    $stmt->execute([$item['qty'], $item['id']]);
                }
                
                $pdo->commit();
                $success = "Commande créée avec succès !";
                $lastCommandeId = $commande_id;
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

$page_title = 'Nouvelle commande';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouvelle commande | PowerStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root { --primary: #E66239; --border: #e2e8f0; }
        .card-custom { background: white; border-radius: 20px; border: 1px solid var(--border); overflow: hidden; }
        .btn-primary-custom { background: var(--primary); border: none; padding: 10px 24px; border-radius: 12px; color: white; font-weight: 500; transition: all 0.2s ease; }
        .btn-primary-custom:hover { background: #d5542e; transform: translateY(-2px); }
        .btn-outline-custom { background: white; border: 1.5px solid var(--border); padding: 10px 24px; border-radius: 12px; color: #64748b; transition: all 0.2s ease; }
        .btn-outline-custom:hover { border-color: var(--primary); color: var(--primary); }
        .btn-add-product { background: #f59e0b; border: none; padding: 10px 20px; border-radius: 10px; color: white; font-weight: 500; }
        .btn-add-product:hover { background: #e67e22; transform: translateY(-1px); }
        .product-row { background: #f8fafc; border-radius: 12px; padding: 12px; margin-bottom: 12px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .remove-product { cursor: pointer; color: #ef4444; font-size: 20px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
        .remove-product:hover { background: rgba(239,68,68,0.1); }
        .total-section { background: #f8fafc; border-left: 4px solid var(--primary); border-radius: 12px; padding: 20px; }
        .form-select, .form-control { border-radius: 10px; border: 1px solid var(--border); padding: 10px 14px; }
        .form-select:focus, .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(230,98,57,0.1); }
        .client-info-card { background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 24px; }
        .choice-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000; animation: fadeIn 0.3s ease; }
        .choice-card { background: white; border-radius: 24px; padding: 30px; max-width: 500px; width: 90%; text-align: center; }
        .choice-buttons { display: flex; gap: 15px; justify-content: center; margin-top: 25px; flex-wrap: wrap; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .stock-out { color: #9ca3af; }
        .stock-low { color: #f59e0b; }
    </style>
</head>
<body>

<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="fs-3 fw-bold mb-1"><i class="fa-solid fa-cart-plus me-2" style="color: var(--primary);"></i>Nouvelle commande</h1>
                <p class="text-secondary mb-0 small">Saisie des ventes PowerStock</p>
            </div>
            <a href="index_commande.php" class="btn-outline-custom"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card-custom">
            <div class="p-4">
                <form id="orderForm" method="POST">
                    
                    <div class="client-info-card">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><i class="fa-solid fa-user me-2" style="color: var(--primary);"></i>Client</label>
                                <select class="form-select" id="clientSelect" name="client_id" required>
                                    <option value="">Sélectionner un client...</option>
                                    <?php foreach($clients as $c): ?>
                                        <option value="<?= $c['id'] ?>" data-tel="<?= htmlspecialchars($c['tel']) ?>" data-adresse="<?= htmlspecialchars($c['adresse']) ?>">
                                            <?= htmlspecialchars($c['prenom'] . ' ' . $c['nomc']) ?> - <?= $c['tel'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><i class="fa-solid fa-calendar me-2" style="color: var(--primary);"></i>Date de commande</label>
                                <input type="date" class="form-control" name="date_commande" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div id="clientInfo" class="mt-3" style="display:none">
                            <div class="d-flex gap-3">
                                <span class="badge" style="background:var(--primary);color:white"><i class="fa-solid fa-phone me-1"></i><span id="selectedClientTel"></span></span>
                                <span class="badge" style="background:#64748b;color:white"><i class="fa-solid fa-location-dot me-1"></i><span id="selectedClientAdresse"></span></span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-semibold mb-0"><i class="fa-solid fa-box me-2" style="color: var(--primary);"></i>Produits commandés</h5>
                    </div>

                    <div class="table-responsive">
                        <table class="table" id="tableProduits">
                            <thead><tr><th style="width:40%">Produit</th><th style="width:15%">Prix unitaire</th><th style="width:15%">Quantité</th><th style="width:20%">Total</th><th style="width:10%"></th></tr></thead>
                            <tbody id="orderBody"></tbody>
                        </table>
                    </div>

                    <div id="emptyMsg" class="empty-state d-none text-center py-5" style="border:2px dashed var(--border); border-radius:16px;">
                        <i class="fa-solid fa-cart-shopping fa-2x mb-3" style="color:#cbd5e1"></i>
                        <h5>Aucun produit</h5>
                        <p class="text-secondary">Cliquez sur "Ajouter un produit"</p>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn-add-product" id="btnAddProduct"><i class="fa-solid fa-plus me-2"></i>Ajouter un produit</button>
                    </div>

                    <div class="row mt-4 justify-content-end">
                        <div class="col-md-5 col-lg-4">
                            <div class="total-section">
                                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Sous-total :</span><span id="sousTotal" class="fw-semibold">0 FCFA</span></div>
                                <hr class="my-3">
                                <div class="d-flex justify-content-between align-items-center"><span class="fw-bold">TOTAL</span><span class="fs-4 fw-bold" style="color: var(--primary);"><span id="totalNet">0</span> FCFA</span></div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="produits_json" id="produits_json">
                    
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="index_commande.php" class="btn-outline-custom"><i class="fa-solid fa-times"></i> Annuler</a>
                        <button type="submit" id="btnSubmit" class="btn-primary-custom"><i class="fa-solid fa-check-circle me-2"></i>Valider la commande</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

 <?php if($success && $lastCommandeId): ?>
<div id="choiceModal" class="choice-modal">
    <div class="choice-card">
        <i class="fa-solid fa-check-circle" style="font-size: 60px; color: #10b981; margin-bottom: 20px;"></i>
        <h3 class="fw-bold">Commande créée !</h3>
        <p class="text-secondary">La commande #<?= $lastCommandeId ?> a été enregistrée avec succès.</p>
        <div class="choice-buttons">
            <a href="view_commande.php?id=<?= $lastCommandeId ?>" class="btn-primary-custom"><i class="fa-solid fa-eye"></i> Voir</a>
            <a href="index_commande.php" class="btn-outline-custom"><i class="fa-solid fa-list"></i> Liste des commandes</a>
            <a href="create_commande.php" class="btn-outline-custom"><i class="fa-solid fa-plus"></i> Nouvelle commande</a>
        </div>
    </div>
</div>
<?php endif; ?>
<script>
const produits = <?= json_encode($produits) ?>;

function formatMoney(amount) { return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA'; }
function getStockClass(stock) { if(stock <= 0) return 'stock-out'; if(stock < 5) return 'stock-low'; return ''; }
function getStockLabel(stock) { if(stock <= 0) return '⚠️ RUPTURE'; if(stock < 5) return '⚠️ Stock: ' + stock; return 'Stock: ' + stock; }

// Client info
document.getElementById('clientSelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if(this.value) {
        document.getElementById('selectedClientTel').innerText = opt.dataset.tel || 'Non renseigné';
        document.getElementById('selectedClientAdresse').innerText = opt.dataset.adresse || 'Non renseignée';
        document.getElementById('clientInfo').style.display = 'block';
    } else {
        document.getElementById('clientInfo').style.display = 'none';
    }
});

// Gestion produits
const orderBody = document.getElementById('orderBody');
const emptyMsg = document.getElementById('emptyMsg');
const btnAdd = document.getElementById('btnAddProduct');

function ajouterLigne(pid='', qty=1) {
    emptyMsg.classList.add('d-none');
    const tr = document.createElement('tr');
    tr.className = 'ligne-produit-dynamique';
    
    let options = '<option value="">-- Sélectionner --</option>';
    produits.forEach(p => {
        const stockClass = getStockClass(p.quantite);
        const stockLabel = getStockLabel(p.quantite);
        options += `<option value="${p.id}" data-prix="${p.prix}" data-stock="${p.quantite}" class="${stockClass}" ${p.id == pid ? 'selected' : ''}>${p.nomp} - ${formatMoney(p.prix)} (${stockLabel})</option>`;
    });
    
    tr.innerHTML = `
        <td><select class="form-select selector-produit" required>${options}</select></td>
        <td><input type="text" class="form-control text-center bg-light prix-u" value="0 FCFA" readonly></td>
        <td><input type="number" class="form-control text-center qte" value="${qty}" min="1"></td>
        <td class="text-end fw-semibold"><span class="total-ligne">0 FCFA</span></td>
        <td class="text-center"><div class="remove-product"><i class="fa-solid fa-trash-alt"></i></div></td>
    `;
    
    orderBody.appendChild(tr);
    updateLineTotal(tr);
}

 function updateLineTotal(row) {
    const select = row.querySelector('.selector-produit');
    const prix = parseFloat(select.options[select.selectedIndex]?.dataset.prix || 0);
    const stock = parseInt(select.options[select.selectedIndex]?.dataset.stock || 0);
    let qte = parseInt(row.querySelector('.qte').value) || 0;
    
    // Ne pas afficher de toast pour une ligne vide
    if (!select.value) {
        row.querySelector('.prix-u').value = '0 FCFA';
        row.querySelector('.total-ligne').innerText = '0 FCFA';
        return 0;
    }
    
    if (qte > stock && stock > 0) { 
        qte = stock; 
        row.querySelector('.qte').value = stock; 
        showToast(`Stock limité à ${stock}`); 
    }
    if (stock <= 0 && qte > 0) { 
        qte = 0; 
        row.querySelector('.qte').value = 0; 
        showToast('Produit en rupture'); 
    }
    
    row.querySelector('.prix-u').value = prix ? formatMoney(prix) : '0 FCFA';
    row.querySelector('.total-ligne').innerText = formatMoney(prix * qte);
    return prix * qte;
}

function calculerTotaux() {
    let total = 0, hasProducts = false;
    document.querySelectorAll('.ligne-produit-dynamique').forEach(row => {
        const select = row.querySelector('.selector-produit');
        if (select && select.value) {
            hasProducts = true;
            const prix = parseFloat(select.options[select.selectedIndex]?.dataset.prix || 0);
            const qte = parseInt(row.querySelector('.qte').value) || 0;
            total += prix * qte;
        }
    });
    document.getElementById('sousTotal').innerText = formatMoney(total);
    document.getElementById('totalNet').innerText = new Intl.NumberFormat('fr-FR').format(total);
    document.getElementById('btnSubmit').disabled = !hasProducts;
    
    const items = [];
    document.querySelectorAll('.ligne-produit-dynamique').forEach(row => {
        const select = row.querySelector('.selector-produit');
        if (select && select.value) {
            items.push({ id: parseInt(select.value), qty: parseInt(row.querySelector('.qte').value) || 0 });
        }
    });
    document.getElementById('produits_json').value = JSON.stringify(items);
}

function showToast(msg) {
    let t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:30px;right:30px;background:#1e293b;color:white;padding:10px 20px;border-radius:40px;font-size:13px;z-index:9999;animation:fadeIn 0.3s ease';
    t.innerText = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2500);
}

orderBody.addEventListener('input', function(e) {
    const row = e.target.closest('.ligne-produit-dynamique');
    if (row && (e.target.classList.contains('selector-produit') || e.target.classList.contains('qte'))) {
        updateLineTotal(row);
        calculerTotaux();
    }
});

orderBody.addEventListener('click', function(e) {
    const btn = e.target.closest('.remove-product');
    if (btn) {
        const row = btn.closest('.ligne-produit-dynamique');
        row.style.transform = "translateX(20px)"; row.style.opacity = "0"; row.style.transition = "all 0.3s ease";
        setTimeout(() => { row.remove(); if(orderBody.children.length === 0) emptyMsg.classList.remove('d-none'); calculerTotaux(); }, 300);
    }
});

btnAdd.addEventListener('click', () => ajouterLigne());
ajouterLigne();

document.getElementById('orderForm').addEventListener('submit', function(e) {
    if (!document.getElementById('clientSelect').value) {
        e.preventDefault();
        showToast('Sélectionnez un client');
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>