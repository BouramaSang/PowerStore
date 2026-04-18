<?php
// src/Frontend/category/delete_category.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$error = '';

// Récupérer l'ID de la catégorie
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id <= 0) {
    $_SESSION['error'] = "ID de catégorie invalide";
    header('Location: index_category.php');
    exit();
}

// Récupérer les informations de la catégorie
$stmt = $pdo->prepare("
    SELECT c.*, u.username as created_by_name 
    FROM categories c
    LEFT JOIN users u ON c.created_by = u.id
    WHERE c.id = ?
");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    $_SESSION['error'] = "Catégorie non trouvée";
    header('Location: index_category.php');
    exit();
}

// Vérifier que l'utilisateur est le créateur ou Super Admin
if ($category['created_by'] != $_SESSION['user_id'] && !isSuperAdmin()) {
    $_SESSION['error'] = "Vous n'avez pas le droit de supprimer cette catégorie";
    header('Location: index_category.php');
    exit();
}

// Vérifier si des produits sont associés à cette catégorie
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM produits WHERE categorie_id = ?");
$stmt->execute([$category_id]);
$productCount = $stmt->fetch()['total'];

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = isset($_POST['confirm']) ? trim($_POST['confirm']) : '';
    $delete_products = isset($_POST['delete_products']) ? true : false;
    
    if ($confirm !== 'SUPPRIMER') {
        $error = "Veuillez saisir 'SUPPRIMER' pour confirmer la suppression";
    } else {
        try {
            // Supprimer les produits associés si demandé
            if ($delete_products && $productCount > 0) {
                $stmt = $pdo->prepare("DELETE FROM produits WHERE categorie_id = ?");
                $stmt->execute([$category_id]);
            } elseif ($productCount > 0 && !$delete_products) {
                $error = "Vous devez confirmer la suppression des produits associés";
            } else {
                // Supprimer l'image si elle existe
                if ($category['image'] && file_exists('../../' . $category['image'])) {
                    unlink('../../' . $category['image']);
                }
                
                // Supprimer la catégorie
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$category_id]);
                
                $_SESSION['success'] = "Catégorie supprimée avec succès !";
                header('Location: index_category.php');
                exit();
            }
        } catch(PDOException $e) {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    }
}

$page_title = 'Supprimer une catégorie';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Supprimer une catégorie - InApp</title>
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
        }
        
        .category-badge-large {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .category-icon-large {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #FF8C00 0%, #FFA500 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .category-details {
            flex: 1;
        }
        
        .category-name-large {
            font-size: 18px;
            font-weight: bold;
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
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
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
                <p>Cette action est <strong>irréversible</strong>. La catégorie sera définitivement supprimée.</p>
            </div>
            
            <div class="category-info-card">
                <div class="category-badge-large">
                    <div class="category-icon-large">
                        <i class="ti ti-category"></i>
                    </div>
                    <div class="category-details">
                        <div class="category-name-large"><?= htmlspecialchars($category['nomcat']) ?></div>
                        <div class="text-secondary small">
                            <i class="ti ti-user"></i> Créée par: <?= htmlspecialchars($category['created_by_name'] ?? 'Inconnu') ?>
                        </div>
                        <div class="mt-2">
                            <?php if($category['status'] == 'pending'): ?>
                                <span class="status-badge status-pending">
                                    <i class="ti ti-clock"></i> En attente
                                </span>
                            <?php elseif($category['status'] == 'approved'): ?>
                                <span class="status-badge status-approved">
                                    <i class="ti ti-check"></i> Validée
                                </span>
                            <?php elseif($category['status'] == 'rejected'): ?>
                                <span class="status-badge status-rejected">
                                    <i class="ti ti-x"></i> Rejetée
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if($productCount > 0): ?>
            <div class="warning-text">
                <i class="ti ti-alert-triangle" style="color: #ffc107;"></i>
                <strong>Attention !</strong> Cette catégorie contient <strong><?= $productCount ?> produit(s)</strong>.
                <div class="mt-2">
                    <label class="checkbox-label">
                        <input type="checkbox" id="deleteProductsCheckbox" onchange="toggleDeleteProducts()">
                        <span>Je confirme vouloir supprimer également les <strong><?= $productCount ?> produit(s)</strong> associés</span>
                    </label>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="confirmation-box">
                <div class="info-text">
                    <i class="ti ti-info-circle"></i>
                    Pour confirmer la suppression, veuillez taper <strong>"SUPPRIMER"</strong> dans le champ ci-dessous.
                </div>
                
                <form method="POST" action="" id="deleteForm">
                    <input type="hidden" name="delete_products" id="deleteProductsField" value="0">
                    
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
                        <a href="index_category.php" class="btn-cancel">
                            <i class="ti ti-arrow-left"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="info-text" style="background: #fff3cd; border-left-color: #ffc107;">
                <i class="ti ti-gavel"></i>
                <strong>Conséquences de la suppression :</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>La catégorie sera définitivement supprimée</li>
                    <?php if($productCount > 0): ?>
                    <li><strong style="color: #dc3545;"><?= $productCount ?> produit(s)</strong> seront également supprimés (si coché)</li>
                    <?php endif; ?>
                    <li>Cette action est irréversible</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<script>
// Activer/désactiver le bouton de suppression
const confirmInput = document.getElementById('confirmInput');
const deleteBtn = document.getElementById('deleteBtn');
const deleteProductsCheckbox = document.getElementById('deleteProductsCheckbox');
const deleteProductsField = document.getElementById('deleteProductsField');

function toggleDeleteProducts() {
    if (deleteProductsCheckbox) {
        deleteProductsField.value = deleteProductsCheckbox.checked ? '1' : '0';
    }
}

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

// Validation du formulaire
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    const confirmValue = confirmInput.value;
    const hasProducts = <?= $productCount > 0 ? 'true' : 'false' ?>;
    const deleteProductsChecked = deleteProductsCheckbox ? deleteProductsCheckbox.checked : false;
    
    if (confirmValue !== 'SUPPRIMER') {
        e.preventDefault();
        alert('❌ Veuillez taper "SUPPRIMER" pour confirmer la suppression');
        return false;
    }
    
    if (hasProducts && !deleteProductsChecked) {
        e.preventDefault();
        alert('❌ Vous devez confirmer la suppression des produits associés');
        return false;
    }
    
    let message = '⚠️ DERNIER AVERTISSEMENT ⚠️\n\n';
    message += 'Cette action est irréversible.\n';
    if (hasProducts) {
        message += '<?= $productCount ?> produit(s) seront également supprimés.\n\n';
    }
    message += 'Voulez-vous vraiment supprimer définitivement cette catégorie ?';
    
    const lastConfirm = confirm(message);
    
    if (!lastConfirm) {
        e.preventDefault();
        return false;
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>