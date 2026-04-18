<?php
// admin_super/users/edit.php - Version complète avec toggle statut
require_once '../../config/app.php';
requireSuperAdmin();

$page_title = 'Modifier un utilisateur';
include '../../includes/sidebar_super_admin.php';

$pdo = getPDO();
$error = '';
$success = '';

// Récupérer l'ID de l'utilisateur
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header('Location: index.php?error=ID utilisateur invalide');
    exit();
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php?error=Utilisateur non trouvé');
    exit();
}

$is_self = ($user_id == $_SESSION['user_id']);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email)) {
        $error = "Le nom d'utilisateur et l'email sont obligatoires";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } elseif (!empty($new_password) && strlen($new_password) < 4) {
        $error = "Le mot de passe doit contenir au moins 4 caractères";
    } else {
        try {
            // Vérifier si le username existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                $error = "Ce nom d'utilisateur est déjà utilisé";
            }
            
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = "Cet email est déjà utilisé";
            }
            
            if (empty($error)) {
                if (!empty($new_password)) {
                    // Avec nouveau mot de passe
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, password = ?, role = ?, nom = ?, prenom = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $password_hash, $role, $nom, $prenom, $is_active, $user_id]);
                } else {
                    // Sans modifier le mot de passe
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, role = ?, nom = ?, prenom = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $role, $nom, $prenom, $is_active, $user_id]);
                }
                
                $success = "Utilisateur modifié avec succès !";
                
                // Mettre à jour la session si c'est l'utilisateur courant
                if ($is_self) {
                    $_SESSION['username'] = $username;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = trim($prenom . ' ' . $nom);
                }
                
                // Recharger les données
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            }
        } catch(PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<style>
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-top: 10px;
    }
    
    .info-item {
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
    }
    
    .info-item strong {
        display: block;
        color: #555;
        font-size: 11px;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    
    .info-item span {
        font-size: 14px;
        color: #333;
    }
    
    .password-requirements {
        background: #f0f2f5;
        padding: 10px;
        border-radius: 5px;
        margin-top: 5px;
        font-size: 12px;
        color: #666;
    }
    
    /* Toggle Switch Styles */
    .status-toggle {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 10px;
        margin-top: 5px;
    }
    
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 30px;
    }
    
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.4s;
        border-radius: 30px;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 22px;
        width: 22px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
    }
    
    input:checked + .slider {
        background-color: #28a745;
    }
    
    input:checked + .slider:before {
        transform: translateX(30px);
    }
    
    .status-label {
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .status-active {
        color: #28a745;
    }
    
    .status-inactive {
        color: #dc3545;
    }
    
    .disabled-overlay {
        opacity: 0.6;
        pointer-events: none;
    }
    
    .warning-badge {
        background: #ff4757;
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        display: inline-block;
        margin-left: 10px;
    }
    
    .preview-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
    }
    
    .preview-avatar {
        width: 60px;
        height: 60px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 30px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }
</style>

<div class="preview-card" id="previewCard">
    <div class="preview-avatar">
        <i class="fas fa-user"></i>
    </div>
    <h3 id="previewName"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h3>
    <p id="previewRole" style="opacity: 0.9;">
        <?= $user['role'] == 'super_admin' ? '👑 Super Admin' : '👤 Admin' ?>
        <?= !$user['is_active'] ? ' (Compte inactif)' : '' ?>
    </p>
</div>

