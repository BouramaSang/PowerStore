<?php
// src/Frontend/factures/update_facture_status.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();

// Support single or multiple IDs
$ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : (isset($_GET['id']) ? [$_GET['id']] : []);
$status = isset($_GET['status']) ? $_GET['status'] : '';

if (empty($ids) || !in_array($status, ['paid', 'unpaid'])) {
    header('Location: index_facture.php');
    exit();
}

$new_etatf = ($status == 'paid') ? 1 : 0;

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE factures SET etatf = ? WHERE id = ?");
    foreach ($ids as $id) {
        $stmt->execute([$new_etatf, $id]);
    }
    
    $pdo->commit();
    
    // Redirect back
    $redirect = (count($ids) == 1) ? "view_facture.php?id={$ids[0]}&success=paid" : "index_facture.php";
    header("Location: $redirect");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: index_facture.php?error=1');
}
exit();