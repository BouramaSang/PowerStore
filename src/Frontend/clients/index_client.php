 <?php
// src/Frontend/clients/index_client.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();

// Récupérer les clients avec stats
$stmt = $pdo->query("
    SELECT c.*, 
           COUNT(cm.id) as nb_commandes,
           SUM(cm.total_ttc) as total_achats
    FROM clients c
    LEFT JOIN commandes cm ON c.id = cm.client_id
    GROUP BY c.id
");
$clients = $stmt->fetchAll();

// TRI SIMPLE ET CORRECT (uniquement sur le prénom)
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';
if ($order == 'asc') {
    usort($clients, function($a, $b) {
        return strcmp(strtolower($a['prenom']), strtolower($b['prenom']));
    });
} else {
    usort($clients, function($a, $b) {
        return strcmp(strtolower($b['prenom']), strtolower($a['prenom']));
    });
}

// Statistiques
$total_clients = count($clients);
$clients_actifs = count(array_filter($clients, fn($c) => $c['nb_commandes'] > 0));
$ca_total = array_sum(array_column($clients, 'total_achats'));

$page_title = 'Clients';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients | PowerStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root { --primary: #E66239; --border: #e2e8f0; --success: #10b981; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 28px; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; border: 1px solid var(--border); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
        .stat-card .value { font-size: 28px; font-weight: 700; color: var(--primary); }
        .stat-card .label { font-size: 13px; color: #64748b; margin-top: 5px; }
        
        .filter-bar { background: white; border-radius: 20px; padding: 16px 20px; margin-bottom: 24px; border: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; }
        .search-box { display: flex; align-items: center; background: #f1f5f9; border-radius: 40px; padding: 4px 16px; gap: 8px; flex: 1; min-width: 200px; }
        .search-box input { border: none; background: transparent; padding: 8px; width: 100%; outline: none; }
        .order-buttons { display: flex; gap: 8px; }
        .btn-order { background: white; border: 1px solid var(--border); border-radius: 10px; padding: 8px 16px; font-size: 13px; transition: all 0.2s; text-decoration: none; color: #1e293b; }
        .btn-order.active { background: var(--primary); color: white; border-color: var(--primary); }
        .btn-order:hover { background: var(--primary); color: white; }
        .btn-export { background: white; border: 1px solid var(--border); border-radius: 10px; padding: 8px 16px; font-size: 13px; transition: all 0.2s; }
        .btn-export:hover { background: var(--primary); color: white; }
        
        .table-container { background: white; border-radius: 20px; border: 1px solid var(--border); overflow-x: auto; }
        table { width: 100%; min-width: 700px; margin-bottom: 0; }
        th { background: #f8fafc; padding: 14px 16px; font-size: 13px; font-weight: 600; border-bottom: 1px solid var(--border); white-space: nowrap; }
        td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; white-space: nowrap; }
        .client-row:hover { background: #fef4f0; }
        .badge-client { background: #e2e8f0; color: #1e293b; padding: 4px 10px; border-radius: 20px; font-size: 12px; }
        .action-btn { width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: white; border: 1px solid var(--border); cursor: pointer; margin: 0 2px; transition: all 0.2s; }
        .action-btn.view:hover { background: var(--primary); color: white; }
        .action-btn.edit:hover { background: #f59e0b; color: white; }
        .action-btn.delete:hover { background: #ef4444; color: white; }
        
        .pagination { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; flex-wrap: wrap; }
        .page-item { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: white; border: 1px solid var(--border); cursor: pointer; transition: all 0.2s; }
        .page-item:hover { border-color: var(--primary); color: var(--primary); }
        .page-item.active { background: var(--primary); color: white; border-color: var(--primary); }
        .btn-primary-custom { background: var(--primary); border: none; padding: 8px 20px; border-radius: 12px; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary-custom:hover { background: #d5542e; transform: translateY(-2px); color: white; }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .order-buttons { justify-content: flex-end; }
            .btn-order, .btn-export { padding: 6px 12px; font-size: 12px; }
        }
    </style>
</head>
<body>

<!-- ============================================= -->
<!-- TOPBAR COMPLETE (avec toggle sidebar)        -->
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

<!-- ============================================= -->
<!-- MAIN CONTENT                                 -->
<!-- ============================================= -->
<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="fs-3 fw-bold mb-1"><i class="fa-solid fa-users me-2" style="color: var(--primary);"></i>Clients</h1>
                <p class="text-secondary mb-0 small">Gérez votre portefeuille clients</p>
            </div>
            <a href="create_client.php" class="btn-primary-custom">
                <i class="fa-solid fa-plus"></i> Nouveau client
            </a>
        </div>

        <!-- Cartes statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= $total_clients ?></div>
                <div class="label">Total clients</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $clients_actifs ?></div>
                <div class="label">Clients actifs</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= number_format($ca_total, 0, ',', ' ') ?></div>
                <div class="label">Chiffre d'affaires (FCFA)</div>
            </div>
        </div>

        <!-- Barre de recherche et tri -->
        <div class="filter-bar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Rechercher (nom, téléphone, email)...">
            </div>
            <div class="order-buttons">
                <a href="?order=asc" class="btn-order <?= $order == 'asc' ? 'active' : '' ?>"><i class="fa-solid fa-arrow-up-a-z"></i> A → Z</a>
                <a href="?order=desc" class="btn-order <?= $order == 'desc' ? 'active' : '' ?>"><i class="fa-solid fa-arrow-down-z-a"></i> Z → A</a>
                <button class="btn-export" id="exportExcelBtn"><i class="fa-solid fa-file-excel"></i> Excel</button>
                <button class="btn-export" id="exportPdfBtn"><i class="fa-solid fa-file-pdf"></i> PDF</button>
            </div>
        </div>

        <!-- Tableau -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Adresse</th>
                        <th>Commandes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach($clients as $c): ?>
                    <tr class="client-row">
                        <td><strong><?= htmlspecialchars($c['prenom'] . ' ' . ($c['nomc'] ?? '')) ?></strong></td>
                        <td><?= htmlspecialchars($c['tel'] ?? '-') ?></td>
                        <td class="text-truncate" style="max-width: 200px;"><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                        <td class="text-truncate" style="max-width: 180px;"><?= htmlspecialchars($c['adresse'] ?? '-') ?></td>
                        <td><span class="badge-client"><?= $c['nb_commandes'] ?? 0 ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <div class="action-btn view" onclick="viewClient(<?= $c['id'] ?>)" title="Voir"><i class="fa-regular fa-eye"></i></div>
                                <div class="action-btn edit" onclick="editClient(<?= $c['id'] ?>)" title="Modifier"><i class="fa-solid fa-pen"></i></div>
                                <div class="action-btn delete" onclick="deleteClient(<?= $c['id'] ?>)" title="Supprimer"><i class="fa-solid fa-trash"></i></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination" id="pagination"></div>
        
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const clientsData = <?= json_encode($clients) ?>;
let currentPage = 1, rowsPerPage = 8, currentSearch = "";

function renderTable() {
    let filtered = clientsData.filter(c => {
        if (currentSearch) {
            let searchLower = currentSearch.toLowerCase();
            let nom = (c.prenom + ' ' + (c.nomc || '')).toLowerCase();
            let tel = (c.tel || '').toLowerCase();
            let email = (c.email || '').toLowerCase();
            return nom.includes(searchLower) || tel.includes(searchLower) || email.includes(searchLower);
        }
        return true;
    });
    
    let totalPages = Math.ceil(filtered.length / rowsPerPage);
    let start = (currentPage - 1) * rowsPerPage;
    let paginated = filtered.slice(start, start + rowsPerPage);
    
    let tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    
    if (paginated.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5">Aucun client trouvé</span></td>`;
    } else {
        paginated.forEach(c => {
            let nom = c.prenom + (c.nomc ? ' ' + c.nomc : '');
            tbody.innerHTML += `
                <tr class="client-row">
                    <td><strong>${escapeHtml(nom)}</strong></td>
                    <td>${c.tel || '-'}</td>
                    <td class="text-truncate" style="max-width: 200px;">${c.email || '-'}</span>
                    <td class="text-truncate" style="max-width: 180px;">${c.adresse || '-'}</span>
                    <td><span class="badge-client">${c.nb_commandes || 0}</span></span>
                    <td>
                        <div class="d-flex gap-1">
                            <div class="action-btn view" onclick="viewClient(${c.id})" title="Voir"><i class="fa-regular fa-eye"></i></div>
                            <div class="action-btn edit" onclick="editClient(${c.id})" title="Modifier"><i class="fa-solid fa-pen"></i></div>
                            <div class="action-btn delete" onclick="deleteClient(${c.id})" title="Supprimer"><i class="fa-solid fa-trash"></i></div>
                        </div>
                    </span>
                </tr>
            `;
        });
    }
    
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
                }
            });
        });
    }
}

function escapeHtml(str) { if (!str) return ''; return str.replace(/[&<>]/g, function(m) { return m === '&' ? '&amp;' : m === '<' ? '&lt;' : '&gt;'; }); }

window.viewClient = function(id) { window.location.href = `view_client.php?id=${id}`; };
window.editClient = function(id) { window.location.href = `edit_client.php?id=${id}`; };
window.deleteClient = function(id) { if(confirm('Supprimer ce client ?')) window.location.href = `delete_client.php?id=${id}`; };

document.getElementById('searchInput')?.addEventListener('keyup', e => { currentSearch = e.target.value.toLowerCase(); currentPage = 1; renderTable(); });
 // Export Excel (version qui fonctionne sur Excel)
document.getElementById('exportExcelBtn')?.addEventListener('click', () => {
    // Préparer les données
    let rows = [];
    
    // En-tête
    rows.push(['Client', 'Téléphone', 'Email', 'Adresse', 'Commandes']);
    
    // Données
    clientsData.forEach(c => {
        let fullName = c.prenom + (c.nomc ? ' ' + c.nomc : '');
        rows.push([
            fullName,
            c.tel || '',
            c.email || '',
            c.adresse || '',
            c.nb_commandes || 0
        ]);
    });
    
    // Convertir en CSV (séparateur ; pour Excel français)
    let csvContent = rows.map(row => 
        row.map(cell => {
            // Échapper les guillemets
            let escaped = String(cell).replace(/"/g, '""');
            // Encadrer les cellules qui contiennent des virgules ou des retours ligne
            if (escaped.includes(',') || escaped.includes('\n') || escaped.includes('"')) {
                return `"${escaped}"`;
            }
            return escaped;
        }).join(';') // Utiliser ; comme séparateur pour Excel français
    ).join('\n');
    
    // Ajouter BOM UTF-8 pour que Excel lise bien les accents
    let blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
    
    // Télécharger
    let link = document.createElement('a');
    let url = URL.createObjectURL(blob);
    link.href = url;
    link.download = `clients_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    showToast("Export Excel terminé", "success");
});
// Export PDF
document.getElementById('exportPdfBtn')?.addEventListener('click', () => {
    // Construire le HTML pour le PDF
    let printContent = `
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Liste des clients - PowerStock</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                padding: 20px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #E66239;
            }
            .header h1 {
                color: #E66239;
                margin-bottom: 5px;
            }
            .header p {
                color: #666;
                margin: 5px 0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
            }
            th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>POWERSTOCK</h1>
            <p>Bamako - Mali | Tel: 77-90-34-44</p>
            <h3>Liste des clients</h3>
            <p>Date : ${new Date().toLocaleDateString('fr-FR')}</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Adresse</th>
                    <th>Commandes</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    // Ajouter les lignes
    clientsData.forEach(c => {
        let fullName = c.prenom + (c.nomc ? ' ' + c.nomc : '');
        printContent += `
            <tr>
                <td>${fullName}</span>
                <td>${c.tel || '-'}</span>
                <td>${c.email || '-'}</span>
                <td>${c.adresse || '-'}</span>
                <td style="text-align: center">${c.nb_commandes || 0}</span>
            </tr>
        `;
    });
    
    printContent += `
            </tbody>
        </table>
        
        <div class="footer">
            <p>PowerStock - Système de facturation</p>
            <p>© ${new Date().getFullYear()} Tous droits réservés</p>
        </div>
    </body>
    </html>
    `;
    
    // Ouvrir nouvelle fenêtre et imprimer
    let win = window.open('', '_blank');
    win.document.write(printContent);
    win.document.close();
    
    // Attendre que le contenu soit chargé puis imprimer
    win.onload = function() {
        win.print();
        showToast("Export PDF lancé", "success");
    };
});
renderTable();
function showToast(msg, type) {
    let toast = document.createElement('div');
    let bgColor = type === 'success' ? '#10b981' : '#ef4444';
    toast.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: ${bgColor};
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        font-size: 14px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    toast.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${msg}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

// Ajouter les animations CSS
let style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>
</body>
</html>