<?php
// admin_super/products/add.php
require_once '../../config/app.php';
requireSuperAdmin();

$pdo = getPDO();

// ============================================
// TRAITEMENT DU FORMULAIRE (AVANT AFFICHAGE)
// ============================================

// Récupérer les catégories pour le select
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nomcat");
$categories = $stmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomp = trim($_POST['nomp']);
    $prix = (int)$_POST['prix'];
    $quantite = (int)$_POST['quantite'];
    $description = trim($_POST['description']);
    $categorie_id = !empty($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : null;
    
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
    
    // Validation
    if (empty($nomp)) {
        $error = "Veuillez entrer un nom de produit";
    } elseif ($prix <= 0) {
        $error = "Veuillez entrer un prix valide";
    } elseif ($quantite < 0) {
        $error = "La quantité ne peut pas être négative";
    } else {
        try {
            // Vérifier si la colonne quantite existe
            $columns = $pdo->query("SHOW COLUMNS FROM produits")->fetchAll(PDO::FETCH_COLUMN);
            $has_quantite = in_array('quantite', $columns);
            $has_description = in_array('description', $columns);
            $has_image = in_array('image', $columns);
            $has_created_by = in_array('created_by', $columns);
            
            if ($has_quantite && $has_description && $has_image && $has_created_by) {
                // Structure complète
                $stmt = $pdo->prepare("
                    INSERT INTO produits (nomp, prix, quantite, description, image, created_by, categorie_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nomp, $prix, $quantite, $description, $image_path, $_SESSION['user_id'], $categorie_id]);
            } else {
                // Structure simple
                $stmt = $pdo->prepare("INSERT INTO produits (nomp, prix, categorie_id) VALUES (?, ?, ?)");
                $stmt->execute([$nomp, $prix, $categorie_id]);
            }
            
            header('Location: index.php?success=Produit ajouté avec succès');
            exit();
        } catch(PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

$page_title = 'Ajouter un produit';
include '../../includes/sidebar_super_admin.php';
?>

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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        color: #666;
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
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.05);
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
        background: linear-gradient(135deg, #28a745 0%, #34ce57 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
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

<div class="add-product-container">
    <div class="form-card">
        <div class="form-header">
            <div class="form-icon">
                <i class="fas fa-box"></i>
            </div>
            <h2>Ajouter un produit</h2>
            <p>Créez un nouveau produit dans votre catalogue</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="preview-product" id="previewCard" style="display: none;">
            <div class="preview-card-product">
                <div class="preview-icon-product">
                    <i class="fas fa-box"></i>
                </div>
                <div class="preview-details">
                    <div class="preview-name-product" id="previewName"></div>
                    <div class="preview-price-product" id="previewPrice"></div>
                    <div class="preview-stock-product" id="previewStock"></div>
                </div>
            </div>
            <div id="imagePreviewContainer" style="margin-top: 15px;"></div>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="input-group-custom">
                    <label>
                        <i class="fas fa-tag"></i> Nom du produit
                        <span class="required">*</span>
                    </label>
                    <input type="text" name="nomp" id="productName" 
                           placeholder="Ex: iPhone 14 Pro, Samsung TV..." 
                           required autofocus>
                </div>
                
                <div class="input-group-custom">
                    <label>
                        <i class="fas fa-money-bill-wave"></i> Prix (FCFA)
                        <span class="required">*</span>
                    </label>
                    <input type="number" name="prix" id="productPrice" 
                           placeholder="Ex: 850000" 
                           min="0" step="1000" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="input-group-custom">
                    <label>
                        <i class="fas fa-boxes"></i> Quantité en stock
                        <span class="required">*</span>
                    </label>
                    <input type="number" name="quantite" id="productStock" 
                           placeholder="Ex: 100" 
                           min="0" step="1" value="0" required>
                    <small class="text-secondary">Nombre d'unités disponibles</small>
                </div>
                
                <div class="input-group-custom">
                    <label>
                        <i class="fas fa-tags"></i> Catégorie
                    </label>
                    <select name="categorie_id" id="productCategory">
                        <option value="">-- Sans catégorie --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nomcat']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="input-group-custom">
                <label>
                    <i class="fas fa-align-left"></i> Description
                </label>
                <textarea name="description" id="productDescription" rows="4" 
                          placeholder="Description détaillée du produit..."></textarea>
            </div>
            
            <div class="input-group-custom">
                <label>
                    <i class="fas fa-image"></i> Image du produit
                </label>
                <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #667eea;"></i>
                    <p class="mb-0 small text-secondary mt-2">Cliquez pour télécharger une image</p>
                    <p class="small text-secondary">Formats acceptés: JPG, PNG, GIF, WEBP</p>
                </div>
                <input type="file" name="product_image" id="imageInput" style="display: none;" accept="image/*" onchange="previewUploadedImage(this)">
            </div>
            
            <div class="info-text">
                <i class="fas fa-info-circle"></i>
                Les produits créés par le Super Admin sont automatiquement disponibles.
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Ajouter le produit
            </button>
        </form>
    </div>
</div>

<script>
const productName = document.getElementById('productName');
const productPrice = document.getElementById('productPrice');
const productStock = document.getElementById('productStock');
const productDescription = document.getElementById('productDescription');
const previewCard = document.getElementById('previewCard');
const previewName = document.getElementById('previewName');
const previewPrice = document.getElementById('previewPrice');
const previewStock = document.getElementById('previewStock');
const imagePreviewContainer = document.getElementById('imagePreviewContainer');

function updatePreview() {
    const name = productName.value;
    const price = productPrice.value;
    const stock = productStock.value;
    
    if (name.length > 0) {
        previewName.textContent = name;
        if (price > 0) {
            previewPrice.textContent = new Intl.NumberFormat('fr-FR').format(price) + ' FCFA';
        } else {
            previewPrice.textContent = 'Prix non défini';
        }
        if (stock >= 0) {
            let stockText = stock + ' unités';
            if (stock == 0) {
                stockText = '⚠️ Rupture de stock';
            } else if (stock < 5) {
                stockText = '⚠️ Stock faible (' + stock + ' unités)';
            } else {
                stockText = '✓ Stock disponible (' + stock + ' unités)';
            }
            previewStock.textContent = stockText;
        }
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
productStock.addEventListener('input', updatePreview);
</script>

<?php include '../../includes/footer.php'; ?>