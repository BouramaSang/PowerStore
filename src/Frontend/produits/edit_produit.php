<?php
// src/Frontend/produits/edit_produit.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$error = '';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: index_produit.php?error=ID invalide');
    exit();
}

// Récupérer le produit
$stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index_produit.php?error=Produit non trouvé');
    exit();
}

// Récupérer les catégories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nomcat");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomp = trim($_POST['nomp']);
    $prix = (int)$_POST['prix'];
    $quantite = (int)$_POST['quantite'];
    $description = trim($_POST['description']);
    $categorie_id = !empty($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : null;
    $current_image = $product['image'];
    
    // Validation
    if (empty($nomp)) {
        $error = "Veuillez entrer un nom de produit";
    } elseif ($prix <= 0) {
        $error = "Veuillez entrer un prix valide";
    } elseif ($quantite < 0) {
        $error = "La quantité ne peut pas être négative";
    } else {
        // Gestion de l'upload d'image
        $image_path = $current_image;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../assets/uploads/products/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            
            if (in_array($_FILES['product_image']['type'], $allowed_types)) {
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                    if ($current_image && file_exists('../../' . $current_image)) {
                        unlink('../../' . $current_image);
                    }
                    $image_path = 'assets/uploads/products/' . $filename;
                } else {
                    $error = "Erreur lors de l'upload";
                }
            } else {
                $error = "Format d'image non autorisé";
            }
        }
        
        // Suppression de l'image
        if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
            if ($current_image && file_exists('../../' . $current_image)) {
                unlink('../../' . $current_image);
            }
            $image_path = null;
        }
        
        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE produits 
                    SET nomp = ?, prix = ?, quantite = ?, description = ?, image = ?, categorie_id = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$nomp, $prix, $quantite, $description, $image_path, $categorie_id, $product_id]);
                
                header('Location: index_produit.php?success=Produit modifié avec succès');
                exit();
            } catch(PDOException $e) {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

$page_title = 'Modifier un produit';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un produit - InApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .edit-product-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 30px;
            color: white;
        }
        
        .info-card {
            background: #e7f3ff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .preview-image {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            object-fit: cover;
            margin-top: 10px;
            border: 2px solid #ddd;
        }
        
        .current-image-container {
            text-align: center;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .current-image-container img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
            border: 2px solid #ddd;
        }
        
        .image-upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .image-upload-area:hover {
            border-color: #ffc107;
            background: rgba(255, 193, 7, 0.05);
        }
        
        .delete-image-checkbox {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #333;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-top: 10px;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .required {
            color: #dc3545;
        }
        
        .stock-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 5px;
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
        
        .row-custom {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
    </style>
</head>
<body>

<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        <div class="edit-product-container">
            <div class="form-card">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="ti ti-edit"></i>
                    </div>
                    <h2>Modifier le produit</h2>
                    <p class="text-secondary">Modifiez les informations du produit</p>
                </div>
                
                <div class="info-card">
                    <span>ID Produit: <strong>#<?= $product['id'] ?></strong></span>
                    <span>Créé le: <strong><?= date('d/m/Y', strtotime($product['created_at'])) ?></strong></span>
                    <span>
                        Statut stock: 
                        <?php 
                        $stock = $product['quantite'] ?? 0;
                        if($stock <= 0): ?>
                            <span class="stock-badge stock-low">⚠ Rupture de stock</span>
                        <?php elseif($stock < 5): ?>
                            <span class="stock-badge stock-medium">⚠ Stock faible (<?= $stock ?> unités)</span>
                        <?php else: ?>
                            <span class="stock-badge stock-high">✓ Stock disponible (<?= $stock ?> unités)</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Nom du produit <span class="required">*</span></label>
                        <input type="text" name="nomp" class="form-control" value="<?= htmlspecialchars($product['nomp']) ?>" required autofocus>
                    </div>
                    
                    <div class="row-custom">
                        <div class="mb-3">
                            <label class="form-label">Prix (FCFA) <span class="required">*</span></label>
                            <input type="number" name="prix" class="form-control" value="<?= $product['prix'] ?>" min="0" step="1000" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Quantité en stock <span class="required">*</span></label>
                            <input type="number" name="quantite" class="form-control" value="<?= $product['quantite'] ?? 0 ?>" min="0" step="1" required>
                            <small class="text-secondary">Nombre d'unités disponibles en stock</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Description détaillée du produit..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <select name="categorie_id" class="form-select">
                            <option value="">-- Sans catégorie --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $product['categorie_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nomcat']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Image du produit</label>
                        
                        <?php if($product['image']): ?>
                            <div class="current-image-container" id="currentImageContainer">
                                <div class="mb-2">Image actuelle</div>
                                <img src="../../<?= htmlspecialchars($product['image']) ?>" alt="Image actuelle">
                                <div class="delete-image-checkbox">
                                    <input type="checkbox" name="delete_image" value="1" id="delete_image">
                                    <label for="delete_image" class="text-danger">
                                        <i class="ti ti-trash"></i> Supprimer cette image
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
                            <i class="ti ti-cloud-upload" style="font-size: 32px; color: #ffc107;"></i>
                            <p class="mb-0 small text-secondary mt-2">Cliquez pour changer l'image</p>
                            <p class="small text-secondary">Formats acceptés: JPG, PNG, GIF, WEBP</p>
                        </div>
                        <input type="file" name="product_image" id="imageInput" style="display: none;" accept="image/*" onchange="previewNewImage(this)">
                        <div class="text-center mt-3" id="newImagePreview" style="display: none;">
                            <div class="mb-2">Nouvelle image</div>
                            <img id="newImageImg" class="preview-image" src="">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="ti ti-device-floppy"></i> Enregistrer les modifications
                    </button>
                    <a href="index_produit.php" class="btn-cancel">
                        <i class="ti ti-arrow-left"></i> Annuler
                    </a>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
const deleteImageCheckbox = document.getElementById('delete_image');
const currentImageContainer = document.getElementById('currentImageContainer');

if (deleteImageCheckbox) {
    deleteImageCheckbox.addEventListener('change', function() {
        if (currentImageContainer) {
            if (this.checked) {
                currentImageContainer.style.opacity = '0.5';
                currentImageContainer.style.textDecoration = 'line-through';
            } else {
                currentImageContainer.style.opacity = '1';
                currentImageContainer.style.textDecoration = 'none';
            }
        }
    });
}

function previewNewImage(input) {
    const newImagePreview = document.getElementById('newImagePreview');
    const newImageImg = document.getElementById('newImageImg');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            newImageImg.src = e.target.result;
            newImagePreview.style.display = 'block';
            
            if (currentImageContainer) {
                currentImageContainer.style.display = 'none';
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>