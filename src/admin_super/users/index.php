<?php
// admin_super/users/index.php
require_once '../../config/app.php';
requireSuperAdmin();

$page_title = 'Gestion des Utilisateurs';
include '../../includes/sidebar_super_admin.php';

$pdo = getPDO();

// Récupérer tous les utilisateurs avec des statistiques supplémentaires
$stmt = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM users) as total_users,
           (SELECT COUNT(*) FROM users WHERE role = 'super_admin') as total_super_admin,
           (SELECT COUNT(*) FROM users WHERE is_active = 1) as total_active
    FROM users u 
    ORDER BY u.role DESC, u.created_at DESC
");
$users = $stmt->fetchAll();

// Statistiques globales
$total_users = !empty($users) ? $users[0]['total_users'] : 0;
$total_super_admin = !empty($users) ? $users[0]['total_super_admin'] : 0;
$total_active = !empty($users) ? $users[0]['total_active'] : 0;
$total_inactive = $total_users - $total_active;

// Messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<style>
    /* Stats Cards */
    .stats-dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card-modern {
        background: white;
        border-radius: 15px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
        cursor: pointer;
    }
    
    .stat-card-modern:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .stat-info h4 {
        font-size: 13px;
        color: #666;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-number {
        font-size: 32px;
        font-weight: bold;
        color: #2a5298;
    }
    
    .stat-icon-modern {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    /* Table Styles */
    .table-container {
        overflow-x: auto;
        border-radius: 15px;
    }
    
    .modern-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 15px;
        overflow: hidden;
    }
    
    .modern-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .modern-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .modern-table td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }
    
    .modern-table tbody tr {
        transition: background 0.3s;
    }
    
    .modern-table tbody tr:hover {
        background: #f8f9ff;
    }
    
    /* Badges */
    .badge-role {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-super-admin {
        background: linear-gradient(135deg, #ff4757 0%, #ff6b81 100%);
        color: white;
    }
    
    .badge-admin {
        background: linear-gradient(135deg, #00d2d3 0%, #48dbfb 100%);
        color: white;
    }
    
    .badge-active {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    /* Avatar */
    .user-avatar-mini {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
        font-weight: bold;
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        text-decoration: none;
    }
    
    .btn-icon:hover {
        transform: translateY(-2px);
    }
    
    .btn-edit {
        background: #ffc107;
        color: #333;
    }
    
    .btn-edit:hover {
        background: #e0a800;
        color: #333;
    }
    
    .btn-delete {
        background: #dc3545;
        color: white;
    }
    
    .btn-delete:hover {
        background: #c82333;
        color: white;
    }
    
    /* Search Bar */
    .search-section {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .search-box {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f8f9fa;
        padding: 8px 15px;
        border-radius: 50px;
        flex: 1;
        max-width: 350px;
    }
    
    .search-box i {
        color: #999;
    }
    
    .search-box input {
        border: none;
        background: none;
        padding: 8px 0;
        width: 100%;
        outline: none;
        font-size: 14px;
    }
    
    .filter-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 8px 20px;
        border: 2px solid #e0e0e0;
        background: white;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 13px;
    }
    
    .filter-btn.active {
        background: #2a5298;
        border-color: #2a5298;
        color: white;
    }
    
    .filter-btn:hover {
        border-color: #2a5298;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-icon {
        font-size: 80px;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animated-row {
        animation: fadeIn 0.3s ease forwards;
    }
</style>

<!-- Statistiques Dashboard -->
<div class="stats-dashboard">
    <div class="stat-card-modern" onclick="filterAll()">
        <div class="stat-info">
            <h4><i class="fas fa-users"></i> Total Utilisateurs</h4>
            <div class="stat-number"><?= $total_users ?></div>
        </div>
        <div class="stat-icon-modern">
            <i class="fas fa-users"></i>
        </div>
    </div>
    
    <div class="stat-card-modern" onclick="filterSuperAdmin()">
        <div class="stat-info">
            <h4><i class="fas fa-crown"></i> Super Admins</h4>
            <div class="stat-number" style="color: #ff4757;"><?= $total_super_admin ?></div>
        </div>
        <div class="stat-icon-modern" style="background: linear-gradient(135deg, #ff4757 0%, #ff6b81 100%);">
            <i class="fas fa-crown"></i>
        </div>
    </div>
    
    <div class="stat-card-modern" onclick="filterActive()">
        <div class="stat-info">
            <h4><i class="fas fa-check-circle"></i> Utilisateurs Actifs</h4>
            <div class="stat-number" style="color: #28a745;"><?= $total_active ?></div>
        </div>
        <div class="stat-icon-modern" style="background: linear-gradient(135deg, #28a745 0%, #34ce57 100%);">
            <i class="fas fa-check-circle"></i>
        </div>
    </div>
    
    <div class="stat-card-modern" onclick="filterInactive()">
        <div class="stat-info">
            <h4><i class="fas fa-ban"></i> Utilisateurs Inactifs</h4>
            <div class="stat-number" style="color: #dc3545;"><?= $total_inactive ?></div>
        </div>
        <div class="stat-icon-modern" style="background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%);">
            <i class="fas fa-ban"></i>
        </div>
    </div>
</div>

<!-- Search and Filter Section -->
<div class="search-section">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Rechercher par nom, email ou rôle..." onkeyup="filterTable()">
        <i class="fas fa-times" id="clearSearch" style="cursor: pointer; display: none;" onclick="clearSearch()"></i>
    </div>
    <div class="filter-buttons">
        <button class="filter-btn active" onclick="filterTableByRole('all')">Tous</button>
        <button class="filter-btn" onclick="filterTableByRole('super_admin')">👑 Super Admin</button>
        <button class="filter-btn" onclick="filterTableByRole('admin')">👤 Admin</button>
        <button class="filter-btn" onclick="filterTableByStatus('active')">✅ Actifs</button>
        <button class="filter-btn" onclick="filterTableByStatus('inactive')">❌ Inactifs</button>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="card-header" style="padding: 20px; background: white; border-bottom: 1px solid #f0f0f0;">
        <div>
            <span><i class="fas fa-users" style="color: #2a5298;"></i> <strong>Liste des utilisateurs</strong></span>
            <span style="margin-left: 10px; font-size: 12px; color: #666;">
                <i class="fas fa-chart-line"></i> <?= $total_users ?> utilisateurs au total
            </span>
        </div>
        <a href="add.php" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Nouvel utilisateur
        </a>
    </div>
    
    <?php if($success): ?>
        <div class="alert alert-success" style="margin: 20px;">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger" style="margin: 20px;">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="table-container">
        <table class="modern-table" id="userTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Date création</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <?php foreach($users as $user): ?>
                <tr class="user-row animated-row" data-role="<?= $user['role'] ?>" data-status="<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                    <td style="font-weight: 600;">#<?= $user['id'] ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="user-avatar-mini">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight: 600;"><?= htmlspecialchars($user['username']) ?></div>
                                <div style="font-size: 11px; color: #999;">
                                    <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-envelope" style="color: #999; font-size: 12px;"></i>
                            <span style="font-size: 13px;"><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                    </td>
                    <td>
                        <?php if($user['role'] == 'super_admin'): ?>
                            <span class="badge-role badge-super-admin">
                                <i class="fas fa-crown"></i> Super Admin
                            </span>
                        <?php else: ?>
                            <span class="badge-role badge-admin">
                                <i class="fas fa-user-shield"></i> Admin
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge-role <?= $user['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                            <i class="fas <?= $user['is_active'] ? 'fa-check-circle' : 'fa-ban' ?>"></i>
                            <?= $user['is_active'] ? 'Actif' : 'Inactif' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-calendar-alt" style="color: #999; font-size: 12px;"></i>
                            <span style="font-size: 13px;"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        <div style="font-size: 10px; color: #999; margin-top: 4px;">
                            <i class="fas fa-clock"></i> <?= date('H:i', strtotime($user['created_at'])) ?>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <div class="action-buttons">
                            <a href="edit.php?id=<?= $user['id'] ?>" class="btn-icon btn-edit" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <a href="delete.php?id=<?= $user['id'] ?>" class="btn-icon btn-delete" title="Supprimer" 
                                   onclick="return confirmDelete('<?= htmlspecialchars($user['username']) ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php else: ?>
                                <span class="btn-icon" style="background: #6c757d; color: white; cursor: not-allowed;" title="Vous ne pouvez pas supprimer votre propre compte">
                                    <i class="fas fa-lock"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div style="padding: 20px; border-top: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="font-size: 13px; color: #666;">
            <i class="fas fa-chart-simple"></i> Affichage de <span id="showingStart">0</span> à <span id="showingEnd">0</span> sur <span id="totalCount"><?= $total_users ?></span> utilisateurs
        </div>
        <div style="display: flex; gap: 5px;" id="pagination">
            <!-- Pagination buttons will be added by JavaScript -->
        </div>
    </div>
</div>

<script>
// Search functionality
function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('userTable');
    const rows = table.getElementsByTagName('tr');
    let visibleCount = 0;
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        const roleFilter = row.getAttribute('data-role');
        const statusFilter = row.getAttribute('data-status');
        
        if (text.includes(filter)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    }
    
    document.getElementById('clearSearch').style.display = filter.length > 0 ? 'inline-block' : 'none';
    updatePagination();
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    filterTable();
}

// Filter by role
let currentRoleFilter = 'all';
let currentStatusFilter = 'all';

function filterTableByRole(role) {
    currentRoleFilter = role;
    applyFilters();
    updateActiveButtons();
}

function filterTableByStatus(status) {
    currentStatusFilter = status;
    applyFilters();
    updateActiveButtons();
}

function filterAll() {
    currentRoleFilter = 'all';
    currentStatusFilter = 'all';
    applyFilters();
    updateActiveButtons();
}

function filterSuperAdmin() {
    currentRoleFilter = 'super_admin';
    currentStatusFilter = 'all';
    applyFilters();
    updateActiveButtons();
}

function filterActive() {
    currentRoleFilter = 'all';
    currentStatusFilter = 'active';
    applyFilters();
    updateActiveButtons();
}

function filterInactive() {
    currentRoleFilter = 'all';
    currentStatusFilter = 'inactive';
    applyFilters();
    updateActiveButtons();
}

function applyFilters() {
    const rows = document.querySelectorAll('.user-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const role = row.getAttribute('data-role');
        const status = row.getAttribute('data-status');
        
        let roleMatch = currentRoleFilter === 'all' || role === currentRoleFilter;
        let statusMatch = currentStatusFilter === 'all' || status === currentStatusFilter;
        
        if (roleMatch && statusMatch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    updatePagination();
}

function updateActiveButtons() {
    const buttons = document.querySelectorAll('.filter-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    if (currentRoleFilter === 'all' && currentStatusFilter === 'all') {
        document.querySelector('.filter-btn[onclick="filterTableByRole(\'all\')"]')?.classList.add('active');
    } else if (currentRoleFilter === 'super_admin') {
        document.querySelector('.filter-btn[onclick="filterTableByRole(\'super_admin\')"]')?.classList.add('active');
    } else if (currentRoleFilter === 'admin') {
        document.querySelector('.filter-btn[onclick="filterTableByRole(\'admin\')"]')?.classList.add('active');
    } else if (currentStatusFilter === 'active') {
        document.querySelector('.filter-btn[onclick="filterTableByStatus(\'active\')"]')?.classList.add('active');
    } else if (currentStatusFilter === 'inactive') {
        document.querySelector('.filter-btn[onclick="filterTableByStatus(\'inactive\')"]')?.classList.add('active');
    }
}

// Pagination
let currentPage = 1;
const rowsPerPage = 10;

function updatePagination() {
    const rows = document.querySelectorAll('.user-row');
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    const totalVisible = visibleRows.length;
    const totalPages = Math.ceil(totalVisible / rowsPerPage);
    
    // Update showing info
    const start = (currentPage - 1) * rowsPerPage + 1;
    const end = Math.min(currentPage * rowsPerPage, totalVisible);
    document.getElementById('showingStart').textContent = totalVisible > 0 ? start : 0;
    document.getElementById('showingEnd').textContent = end;
    document.getElementById('totalCount').textContent = totalVisible;
    
    // Hide all rows first
    visibleRows.forEach(row => row.style.display = 'none');
    
    // Show current page rows
    for (let i = (currentPage - 1) * rowsPerPage; i < currentPage * rowsPerPage && i < visibleRows.length; i++) {
        visibleRows[i].style.display = '';
    }
    
    // Create pagination buttons
    const paginationDiv = document.getElementById('pagination');
    paginationDiv.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.style.cssText = 'padding: 8px 12px; border: 1px solid #e0e0e0; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;';
    prevBtn.onclick = () => { if (currentPage > 1) { currentPage--; updatePagination(); } };
    paginationDiv.appendChild(prevBtn);
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.textContent = i;
        pageBtn.style.cssText = `padding: 8px 12px; border: 1px solid ${i === currentPage ? '#2a5298' : '#e0e0e0'}; background: ${i === currentPage ? '#2a5298' : 'white'}; color: ${i === currentPage ? 'white' : '#333'}; border-radius: 8px; cursor: pointer; transition: all 0.3s;`;
        pageBtn.onclick = () => { currentPage = i; updatePagination(); };
        paginationDiv.appendChild(pageBtn);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.style.cssText = 'padding: 8px 12px; border: 1px solid #e0e0e0; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;';
    nextBtn.onclick = () => { if (currentPage < totalPages) { currentPage++; updatePagination(); } };
    paginationDiv.appendChild(nextBtn);
}

// Confirm delete with sweet alert style
function confirmDelete(username) {
    return confirm(`⚠️ Êtes-vous sûr de vouloir supprimer l'utilisateur "${username}" ?\n\nCette action est irréversible !`);
}

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    updatePagination();
});
</script>

<?php include '../../includes/footer.php'; ?>