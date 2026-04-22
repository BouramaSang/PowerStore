<?php
// src/Frontend/factures/delete_facture.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();

// Support single or multiple IDs
$ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : (isset($_GET['id']) ? [$_GET['id']] : []);

if (empty($ids)) {
    header('Location: index_facture.php');
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Remove facture_id from linked commands
    $stmt = $pdo->prepare("UPDATE commandes SET facture_id = NULL WHERE facture_id = ?");
    foreach ($ids as $id) {
        $stmt->execute([$id]);
    }
    
    // Delete factures
    $stmt = $pdo->prepare("DELETE FROM factures WHERE id = ?");
    foreach ($ids as $id) {
        $stmt->execute([$id]);
    }
    
    $pdo->commit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
}

header('Location: index_facture.php');
exit();