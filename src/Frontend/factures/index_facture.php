 <?php
// src/Frontend/factures/index_facture.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();

// Récupérer les factures avec infos client
$stmt = $pdo->query("
    SELECT 
        f.id,
        f.nomf as numero_facture,
        f.datef as date_emission,
        f.etatf as statut_paiement,
        COUNT(DISTINCT c.id) as nb_commandes,
        SUM(c.total_ttc) as montant_total,
        GROUP_CONCAT(DISTINCT CONCAT(cl.prenom, ' ', cl.nomc) SEPARATOR ', ') as clients,
        GROUP_CONCAT(DISTINCT cl.tel SEPARATOR ', ') as tels
    FROM factures f
    LEFT JOIN commandes c ON f.id = c.facture_id
    LEFT JOIN clients cl ON c.client_id = cl.id
    GROUP BY f.id
    ORDER BY f.datef DESC
");
$factures = $stmt->fetchAll();

// Statistiques
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN etatf = 1 THEN 1 ELSE 0 END) as payees,
        SUM(CASE WHEN etatf = 0 THEN 1 ELSE 0 END) as impayees
    FROM factures
")->fetch();

// Montant total impayé
$stmt = $pdo->query("
    SELECT SUM(c.total_ttc) as total_impaye
    FROM factures f
    LEFT JOIN commandes c ON f.id = c.facture_id
    WHERE f.etatf = 0
");
$total_impaye = $stmt->fetch()['total_impaye'] ?? 0;

$page_title = 'Factures';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factures | PowerStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root { --primary: #E66239; --success: #10b981; --danger: #ef4444; --warning: #f59e0b; --border: #e2e8f0; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; border: 1px solid var(--border); cursor: pointer; transition: all 0.3s; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--primary); transform: scaleX(0); transition: transform 0.3s ease; }
        .stat-card:hover::before { transform: scaleX(1); }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
        .stat-card .icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .stat-card .value { font-size: 28px; font-weight: 700; }
        .badge-paid { background: var(--success); color: white; padding: 4px 12px; border-radius: 30px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .badge-unpaid { background: var(--danger); color: white; padding: 4px 12px; border-radius: 30px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .filter-bar { background: white; border-radius: 20px; padding: 16px 20px; margin-bottom: 24px; border: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; }
        .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
        .filter-tab { padding: 8px 20px; background: #f1f5f9; border-radius: 30px; font-size: 13px; font-weight: 500; cursor: pointer; }
        .filter-tab.active { background: var(--primary); color: white; }
        .search-box { display: flex; align-items: center; background: #f1f5f9; border-radius: 40px; padding: 4px 16px; gap: 8px; }
        .search-box input { border: none; background: transparent; padding: 8px; width: 220px; outline: none; }
        .table-container { background: white; border-radius: 20px; border: 1px solid var(--border); overflow-x: auto; }
        table { width: 100%; min-width: 900px; }
        th { background: #f8fafc; padding: 14px 16px; font-size: 13px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .facture-row:hover { background: #fef4f0; }
        .action-btn { width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: white; border: 1px solid var(--border); cursor: pointer; margin: 0 2px; transition: all 0.2s; }
        .action-btn.view:hover { background: var(--primary); color: white; }
        .action-btn.print:hover { background: var(--success); color: white; }
        .action-btn.pay:hover { background: var(--success); color: white; }
        .action-btn.delete:hover { background: var(--danger); color: white; }
        .pagination { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; flex-wrap: wrap; }
        .page-item { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: white; border: 1px solid var(--border); cursor: pointer; font-weight: 500; }
        .page-item:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-2px); }
        .page-item.active { background: var(--primary); color: white; border-color: var(--primary); }
        .bulk-bar { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); background: #1e293b; color: white; padding: 12px 28px; border-radius: 60px; display: flex; gap: 20px; z-index: 1000; }
        .toast-soft { position: fixed; bottom: 30px; right: 30px; background: #1e293b; color: white; padding: 10px 20px; border-radius: 40px; font-size: 13px; z-index: 9999; animation: fadeInUp 0.3s ease; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
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
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="fs-3 fw-bold mb-1"><i class="fa-solid fa-file-invoice me-2" style="color: var(--primary);"></i>Factures</h1>
                <p class="text-secondary mb-0 small">Gérez vos factures et suivez les paiements</p>
            </div>
        </div>

        <!-- Cartes statistiques -->
        <div class="stats-grid">
            <div class="stat-card" data-filter="all">
                <div class="icon" style="background: rgba(230,98,57,0.1);"><i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary); font-size: 24px;"></i></div>
                <div class="value" id="totalCount"><?= $stats['total'] ?></div>
                <div class="label">Total factures</div>
            </div>
            <div class="stat-card" data-filter="paid">
                <div class="icon" style="background: rgba(16,185,129,0.1);"><i class="fa-solid fa-circle-check" style="color: var(--success); font-size: 24px;"></i></div>
                <div class="value" id="paidCount"><?= $stats['payees'] ?></div>
                <div class="label">Payées</div>
            </div>
            <div class="stat-card" data-filter="unpaid">
                <div class="icon" style="background: rgba(239,68,68,0.1);"><i class="fa-solid fa-circle-xmark" style="color: var(--danger); font-size: 24px;"></i></div>
                <div class="value" id="unpaidCount"><?= $stats['impayees'] ?></div>
                <div class="label">Impayées</div>
            </div>
            <div class="stat-card" data-filter="amount">
                <div class="icon" style="background: rgba(245,158,11,0.1);"><i class="fa-solid fa-money-bill-wave" style="color: var(--warning); font-size: 24px;"></i></div>
                <div class="value" id="totalAmount"><?= number_format($total_impaye, 0, ',', ' ') ?></div>
                <div class="label">À encaisser (FCFA)</div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-bar">
            <div class="filter-tabs">
                <span class="filter-tab active" data-filter="all">Toutes</span>
                <span class="filter-tab" data-filter="paid">Payées</span>
                <span class="filter-tab" data-filter="unpaid">Impayées</span>
            </div>
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="N° facture, client...">
            </div>
            <div class="date-filter">
                <input type="date" id="dateFilter">
            </div>
        </div>

        <!-- Tableau -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="selectAllCheckbox"></th>
                        <th>N° Facture</th>
                        <th>Client(s)</th>
                        <th>Date</th>
                        <th>Nb commandes</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>

        <div class="pagination" id="pagination"></div>
        
    </div>
</main>

<!-- Barre d'actions groupées -->
<div id="bulkBar" class="bulk-bar" style="display: none;">
    <span id="selectedCount">0</span> sélectionnée(s)
    <button id="bulkPayBtn" style="background: var(--success); border: none; color: white; border-radius: 30px; padding: 6px 18px;">
        <i class="fa-solid fa-check"></i> Marquer payées
    </button>
    <button id="bulkDeleteBtn" style="background: var(--danger); border: none; color: white; border-radius: 30px; padding: 6px 18px;">
        <i class="fa-solid fa-trash"></i> Supprimer
    </button>
    <button id="closeBulkBtn" class="btn btn-sm btn-light">✕</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const facturesData = <?= json_encode($factures) ?>;

let currentPage = 1, rowsPerPage = 5, currentFilter = "all", currentSearch = "", currentDate = "";
let selectedIds = [];

function formatMoney(amount) { 
    return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
}

function formatDate(dateStr) { 
    if (!dateStr) return '-';
    let d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR');
}

function getBadge(statut) {
    if (statut == 1) {
        return '<span class="badge-paid"><i class="fa-solid fa-check"></i> Payée</span>';
    }
    return '<span class="badge-unpaid"><i class="fa-solid fa-xmark"></i> Impayée</span>';
}

function showToast(msg) {
    let t = document.createElement('div'); 
    t.className = 'toast-soft'; 
    t.innerText = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2500);
}

function renderTable() {
    let filtered = facturesData.filter(f => {
        if (currentFilter === 'paid' && f.statut_paiement != 1) return false;
        if (currentFilter === 'unpaid' && f.statut_paiement != 0) return false;
        if (currentSearch) {
            let searchLower = currentSearch.toLowerCase();
            let matchNumero = (f.numero_facture || '').toLowerCase().includes(searchLower);
            let matchClient = (f.clients || '').toLowerCase().includes(searchLower);
            if (!matchNumero && !matchClient) return false;
        }
        if (currentDate) {
            let factureDate = new Date(f.date_emission).toISOString().split('T')[0];
            if (factureDate !== currentDate) return false;
        }
        return true;
    });
    
    let totalPages = Math.ceil(filtered.length / rowsPerPage);
    let start = (currentPage - 1) * rowsPerPage;
    let paginated = filtered.slice(start, start + rowsPerPage);
    
    let tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    
    if (paginated.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5">Aucune facture trouvée</td></tr>`;
    } else {
        paginated.forEach(f => {
            let clientsDisplay = f.clients ? f.clients.substring(0, 50) : 'Client inconnu';
            if (f.clients && f.clients.length > 50) clientsDisplay += '...';
            
            tbody.innerHTML += `
                <tr class="facture-row">
                    <td><input type="checkbox" class="factureCheckbox" value="${f.id}"></td>
                    <td><strong style="color: var(--primary);">${f.numero_facture || 'FACT-' + f.id}</strong></td>
                    <td>${clientsDisplay}</td>
                    <td>${formatDate(f.date_emission)}</td>
                    <td class="text-center">${f.nb_commandes || 0}</td>
                    <td class="fw-bold">${formatMoney(f.montant_total || 0)}</td>
                    <td>${getBadge(f.statut_paiement)}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <div class="action-btn view" onclick="viewFacture(${f.id})"><i class="fa-regular fa-eye"></i></div>
                            <div class="action-btn print" onclick="printFacture(${f.id})"><i class="fa-solid fa-print"></i></div>
                            ${f.statut_paiement == 0 ? `<div class="action-btn pay" onclick="markAsPaid(${f.id})"><i class="fa-solid fa-check"></i></div>` : ''}
                            <div class="action-btn delete" onclick="deleteFacture(${f.id})"><i class="fa-solid fa-trash"></i></div>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    // Pagination
    let pag = document.getElementById('pagination');
    pag.innerHTML = '';
    if (totalPages > 1) {
        pag.innerHTML += `<div class="page-item ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}"><i class="fa-solid fa-chevron-left"></i></div>`;
        for (let i = 1; i <= totalPages; i++) {
            pag.innerHTML += `<div class="page-item ${currentPage === i ? 'active' : ''}" data-page="${i}">${i}</div>`;
        }
        pag.innerHTML += `<div class="page-item ${currentPage === totalPages ? 'disabled' : ''}" data-page="${currentPage + 1}"><i class="fa-solid fa-chevron-right"></i></div>`;
        
        document.querySelectorAll('#pagination .page-item').forEach(el => {
            el.addEventListener('click', function() {
                let p = parseInt(this.dataset.page);
                if (p >= 1 && p <= totalPages) {
                    currentPage = p;
                    renderTable();
                    updateBulkBar();
                }
            });
        });
    }
    
    updateBulkBar();
}

function updateBulkBar() {
    selectedIds = Array.from(document.querySelectorAll('.factureCheckbox:checked')).map(cb => parseInt(cb.value));
    let bar = document.getElementById('bulkBar');
    bar.style.display = selectedIds.length >= 2 ? 'flex' : 'none';
    document.getElementById('selectedCount').innerText = selectedIds.length;
}

window.viewFacture = function(id) {
    window.location.href = `view_facture.php?id=${id}`;
};

window.printFacture = function(id) {
    window.open(`print_facture.php?id=${id}`, '_blank');
};

window.markAsPaid = function(id) {
    if (confirm('Marquer cette facture comme payée ?')) {
        window.location.href = `update_facture_status.php?id=${id}&status=paid`;
    }
};

window.deleteFacture = function(id) {
    if (confirm('⚠️ Supprimer cette facture ? Les commandes ne seront plus liées.')) {
        window.location.href = `delete_facture.php?id=${id}`;
    }
};

// Filtres
document.getElementById('searchInput')?.addEventListener('keyup', e => {
    currentSearch = e.target.value.toLowerCase();
    currentPage = 1;
    renderTable();
});

document.getElementById('dateFilter')?.addEventListener('change', e => {
    currentDate = e.target.value;
    currentPage = 1;
    renderTable();
});

document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.filter;
        currentPage = 1;
        renderTable();
    });
});

document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('click', function() {
        let filter = this.dataset.filter;
        document.querySelectorAll('.filter-tab').forEach(tab => {
            if (tab.dataset.filter === filter) tab.classList.add('active');
            else tab.classList.remove('active');
        });
        currentFilter = filter;
        currentPage = 1;
        renderTable();
    });
});

// Bulk actions
document.getElementById('selectAllCheckbox')?.addEventListener('change', function() {
    document.querySelectorAll('.factureCheckbox').forEach(cb => cb.checked = this.checked);
    updateBulkBar();
});

document.getElementById('tableBody')?.addEventListener('change', function(e) {
    if (e.target.classList.contains('factureCheckbox')) updateBulkBar();
});

document.getElementById('bulkPayBtn')?.addEventListener('click', () => {
    if (selectedIds.length < 2) {
        showToast('Sélectionnez au moins 2 factures');
        return;
    }
    if (confirm(`Marquer ${selectedIds.length} facture(s) comme payées ?`)) {
        window.location.href = `update_facture_status.php?ids=${selectedIds.join(',')}&status=paid`;
    }
});

document.getElementById('bulkDeleteBtn')?.addEventListener('click', () => {
    if (selectedIds.length < 2) {
        showToast('Sélectionnez au moins 2 factures');
        return;
    }
    if (confirm(`⚠️ Supprimer ${selectedIds.length} facture(s) ?`)) {
        window.location.href = `delete_facture.php?ids=${selectedIds.join(',')}`;
    }
});

document.getElementById('closeBulkBtn')?.addEventListener('click', () => {
    document.querySelectorAll('.factureCheckbox').forEach(cb => cb.checked = false);
    updateBulkBar();
});

// Initialisation
renderTable();
</script>
</body>
</html>