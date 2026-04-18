<?php
// models/User.php
namespace Facturation\Models;

use Facturation\Config\Database;

class User {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function findByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }
    
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }
    
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password, role, nom, prenom) 
            VALUES (:username, :email, :password, :role, :nom, :prenom)
        ");
        
        return $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => $password_hash,
            ':role' => $data['role'],
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom']
        ]);
    }
    
    public function updateLastLogin($id) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    public function toggleStatus($id) {
        $stmt = $this->pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    public function updateRole($id, $role) {
        $stmt = $this->pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        return $stmt->execute([':id' => $id, ':role' => $role]);
    }
    
    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        
        if ($user && password_verify($password, $user['password'])) {
            $this->updateLastLogin($user['id']);
            return $user;
        }
        
        return false;
    }
}