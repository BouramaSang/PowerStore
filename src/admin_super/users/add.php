<?php
// admin_super/users/add.php
require_once '../../config/app.php';
requireSuperAdmin();

$page_title = 'Ajouter un utilisateur';
include '../../includes/sidebar_super_admin.php';

$error = '';
$success = '';
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données
    $form_data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'role' => $_POST['role'] ?? 'admin',
        'nom' => trim($_POST['nom'] ?? ''),
        'prenom' => trim($_POST['prenom'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // Validations
    $errors = [];
    
    if (empty($form_data['username'])) {
        $errors[] = "Le nom d'utilisateur est obligatoire";
    } elseif (strlen($form_data['username']) < 3) {
        $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
        $errors[] = "Le nom d'utilisateur ne peut contenir que des lettres, chiffres et underscores";
    }
    
    if (empty($form_data['email'])) {
        $errors[] = "L'email est obligatoire";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }
    
    if (empty($form_data['password'])) {
        $errors[] = "Le mot de passe est obligatoire";
    } elseif (strlen($form_data['password']) < 4) {
        $errors[] = "Le mot de passe doit contenir au moins 4 caractères";
    } elseif ($form_data['password'] !== $form_data['confirm_password']) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    if (empty($errors)) {
        $pdo = getPDO();
        
        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$form_data['username'], $form_data['email']]);
        
        if ($stmt->fetch()) {
            $error = "Ce nom d'utilisateur ou cet email est déjà utilisé";
        } else {
            try {
                $hash = password_hash($form_data['password'], PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, role, nom, prenom, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $form_data['username'],
                    $form_data['email'],
                    $hash,
                    $form_data['role'],
                    $form_data['nom'],
                    $form_data['prenom'],
                    $form_data['is_active']
                ]);
                
                $new_id = $pdo->lastInsertId();
                $success = "Utilisateur créé avec succès !";
                
                // Redirection après 2 secondes
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "index.php?success=Utilisateur ajouté avec succès";
                    }, 2000);
                </script>';
                
                // Réinitialiser le formulaire
                $form_data = [];
            } catch(PDOException $e) {
                $error = "Erreur lors de la création : " . $e->getMessage();
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<style>
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .password-strength {
        margin-top: 8px;
        height: 4px;
        border-radius: 2px;
        background: #e0e0e0;
        overflow: hidden;
    }
    
    .password-strength-bar {
        height: 100%;
        width: 0%;
        transition: all 0.3s;
    }
    
    .strength-weak { background: #dc3545; width: 33%; }
    .strength-medium { background: #ffc107; width: 66%; }
    .strength-strong { background: #28a745; width: 100%; }
    
    .password-requirements {
        background: #f8f9fa;
        padding: 12px;
        border-radius: 8px;
        margin-top: 10px;
        font-size: 12px;
    }
    
    .requirement {
        color: #666;
        margin: 5px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .requirement.valid {
        color: #28a745;
    }
    
    .requirement i {
        width: 16px;
        font-size: 12px;
    }
    
    .info-tooltip {
        cursor: help;
        border-bottom: 1px dashed #999;
        margin-left: 5px;
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
    
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
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
        border-radius: 24px;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
    }
    
    input:checked + .slider {
        background-color: #28a745;
    }
    
    input:checked + .slider:before {
        transform: translateX(26px);
    }
</style>

<div class="preview-card" id="previewCard" style="display: none;">
    <div class="preview-avatar">
        <i class="fas fa-user-plus"></i>
    </div>
    <h3 id="previewName">Nouvel utilisateur</h3>
    <p id="previewRole" style="opacity: 0.9;"></p>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <i class="fas fa-user-plus"></i> Ajouter un utilisateur
        </div>
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
                <a href="add.php" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Ajouter un autre
                </a>
                <a href="index.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-list"></i> Voir la liste
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="userForm">
        <div class="form-row">
            <!-- Colonne gauche -->
            <div>
                <div class="form-group">
                    <label>
                        Nom d'utilisateur <span style="color: red;">*</span>
                        <span class="info-tooltip" title="3 caractères minimum, lettres, chiffres et underscores uniquement">
                            <i class="fas fa-question-circle"></i>
                        </span>
                    </label>
                    <input type="text" name="username" class="form-control" 
                           value="<?= htmlspecialchars($form_data['username'] ?? '') ?>"
                           pattern="[a-zA-Z0-9_]{3,}"
                           title="3 caractères minimum, lettres, chiffres et underscores"
                           required>
                    <small style="color: #666; font-size: 11px;">
                        <span id="usernameCheck"></span>
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Email <span style="color: red;">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($form_data['email'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label>Rôle <span style="color: red;">*</span></label>
                    <select name="role" class="form-control" id="roleSelect" required>
                        <option value="admin" <?= (isset($form_data['role']) && $form_data['role'] == 'admin') ? 'selected' : '' ?>>
                            👤 Admin - Accès limité
                        </option>
                        <option value="super_admin" <?= (isset($form_data['role']) && $form_data['role'] == 'super_admin') ? 'selected' : '' ?>>
                            👑 Super Admin - Accès total
                        </option>
                    </select>
                    <small style="color: #666; font-size: 11px;">
                        <i class="fas fa-info-circle"></i> 
                        <span id="roleDescription">L'Admin a un accès limité à la gestion</span>
                    </small>
                </div>
            </div>
            
            <!-- Colonne droite -->
            <div>
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" class="form-control" 
                           value="<?= htmlspecialchars($form_data['nom'] ?? '') ?>"
                           placeholder="Nom de famille">
                </div>
                
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" class="form-control" 
                           value="<?= htmlspecialchars($form_data['prenom'] ?? '') ?>"
                           placeholder="Prénom">
                </div>
                
                <div class="form-group">
                    <label>
                        Statut du compte
                        <span class="info-tooltip" title="Si inactif, l'utilisateur ne pourra pas se connecter">
                            <i class="fas fa-question-circle"></i>
                        </span>
                    </label>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <label class="switch">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?= (!isset($form_data['is_active']) || $form_data['is_active']) ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                        <span id="statusLabel" style="color: #28a745;">
                            <i class="fas fa-check-circle"></i> Actif
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section mot de passe -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h4 style="margin-bottom: 15px;">
                <i class="fas fa-lock"></i> Sécurité du mot de passe
            </h4>
            
            <div class="form-group">
                <label>Mot de passe <span style="color: red;">*</span></label>
                <input type="password" name="password" id="password" class="form-control" required>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <div class="password-requirements">
                    <div class="requirement" id="reqLength">
                        <i class="fas fa-circle"></i> Au moins 4 caractères
                    </div>
                    <div class="requirement" id="reqLetter">
                        <i class="fas fa-circle"></i> Au moins une lettre
                    </div>
                    <div class="requirement" id="reqNumber">
                        <i class="fas fa-circle"></i> Au moins un chiffre
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Confirmer le mot de passe <span style="color: red;">*</span></label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                <small id="matchMessage" style="color: #666;"></small>
            </div>
        </div>
        
        <div class="form-group" style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" class="btn btn-success" id="submitBtn">
                <i class="fas fa-save"></i> Créer l'utilisateur
            </button>
            <button type="reset" class="btn btn-danger" onclick="resetForm()">
                <i class="fas fa-undo"></i> Réinitialiser
            </button>
        </div>
    </form>
</div>

<script>
// Rôle descriptions
const roleDescriptions = {
    'admin': 'L\'Admin peut gérer les clients, produits et factures mais n\'a pas accès à la gestion des utilisateurs.',
    'super_admin': 'Le Super Admin a un accès complet à toutes les fonctionnalités, y compris la gestion des utilisateurs.'
};

// Mise à jour de la description du rôle
document.getElementById('roleSelect').addEventListener('change', function() {
    const role = this.value;
    document.getElementById('roleDescription').innerHTML = roleDescriptions[role];
    updatePreview();
});

// Mise à jour du statut
document.querySelector('input[name="is_active"]').addEventListener('change', function() {
    const statusLabel = document.getElementById('statusLabel');
    if (this.checked) {
        statusLabel.innerHTML = '<i class="fas fa-check-circle"></i> Actif';
        statusLabel.style.color = '#28a745';
    } else {
        statusLabel.innerHTML = '<i class="fas fa-ban"></i> Inactif';
        statusLabel.style.color = '#dc3545';
    }
    updatePreview();
});

// Vérification de la force du mot de passe
function checkPasswordStrength(password) {
    let strength = 0;
    let requirements = {
        length: password.length >= 4,
        letter: /[a-zA-Z]/.test(password),
        number: /[0-9]/.test(password)
    };
    
    // Mise à jour des icônes
    document.getElementById('reqLength').innerHTML = `<i class="fas ${requirements.length ? 'fa-check-circle' : 'fa-circle'}"></i> Au moins 4 caractères`;
    document.getElementById('reqLetter').innerHTML = `<i class="fas ${requirements.letter ? 'fa-check-circle' : 'fa-circle'}"></i> Au moins une lettre`;
    document.getElementById('reqNumber').innerHTML = `<i class="fas ${requirements.number ? 'fa-check-circle' : 'fa-circle'}"></i> Au moins un chiffre`;
    
    document.getElementById('reqLength').classList.toggle('valid', requirements.length);
    document.getElementById('reqLetter').classList.toggle('valid', requirements.letter);
    document.getElementById('reqNumber').classList.toggle('valid', requirements.number);
    
    // Calcul de la force
    if (requirements.length) strength++;
    if (requirements.letter) strength++;
    if (requirements.number) strength++;
    
    // Barre de force
    const bar = document.getElementById('strengthBar');
    bar.className = 'password-strength-bar';
    
    if (strength === 0) {
        bar.style.width = '0%';
    } else if (strength === 1) {
        bar.classList.add('strength-weak');
        bar.style.width = '33%';
    } else if (strength === 2) {
        bar.classList.add('strength-medium');
        bar.style.width = '66%';
    } else {
        bar.classList.add('strength-strong');
        bar.style.width = '100%';
    }
    
    return strength === 3;
}

// Vérification de la confirmation du mot de passe
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    const matchMessage = document.getElementById('matchMessage');
    
    if (confirm.length > 0) {
        if (password === confirm) {
            matchMessage.innerHTML = '<i class="fas fa-check-circle" style="color:#28a745;"></i> Les mots de passe correspondent';
            matchMessage.style.color = '#28a745';
            return true;
        } else {
            matchMessage.innerHTML = '<i class="fas fa-times-circle" style="color:#dc3545;"></i> Les mots de passe ne correspondent pas';
            matchMessage.style.color = '#dc3545';
            return false;
        }
    }
    return false;
}

// Vérification du nom d'utilisateur en temps réel
document.querySelector('input[name="username"]').addEventListener('input', function() {
    const username = this.value;
    const checkSpan = document.getElementById('usernameCheck');
    
    if (username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username)) {
        checkSpan.innerHTML = '<i class="fas fa-check-circle" style="color:#28a745;"></i> Disponible';
        checkSpan.style.color = '#28a745';
    } else if (username.length > 0) {
        checkSpan.innerHTML = '<i class="fas fa-times-circle" style="color:#dc3545;"></i> 3 caractères minimum, lettres, chiffres et _';
        checkSpan.style.color = '#dc3545';
    } else {
        checkSpan.innerHTML = '';
    }
    updatePreview();
});

// Mise à jour des champs de mot de passe
document.getElementById('password').addEventListener('input', function() {
    checkPasswordStrength(this.value);
    checkPasswordMatch();
});

document.getElementById('confirm_password').addEventListener('input', function() {
    checkPasswordMatch();
});

// Mise à jour de l'aperçu en direct
function updatePreview() {
    const username = document.querySelector('input[name="username"]').value;
    const nom = document.querySelector('input[name="nom"]').value;
    const prenom = document.querySelector('input[name="prenom"]').value;
    const role = document.getElementById('roleSelect').value;
    const isActive = document.querySelector('input[name="is_active"]').checked;
    const previewCard = document.getElementById('previewCard');
    
    if (username || nom || prenom) {
        previewCard.style.display = 'block';
        const fullName = [prenom, nom].filter(n => n).join(' ') || username || 'Nouvel utilisateur';
        document.getElementById('previewName').innerHTML = fullName;
        document.getElementById('previewRole').innerHTML = role === 'super_admin' ? '👑 Super Admin' : '👤 Admin';
        if (!isActive) {
            document.getElementById('previewRole').innerHTML += ' (Compte inactif)';
        }
    } else {
        previewCard.style.display = 'none';
    }
}

// Validation du formulaire avant soumission
document.getElementById('userForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Les mots de passe ne correspondent pas !');
        return false;
    }
    
    if (password.length < 4) {
        e.preventDefault();
        alert('Le mot de passe doit contenir au moins 4 caractères !');
        return false;
    }
    
    if (!confirm('Confirmez-vous la création de cet utilisateur ?')) {
        e.preventDefault();
        return false;
    }
});

// Réinitialisation du formulaire
function resetForm() {
    document.getElementById('userForm').reset();
    document.getElementById('password').value = '';
    document.getElementById('confirm_password').value = '';
    document.getElementById('strengthBar').style.width = '0%';
    document.getElementById('matchMessage').innerHTML = '';
    document.getElementById('previewCard').style.display = 'none';
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
    document.getElementById('roleSelect').dispatchEvent(new Event('change'));
});
</script>

<?php include '../../includes/footer.php'; ?>