<?php
// users.php
require_once 'config/app.php';
requireSuperAdmin();

use Facturation\Models\User;

$userModel = new User();
$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch($_POST['action'] ?? '') {
            case 'add':
                $data = [
                    'username' => trim($_POST['username']),
                    'email' => trim($_POST['email']),
                    'password' => $_POST['password'],
                    'role' => $_POST['role'],
                    'nom' => $_POST['nom'] ?? '',
                    'prenom' => $_POST['prenom'] ?? ''
                ];
                
                if ($userModel->create($data)) {
                    $message = "Utilisateur créé avec succès";
                } else {
                    $error = "Erreur lors de la création";
                }
                break;
                
            case 'toggle_status':
                $id = (int)$_POST['user_id'];
                if ($id !== $_SESSION['user_id']) {
                    if ($userModel->toggleStatus($id)) {
                        $message = "Statut modifié avec succès";
                    } else {
                        $error = "Erreur lors de la modification";
                    }
                } else {
                    $error = "Vous ne pouvez pas modifier votre propre statut";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['user_id'];
                if ($id !== $_SESSION['user_id']) {
                    if ($userModel->delete($id)) {
                        $message = "Utilisateur supprimé avec succès";
                    } else {
                        $error = "Erreur lors de la suppression";
                    }
                } else {
                    $error = "Vous ne pouvez pas supprimer votre propre compte";
                }
                break;
                
            case 'update_role':
                $id = (int)$_POST['user_id'];
                $role = $_POST['role'];
                if ($id !== $_SESSION['user_id']) {
                    if ($userModel->updateRole($id, $role)) {
                        $message = "Rôle modifié avec succès";
                    } else {
                        $error = "Erreur lors de la modification";
                    }
                } else {
                    $error = "Vous ne pouvez pas modifier votre propre rôle";
                }
                break;
        }
    } catch (Exception $e) {
        $error = "Erreur: " . $e->getMessage();
    }
}

$users = $userModel->getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - <?= APP_NAME ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0 30px;
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #ff4757;
            color: white;
        }
        
        .btn-danger:hover {
            background: #ee5a6f;
        }
        
        .btn-warning {
            background: #ffa502;
            color: white;
        }
        
        .btn-warning:hover {
            background: #ff7f50;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th,
        table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-active {
            background: #28a745;
            color: white;
        }
        
        .badge-inactive {
            background: #dc3545;
            color: white;
        }
        
        .badge-super-admin {
            background: #ff4757;
            color: white;
        }
        
        .badge-admin {
            background: #00d2d3;
            color: white;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 20px;
            }
            
            table {
                font-size: 12px;
            }
            
            table th,
            table td {
                padding: 10px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Gestion des Utilisateurs</h1>
        <a href="dashboard.php" style="color: white; text-decoration: none;">← Retour au Dashboard</a>
    </div>
    
    <div class="container">
        <?php if($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>➕ Ajouter un utilisateur</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nom d'utilisateur *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe *</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Rôle *</label>
                        <select name="role" required>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="nom">
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
            </form>
        </div>
        
        <div class="card">
            <h2>👥 Liste des utilisateurs</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom d'utilisateur</th>
                            <th>Email</th>
                            <th>Nom complet</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Dernière connexion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                            <td>
                                <?php if($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge <?= $user['role'] == 'super_admin' ? 'badge-super-admin' : 'badge-admin' ?>">
                                        <?= $user['role'] == 'super_admin' ? 'Super Admin' : 'Admin' ?> (Vous)
                                    </span>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <select name="role" onchange="this.form.submit()" style="padding: 5px;">
                                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                            <option value="super_admin" <?= $user['role'] == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $user['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $user['is_active'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais' ?></td>
                            <td class="actions">
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <?= $user['is_active'] ? 'Désactiver' : 'Activer' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmer la suppression de <?= htmlspecialchars($user['username']) ?> ?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #999;">Compte actuel</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>