<?php
// admin_super/categories/edit.php
require_once '../../config/app.php';
requireSuperAdmin();

$pdo = getPDO();

// ============================================
// TRAITEMENT DU FORMULAIRE (AVANT TOUT AFFICHAGE)
// ============================================

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

// Vérifier les colonnes existantes
$columns = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_COLUMN);
$has_image = in_array('image', $columns);
$has_status = in_array('status', $columns);

// Compter les produits associés
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM produits WHERE categorie_id = ?");
$stmt->execute([$category_id]);
$productCount = $stmt->fetch()['total'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomcat = trim($_POST['nomcat']);
    $current_image = $category['image'] ?? null;
    $new_status = $_POST['status'] ?? $category['status'] ?? 'approved';
    
    // Gestion de l'upload d'image
    $image_path = $current_image;
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
                // Supprimer l'ancienne image si elle existe
                if ($current_image && file_exists('../../' . $current_image)) {
                    unlink('../../' . $current_image);
                }
                $image_path = 'assets/uploads/categories/' . $filename;
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
    
    if (empty($nomcat)) {
        $error = "Veuillez entrer un nom de catégorie";
    }
    
    if (empty($error)) {
        try {
            if ($has_image && $has_status) {
                // Avec image et statut
                $stmt = $pdo->prepare("
                    UPDATE categories 
                    SET nomcat = ?, image = ?, status = ?, 
                        approved_by = CASE WHEN ? = 'approved' THEN ? ELSE approved_by END,
                        approved_at = CASE WHEN ? = 'approved' THEN NOW() ELSE approved_at END
                    WHERE id = ?
                ");
                $stmt->execute([
                    $nomcat, $image_path, $new_status,
                    $new_status, $_SESSION['user_id'],
                    $new_status,
                    $category_id
                ]);
            } elseif ($has_image) {
                // Avec image seulement
                $stmt = $pdo->prepare("UPDATE categories SET nomcat = ?, image = ? WHERE id = ?");
                $stmt->execute([$nomcat, $image_path, $category_id]);
            } elseif ($has_status) {
                // Avec statut seulement
                $stmt = $pdo->prepare("
                    UPDATE categories 
                    SET nomcat = ?, status = ?,
                        approved_by = CASE WHEN ? = 'approved' THEN ? ELSE approved_by END,
                        approved_at = CASE WHEN ? = 'approved' THEN NOW() ELSE approved_at END
                    WHERE id = ?
                ");
                $stmt->execute([
                    $nomcat, $new_status,
                    $new_status, $_SESSION['user_id'],
                    $new_status,
                    $category_id
                ]);
            } else {
                // Structure simple
                $stmt = $pdo->prepare("UPDATE categories SET nomcat = ? WHERE id = ?");
                $stmt->execute([$nomcat, $category_id]);
            }
            
            header('Location: index.php?success=Catégorie modifiée avec succès');
            exit();
        } catch(PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

$page_title = 'Modifier une catégorie';
include '../../includes/sidebar_super_admin.php';
?>

<style>
    .edit-category-container {
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
    
    .warning-info {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
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
    
    .input-group-custom input,
    .input-group-custom select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.3s;
    }
    
    .input-group-custom input:focus,
    .input-group-custom select:focus {
        outline: none;
        border-color: #ffc107;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.1);
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
    
    .preview-category {
        background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
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
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
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
        width: 50px;
        height: 50px;
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
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-approved {
        background: #d4edda;
        color: #155724;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-rejected {
        background: #f8d7da;
        color: #721c24;
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
    
    .status-note {
        background: #e7f3ff;
        border-left: 4px solid #2196f3;
        padding: 12px;
        border-radius: 8px;
        margin: 15px 0;
        font-size: 13px;
    }
</style>

<div class="edit-category-container">
    <div class="form-card">
        <div class="form-header">
            <div class="form-icon">
                <i class="fas fa-edit"></i>
            </div>
            <h2>Modifier la catégorie</h2>
            <p>Modifiez les informations de la catégorie</p>
        </div>
        
        <div class="info-card">
            <span class="info-label"><i class="fas fa-id-badge"></i> ID Catégorie</span>
            <span class="info-value">#<?= $category['id'] ?></span>
        </div>
        
        <?php if($productCount > 0): ?>
        <div class="warning-info">
            <i class="fas fa-info-circle"></i>
            <strong>Information :</strong> Cette catégorie contient <strong><?= $productCount ?> produit(s)</strong>. 
            La modification du nom n'affectera pas les produits associés.
        </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="preview-category">
            <div class="preview-badge">
                <div class="preview-icon">
                    <i class="fas fa-tag"></i>
                </div>
                <span class="preview-name" id="previewName"><?= htmlspecialchars($category['nomcat']) ?></span>
                <?php if($has_status): ?>
                <span class="status-badge status-<?= $category['status'] ?? 'approved' ?>">
                    <?php if(($category['status'] ?? 'approved') == 'approved'): ?>
                        <i class="fas fa-check-circle"></i> Validée
                    <?php elseif(($category['status'] ?? 'approved') == 'pending'): ?>
                        <i class="fas fa-clock"></i> En attente
                    <?php elseif(($category['status'] ?? 'approved') == 'rejected'): ?>
                        <i class="fas fa-times-circle"></i> Rejetée
                    <?php endif; ?>
                </span>
                <?php endif; ?>
                <?php if($has_image && $category['image']): ?>
                    <img src="../../<?= htmlspecialchars($category['image']) ?>" class="preview-image-cat" alt="Image">
                <?php endif; ?>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="input-group-custom">
                <label>
                    <i class="fas fa-font"></i> Nom de la catégorie
                    <span style="color: red;">*</span>
                </label>
                <input type="text" name="nomcat" id="categoryName" 
                       value="<?= htmlspecialchars($category['nomcat']) ?>"
                       required autofocus>
            </div>
            
            <?php if($has_status): ?>
            <div class="input-group-custom">
                <label>
                    <i class="fas fa-tag"></i> Statut de la catégorie
                </label>
                <select name="status" id="statusSelect">
                    <option value="approved" <?= ($category['status'] ?? 'approved') == 'approved' ? 'selected' : '' ?>>
                        ✅ Validée - Visible par tous
                    </option>
                    <option value="pending" <?= ($category['status'] ?? 'approved') == 'pending' ? 'selected' : '' ?>>
                        ⏳ En attente - En attente de validation
                    </option>
                    <option value="rejected" <?= ($category['status'] ?? 'approved') == 'rejected' ? 'selected' : '' ?>>
                        ❌ Rejetée - Non visible
                    </option>
                </select>
                <div class="status-note" id="statusNote">
                    <?php if(($category['status'] ?? 'approved') == 'approved'): ?>
                        <i class="fas fa-check-circle"></i> Cette catégorie est actuellement visible par tous les utilisateurs.
                    <?php elseif(($category['status'] ?? 'approved') == 'pending'): ?>
                        <i class="fas fa-clock"></i> Cette catégorie est en attente de validation. Elle n'est pas encore visible.
                    <?php elseif(($category['status'] ?? 'approved') == 'rejected'): ?>
                        <i class="fas fa-times-circle"></i> Cette catégorie a été rejetée. Vous pouvez la modifier et la soumettre à nouveau.
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($has_image): ?>
            <div class="input-group-custom">
                <label>
                    <i class="fas fa-image"></i> Image de la catégorie
                </label>
                
                <?php if($category['image']): ?>
                <div class="current-image-container" id="currentImageContainer">
                    <div class="current-image-label">Image actuelle</div>
                    <img src="../../<?= htmlspecialchars($category['image']) ?>" alt="Image actuelle">
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
                <input type="file" name="category_image" id="imageInput" style="display: none;" accept="image/*" onchange="previewNewImage(this)">
                <div class="current-image-container" id="newImagePreview" style="display: none;">
                    <div class="current-image-label">Nouvelle image</div>
                    <img id="newImageImg" src="" alt="Aperçu">
                </div>
            </div>
            <?php endif; ?>
            
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
const categoryInput = document.getElementById('categoryName');
const previewName = document.getElementById('previewName');
const statusSelect = document.getElementById('statusSelect');
const statusNote = document.getElementById('statusNote');

// Aperçu du nom en temps réel
categoryInput.addEventListener('input', function() {
    if (this.value.length > 0) {
        previewName.textContent = this.value;
    } else {
        previewName.textContent = 'Aperçu';
    }
});

// Mise à jour du statut en temps réel
if (statusSelect) {
    statusSelect.addEventListener('change', function() {
        const status = this.value;
        let noteText = '';
        let noteIcon = '';
        
        switch(status) {
            case 'approved':
                noteIcon = '<i class="fas fa-check-circle"></i>';
                noteText = ' Cette catégorie sera visible par tous les utilisateurs.';
                break;
            case 'pending':
                noteIcon = '<i class="fas fa-clock"></i>';
                noteText = ' Cette catégorie sera en attente de validation. Elle ne sera pas visible tant qu\'un Super Admin ne l\'aura pas validée.';
                break;
            case 'rejected':
                noteIcon = '<i class="fas fa-times-circle"></i>';
                noteText = ' Cette catégorie sera rejetée. L\'utilisateur pourra la modifier et la soumettre à nouveau.';
                break;
        }
        
        statusNote.innerHTML = noteIcon + noteText;
    });
}

// Gestion de la suppression d'image
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

// Aperçu de la nouvelle image
function previewNewImage(input) {
    const newImagePreview = document.getElementById('newImagePreview');
    const newImageImg = document.getElementById('newImageImg');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            newImageImg.src = e.target.result;
            newImagePreview.style.display = 'block';
            
            // Cacher l'aperçu de l'ancienne image si elle existe
            if (currentImageContainer) {
                currentImageContainer.style.display = 'none';
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../../includes/footer.php'; ?>