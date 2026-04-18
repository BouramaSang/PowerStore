<?php
// src/Frontend/products/delete_produit.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: index_product.php?error=ID invalide');
    exit();
}

// Récupérer le produit
$stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index_product.php?error=Produit non trouvé');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = isset($_POST['confirm']) ? trim($_POST['confirm']) : '';
    
    if ($confirm === 'SUPPRIMER') {
        try {
            // Supprimer l'image si elle existe
            if ($product['image'] && file_exists('../../' . $product['image'])) {
                unlink('../../' . $product['image']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
            $stmt->execute([$product_id]);
            
            // ✅ Redirection vers index_product.php avec message de succès
            header('Location: index_produit.php?success=Produit supprimé avec succès');
            exit();
        } catch(PDOException $e) {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez saisir 'SUPPRIMER' pour confirmer la suppression";
    }
}

$page_title = 'Supprimer un produit';
include '../../sidebar.php';
?>

<!-- Le reste du HTML... -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Supprimer un produit - InApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
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
        
        .product-badge-large {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .product-icon-large {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #E66239 0%, #d4552e 100%);
            border-radius: 12px;
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
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .product-price-large {
            font-size: 16px;
            color: #28a745;
            font-weight: bold;
            margin-top: 5px;
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
</head>
<body>

<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        <div class="delete-container">
            <div class="warning-card">
                <div class="warning-icon">
                    <i class="ti ti-trash"></i>
                </div>
                <h2 style="color: #dc3545;">⚠️ Suppression définitive</h2>
                <p>Cette action est <strong>irréversible</strong>. Le produit sera définitivement supprimé.</p>
            </div>
            
            <div class="product-info-card">
                <div class="product-badge-large">
                    <div class="product-icon-large">
                        <i class="ti ti-package"></i>
                    </div>
                    <div class="product-details">
                        <div class="product-name-large"><?= htmlspecialchars($product['nomp']) ?></div>
                        <div class="product-price-large"><?= number_format($product['prix'], 0, ',', ' ') ?> FCFA</div>
                    </div>
                </div>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="confirmation-box">
                <div class="info-text">
                    <i class="ti ti-info-circle"></i>
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
                            <i class="ti ti-trash"></i> Supprimer définitivement
                        </button>
                        <a href="index_produit.php" class="btn-cancel">
                            <i class="ti ti-arrow-left"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="info-text" style="background: #fff3cd; border-left-color: #ffc107;">
                <i class="ti ti-gavel"></i>
                <strong>Conséquences de la suppression :</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>Le produit sera définitivement supprimé du catalogue</li>
                    <li>L'image associée sera également supprimée</li>
                    <li>Cette action est irréversible</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<script>
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
    
    const lastConfirm = confirm('⚠️ DERNIER AVERTISSEMENT ⚠️\n\nVoulez-vous vraiment supprimer définitivement ce produit ?');
    if (!lastConfirm) {
        e.preventDefault();
        return false;
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>