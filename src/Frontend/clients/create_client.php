<?php
// src/Frontend/clients/create_client.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomc = trim($_POST['nomc']);
    $prenom = trim($_POST['prenom']);
    $tel = trim($_POST['tel']);
    $email = trim($_POST['email']);
    $adresse = trim($_POST['adresse']);
    
    $errors = [];
    if (empty($prenom)) $errors[] = "Le prénom est obligatoire";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO clients (nomc, prenom, tel, email, adresse) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nomc, $prenom, $tel, $email, $adresse]);
            $success = "Client ajouté avec succès !";
            
            // Redirection après 1.5 secondes
            echo "<script>setTimeout(function() { window.location.href = 'index_client.php'; }, 1500);</script>";
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$page_title = 'Nouveau client';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau client | PowerStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root { --primary: #E66239; --border: #e2e8f0; }
        .form-card { background: white; border-radius: 20px; border: 1px solid var(--border); padding: 30px; max-width: 700px; margin: 0 auto; }
        .form-label { font-weight: 600; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 10px; padding: 10px 14px; border: 1px solid var(--border); }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(230,98,57,0.1); }
        .btn-primary-custom { background: var(--primary); border: none; padding: 10px 24px; border-radius: 12px; color: white; font-weight: 500; }
        .btn-outline-custom { background: white; border: 1.5px solid var(--border); padding: 10px 24px; border-radius: 12px; color: #64748b; text-decoration: none; }
        .btn-outline-custom:hover { border-color: var(--primary); color: var(--primary); }
    </style>
</head>
<body>

<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fs-3 fw-bold mb-1"><i class="fa-solid fa-user-plus me-2" style="color: var(--primary);"></i>Nouveau client</h1>
                <p class="text-secondary mb-0 small">Ajoutez un nouveau client à votre portefeuille</p>
            </div>
            <a href="index_client.php" class="btn-outline-custom"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="prenom" class="form-control" placeholder="Jean" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom</label>
                        <input type="text" name="nomc" class="form-control" placeholder="Dupont">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="tel" class="form-control" placeholder="77-90-34-44">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="client@exemple.com">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <textarea name="adresse" class="form-control" rows="2" placeholder="Adresse complète..."></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-3">
                        <a href="index_client.php" class="btn-outline-custom">Annuler</a>
                        <button type="submit" class="btn-primary-custom"><i class="fa-solid fa-save"></i> Enregistrer</button>
                    </div>
                </div>
            </form>
        </div>
        
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>