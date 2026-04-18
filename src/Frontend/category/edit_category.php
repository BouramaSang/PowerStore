<?php
// src/Frontend/category/edit_category.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$error = '';
$success = '';

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
    $_SESSION['error'] = "Vous n'avez pas le droit de modifier cette catégorie";
    header('Location: index_category.php');
    exit();
}

// Vérifier que la catégorie n'est pas déjà validée (sauf pour Super Admin)
if ($category['status'] == 'approved' && !isSuperAdmin()) {
    $_SESSION['error'] = "Cette catégorie a déjà été validée et ne peut plus être modifiée";
    header('Location: index_category.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomcat = trim($_POST['nomcat']);
    $current_image = $category['image'];
    
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
    } elseif (empty($error)) {
        try {
            // Si c'est une modification après rejet, remettre en attente
            $new_status = $category['status'];
            if ($category['status'] == 'rejected') {
                $new_status = 'pending';
            }
            
            $stmt = $pdo->prepare("
                UPDATE categories 
                SET nomcat = ?, image = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nomcat, $image_path, $new_status, $category_id]);
            
            $_SESSION['success'] = "Catégorie modifiée avec succès !" . 
                ($new_status == 'pending' ? " Elle est à nouveau en attente de validation." : "");
            header('Location: index_category.php');
            exit();
        } catch(PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

$page_title = 'Modifier une catégorie';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier une catégorie - InApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
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
        
        .preview-image {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            object-fit: cover;
            margin-bottom: 15px;
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
            border-color: #FF8C00;
            background: rgba(255, 140, 0, 0.05);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
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
        
        .info-card {
            background: #e7f3ff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .current-image {
            margin-top: 10px;
            text-align: center;
        }
        
        .delete-image-checkbox {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        <div class="edit-category-container">
            <div class="form-card">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="ti ti-edit" style="font-size: 48px; color: #FF8C00;"></i>
                    </div>
                    <h2>Modifier la catégorie</h2>
                    <p class="text-secondary">
                        <?php if($category['status'] == 'pending'): ?>
                            <span class="status-badge status-pending">
                                <i class="ti ti-clock"></i> En attente de validation
                            </span>
                        <?php elseif($category['status'] == 'approved'): ?>
                            <span class="status-badge status-approved">
                                <i class="ti ti-check"></i> Déjà validée
                            </span>
                        <?php elseif($category['status'] == 'rejected'): ?>
                            <span class="status-badge status-rejected">
                                <i class="ti ti-x"></i> Rejetée
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="info-card">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-secondary">ID Catégorie</small>
                            <div class="fw-semibold">#<?= $category['id'] ?></div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-secondary">Créée par</small>
                            <div class="fw-semibold"><?= htmlspecialchars($category['created_by_name'] ?? 'Inconnu') ?></div>
                        </div>
                        <?php if($category['status'] == 'rejected' && $category['rejection_reason']): ?>
                        <div class="col-12 mt-2">
                            <small class="text-danger">Raison du rejet</small>
                            <div class="text-danger"><?= htmlspecialchars($category['rejection_reason']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="ti ti-tag"></i> Nom de la catégorie *
                        </label>
                        <input type="text" name="nomcat" class="form-control" 
                               value="<?= htmlspecialchars($category['nomcat']) ?>"
                               required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="ti ti-photo"></i> Image de la catégorie
                        </label>
                        
                        <?php if($category['image']): ?>
                            <div class="current-image">
                                <img src="../../<?= htmlspecialchars($category['image']) ?>" class="preview-image" alt="Image actuelle">
                                <div class="delete-image-checkbox">
                                    <input type="checkbox" name="delete_image" value="1" id="delete_image">
                                    <label for="delete_image" class="text-danger">Supprimer l'image actuelle</label>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="image-upload-area mt-2" onclick="document.getElementById('imageInput').click()">
                            <i class="ti ti-cloud-upload" style="font-size: 32px; color: #FF8C00;"></i>
                            <p class="mb-0 small text-secondary">Cliquez pour changer l'image</p>
                            <p class="small text-secondary">Formats acceptés: JPG, PNG, GIF, WEBP</p>
                        </div>
                        <input type="file" name="category_image" id="imageInput" style="display: none;" accept="image/*" onchange="previewImage(this)">
                        <div class="text-center mt-3">
                            <img id="imagePreview" class="preview-image" style="display: none;">
                        </div>
                    </div>
                    
                    <?php if($category['status'] == 'rejected'): ?>
                    <div class="alert alert-warning small">
                        <i class="ti ti-info-circle"></i> 
                        Après modification, votre catégorie sera à nouveau soumise à validation par un Super Admin.
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn" style="background: #FF8C00; color: white; padding: 10px 25px;">
                            <i class="ti ti-device-floppy"></i> Enregistrer les modifications
                        </button>
                        <a href="index_category.php" class="btn btn-secondary">
                            <i class="ti ti-arrow-left"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            
            // Cacher l'image actuelle si elle existe
            const currentImage = document.querySelector('.current-image');
            if (currentImage) {
                currentImage.style.display = 'none';
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Gérer l'affichage de l'aperçu quand on coche "supprimer l'image"
document.getElementById('delete_image')?.addEventListener('change', function() {
    const currentImage = document.querySelector('.current-image');
    if (currentImage) {
        if (this.checked) {
            currentImage.style.opacity = '0.5';
        } else {
            currentImage.style.opacity = '1';
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>