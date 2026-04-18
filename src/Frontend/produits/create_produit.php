<?php
// src/Frontend/produits/create_produit.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$error = '';
$success = '';

// Vérifier les colonnes existantes dans categories
$cat_columns = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_COLUMN);
$has_cat_status = in_array('status', $cat_columns);

// Récupérer UNIQUEMENT les catégories validées (approved)
if ($has_cat_status) {
    $stmt = $pdo->query("
        SELECT * FROM categories 
        WHERE status = 'approved' OR status IS NULL
        ORDER BY nomcat
    ");
} else {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY nomcat");
}
$categories = $stmt->fetchAll();

// Vérifier les colonnes existantes dans produits
$prod_columns = $pdo->query("SHOW COLUMNS FROM produits")->fetchAll(PDO::FETCH_COLUMN);
$has_quantite = in_array('quantite', $prod_columns);
$has_description = in_array('description', $prod_columns);
$has_image = in_array('image', $prod_columns);
$has_created_by = in_array('created_by', $prod_columns);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomp = trim($_POST['nomp']);
    $prix = (int)$_POST['prix'];
    $quantite = isset($_POST['quantite']) ? (int)$_POST['quantite'] : 0;
    $description = trim($_POST['description']);
    $categorie_id = !empty($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : null;
    
    // Vérifier que la catégorie sélectionnée est bien validée
    if ($categorie_id && $has_cat_status) {
        $stmt = $pdo->prepare("SELECT status FROM categories WHERE id = ?");
        $stmt->execute([$categorie_id]);
        $cat_status = $stmt->fetch();
        if ($cat_status && $cat_status['status'] !== 'approved') {
            $error = "Vous ne pouvez pas ajouter un produit à une catégorie en attente ou rejetée.";
        }
    }
    
    // Gestion de l'upload d'image
    $image_path = null;
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
                $image_path = 'assets/uploads/products/' . $filename;
            } else {
                $error = "Erreur lors de l'upload de l'image";
            }
        } else {
            $error = "Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WEBP";
        }
    }
    
    if (empty($nomp)) {
        $error = "Veuillez entrer un nom de produit";
    } elseif ($prix <= 0) {
        $error = "Veuillez entrer un prix valide";
    } elseif ($has_quantite && $quantite < 0) {
        $error = "La quantité ne peut pas être négative";
    } elseif (empty($error)) {
        try {
            // Construction de la requête selon les colonnes existantes
            if ($has_quantite && $has_description && $has_image && $has_created_by) {
                $stmt = $pdo->prepare("
                    INSERT INTO produits (nomp, prix, quantite, description, image, created_by, categorie_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nomp, $prix, $quantite, $description, $image_path, $_SESSION['user_id'], $categorie_id]);
            } elseif ($has_quantite && $has_description && $has_image) {
                $stmt = $pdo->prepare("
                    INSERT INTO produits (nomp, prix, quantite, description, image, categorie_id) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nomp, $prix, $quantite, $description, $image_path, $categorie_id]);
            } elseif ($has_quantite && $has_description) {
                $stmt = $pdo->prepare("
                    INSERT INTO produits (nomp, prix, quantite, description, categorie_id) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nomp, $prix, $quantite, $description, $categorie_id]);
            } elseif ($has_quantite) {
                $stmt = $pdo->prepare("
                    INSERT INTO produits (nomp, prix, quantite, categorie_id) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$nomp, $prix, $quantite, $categorie_id]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO produits (nomp, prix, description, image, created_by, categorie_id) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nomp, $prix, $description, $image_path, $_SESSION['user_id'], $categorie_id]);
            }
            
            header('Location: index_produit.php?success=Produit ajouté avec succès');
            exit();
        } catch(PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

$page_title = 'Ajouter un produit';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un produit - InApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .add-product-container {
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
            background: linear-gradient(135deg, #E66239 0%, #d4552e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 30px;
            color: white;
        }
        
        .form-header h2 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .form-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .input-group-custom {
            margin-bottom: 20px;
        }
        
        .input-group-custom label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .input-group-custom input,
        .input-group-custom select,
        .input-group-custom textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .input-group-custom input:focus,
        .input-group-custom select:focus,
        .input-group-custom textarea:focus {
            outline: none;
            border-color: #E66239;
            box-shadow: 0 0 0 3px rgba(230, 98, 57, 0.1);
        }
        
        .preview-product {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .preview-card-product {
            display: inline-flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .preview-icon-product {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #E66239 0%, #d4552e 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .preview-details {
            text-align: left;
        }
        
        .preview-name-product {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .preview-price-product {
            font-size: 14px;
            color: #28a745;
            margin-top: 5px;
        }
        
        .preview-stock-product {
            font-size: 12px;
            margin-top: 3px;
        }
        
        .image-upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 5px;
        }
        
        .image-upload-area:hover {
            border-color: #E66239;
            background: rgba(230, 98, 57, 0.05);
        }
        
        .preview-image {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            object-fit: cover;
            margin-top: 10px;
            border: 2px solid #ddd;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
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
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
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
        
        .info-text {
            background: #e7f3ff;
            border-left: 4px solid #2196f3;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 13px;
        }
        
        .info-text i {
            color: #2196f3;
            margin-right: 8px;
        }
    </style>
</head>
<body>

<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        <div class="add-product-container">
            <div class="form-card">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="ti ti-package"></i>
                    </div>
                    <h2>Ajouter un produit</h2>
                    <p class="text-secondary">Créez un nouveau produit dans votre catalogue</p>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <div class="preview-product" id="previewCard" style="display: none;">
                    <div class="preview-card-product">
                        <div class="preview-icon-product">
                            <i class="ti ti-box"></i>
                        </div>
                        <div class="preview-details">
                            <div class="preview-name-product" id="previewName"></div>
                            <div class="preview-price-product" id="previewPrice"></div>
                            <?php if($has_quantite): ?>
                            <div class="preview-stock-product" id="previewStock"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="imagePreviewContainer" style="margin-top: 15px;"></div>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="input-group-custom">
                            <label>Nom du produit <span class="required">*</span></label>
                            <input type="text" name="nomp" id="productName" 
                                   placeholder="Ex: iPhone 14 Pro, Samsung TV..." 
                                   required autofocus>
                        </div>
                        
                        <div class="input-group-custom">
                            <label>Prix (FCFA) <span class="required">*</span></label>
                            <input type="number" name="prix" id="productPrice" 
                                   placeholder="Ex: 850000" 
                                   min="0" step="1000" required>
                        </div>
                    </div>
                    
                    <?php if($has_quantite): ?>
                    <div class="form-row">
                        <div class="input-group-custom">
                            <label>Quantité en stock</label>
                            <input type="number" name="quantite" id="productStock" 
                                   placeholder="Ex: 100" 
                                   min="0" step="1" value="0">
                            <small class="text-secondary">Nombre d'unités disponibles</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($has_description): ?>
                    <div class="input-group-custom">
                        <label>Description</label>
                        <textarea name="description" id="productDescription" rows="4" 
                                  placeholder="Description détaillée du produit..."></textarea>
                    </div>
                    <?php endif; ?>
                    
                    <div class="input-group-custom">
                        <label>Catégorie</label>
                        <select name="categorie_id" id="productCategory" class="form-select">
                            <option value="">-- Sélectionnez une catégorie --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nomcat']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if($has_cat_status): ?>
                        <small class="text-secondary">
                            <i class="ti ti-info-circle"></i> 
                            Seules les catégories validées sont disponibles
                        </small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($has_image): ?>
                    <div class="input-group-custom">
                        <label>Image du produit</label>
                        <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
                            <i class="ti ti-cloud-upload" style="font-size: 32px; color: #E66239;"></i>
                            <p class="mb-0 small text-secondary mt-2">Cliquez pour télécharger une image</p>
                            <p class="small text-secondary">Formats acceptés: JPG, PNG, GIF, WEBP</p>
                        </div>
                        <input type="file" name="product_image" id="imageInput" style="display: none;" accept="image/*" onchange="previewUploadedImage(this)">
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-text">
                        <i class="ti ti-info-circle"></i>
                        Le produit sera immédiatement disponible dans le catalogue.
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="ti ti-device-floppy"></i> Ajouter le produit
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
const productName = document.getElementById('productName');
const productPrice = document.getElementById('productPrice');
<?php if($has_quantite): ?>
const productStock = document.getElementById('productStock');
<?php endif; ?>
const previewCard = document.getElementById('previewCard');
const previewName = document.getElementById('previewName');
const previewPrice = document.getElementById('previewPrice');
<?php if($has_quantite): ?>
const previewStock = document.getElementById('previewStock');
<?php endif; ?>
const imagePreviewContainer = document.getElementById('imagePreviewContainer');

function updatePreview() {
    const name = productName.value;
    const price = productPrice.value;
    
    if (name.length > 0) {
        previewName.textContent = name;
        if (price > 0) {
            previewPrice.textContent = new Intl.NumberFormat('fr-FR').format(price) + ' FCFA';
        } else {
            previewPrice.textContent = 'Prix non défini';
        }
        <?php if($has_quantite): ?>
        const stock = productStock.value;
        if (stock >= 0) {
            if (stock == 0) {
                previewStock.innerHTML = '<span style="color: #dc3545;">⚠️ Rupture de stock</span>';
            } else if (stock < 5) {
                previewStock.innerHTML = '<span style="color: #856404;">⚠️ Stock faible (' + stock + ' unités)</span>';
            } else {
                previewStock.innerHTML = '<span style="color: #28a745;">✓ Stock disponible (' + stock + ' unités)</span>';
            }
        }
        <?php endif; ?>
        previewCard.style.display = 'block';
    } else {
        previewCard.style.display = 'none';
    }
}

function previewUploadedImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreviewContainer.innerHTML = `
                <div style="margin-top: 15px;">
                    <div class="mb-2 small text-secondary">Aperçu de l'image :</div>
                    <img src="${e.target.result}" class="preview-image" style="width: 120px; height: 120px; object-fit: cover;">
                </div>
            `;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

productName.addEventListener('input', updatePreview);
productPrice.addEventListener('input', updatePreview);
<?php if($has_quantite): ?>
productStock.addEventListener('input', updatePreview);
<?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>