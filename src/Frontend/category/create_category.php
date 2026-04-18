<?php
// src/Frontend/category/create_category.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$error = '';
$success = '';

// Vérifier si le formulaire a été soumis
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
    } elseif (empty($error)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO categories (nomcat, image, status, created_by) 
                VALUES (?, ?, 'pending', ?)
            ");
            $stmt->execute([$nomcat, $image_path, $_SESSION['user_id']]);
            
            $_SESSION['success'] = "Catégorie créée avec succès ! En attente de validation par un Super Admin.";
            header('Location: index_category.php');
            exit();
        } catch(PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

$page_title = 'Créer une catégorie';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer une catégorie - InApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .create-category-container {
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
            border: 2px dashed #ddd;
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
        
        .pending-badge {
            background: #ffc107;
            color: #333;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        <div class="create-category-container">
            <div class="form-card">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="ti ti-category" style="font-size: 48px; color: #FF8C00;"></i>
                    </div>
                    <h2>Créer une nouvelle catégorie</h2>
                    <p class="text-secondary">
                        <span class="pending-badge">
                            <i class="ti ti-clock"></i> En attente de validation
                        </span>
                    </p>
                    <p class="text-secondary small">
                        Votre catégorie sera soumise à validation par un Super Admin avant d'être visible.
                    </p>
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
                               placeholder="Ex: Smartphones, Ordinateurs, Accessoires..." 
                               required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="ti ti-photo"></i> Image de la catégorie
                        </label>
                        <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
                            <i class="ti ti-cloud-upload" style="font-size: 32px; color: #FF8C00;"></i>
                            <p class="mb-0 small text-secondary">Cliquez pour télécharger une image</p>
                            <p class="small text-secondary">Formats acceptés: JPG, PNG, GIF, WEBP</p>
                        </div>
                        <input type="file" name="category_image" id="imageInput" style="display: none;" accept="image/*" onchange="previewImage(this)">
                        <div class="text-center mt-3">
                            <img id="imagePreview" class="preview-image" style="display: none;">
                        </div>
                    </div>
                    
                    <div class="alert alert-info small">
                        <i class="ti ti-info-circle"></i> 
                        Note: Après création, un Super Admin doit valider votre catégorie. Vous serez notifié une fois validée.
                    </div>
                    
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn" style="background: #FF8C00; color: white; padding: 10px 25px;">
                            <i class="ti ti-device-floppy"></i> Soumettre pour validation
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
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>