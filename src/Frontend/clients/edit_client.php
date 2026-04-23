 <?php
// src/Frontend/clients/edit_client.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = false;

if ($id <= 0) {
    header('Location: index_client.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: index_client.php');
    exit();
}

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
            $stmt = $pdo->prepare("UPDATE clients SET nomc=?, prenom=?, tel=?, email=?, adresse=? WHERE id=?");
            $stmt->execute([$nomc, $prenom, $tel, $email, $adresse, $id]);
            $success = true;
            echo "<script>setTimeout(function() { window.location.href = 'view_client.php?id=$id'; }, 1500);</script>";
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$page_title = 'Modifier client';
include '../../sidebar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier client | PowerStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .form-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 30px; max-width: 700px; margin: 0 auto; }
        .form-label { font-weight: 600; margin-bottom: 8px; }
        .form-control { border-radius: 10px; padding: 10px 14px; border: 1px solid #e2e8f0; }
        .form-control:focus { border-color: #E66239; box-shadow: 0 0 0 3px rgba(230,98,57,0.1); }
        .btn-primary-custom { background: #E66239; border: none; padding: 10px 24px; border-radius: 12px; color: white; font-weight: 500; transition: all 0.2s; }
        .btn-primary-custom:hover { background: #d5542e; transform: translateY(-2px); }
        .btn-outline-custom { background: white; border: 1.5px solid #e2e8f0; padding: 10px 24px; border-radius: 12px; color: #64748b; text-decoration: none; transition: all 0.2s; }
        .btn-outline-custom:hover { border-color: #E66239; color: #E66239; }
        @media (max-width: 768px) {
            .form-card { padding: 20px; }
            .btn-primary-custom, .btn-outline-custom { padding: 8px 16px; font-size: 13px; }
        }
    </style>
</head>
<body>

<!-- TO PBAR -->
<nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
    <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm ">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>
    <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>
    <div>
        <ul class="list-unstyled d-flex align-items-center mb-0 gap-1">
            <li>
                <a class="position-relative btn-icon btn-sm btn-light btn rounded-circle" data-bs-toggle="dropdown" href="#" role="button">
                    <i class="ti ti-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger mt-2 ms-n2">3</span>
                </a>
            </li>
            <li class="ms-3 dropdown">
                <a href="#" data-bs-toggle="dropdown">
                    <img src="../../assets/images/avatar/avatar-1.jpg" alt="" class="avatar avatar-sm rounded-circle" />
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0">
                    <div class="p-3"><a href="../../logout.php" class="text-decoration-none">Déconnexion</a></div>
                </div>
            </li>
        </ul>
    </div>
</nav>

<main id="content" class="content py-10">
    <div class="container-fluid px-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="fs-3 fw-bold mb-1"><i class="fa-solid fa-pen me-2" style="color: #E66239;"></i>Modifier client</h1>
                <p class="text-secondary mb-0 small">Modifiez les informations du client</p>
            </div>
            <a href="view_client.php?id=<?= $id ?>" class="btn-outline-custom"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Client modifié ! Redirection...</div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($client['prenom']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom</label>
                        <input type="text" name="nomc" class="form-control" value="<?= htmlspecialchars($client['nomc']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="tel" class="form-control" value="<?= htmlspecialchars($client['tel']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($client['email']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <textarea name="adresse" class="form-control" rows="2"><?= htmlspecialchars($client['adresse']) ?></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-3">
                        <a href="view_client.php?id=<?= $id ?>" class="btn-outline-custom">Annuler</a>
                        <button type="submit" class="btn-primary-custom"><i class="fa-solid fa-save"></i> Enregistrer</button>
                    </div>
                </div>
            </form>
        </div>
        
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById("toggleBtn")?.addEventListener("click", function() {
        document.querySelector(".sidebar")?.classList.toggle("active");
    });
    document.getElementById("mobileBtn")?.addEventListener("click", function() {
        document.querySelector(".sidebar")?.classList.toggle("active");
        document.getElementById("overlay")?.classList.add("show");
    });
</script>
</body>
</html>