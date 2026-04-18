<?php
// admin_super/categories/pending.php - Version avec gestion des produits
require_once '../../config/app.php';
requireSuperAdmin();

$pdo = getPDO();

// ============================================
// TRAITEMENT DES ACTIONS (AVANT TOUT AFFICHAGE)
// ============================================

$error = '';
$success = '';

// Récupérer les colonnes existantes
$columns = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_COLUMN);
$has_created_at = in_array('created_at', $columns);
$has_image = in_array('image', $columns);

// Traitement de la validation/rejet (AVANT tout affichage)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)$_POST['category_id'];
    $action = $_POST['action'];
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    $handle_products = $_POST['handle_products'] ?? 'keep'; // keep, deactivate, move, delete
    
    // Vérifier si la catégorie a des produits
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM produits WHERE categorie_id = ?");
    $stmt->execute([$category_id]);
    $productCount = $stmt->fetch()['total'];
    
    if ($action === 'approve') {
        try {
            $stmt = $pdo->prepare("
                UPDATE categories 
                SET status = 'approved', approved_by = ?, approved_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $category_id]);
            
            // Activer les produits associés
            if ($productCount > 0) {
                // Vérifier si la table produits a une colonne status
                $prod_columns = $pdo->query("SHOW COLUMNS FROM produits")->fetchAll(PDO::FETCH_COLUMN);
                if (in_array('status', $prod_columns)) {
                    $stmt = $pdo->prepare("UPDATE produits SET status = 'active' WHERE categorie_id = ?");
                    $stmt->execute([$category_id]);
                }
            }
            
            $success = "Catégorie validée avec succès !";
        } catch(PDOException $e) {
            $error = "Erreur lors de la validation : " . $e->getMessage();
        }
    } elseif ($action === 'reject') {
        if (empty($rejection_reason)) {
            $error = "Veuillez fournir une raison pour le rejet";
        } else {
            try {
                // Gestion des produits selon le choix
                if ($productCount > 0) {
                    switch($handle_products) {
                        case 'deactivate':
                            // Désactiver les produits (si colonne status existe)
                            $prod_columns = $pdo->query("SHOW COLUMNS FROM produits")->fetchAll(PDO::FETCH_COLUMN);
                            if (in_array('status', $prod_columns)) {
                                $stmt = $pdo->prepare("UPDATE produits SET status = 'inactive' WHERE categorie_id = ?");
                                $stmt->execute([$category_id]);
                            }
                            break;
                            
                        case 'move':
                            // Déplacer vers "Sans catégorie" (categorie_id = NULL)
                            $stmt = $pdo->prepare("UPDATE produits SET categorie_id = NULL WHERE categorie_id = ?");
                            $stmt->execute([$category_id]);
                            break;
                            
                        case 'delete':
                            // Supprimer les produits associés
                            $stmt = $pdo->prepare("DELETE FROM produits WHERE categorie_id = ?");
                            $stmt->execute([$category_id]);
                            break;
                            
                        case 'keep':
                        default:
                            // Garder les produits avec la catégorie rejetée
                            break;
                    }
                }
                
                $stmt = $pdo->prepare("
                    UPDATE categories 
                    SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $rejection_reason, $category_id]);
                $success = "Catégorie rejetée";
            } catch(PDOException $e) {
                $error = "Erreur lors du rejet : " . $e->getMessage();
            }
        }
    }
    
    // Redirection après traitement
    if (empty($error) && !empty($success)) {
        header('Location: pending.php?success=' . urlencode($success));
        exit();
    } elseif (!empty($error)) {
        header('Location: pending.php?error=' . urlencode($error));
        exit();
    }
}

// Récupérer les messages après redirection
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';

// Récupérer les catégories en attente
$query = "
    SELECT c.*, u.username as created_by_name, u.email as created_by_email,
           (SELECT COUNT(*) FROM produits WHERE categorie_id = c.id) as total_produits
    FROM categories c
    LEFT JOIN users u ON c.created_by = u.id
    WHERE c.status = 'pending'
    ORDER BY c.id DESC
";
$stmt = $pdo->query($query);
$pending_categories = $stmt->fetchAll();

$page_title = 'Catégories en attente de validation';
include '../../includes/sidebar_super_admin.php';
?>

