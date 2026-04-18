<?php
// admin_super/categories/delete.php
require_once '../../config/app.php';
requireSuperAdmin();

$pdo = getPDO();
$error = '';
$success = '';

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============================================
// TOUTES LES REDIRECTIONS AVANT TOUT AFFICHAGE
// ============================================

if ($category_id <= 0) {
    header('Location: index.php?error=ID de catégorie invalide');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php?error=Catégorie non trouvée');
    exit();
}

// Compter les produits associés
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM produits WHERE categorie_id = ?");
$stmt->execute([$category_id]);
$productCount = $stmt->fetch()['total'];

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = isset($_POST['confirm']) ? trim($_POST['confirm']) : '';
    
    if ($confirm === 'SUPPRIMER') {
        try {
            // Si des produits existent, les supprimer d'abord ou les réassigner ?
            if ($productCount > 0) {
                // Option 1: Supprimer aussi les produits
                $stmt = $pdo->prepare("DELETE FROM produits WHERE categorie_id = ?");
                $stmt->execute([$category_id]);
            }
            
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            
            header('Location: index.php?success=Catégorie supprimée avec succès');
            exit();
        } catch(PDOException $e) {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez saisir 'SUPPRIMER' pour confirmer la suppression";
    }
}

$page_title = 'Supprimer une catégorie';
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
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }
        70% {
            transform: scale(1.05);
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }
    
    .category-info-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .category-badge-large {
        display: inline-flex;
        align-items: center;
        gap: 15px;
        padding: 15px 30px;
        background: linear-gradient(135deg, #f0f2f5 0%, #e9ecef 100%);
        border-radius: 60px;
        margin-bottom: 20px;
    }
    
    .category-icon-large {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    .category-name-large {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }
    
    .stats-badge {
        display: inline-block;
        padding: 8px 20px;
        background: #e7f3ff;
        border-radius: 50px;
        font-size: 14px;
        color: #2196f3;
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
        <h2 style="color: #dc3545; margin-bottom: 10px;">⚠️ Suppression définitive</h2>
        <p style="color: #666;">Cette action est <strong>irréversible</strong>. La catégorie sera définitivement supprimée.</p>
    </div>
    
    <div class="category-info-card">
        <div class="category-badge-large">
            <div class="category-icon-large">
                <i class="fas fa-tag"></i>
            </div>
            <div>
                <div class="category-name-large"><?= htmlspecialchars($category['nomcat']) ?></div>
                <div style="font-size: 12px; color: #999; margin-top: 5px;">ID: #<?= $category['id'] ?></div>
            </div>
        </div>
        
        <div class="stats-badge">
            <i class="fas fa-box"></i> <?= $productCount ?> produit(s) associé(s)
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if($productCount > 0): ?>
    <div class="warning-text">
        <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
        <strong>Attention !</strong> Cette catégorie contient <strong><?= $productCount ?> produit(s)</strong>.
        La suppression de cette catégorie entraînera également la suppression de tous les produits associés.
    </div>
    <?php endif; ?>
    
    <div class="confirmation-box">
        <div class="info-text">
            <i class="fas fa-info-circle"></i>
            Pour confirmer la suppression, veuillez taper <strong>"SUPPRIMER"</strong> dans le champ ci-dessous.
        </div>
        
        <form method="POST" action="" id="deleteForm">
            <div class="form-group">
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
    
    <div class="info-text" style="background: #fff3cd; border-left-color: #ffc107;">
        <i class="fas fa-gavel"></i>
        <strong>Conséquences de la suppression :</strong>
        <ul style="margin-top: 10px; margin-left: 20px;">
            <li>La catégorie sera définitivement supprimée</li>
            <?php if($productCount > 0): ?>
            <li><strong style="color: #dc3545;"><?= $productCount ?> produit(s)</strong> seront également supprimés</li>
            <?php endif; ?>
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
        
        let message = '⚠️ DERNIER AVERTISSEMENT ⚠️\n\n';
        message += 'Cette action est irréversible.\n';
        <?php if($productCount > 0): ?>
        message += '<?= $productCount ?> produit(s) seront également supprimés.\n\n';
        <?php endif; ?>
        message += 'Voulez-vous vraiment supprimer définitivement cette catégorie ?';
        
        const lastConfirm = confirm(message);
        
        if (!lastConfirm) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>