<div class="card">
    <div class="card-header">
        <span><i class="fas fa-user-edit"></i> Modifier l'utilisateur</span>
        <div>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
    
    <?php if($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $success ?>
            <div style="margin-top: 10px;">
                <a href="index.php" class="btn btn-success btn-sm">Aller à la liste</a>
                <a href="edit.php?id=<?= $user_id ?>" class="btn btn-primary btn-sm">Continuer l'édition</a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" onsubmit="return validateForm()">
        <div class="form-row">
            <!-- Colonne gauche -->
            <div>
                <div class="form-group">
                    <label>Nom d'utilisateur <span style="color: red;">*</span></label>
                    <input type="text" name="username" class="form-control" 
                           value="<?= htmlspecialchars($user['username']) ?>" 
                           pattern="[a-zA-Z0-9_]{3,}"
                           title="3 caractères minimum, lettres, chiffres et underscores"
                           required>
                </div>
                
                <div class="form-group">
                    <label>Email <span style="color: red;">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Rôle <span style="color: red;">*</span></label>
                    <select name="role" class="form-control" <?= $is_self ? 'disabled' : '' ?>>
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>
                            👤 Admin - Accès limité
                        </option>
                        <option value="super_admin" <?= $user['role'] == 'super_admin' ? 'selected' : '' ?>>
                            👑 Super Admin - Accès total
                        </option>
                    </select>
                    <?php if($is_self): ?>
                        <small style="color: #ff4757; font-size: 12px; display: block; margin-top: 5px;">
                            <i class="fas fa-exclamation-triangle"></i> Vous ne pouvez pas modifier votre propre rôle
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Colonne droite -->
            <div>
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" class="form-control" 
                           value="<?= htmlspecialchars($user['nom'] ?? '') ?>"
                           id="nom" oninput="updatePreview()">
                </div>
                
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" class="form-control" 
                           value="<?= htmlspecialchars($user['prenom'] ?? '') ?>"
                           id="prenom" oninput="updatePreview()">
                </div>
                
                <div class="form-group">
                    <label>Statut du compte</label>
                    <?php if($is_self): ?>
                        <div class="status-toggle disabled-overlay">
                            <label class="switch">
                                <input type="checkbox" name="is_active" value="1" 
                                       <?= $user['is_active'] ? 'checked' : '' ?> disabled>
                                <span class="slider"></span>
                            </label>
                            <div class="status-label <?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                <i class="fas <?= $user['is_active'] ? 'fa-check-circle' : 'fa-ban' ?>"></i>
                                <span id="statusText"><?= $user['is_active'] ? 'Actif' : 'Inactif' ?></span>
                            </div>
                            <span class="warning-badge">
                                <i class="fas fa-lock"></i> Non modifiable
                            </span>
                        </div>
                        <small style="color: #ff4757; font-size: 12px; display: block; margin-top: 8px;">
                            ⚠️ Vous ne pouvez pas modifier votre propre statut
                        </small>
                    <?php else: ?>
                        <div class="status-toggle">
                            <label class="switch">
                                <input type="checkbox" name="is_active" value="1" 
                                       id="statusSwitch" <?= $user['is_active'] ? 'checked' : '' ?>
                                       onchange="updateStatusLabel()">
                                <span class="slider"></span>
                            </label>
                            <div class="status-label" id="statusLabelDiv">
                                <i class="fas <?= $user['is_active'] ? 'fa-check-circle' : 'fa-ban' ?>"></i>
                                <span id="statusText"><?= $user['is_active'] ? 'Actif' : 'Inactif' ?></span>
                            </div>
                        </div>
                        <small style="color: #666; font-size: 12px; display: block; margin-top: 8px;">
                            <i class="fas fa-info-circle"></i> 
                            Si inactif, l'utilisateur ne pourra pas se connecter
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Section mot de passe -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h4 style="margin-bottom: 15px;">
                <i class="fas fa-lock"></i> Changer le mot de passe
            </h4>
            
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" id="new_password" class="form-control" 
                       placeholder="Laisser vide pour ne pas modifier">
                <div class="password-requirements">
                    <small><i class="fas fa-info-circle"></i> Le mot de passe doit contenir au moins 4 caractères</small>
                </div>
            </div>
            
            <div class="form-group" id="confirm_group" style="display: none;">
                <label>Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                       placeholder="Retapez le nouveau mot de passe">
                <small id="matchMessage" style="color: #666;"></small>
            </div>
        </div>
        
        <div class="form-group" style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" class="btn btn-success" id="submitBtn">
                <i class="fas fa-save"></i> Enregistrer les modifications
            </button>
            <a href="index.php" class="btn btn-danger">
                <i class="fas fa-times"></i> Annuler
            </a>
        </div>
    </form>
</div>

<!-- Informations système -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-info-circle"></i> Informations système</span>
    </div>
    <div class="info-grid">
        <div class="info-item">
            <strong>ID utilisateur</strong>
            <span>#<?= $user['id'] ?></span>
        </div>
        <div class="info-item">
            <strong>Date de création</strong>
            <span><?= date('d/m/Y à H:i:s', strtotime($user['created_at'])) ?></span>
        </div>
        <div class="info-item">
            <strong>Dernière connexion</strong>
            <span><?= $user['last_login'] ? date('d/m/Y à H:i:s', strtotime($user['last_login'])) : 'Jamais connecté' ?></span>
        </div>
        <div class="info-item">
            <strong>Statut actuel</strong>
            <span style="color: <?= $user['is_active'] ? '#28a745' : '#dc3545' ?>; font-weight: bold;">
                <?= $user['is_active'] ? '✅ Actif' : '❌ Inactif' ?>
            </span>
        </div>
    </div>
</div>

<script>
// Mise à jour du statut en temps réel
function updateStatusLabel() {
    const switchCheckbox = document.getElementById('statusSwitch');
    const statusLabelDiv = document.getElementById('statusLabelDiv');
    const statusText = document.getElementById('statusText');
    
    if (switchCheckbox && statusLabelDiv) {
        if (switchCheckbox.checked) {
            statusLabelDiv.innerHTML = '<i class="fas fa-check-circle"></i> <span id="statusText">Actif</span>';
            statusLabelDiv.className = 'status-label status-active';
            statusText.innerHTML = 'Actif';
        } else {
            statusLabelDiv.innerHTML = '<i class="fas fa-ban"></i> <span id="statusText">Inactif</span>';
            statusLabelDiv.className = 'status-label status-inactive';
            statusText.innerHTML = 'Inactif';
        }
    }
    updatePreview();
}

// Mise à jour de l'aperçu
function updatePreview() {
    const nom = document.getElementById('nom')?.value || '';
    const prenom = document.getElementById('prenom')?.value || '';
    const roleSelect = document.querySelector('select[name="role"]');
    const statusSwitch = document.getElementById('statusSwitch');
    
    let fullName = '';
    if (prenom || nom) {
        fullName = [prenom, nom].filter(n => n).join(' ');
    } else {
        fullName = document.querySelector('input[name="username"]')?.value || 'Utilisateur';
    }
    
    document.getElementById('previewName').innerHTML = fullName;
    
    if (roleSelect) {
        const role = roleSelect.value;
        let roleText = role === 'super_admin' ? '👑 Super Admin' : '👤 Admin';
        if (statusSwitch && !statusSwitch.checked) {
            roleText += ' (Compte inactif)';
        }
        document.getElementById('previewRole').innerHTML = roleText;
    }
}

// Afficher/cacher la confirmation du mot de passe
document.getElementById('new_password').addEventListener('input', function() {
    const confirmGroup = document.getElementById('confirm_group');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (this.value.length > 0) {
        confirmGroup.style.display = 'block';
    } else {
        confirmGroup.style.display = 'none';
        if (confirmPassword) confirmPassword.value = '';
        const matchMessage = document.getElementById('matchMessage');
        if (matchMessage) matchMessage.innerHTML = '';
    }
    checkPasswordMatch();
});

// Vérification de la correspondance des mots de passe
function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchMessage = document.getElementById('matchMessage');
    
    if (confirmPassword.length > 0) {
        if (newPassword === confirmPassword) {
            matchMessage.innerHTML = '<i class="fas fa-check-circle" style="color:#28a745;"></i> Les mots de passe correspondent';
            matchMessage.style.color = '#28a745';
            return true;
        } else {
            matchMessage.innerHTML = '<i class="fas fa-times-circle" style="color:#dc3545;"></i> Les mots de passe ne correspondent pas';
            matchMessage.style.color = '#dc3545';
            return false;
        }
    }
    return true;
}

document.getElementById('confirm_password')?.addEventListener('input', function() {
    checkPasswordMatch();
});

// Validation du formulaire
function validateForm() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword.length > 0 && newPassword !== confirmPassword) {
        alert('Les mots de passe ne correspondent pas !');
        return false;
    }
    
    if (newPassword.length > 0 && newPassword.length < 4) {
        alert('Le mot de passe doit contenir au moins 4 caractères !');
        return false;
    }
    
    // Vérifier si on essaie de se désactiver soi-même
    <?php if($is_self): ?>
    // Désactiver la vérification pour soi-même
    <?php else: ?>
    // Pas de vérification supplémentaire
    <?php endif; ?>
    
    return confirm('Confirmez-vous les modifications ?');
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
    
    // Ajouter des écouteurs sur les champs pour l'aperçu
    document.querySelector('input[name="username"]')?.addEventListener('input', updatePreview);
    document.querySelector('select[name="role"]')?.addEventListener('change', updatePreview);
    document.getElementById('nom')?.addEventListener('input', updatePreview);
    document.getElementById('prenom')?.addEventListener('input', updatePreview);
});
</script>

<?php include '../../includes/footer.php'; ?>