 <?php
// src/Frontend/commandes/index_commande.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();

// Récupérer les commandes avec GROUP_CONCAT
$stmt = $pdo->query("
    SELECT 
        c.id,
        c.date_commande,
        c.statut,
        c.total_ttc as amount,
        c.facture_id,
        CONCAT(cl.prenom, ' ', cl.nomc) as clientName,
        cl.tel as clientTel,
        COALESCE(GROUP_CONCAT(DISTINCT CONCAT(p.nomp, ' (', d.quantite, ')') SEPARATOR ', '), 'Aucun produit') as produits
    FROM commandes c
    LEFT JOIN clients cl ON c.client_id = cl.id
    LEFT JOIN detail_commande d ON c.id = d.commande_id
    LEFT JOIN produits p ON d.produit_id = p.id
    GROUP BY c.id
    ORDER BY c.date_commande DESC
");
$orders = $stmt->fetchAll();

// Statistiques
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as attente,
        SUM(CASE WHEN statut = 'livree' THEN 1 ELSE 0 END) as livree,
        SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END) as annulee
    FROM commandes
")->fetch();

$page_title = 'Commandes';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes | PowerStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root { --primary: #E66239; --success: #10b981; --danger: #ef4444; --warning: #f59e0b; --dark: #1e293b; --border: #e2e8f0; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; border: 1px solid var(--border); cursor: pointer; transition: all 0.3s; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--primary); transform: scaleX(0); transition: transform 0.3s ease; }
        .stat-card:hover::before { transform: scaleX(1); }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
        .stat-card .icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .stat-card .value { font-size: 28px; font-weight: 700; }
        .badge-en_attente { background: var(--warning); color: white; padding: 4px 12px; border-radius: 30px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .badge-livree { background: var(--success); color: white; padding: 4px 12px; border-radius: 30px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .badge-annulee { background: var(--danger); color: white; padding: 4px 12px; border-radius: 30px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .filter-bar { background: white; border-radius: 20px; padding: 16px 20px; margin-bottom: 24px; border: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; }
        .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
        .filter-tab { padding: 8px 20px; background: #f1f5f9; border-radius: 30px; font-size: 13px; font-weight: 500; cursor: pointer; }
        .filter-tab.active { background: var(--primary); color: white; }
        .search-box { display: flex; align-items: center; background: #f1f5f9; border-radius: 40px; padding: 4px 16px; gap: 8px; }
        .search-box input { border: none; background: transparent; padding: 8px; width: 220px; outline: none; }
        .table-container { background: white; border-radius: 20px; border: 1px solid var(--border); overflow-x: auto; }
        table { width: 100%; min-width: 1100px; }
        th { background: #f8fafc; padding: 14px 16px; font-size: 13px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .order-row:hover { background: #fef4f0; }
        .status-select { padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border); font-size: 12px; margin-left: 8px; }
        .action-btn { width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: white; border: 1px solid var(--border); cursor: pointer; margin: 0 2px; }
        .action-btn.view:hover { background: var(--primary); color: white; }
        .action-btn.edit:hover { background: var(--warning); color: white; }
        .action-btn.delete:hover { background: var(--danger); color: white; }
        .pagination { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; flex-wrap: wrap; }
        .page-item { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: white; border: 1px solid var(--border); cursor: pointer; font-weight: 500; }
        .page-item:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-2px); }
        .page-item.active { background: var(--primary); color: white; border-color: var(--primary); }
        .page-item.disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-primary-custom { background: var(--primary); border: none; padding: 8px 20px; border-radius: 12px; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .bulk-bar { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); background: #1e293b; color: white; padding: 12px 28px; border-radius: 60px; display: flex; gap: 20px; z-index: 1000; }
        .product-tag { background: #f1f5f9; font-size: 12px; padding: 2px 8px; border-radius: 4px; display: inline-block; margin-right: 4px; margin-bottom: 4px; }
        .toast-soft { position: fixed; bottom: 30px; right: 30px; background: #1e293b; color: white; padding: 10px 20px; border-radius: 40px; font-size: 13px; z-index: 9999; animation: fadeInUp 0.3s ease; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
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
<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="fs-3 fw-bold mb-1"><i class="fa-solid fa-cart-shopping me-2" style="color: var(--primary);"></i>Commandes</h1>
                <p class="text-secondary mb-0 small">Gérez vos commandes et suivez leur statut</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="exportBtn" style="border-radius: 10px;"><i class="fa-solid fa-download me-2"></i>Exporter</button>
                <a href="create_commande.php" class="btn-primary-custom"><i class="fa-solid fa-plus"></i> Nouvelle commande</a>
            </div>
        </div>

    <!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100" data-filter="all">
            <div class="d-flex justify-content-between align-items-center h-100">
                <div>
                    <p class="text-secondary mb-1 small">Total commandes</p>
                    <h3 class="mb-0 fw-bold" id="totalCount"><?= $stats['total'] ?></h3>
                </div>
                <div class="stat-icon bg-primary-light rounded-circle">
                    <i class="ti ti-shopping-cart" style="color: var(--primary-color); font-size: 24px;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100" data-filter="en_attente">
            <div class="d-flex justify-content-between align-items-center h-100">
                <div>
                    <p class="text-secondary mb-1 small">En attente</p>
                    <h3 class="mb-0 fw-bold" id="pendingCount"><?= $stats['attente'] ?></h3>
                </div>
                <div class="stat-icon bg-warning-light rounded-circle">
                    <i class="ti ti-clock" style="color: #f39c12; font-size: 24px;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100" data-filter="livree">
            <div class="d-flex justify-content-between align-items-center h-100">
                <div>
                    <p class="text-secondary mb-1 small">Livrées</p>
                    <h3 class="mb-0 fw-bold" id="deliveredCount"><?= $stats['livree'] ?></h3>
                </div>
                <div class="stat-icon bg-success-light rounded-circle">
                    <i class="ti ti-truck" style="color: #28a745; font-size: 24px;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100" data-filter="annulee">
            <div class="d-flex justify-content-between align-items-center h-100">
                <div>
                    <p class="text-secondary mb-1 small">Annulées</p>
                    <h3 class="mb-0 fw-bold" id="cancelledCount"><?= $stats['annulee'] ?></h3>
                </div>
                <div class="stat-icon bg-danger-light rounded-circle">
                    <i class="ti ti-circle-x" style="color: #dc3545; font-size: 24px;"></i>
                </div>
            </div>
        </div>
    </div>
</div>
 
        <div class="filter-bar">
            <div class="filter-tabs"><span class="filter-tab active" data-filter="all">Toutes</span><span class="filter-tab" data-filter="en_attente">En attente</span><span class="filter-tab" data-filter="livree">Livrées</span><span class="filter-tab" data-filter="annulee">Annulées</span></div>
            <div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="searchInput" placeholder="Client ou N° commande..."></div>
            <div class="date-filter"><input type="date" id="dateFilter"></div>
        </div>

        <div class="table-container">
            <table class="table">
                <thead><tr><th style="width:40px"><input type="checkbox" id="selectAllCheckbox"></th>
                <th>N° Commande</th>
                <th>Client</th>
                <th>Date</th>
                <th>Produits</th>
                <th>Total</th>
                <th>Statut</th>
                <th>Facture</th>
                <th>Actions</th>
            </tr>
        </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>
        <div class="pagination" id="pagination"></div>
    </div>
</main>

<div id="bulkBar" class="bulk-bar" style="display:none"><span id="selectedCount">0</span> sélectionnée(s)<select id="bulkStatusSelect"><option value="">Changer statut</option><option value="en_attente">En attente</option><option value="livree">Livrée</option><option value="annulee">Annulée</option></select><button id="bulkDeleteBtn" style="background:var(--danger);border:none;color:white;border-radius:30px;padding:6px 18px"><i class="fa-solid fa-trash"></i> Supprimer</button><button id="closeBulkBtn" class="btn btn-sm btn-light">✕</button></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ordersData = <?= json_encode($orders) ?>;
let currentPage = 1, rowsPerPage = 5, currentFilter = "all", currentSearch = "", currentDate = "", selectedIds = [];

function formatMoney(v) { return new Intl.NumberFormat('fr-FR').format(v) + ' FCFA'; }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('fr-FR') : '-'; }
function escapeHtml(str) { if(!str) return ''; return str.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'})[m]); }

function getBadge(s) {
    if (s === 'en_attente') return '<span class="badge-en_attente"><i class="fa-solid fa-clock"></i> En attente</span>';
    if (s === 'livree') return '<span class="badge-livree"><i class="fa-solid fa-check-circle"></i> Livrée</span>';
    return '<span class="badge-annulee"><i class="fa-solid fa-circle-xmark"></i> Annulée</span>';
}

function showToast(msg) {
    let t = document.createElement('div'); t.className = 'toast-soft'; t.innerText = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2500);
}

function renderTable() {
    let filtered = ordersData.filter(o => {
        if (currentFilter !== 'all' && o.statut !== currentFilter) return false;
        if (currentSearch && !o.id.toString().includes(currentSearch) && !o.clientName?.toLowerCase().includes(currentSearch)) return false;
        if (currentDate && o.date_commande !== currentDate) return false;
        return true;
    });
    filtered.sort((a,b) => new Date(b.date_commande) - new Date(a.date_commande));
    let totalPages = Math.ceil(filtered.length / rowsPerPage);
    let start = (currentPage-1) * rowsPerPage;
    let paginated = filtered.slice(start, start+rowsPerPage);
    
    let tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    if (paginated.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state text-center py-5"><i class="fa-solid fa-box-open fa-2x"></i><h5>Aucune commande</h5></div></td></tr>`;
    } else {
        paginated.forEach(o => {
            let productsTags = o.produits ? o.produits.split(',').map(p => `<span class="product-tag">${escapeHtml(p.trim())}</span>`).join('') : '-';
             // Bouton facture
     let factureBtn = '';
if (o.statut === 'livree' && o.facture_id) {
    factureBtn = `<a href="view_facture.php?id=${o.facture_id}" class="btn btn-sm btn-outline-danger" title="Voir facture">
                    <i class="fa-regular fa-file-pdf"></i> Facture
                  </a>`;
} else {
    factureBtn = '<span class="text-secondary">-</span>';
}
    
            tbody.innerHTML += `
                <tr class="order-row">
                    <td><input type="checkbox" class="orderCheckbox" value="${o.id}"></td>
                    <td><strong style="color:var(--primary);">#${o.id}</strong></td>
                    <td><strong>${escapeHtml(o.clientName || 'N/A')}</strong><br><small class="text-muted">${o.clientTel || ''}</small></td>
                    <td>${formatDate(o.date_commande)}</td>
                    <td>${productsTags}</td>
                    <td class="fw-bold text-primary">${formatMoney(o.amount)}</td>
                    <td style="white-space:nowrap;">${getBadge(o.statut)}<select class="status-select" data-id="${o.id}" data-old="${o.statut}"><option value="">Changer</option><option value="en_attente" ${o.statut === 'en_attente' ? 'selected' : ''}>En attente</option><option value="livree" ${o.statut === 'livree' ? 'selected' : ''}>Livrée</option><option value="annulee" ${o.statut === 'annulee' ? 'selected' : ''}>Annulée</option></select></td>
                       <td class="text-center">${factureBtn}</td>
                    <td><div class="d-flex gap-1"><div class="action-btn view" onclick="viewOrder(${o.id})"><i class="fa-regular fa-eye"></i></div><div class="action-btn edit" onclick="editOrder(${o.id})"><i class="fa-solid fa-pen"></i></div><div class="action-btn delete" onclick="deleteOrder(${o.id})"><i class="fa-solid fa-trash"></i></div></div></td>
                </tr>
            `;
        });
    }
    
    document.querySelectorAll('.status-select').forEach(sel => {
        sel.removeEventListener('change', handleStatusChange);
        sel.addEventListener('change', handleStatusChange);
    });
    
    let pag = document.getElementById('pagination');
    pag.innerHTML = '';
    if (totalPages > 1) {
        pag.innerHTML += `<div class="page-item ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}"><i class="fa-solid fa-chevron-left"></i></div>`;
        for (let i = 1; i <= totalPages; i++) pag.innerHTML += `<div class="page-item ${currentPage === i ? 'active' : ''}" data-page="${i}">${i}</div>`;
        pag.innerHTML += `<div class="page-item ${currentPage === totalPages ? 'disabled' : ''}" data-page="${currentPage + 1}"><i class="fa-solid fa-chevron-right"></i></div>`;
        document.querySelectorAll('#pagination .page-item').forEach(el => { el.addEventListener('click', function() { let p = parseInt(this.dataset.page); if (p >= 1 && p <= totalPages) { currentPage = p; renderTable(); updateBulkBar(); } }); });
    }
    updateBulkBar();
}
 async function handleStatusChange(e) {
    let sel = e.target;
    let id = parseInt(sel.dataset.id);
    let newStatus = sel.value;
    if (!newStatus) return;
    
    // Si c'est un passage en livrée, on redirige vers la page de choix
    if (newStatus === 'livree') {
        window.location.href = `update_status.php?id=${id}&status=livree`;
        return;
    }
    
    // Sinon, changement direct
    sel.disabled = true;
    try {
        let res = await fetch(`update_status.php?id=${id}&status=${newStatus}`);
        if (res.ok) {
            showToast(`Statut mis à jour`);
            location.reload();
        } else showToast(`Erreur`);
    } catch(e) { showToast(`Erreur réseau`); }
    sel.disabled = false;
}
function updateStatsUI() {
    // Recharger les stats depuis la BDD via AJAX
    fetch('get_stats.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById('totalCount').innerText = data.total;
            document.getElementById('pendingCount').innerText = data.attente;
            document.getElementById('deliveredCount').innerText = data.livree;
            document.getElementById('cancelledCount').innerText = data.annulee;
        })
        .catch(err => console.log('Erreur stats:', err));
}
function updateBulkBar() {
    selectedIds = Array.from(document.querySelectorAll('.orderCheckbox:checked')).map(cb => parseInt(cb.value));
    let bar = document.getElementById('bulkBar');
    bar.style.display = selectedIds.length >= 2 ? 'flex' : 'none';
    document.getElementById('selectedCount').innerText = selectedIds.length;
}

window.viewOrder = function(id) { window.location.href = `view_commande.php?id=${id}`; };
window.editOrder = function(id) { window.location.href = `edit_commande.php?id=${id}`; };
window.deleteOrder = function(id) { if(confirm('Supprimer ?')) window.location.href = `delete_commande.php?id=${id}`; };

// Filtres
document.getElementById('searchInput')?.addEventListener('keyup', e => { currentSearch = e.target.value.toLowerCase(); currentPage = 1; renderTable(); });
document.getElementById('dateFilter')?.addEventListener('change', e => { currentDate = e.target.value; currentPage = 1; renderTable(); });
document.querySelectorAll('.filter-tab').forEach(tab => { tab.addEventListener('click', function() { document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active')); this.classList.add('active'); currentFilter = this.dataset.filter; currentPage = 1; renderTable(); }); });
document.querySelectorAll('.stat-card').forEach(card => { card.addEventListener('click', function() { let f = this.dataset.filter; document.querySelectorAll('.filter-tab').forEach(t => { if(t.dataset.filter === f) t.classList.add('active'); else t.classList.remove('active'); }); currentFilter = f; currentPage = 1; renderTable(); }); });

// Bulk actions
document.getElementById('selectAllCheckbox')?.addEventListener('change', function() { document.querySelectorAll('.orderCheckbox').forEach(cb => cb.checked = this.checked); updateBulkBar(); });
document.getElementById('tableBody')?.addEventListener('change', function(e) { if(e.target.classList.contains('orderCheckbox')) updateBulkBar(); });
document.getElementById('bulkStatusSelect')?.addEventListener('change', async function() { let val = this.value; if(!val) return; for(let id of selectedIds) await fetch(`update_status.php?id=${id}&status=${val}`); showToast(`${selectedIds.length} commande(s) mises à jour`); setTimeout(() => location.reload(), 500); });
document.getElementById('bulkDeleteBtn')?.addEventListener('click', () => { if(confirm(`Supprimer ${selectedIds.length} commande(s) ?`)) { selectedIds.forEach(id => fetch(`delete_commande.php?id=${id}`)); showToast(`Suppression en cours`); setTimeout(() => location.reload(), 500); } });
document.getElementById('closeBulkBtn')?.addEventListener('click', () => { document.querySelectorAll('.orderCheckbox').forEach(cb => cb.checked = false); updateBulkBar(); });

// Export CSV (structure propre pour Excel)
document.getElementById('exportBtn')?.addEventListener('click', () => {
    let csvRows = [["N° Commande","Client","Téléphone","Date","Produits","Montant","Statut"]];
    ordersData.forEach(o => {
        csvRows.push([
            o.id,
            o.clientName || '',
            o.clientTel || '',
            o.date_commande,
            o.produits || '',
            o.amount,
            o.statut
        ]);
    });
    let csvContent = csvRows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
    let blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
    let link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `commandes_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    showToast(`Export CSV lancé`);
});

renderTable();
</script>
</body>
</html>