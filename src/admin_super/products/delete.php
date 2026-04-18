<?php
// admin_super/products/delete.php
require_once '../../config/app.php';
requireSuperAdmin();

$pdo = getPDO();

// ============================================
// TRAITEMENT DE LA SUPPRESSION (AVANT AFFICHAGE)
// ============================================

// Vérifier les colonnes existantes
$columns = $pdo->query("SHOW COLUMNS FROM produits")->fetchAll(PDO::FETCH_COLUMN);
$has_quantite = in_array('quantite', $columns);
$has_image = in_array('image', $columns);

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: index.php?error=ID de produit invalide');
    exit();
}

$stmt = $pdo->prepare("
    SELECT p.*, c.nomcat 
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    WHERE p.id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php?error=Produit non trouvé');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = isset($_POST['confirm']) ? trim($_POST['confirm']) : '';
    
    if ($confirm === 'SUPPRIMER') {
        try {
            // Supprimer l'image si elle existe
            if ($has_image && $product['image'] && file_exists('../../' . $product['image'])) {
                unlink('../../' . $product['image']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
            $stmt->execute([$product_id]);
            
            header('Location: index.php?success=Produit supprimé avec succès');
            exit();
        } catch(PDOException $e) {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez saisir 'SUPPRIMER' pour confirmer la suppression";
    }
}

$page_title = 'Supprimer un produit';
include '../../includes/sidebar_super_admin.php';
?>

<style>
    .delete-container {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .warning-card {
        background: linear-gradient(135deg, #fff5f5 0%, #ffe0e0 100%);
        border: 2px solid #dc3545;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .warning-icon {
        width: 80px;
        height: 80px;
        background: #dc3545;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 40px;
        color: white;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    
    .product-info-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .product-image-thumb {
        width: 80px;
        height: 80px;
        border-radius: 10px;
        object-fit: cover;
        margin-bottom: 15px;
    }
    
    .product-badge-large {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        margin-bottom: 20px;
    }
    
    .product-icon-large {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        color: white;
    }
    
    .product-details {
        flex: 1;
    }
    
    .product-name-large {
        font-size: 20px;
        font-weight: bold;
        color: #333;
    }
    
    .product-price-large {
        font-size: 18px;
        color: #28a745;
        font-weight: bold;
        margin-top: 5px;
    }
    
    .product-category-large {
        font-size: 13px;
        color: #666;
        margin-top: 5px;
    }
    
    .product-stock-large {
        font-size: 13px;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .stock-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .stock-high {
        background: #d4edda;
        color: #155724;
    }
    
    .stock-medium {
        background: #fff3cd;
        color: #856404;
    }
    
    .stock-low {
        background: #f8d7da;
        color: #721c24;
    }
    
    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-label {
        width: 120px;
        font-weight: 600;
        color: #555;
    }
    
    .info-value {
        flex: 1;
        color: #333;
    }
    
    .confirmation-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin: 20px 0;
    }
    
    .confirmation-input {
        font-family: monospace;
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        letter-spacing: 2px;
    }
    
    .btn-delete {
        background: #dc3545;
        color: white;
        padding: 12px 30px;
        font-size: 16px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-delete:hover:not(:disabled) {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
    
    .btn-delete:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .btn-cancel {
        background: #6c757d;
        color: white;
        padding: 12px 30px;
        font-size: 16px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
    }
    
    .btn-cancel:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }
    
    .info-text {
        background: #e7f3ff;
        border-left: 4px solid #2196f3;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        font-size: 14px;
    }
    
    .warning-text {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        font-size: 14px;
    }
    
    .alert {
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }
</style>

<div class="delete-container">
    <div class="warning-card">
        <div class="warning-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2 style="color: #dc3545;">⚠️ Suppression définitive</h2>
        <p>Cette action est <strong>irréversible</strong>. Le produit sera définitivement supprimé.</p>
    </div>
    
    <div class="product-info-card">
        <?php if($has_image && $product['image']): ?>
        <div class="text-center mb-3">
            <img src="../../<?= htmlspecialchars($product['image']) ?>" class="product-image-thumb" alt="<?= htmlspecialchars($product['nomp']) ?>">
        </div>
        <?php endif; ?>
        
        <div class="product-badge-large">
            <div class="product-icon-large">
                <i class="fas fa-box"></i>
            </div>
            <div class="product-details">
                <div class="product-name-large"><?= htmlspecialchars($product['nomp']) ?></div>
                <div class="product-price-large"><?= number_format($product['prix'], 0, ',', ' ') ?> FCFA</div>
                <div class="product-category-large">
                    <i class="fas fa-tag"></i> 
                    <?= $product['nomcat'] ? htmlspecialchars($product['nomcat']) : 'Non catégorisé' ?>
                </div>
                <?php if($has_quantite): ?>
                <div class="product-stock-large">
                    <i class="fas fa-boxes"></i> Quantité en stock : 
                    <?php 
                    $stock = $product['quantite'] ?? 0;
                    if($stock <= 0): ?>
                        <span class="stock-badge stock-low"><?= $stock ?> unités (Rupture)</span>
                    <?php elseif($stock < 5): ?>
                        <span class="stock-badge stock-medium"><?= $stock ?> unités (Stock faible)</span>
                    <?php else: ?>
                        <span class="stock-badge stock-high"><?= $stock ?> unités</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-label"><i class="fas fa-id-badge"></i> ID Produit</div>
            <div class="info-value">#<?= $product['id'] ?></div>
        </div>
        
        <?php if($has_quantite): ?>
        <div class="info-row">
            <div class="info-label"><i class="fas fa-boxes"></i> Quantité</div>
            <div class="info-value"><?= $product['quantite'] ?? 0 ?> unités</div>
        </div>
        <?php endif; ?>
        
        <div class="info-row">
            <div class="info-label"><i class="fas fa-money-bill-wave"></i> Valeur totale</div>
            <div class="info-value">
                <?php 
                $total_value = ($product['prix'] ?? 0) * ($product['quantite'] ?? 0);
                echo number_format($total_value, 0, ',', ' ') . ' FCFA';
                ?>
            </div>
        </div>
        
        <?php if($product['description']): ?>
        <div class="info-row">
            <div class="info-label"><i class="fas fa-align-left"></i> Description</div>
            <div class="info-value">
                <small class="text-secondary"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</small>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="confirmation-box">
        <div class="info-text">
            <i class="fas fa-info-circle"></i>
            Pour confirmer la suppression, veuillez taper <strong>"SUPPRIMER"</strong> dans le champ ci-dessous.
        </div>
        
        <form method="POST" action="" id="deleteForm">
            <div class="form-group mb-3">
                <label>Tapez <strong style="color: #dc3545;">SUPPRIMER</strong> pour confirmer :</label>
                <input type="text" name="confirm" id="confirmInput" class="form-control confirmation-input" 
                       placeholder="SUPPRIMER" autocomplete="off" required
                       style="text-transform: uppercase;">
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: center; margin-top: 25px;">
                <button type="submit" class="btn-delete" id="deleteBtn" disabled>
                    <i class="fas fa-trash-alt"></i> Supprimer définitivement
                </button>
                <a href="index.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i> Annuler
                </a>
            </div>
        </form>
    </div>
    
    <?php if($has_quantite && ($product['quantite'] ?? 0) > 0): ?>
    <div class="warning-text">
        <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
        <strong>Attention !</strong> Ce produit a une quantité en stock de <strong><?= $product['quantite'] ?? 0 ?> unités</strong>.
        La suppression supprimera également ces stocks.
    </div>
    <?php endif; ?>
    
    <?php if($has_image && $product['image']): ?>
    <div class="warning-text">
        <i class="fas fa-image" style="color: #ffc107;"></i>
        <strong>Note :</strong> L'image associée à ce produit sera également supprimée définitivement.
    </div>
    <?php endif; ?>
    
    <div class="info-text" style="background: #fff3cd; border-left-color: #ffc107;">
        <i class="fas fa-gavel"></i>
        <strong>Conséquences de la suppression :</strong>
        <ul style="margin-top: 10px; margin-left: 20px;">
            <li>Le produit sera définitivement supprimé du catalogue</li>
            <?php if($has_quantite && ($product['quantite'] ?? 0) > 0): ?>
            <li>Les <strong><?= $product['quantite'] ?? 0 ?> unités</strong> en stock seront supprimées</li>
            <?php endif; ?>
            <?php if($has_image && $product['image']): ?>
            <li>L'image du produit sera supprimée du serveur</li>
            <?php endif; ?>
            <li>Les commandes associées à ce produit seront affectées</li>
            <li>Cette action est irréversible</li>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmInput = document.getElementById('confirmInput');
    const deleteBtn = document.getElementById('deleteBtn');
    const deleteForm = document.getElementById('deleteForm');
    
    confirmInput.addEventListener('input', function() {
        if (this.value === 'SUPPRIMER') {
            deleteBtn.disabled = false;
            deleteBtn.style.opacity = '1';
            deleteBtn.style.cursor = 'pointer';
        } else {
            deleteBtn.disabled = true;
            deleteBtn.style.opacity = '0.5';
            deleteBtn.style.cursor = 'not-allowed';
        }
    });
    
    deleteForm.addEventListener('submit', function(e) {
        const confirmValue = confirmInput.value;
        
        if (confirmValue !== 'SUPPRIMER') {
            e.preventDefault();
            alert('❌ Veuillez taper "SUPPRIMER" pour confirmer la suppression');
            return false;
        }
        
        <?php if($has_quantite && ($product['quantite'] ?? 0) > 0): ?>
        const hasStock = <?= $product['quantite'] ?? 0 ?>;
        if (hasStock > 0) {
            const confirmStock = confirm('⚠️ ATTENTION : Ce produit a ' + hasStock + ' unités en stock.\n\nLa suppression supprimera également ces stocks.\n\nConfirmer la suppression ?');
            if (!confirmStock) {
                e.preventDefault();
                return false;
            }
        }
        <?php endif; ?>
        
        const lastConfirm = confirm('⚠️ DERNIER AVERTISSEMENT ⚠️\n\nVoulez-vous vraiment supprimer définitivement ce produit ?\n\nCette action est irréversible.');
        if (!lastConfirm) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>