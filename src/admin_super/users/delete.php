<?php
// admin_super/users/delete.php
require_once '../../config/app.php';
requireSuperAdmin();

$pdo = getPDO();
$error = '';
$success = '';

// Récupérer l'ID de l'utilisateur
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============================================
// TOUTES LES REDIRECTIONS SONT ICI (AVANT TOUT AFFICHAGE)
// ============================================

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

// Vérifier qu'on ne supprime pas son propre compte
$is_self = ($user_id == $_SESSION['user_id']);

if ($is_self) {
    header('Location: index.php?error=Vous ne pouvez pas supprimer votre propre compte');
    exit();
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = isset($_POST['confirm']) ? trim($_POST['confirm']) : '';
    
    if ($confirm === 'SUPPRIMER') {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            header('Location: index.php?success=Utilisateur supprimé avec succès');
            exit();
        } catch(PDOException $e) {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez saisir 'SUPPRIMER' pour confirmer la suppression";
    }
}

// ============================================
// AFFICHAGE DE LA PAGE (APRÈS TOUTES LES REDIRECTIONS)
// ============================================

$page_title = 'Supprimer un utilisateur';
include '../../includes/sidebar_super_admin.php';
?>

<style>
    .delete-container {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .warning-card {
        background: linear-gradient(135deg, #fff5f5 0%, #ffe0e0 100%);
        border: 2px solid #dc3545;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .warning-icon {
        width: 80px;
        height: 80px;
        background: #dc3545;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 40px;
        color: white;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }
        70% {
            transform: scale(1.05);
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }
    
    .user-info-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .user-detail {
        display: flex;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .user-detail:last-child {
        border-bottom: none;
    }
    
    .user-detail-label {
        width: 120px;
        font-weight: 600;
        color: #555;
    }
    
    .user-detail-value {
        flex: 1;
        color: #333;
    }
    
    .user-avatar-large {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 50px;
        color: white;
    }
    
    .role-badge-large {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
        margin-top: 10px;
    }
    
    .role-super-admin {
        background: #ff4757;
        color: white;
    }
    
    .role-admin {
        background: #00d2d3;
        color: white;
    }
    
    .confirmation-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin: 20px 0;
    }
    
    .confirmation-input {
        font-family: monospace;
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        letter-spacing: 2px;
    }
    
    .btn-delete {
        background: #dc3545;
        color: white;
        padding: 12px 30px;
        font-size: 16px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-delete:hover:not(:disabled) {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
    
    .btn-delete:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .btn-cancel {
        background: #6c757d;
        color: white;
        padding: 12px 30px;
        font-size: 16px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
    }
    
    .btn-cancel:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }
    
    .info-text {
        background: #e7f3ff;
        border-left: 4px solid #2196f3;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        font-size: 14px;
    }
    
    .info-text i {
        color: #2196f3;
        margin-right: 10px;
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
</style>

<div class="delete-container">
    <div class="warning-card">
        <div class="warning-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2 style="color: #dc3545; margin-bottom: 10px;">⚠️ Suppression définitive</h2>
        <p style="color: #666;">Cette action est <strong>irréversible</strong>. L'utilisateur sera définitivement supprimé de la base de données.</p>
    </div>
    
    <div class="user-info-card">
        <div class="user-avatar-large">
            <i class="fas fa-user"></i>
        </div>
        <h3 style="text-align: center; margin-bottom: 20px;"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h3>
        
        <div class="user-detail">
            <div class="user-detail-label">
                <i class="fas fa-id-badge"></i> ID
            </div>
            <div class="user-detail-value">#<?= $user['id'] ?></div>
        </div>
        
        <div class="user-detail">
            <div class="user-detail-label">
                <i class="fas fa-user-circle"></i> Nom d'utilisateur
            </div>
            <div class="user-detail-value">
                <strong><?= htmlspecialchars($user['username']) ?></strong>
            </div>
        </div>
        
        <div class="user-detail">
            <div class="user-detail-label">
                <i class="fas fa-envelope"></i> Email
            </div>
            <div class="user-detail-value"><?= htmlspecialchars($user['email']) ?></div>
        </div>
        
        <div class="user-detail">
            <div class="user-detail-label">
                <i class="fas fa-tag"></i> Rôle
            </div>
            <div class="user-detail-value">
                <span class="role-badge-large <?= $user['role'] == 'super_admin' ? 'role-super-admin' : 'role-admin' ?>">
                    <?= $user['role'] == 'super_admin' ? '👑 Super Admin' : '👤 Admin' ?>
                </span>
            </div>
        </div>
        
        <div class="user-detail">
            <div class="user-detail-label">
                <i class="fas fa-calendar-alt"></i> Date création
            </div>
            <div class="user-detail-value"><?= date('d/m/Y à H:i:s', strtotime($user['created_at'])) ?></div>
        </div>
        
        <div class="user-detail">
            <div class="user-detail-label">
                <i class="fas fa-clock"></i> Dernière connexion
            </div>
            <div class="user-detail-value">
                <?= $user['last_login'] ? date('d/m/Y à H:i:s', strtotime($user['last_login'])) : 'Jamais connecté' ?>
            </div>
        </div>
        
        <div class="user-detail">
            <div class="user-detail-label">
                <i class="fas fa-circle"></i> Statut
            </div>
            <div class="user-detail-value">
                <span style="color: <?= $user['is_active'] ? '#28a745' : '#dc3545' ?>; font-weight: bold;">
                    <?= $user['is_active'] ? '✅ Actif' : '❌ Inactif' ?>
                </span>
            </div>
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="confirmation-box">
        <div class="info-text">
            <i class="fas fa-info-circle"></i>
            Pour confirmer la suppression, veuillez taper <strong>"SUPPRIMER"</strong> dans le champ ci-dessous.
        </div>
        
        <form method="POST" action="" id="deleteForm">
            <div class="form-group">
                <label>Tapez <strong style="color: #dc3545;">SUPPRIMER</strong> pour confirmer :</label>
                <input type="text" name="confirm" id="confirmInput" class="form-control confirmation-input" 
                       placeholder="SUPPRIMER" autocomplete="off" required
                       style="text-transform: uppercase;">
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: center; margin-top: 25px;">
                <button type="submit" class="btn-delete" id="deleteBtn" disabled>
                    <i class="fas fa-trash-alt"></i> Supprimer définitivement
                </button>
                <a href="index.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i> Annuler
                </a>
            </div>
        </form>
    </div>
    
    <div class="info-text" style="background: #fff3cd; border-left-color: #ffc107;">
        <i class="fas fa-gavel"></i>
        <strong>Conséquences de la suppression :</strong>
        <ul style="margin-top: 10px; margin-left: 20px;">
            <li>L'utilisateur ne pourra plus se connecter</li>
            <li>Toutes les données associées à cet utilisateur seront perdues</li>
            <li>Cette action est irréversible</li>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmInput = document.getElementById('confirmInput');
    const deleteBtn = document.getElementById('deleteBtn');
    const deleteForm = document.getElementById('deleteForm');
    
    confirmInput.addEventListener('input', function() {
        if (this.value === 'SUPPRIMER') {
            deleteBtn.disabled = false;
            deleteBtn.style.opacity = '1';
            deleteBtn.style.cursor = 'pointer';
        } else {
            deleteBtn.disabled = true;
            deleteBtn.style.opacity = '0.5';
            deleteBtn.style.cursor = 'not-allowed';
        }
    });
    
    deleteForm.addEventListener('submit', function(e) {
        const confirmValue = confirmInput.value;
        
        if (confirmValue !== 'SUPPRIMER') {
            e.preventDefault();
            alert('❌ Veuillez taper "SUPPRIMER" pour confirmer la suppression');
            return false;
        }
        
        const lastConfirm = confirm('⚠️ DERNIER AVERTISSEMENT ⚠️\n\nCette action est irréversible.\n\nVoulez-vous vraiment supprimer définitivement cet utilisateur ?');
        
        if (!lastConfirm) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>