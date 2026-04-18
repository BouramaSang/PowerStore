<?php
// admin_super/products/edit.php
require_once '../../config/app.php';
requireSuperAdmin();

$pdo = getPDO();

// ============================================
// TRAITEMENT DU FORMULAIRE (AVANT AFFICHAGE)
// ============================================

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: index.php?error=ID de produit invalide');
    exit();
}

// Récupérer le produit
$stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php?error=Produit non trouvé');
    exit();
}

// Récupérer les catégories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nomcat");
$categories = $stmt->fetchAll();

// Vérifier les colonnes existantes
$columns = $pdo->query("SHOW COLUMNS FROM produits")->fetchAll(PDO::FETCH_COLUMN);
$has_quantite = in_array('quantite', $columns);
$has_description = in_array('description', $columns);
$has_image = in_array('image', $columns);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomp = trim($_POST['nomp']);
    $prix = (int)$_POST['prix'];
    $quantite = isset($_POST['quantite']) ? (int)$_POST['quantite'] : 0;
    $description = trim($_POST['description']);
    $categorie_id = !empty($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : null;
    $current_image = $product['image'] ?? null;
    
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
                $error = "Erreur lors de l'upload de l'image";
            }
        } else {
            $error = "Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WEBP";
        }
    }
    
    // Suppression de l'image
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if ($current_image && file_exists('../../' . $current_image)) {
            unlink('../../' . $current_image);
        }
        $image_path = null;
    }
    
    // Validation
    if (empty($nomp)) {
        $error = "Veuillez entrer un nom de produit";
    } elseif ($prix <= 0) {
        $error = "Veuillez entrer un prix valide";
    } elseif ($has_quantite && $quantite < 0) {
        $error = "La quantité ne peut pas être négative";
    } else {
        try {
            // Construction de la requête dynamique selon les colonnes existantes
            if ($has_quantite && $has_description && $has_image) {
                $stmt = $pdo->prepare("
                    UPDATE produits 
                    SET nomp = ?, prix = ?, quantite = ?, description = ?, image = ?, categorie_id = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$nomp, $prix, $quantite, $description, $image_path, $categorie_id, $product_id]);
            } elseif ($has_quantite && $has_description) {
                $stmt = $pdo->prepare("
                    UPDATE produits 
                    SET nomp = ?, prix = ?, quantite = ?, description = ?, categorie_id = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$nomp, $prix, $quantite, $description, $categorie_id, $product_id]);
            } elseif ($has_quantite) {
                $stmt = $pdo->prepare("
                    UPDATE produits 
                    SET nomp = ?, prix = ?, quantite = ?, categorie_id = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$nomp, $prix, $quantite, $categorie_id, $product_id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE produits 
                    SET nomp = ?, prix = ?, categorie_id = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$nomp, $prix, $categorie_id, $product_id]);
            }
            
            header('Location: index.php?success=Produit modifié avec succès');
            exit();
        } catch(PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

$page_title = 'Modifier un produit';
include '../../includes/sidebar_super_admin.php';
?>

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
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
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
        gap: 10px;
    }
    
    .info-label {
        font-weight: 600;
        color: #2196f3;
    }
    
    .info-value {
        font-size: 18px;
        font-weight: bold;
        color: #2196f3;
    }
    
    .stock-badge-info {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .stock-high-info {
        background: #d4edda;
        color: #155724;
    }
    
    .stock-medium-info {
        background: #fff3cd;
        color: #856404;
    }
    
    .stock-low-info {
        background: #f8d7da;
        color: #721c24;
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
        border-color: #ffc107;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.1);
    }
    
    .preview-product {
        background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
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
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
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
        border-color: #ffc107;
        background: rgba(255, 193, 7, 0.05);
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
    
    .current-image-label {
        font-size: 12px;
        color: #666;
        margin-bottom: 10px;
    }
    
    .delete-image-checkbox {
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .preview-image {
        width: 100px;
        height: 100px;
        border-radius: 10px;
        object-fit: cover;
        margin-top: 10px;
        border: 2px solid #ddd;
    }
    
    .btn-submit {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        color: #333;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
    }
    
    .btn-cancel {
        width: 100%;
        padding: 12px;
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        margin-top: 10px;
        transition: all 0.3s;
    }
    
    .btn-cancel:hover {
        background: #5a6268;
        transform: translateY(-2px);
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
    
    .required {
        color: #dc3545;
    }
</style>

<div class="edit-product-container">
    <div class="form-card">
        <div class="form-header">
            <div class="form-icon">
                <i class="fas fa-edit"></i>
            </div>
            <h2>Modifier le produit</h2>
            <p>Modifiez les informations du produit</p>
        </div>
        
        <div class="info-card">
            <span class="info-label"><i class="fas fa-id-badge"></i> ID Produit</span>
            <span class="info-value">#<?= $product['id'] ?></span>
            <?php if($has_quantite): ?>
            <span class="info-label"><i class="fas fa-boxes"></i> Stock actuel</span>
            <span class="info-value">
                <?php 
                $stock = $product['quantite'] ?? 0;
                if($stock <= 0): ?>
                    <span class="stock-badge-info stock-low-info">⚠️ Rupture de stock (<?= $stock ?> unités)</span>
                <?php elseif($stock < 5): ?>
                    <span class="stock-badge-info stock-medium-info">⚠️ Stock faible (<?= $stock ?> unités)</span>
                <?php else: ?>
                    <span class="stock-badge-info stock-high-info">✓ Stock disponible (<?= $stock ?> unités)</span>
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="preview-product">
            <div class="preview-card-product">
                <div class="preview-icon-product">
                    <i class="fas fa-box"></i>
                </div>
                <div class="preview-details">
                    <div class="preview-name-product" id="previewName"><?= htmlspecialchars($product['nomp']) ?></div>
                    <div class="preview-price-product" id="previewPrice"><?= number_format($product['prix'], 0, ',', ' ') ?> FCFA</div>
                    <?php if($has_quantite): ?>
                    <div class="preview-stock-product" id="previewStock">
                        <?php 
                        $stock = $product['quantite'] ?? 0;
                        if($stock <= 0): ?>
                            <span style="color: #dc3545;">⚠️ Rupture de stock</span>
                        <?php elseif($stock < 5): ?>
                            <span style="color: #856404;">⚠️ Stock faible (<?= $stock ?> unités)</span>
                        <?php else: ?>
                            <span style="color: #28a745;">✓ Stock disponible (<?= $stock ?> unités)</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="input-group-custom">
                    <label>
                        <i class="fas fa-tag"></i> Nom du produit
                        <span class="required">*</span>
                    </label>
                    <input type="text" name="nomp" id="productName" 
                           value="<?= htmlspecialchars($product['nomp']) ?>"
                           required autofocus>
                </div>
                
                <div class="input-group-custom">
                    <label>
                        <i class="fas fa-money-bill-wave"></i> Prix (FCFA)
                        <span class="required">*</span>
                    </label>
                    <input type="number" name="prix" id="productPrice" 
                           value="<?= $product['prix'] ?>" 
                           min="0" step="1000" required>
                </div>
            </div>
            
            <?php if($has_quantite): ?>
            <div class="form-row">
                <div class="input-group-custom">
                    <label>
                        <i class="fas fa-boxes"></i> Quantité en stock
                        <span class="required">*</span>
                    </label>
                    <input type="number" name="quantite" id="productStock" 
                           value="<?= $product['quantite'] ?? 0 ?>" 
                           min="0" step="1" required>
                    <small class="text-secondary">Nombre d'unités disponibles en stock</small>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($has_description): ?>
            <div class="input-group-custom">
                <label>
                    <i class="fas fa-align-left"></i> Description
                </label>
                <textarea name="description" id="productDescription" rows="4" 
                          placeholder="Description détaillée du produit..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
            <?php endif; ?>
            
            <div class="input-group-custom">
                <label>
                    <i class="fas fa-tags"></i> Catégorie
                </label>
                <select name="categorie_id" id="productCategory">
                    <option value="">-- Sans catégorie --</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $product['categorie_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nomcat']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if($has_image): ?>
            <div class="input-group-custom">
                <label>
                    <i class="fas fa-image"></i> Image du produit
                </label>
                
                <?php if($product['image']): ?>
                    <div class="current-image-container" id="currentImageContainer">
                        <div class="current-image-label">Image actuelle</div>
                        <img src="../../<?= htmlspecialchars($product['image']) ?>" alt="Image actuelle">
                        <div class="delete-image-checkbox">
                            <input type="checkbox" name="delete_image" value="1" id="delete_image">
                            <label for="delete_image" class="text-danger">
                                <i class="fas fa-trash"></i> Supprimer cette image
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #ffc107;"></i>
                    <p class="mb-0 small text-secondary mt-2">Cliquez pour changer l'image</p>
                    <p class="small text-secondary">Formats acceptés: JPG, PNG, GIF, WEBP</p>
                </div>
                <input type="file" name="product_image" id="imageInput" style="display: none;" accept="image/*" onchange="previewNewImage(this)">
                <div class="text-center mt-3" id="newImagePreview" style="display: none;">
                    <div class="mb-2">Nouvelle image</div>
                    <img id="newImageImg" class="preview-image" src="">
                </div>
            </div>
            <?php endif; ?>
            
            <div class="info-text">
                <i class="fas fa-info-circle"></i>
                Les modifications seront immédiatement visibles dans le catalogue.
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Enregistrer les modifications
            </button>
            <a href="index.php" class="btn-cancel">
                <i class="fas fa-arrow-left"></i> Annuler
            </a>
        </form>
    </div>
</div>

<script>
const productName = document.getElementById('productName');
const productPrice = document.getElementById('productPrice');
const productStock = document.getElementById('productStock');
const previewName = document.getElementById('previewName');
const previewPrice = document.getElementById('previewPrice');
const previewStock = document.getElementById('previewStock');

function updatePreview() {
    const name = productName.value;
    const price = productPrice.value;
    
    if (name.length > 0) {
        previewName.textContent = name;
    }
    if (price > 0) {
        previewPrice.textContent = new Intl.NumberFormat('fr-FR').format(price) + ' FCFA';
    } else if (price === '') {
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
}

productName.addEventListener('input', updatePreview);
productPrice.addEventListener('input', updatePreview);
<?php if($has_quantite): ?>
productStock.addEventListener('input', updatePreview);
<?php endif; ?>

// Gestion de l'image
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

<?php include '../../includes/footer.php'; ?>