<?php
// admin_super/categories/add.php
require_once '../../config/app.php';
requireSuperAdmin();

$pdo = getPDO();

// ============================================
// TRAITEMENT DU FORMULAIRE (AVANT TOUT AFFICHAGE)
// ============================================

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomcat = trim($_POST['nomcat']);
    
    // Gestion de l'upload d'image
    $image_path = null;
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/uploads/categories/';
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $filename;
        
        // Types d'images autorisés
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        
        if (in_array($_FILES['category_image']['type'], $allowed_types)) {
            if (move_uploaded_file($_FILES['category_image']['tmp_name'], $upload_path)) {
                $image_path = 'assets/uploads/categories/' . $filename;
            } else {
                $error = "Erreur lors de l'upload de l'image";
            }
        } else {
            $error = "Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WEBP";
        }
    }
    
    if (empty($nomcat)) {
        $error = "Veuillez entrer un nom de catégorie";
    }
    
    if (empty($error)) {
        try {
            // Vérifier si les colonnes existent
            $columns = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_COLUMN);
            $has_image = in_array('image', $columns);
            $has_status = in_array('status', $columns);
            $has_created_by = in_array('created_by', $columns);
            
            if ($has_status && $has_created_by) {
                // Nouvelle structure avec validation
                $stmt = $pdo->prepare("
                    INSERT INTO categories (nomcat, image, status, created_by) 
                    VALUES (?, ?, 'approved', ?)
                ");
                $stmt->execute([$nomcat, $image_path, $_SESSION['user_id']]);
            } else {
                // Ancienne structure simple
                $stmt = $pdo->prepare("INSERT INTO categories (nomcat) VALUES (?)");
                $stmt->execute([$nomcat]);
            }
            
            header('Location: index.php?success=Catégorie ajoutée avec succès');
            exit();
        } catch(PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

$page_title = 'Ajouter une catégorie';
include '../../includes/sidebar_super_admin.php';
?>

<style>
    .add-category-container {
        max-width: 700px;
        margin: 0 auto;
    }
    
    .form-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
    
    .preview-category {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .preview-badge {
        display: inline-flex;
        align-items: center;
        gap: 15px;
        padding: 10px 25px;
        background: white;
        border-radius: 50px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .preview-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
    }
    
    .preview-name {
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }
    
    .preview-image-cat {
        width: 60px;
        height: 60px;
        border-radius: 10px;
        object-fit: cover;
    }
    
    .image-upload-area {
        border: 2px dashed #ddd;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 15px;
    }
    
    .image-upload-area:hover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.05);
    }
    
    .current-image-preview {
        text-align: center;
        margin-top: 10px;
    }
    
    .current-image-preview img {
        max-width: 150px;
        max-height: 150px;
        border-radius: 10px;
        border: 2px solid #ddd;
    }
    
    .input-group-custom {
        margin-bottom: 25px;
    }
    
    .input-group-custom label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
    }
    
    .input-group-custom input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.3s;
    }
    
    .input-group-custom input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        margin: 20px 0;
        font-size: 13px;
    }
    
    .info-text i {
        color: #2196f3;
        margin-right: 8px;
    }
</style>

<div class="add-category-container">
    <div class="form-card">
        <div class="form-header">
            <div class="form-icon">
                <i class="fas fa-tag"></i>
            </div>
            <h2>Ajouter une catégorie</h2>
            <p>Créez une nouvelle catégorie pour organiser vos produits</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="preview-category" id="previewCard" style="display: none;">
            <div class="preview-badge">
                <div class="preview-icon">
                    <i class="fas fa-tag"></i>
                </div>
                <span class="preview-name" id="previewName"></span>
                <img id="previewImage" class="preview-image-cat" style="display: none;">
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="input-group-custom">
                <label>
                    <i class="fas fa-font"></i> Nom de la catégorie
                    <span style="color: red;">*</span>
                </label>
                <input type="text" name="nomcat" id="categoryName" 
                       placeholder="Ex: Téléphones, Ordinateurs, Accessoires..." 
                       required autofocus>
            </div>
            
            <div class="input-group-custom">
                <label>
                    <i class="fas fa-image"></i> Image de la catégorie
                </label>
                <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #667eea;"></i>
                    <p class="mb-0 small text-secondary mt-2">Cliquez pour télécharger une image</p>
                    <p class="small text-secondary">Formats acceptés: JPG, PNG, GIF, WEBP (Max 2MB)</p>
                </div>
                <input type="file" name="category_image" id="imageInput" style="display: none;" accept="image/*" onchange="previewUploadedImage(this)">
                <div class="current-image-preview" id="imagePreviewContainer" style="display: none;">
                    <img id="uploadedImagePreview" src="" alt="Aperçu">
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeImage()">
                            <i class="fas fa-trash"></i> Supprimer l'image
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="info-text">
                <i class="fas fa-info-circle"></i>
                Les catégories créées par le Super Admin sont automatiquement validées et visibles par tous.
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Créer la catégorie
            </button>
        </form>
    </div>
</div>

<script>
const categoryInput = document.getElementById('categoryName');
const previewCard = document.getElementById('previewCard');
const previewName = document.getElementById('previewName');
const previewImage = document.getElementById('previewImage');
let uploadedImageData = null;

// Aperçu du nom en temps réel
categoryInput.addEventListener('input', function() {
    if (this.value.length > 0) {
        previewName.textContent = this.value;
        previewCard.style.display = 'block';
    } else {
        previewCard.style.display = 'none';
    }
});

// Aperçu de l'image uploadée
function previewUploadedImage(input) {
    const previewContainer = document.getElementById('imagePreviewContainer');
    const uploadedPreview = document.getElementById('uploadedImagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            uploadedPreview.src = e.target.result;
            previewContainer.style.display = 'block';
            previewImage.src = e.target.result;
            previewImage.style.display = 'inline-block';
            uploadedImageData = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Supprimer l'image sélectionnée
function removeImage() {
    const imageInput = document.getElementById('imageInput');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const uploadedPreview = document.getElementById('uploadedImagePreview');
    
    imageInput.value = '';
    previewContainer.style.display = 'none';
    uploadedPreview.src = '';
    previewImage.style.display = 'none';
    previewImage.src = '';
    uploadedImageData = null;
}
</script>

<?php include '../../includes/footer.php'; ?>