<style>
    /* Styles existants... */
    .stats-pending {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-pending-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s;
    }
    
    .stat-pending-card:hover {
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
        color: #ffc107;
    }
    
    .stat-icon-pending {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    .pending-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }
    
    .pending-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s;
        position: relative;
    }
    
    .pending-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    
    .pending-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #ffc107;
        color: #333;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        z-index: 10;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .pending-image {
        height: 180px;
        overflow: hidden;
        background: #f8f9fa;
    }
    
    .pending-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    
    .pending-card:hover .pending-image img {
        transform: scale(1.05);
    }
    
    .pending-image-placeholder {
        height: 180px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 48px;
    }
    
    .pending-content {
        padding: 20px;
    }
    
    .pending-title {
        font-size: 20px;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
    }
    
    .pending-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #666;
    }
    
    .meta-item i {
        color: #ffc107;
        width: 16px;
    }
    
    .pending-stats {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .stat-item {
        flex: 1;
        text-align: center;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 10px;
    }
    
    .stat-value {
        font-size: 18px;
        font-weight: bold;
        color: #333;
    }
    
    .stat-label {
        font-size: 11px;
        color: #999;
        margin-top: 5px;
    }
    
    .product-warning {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 12px;
        border-radius: 8px;
        margin: 15px 0;
        font-size: 13px;
    }
    
    .radio-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .radio-option {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }
    
    .radio-option input {
        width: auto;
        margin: 0;
    }
    
    .action-buttons-pending {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-approve {
        flex: 1;
        background: linear-gradient(135deg, #28a745 0%, #34ce57 100%);
        color: white;
        border: none;
        padding: 10px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-approve:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }
    
    .btn-reject {
        flex: 1;
        background: linear-gradient(135deg, #dc3545 0%, #ff4757 100%);
        color: white;
        border: none;
        padding: 10px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-reject:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
    
    .rejection-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    
    .rejection-modal.active {
        display: flex;
    }
    
    .rejection-modal-content {
        background: white;
        border-radius: 15px;
        padding: 25px;
        width: 90%;
        max-width: 500px;
        animation: modalSlideIn 0.3s ease;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    @keyframes modalSlideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .rejection-modal-content h3 {
        margin-bottom: 15px;
        color: #dc3545;
    }
    
    .rejection-modal-content textarea {
        width: 100%;
        padding: 10px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        resize: vertical;
        min-height: 100px;
        font-size: 14px;
    }
    
    .rejection-modal-content textarea:focus {
        outline: none;
        border-color: #dc3545;
    }
    
    .modal-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-confirm-reject {
        background: #dc3545;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        flex: 1;
    }
    
    .btn-cancel-reject {
        background: #6c757d;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        flex: 1;
    }
    
    .alert {
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-icon {
        font-size: 80px;
        color: #ddd;
        margin-bottom: 20px;
    }
</style>

<!-- Statistiques Dashboard -->
<div class="stats-pending">
    <div class="stat-pending-card">
        <div class="stat-info">
            <h4><i class="fas fa-clock"></i> En attente de validation</h4>
            <div class="stat-number"><?= count($pending_categories) ?></div>
        </div>
        <div class="stat-icon-pending">
            <i class="fas fa-clock"></i>
        </div>
    </div>
</div>

<?php if($success_msg): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?>
    </div>
<?php endif; ?>

<?php if($error_msg): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_msg) ?>
    </div>
<?php endif; ?>

<?php if(count($pending_categories) > 0): ?>
    <div class="pending-grid">
        <?php foreach($pending_categories as $cat): ?>
        <div class="pending-card">
            <div class="pending-badge">
                <i class="fas fa-clock"></i> En attente de validation
            </div>
            
            <div class="pending-image">
                <?php if($has_image && $cat['image']): ?>
                    <img src="../../<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['nomcat']) ?>">
                <?php else: ?>
                    <div class="pending-image-placeholder">
                        <i class="fas fa-tag"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="pending-content">
                <h3 class="pending-title"><?= htmlspecialchars($cat['nomcat']) ?></h3>
                
                <div class="pending-meta">
                    <div class="meta-item">
                        <i class="fas fa-id-badge"></i>
                        <span>ID: #<?= $cat['id'] ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>Créé par: <?= htmlspecialchars($cat['created_by_name'] ?? 'Inconnu') ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <span><?= htmlspecialchars($cat['created_by_email'] ?? 'Email non disponible') ?></span>
                    </div>
                </div>
                
                <div class="pending-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= $cat['total_produits'] ?></div>
                        <div class="stat-label">Produits associés</div>
                    </div>
                </div>
                
                <div class="action-buttons-pending">
                    <button class="btn-approve" onclick="approveCategory(<?= $cat['id'] ?>)">
                        <i class="fas fa-check-circle"></i> Valider
                    </button>
                    <button class="btn-reject" onclick="showRejectModal(<?= $cat['id'] ?>, <?= $cat['total_produits'] ?>)">
                        <i class="fas fa-times-circle"></i> Rejeter
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>Aucune catégorie en attente</h3>
        <p class="text-secondary">Toutes les catégories ont été traitées ou il n'y a pas de demande en attente.</p>
    </div>
<?php endif; ?>

<!-- Modal pour le rejet -->
<div id="rejectModal" class="rejection-modal">
    <div class="rejection-modal-content">
        <h3><i class="fas fa-exclamation-triangle"></i> Rejeter la catégorie</h3>
        
        <div id="productWarning" class="product-warning" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Attention !</strong> Cette catégorie contient des produits.
            <div class="radio-group" id="productOptions">
                <label class="radio-option">
                    <input type="radio" name="handle_products" value="keep" checked>
                    <span>📦 Garder les produits (ils deviendront orphelins)</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="handle_products" value="deactivate">
                    <span>⛔ Désactiver les produits</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="handle_products" value="move">
                    <span>📁 Déplacer vers "Sans catégorie"</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="handle_products" value="delete">
                    <span class="text-danger">🗑️ Supprimer définitivement les produits</span>
                </label>
            </div>
        </div>
        
        <p>Veuillez indiquer la raison du rejet :</p>
        <textarea id="rejectionReason" placeholder="Expliquez pourquoi cette catégorie est rejetée..."></textarea>
        
        <div class="modal-buttons">
            <button class="btn-confirm-reject" onclick="confirmReject()">
                <i class="fas fa-check"></i> Confirmer le rejet
            </button>
            <button class="btn-cancel-reject" onclick="closeRejectModal()">
                <i class="fas fa-times"></i> Annuler
            </button>
        </div>
    </div>
</div>

<!-- Formulaires cachés -->
<form id="approveForm" method="POST" style="display: none;">
    <input type="hidden" name="category_id" id="approveCategoryId">
    <input type="hidden" name="action" value="approve">
</form>

<form id="rejectForm" method="POST" style="display: none;">
    <input type="hidden" name="category_id" id="rejectCategoryId">
    <input type="hidden" name="action" value="reject">
    <input type="hidden" name="rejection_reason" id="rejectReason">
    <input type="hidden" name="handle_products" id="handleProducts" value="keep">
</form>

<script>
let currentRejectId = null;
let currentProductCount = 0;

function approveCategory(id) {
    if (confirm('✅ Confirmer la validation de cette catégorie ?\n\nElle sera immédiatement visible par tous les utilisateurs.')) {
        document.getElementById('approveCategoryId').value = id;
        document.getElementById('approveForm').submit();
    }
}

function showRejectModal(id, productCount) {
    currentRejectId = id;
    currentProductCount = productCount;
    
    const modal = document.getElementById('rejectModal');
    const productWarning = document.getElementById('productWarning');
    const productOptions = document.getElementById('productOptions');
    
    if (productCount > 0) {
        productWarning.style.display = 'block';
    } else {
        productWarning.style.display = 'none';
    }
    
    modal.classList.add('active');
    document.getElementById('rejectionReason').value = '';
    
    // Réinitialiser les options
    const radios = document.querySelectorAll('input[name="handle_products"]');
    radios.forEach(radio => {
        if (radio.value === 'keep') radio.checked = true;
    });
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('active');
    currentRejectId = null;
}

function confirmReject() {
    const reason = document.getElementById('rejectionReason').value.trim();
    
    if (!reason) {
        alert('⚠️ Veuillez indiquer une raison pour le rejet');
        return;
    }
    
    // Récupérer l'option choisie pour les produits
    let handleProducts = 'keep';
    const selectedRadio = document.querySelector('input[name="handle_products"]:checked');
    if (selectedRadio) {
        handleProducts = selectedRadio.value;
    }
    
    let confirmMessage = '⚠️ Confirmer le rejet de cette catégorie ?\n\n';
    confirmMessage += 'L\'utilisateur sera informé de la raison.\n\n';
    
    if (currentProductCount > 0) {
        switch(handleProducts) {
            case 'keep':
                confirmMessage += '📦 Les produits resteront dans cette catégorie rejetée.';
                break;
            case 'deactivate':
                confirmMessage += '⛔ Les produits seront désactivés.';
                break;
            case 'move':
                confirmMessage += '📁 Les produits seront déplacés vers "Sans catégorie".';
                break;
            case 'delete':
                confirmMessage += '🗑️ Les ' + currentProductCount + ' produit(s) seront SUPPRIMÉS définitivement !';
                break;
        }
        confirmMessage += '\n\n';
    }
    
    if (confirm(confirmMessage)) {
        document.getElementById('rejectCategoryId').value = currentRejectId;
        document.getElementById('rejectReason').value = reason;
        document.getElementById('handleProducts').value = handleProducts;
        document.getElementById('rejectForm').submit();
    }
}

// Fermer le modal en cliquant en dehors
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});

// Fermer le modal avec Echap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('rejectModal').classList.contains('active')) {
        closeRejectModal();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>