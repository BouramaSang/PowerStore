<?php
// src/Frontend/factures/remove_commande.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();

$facture_id = isset($_GET['facture_id']) ? (int)$_GET['facture_id'] : 0;
$commande_id = isset($_GET['commande_id']) ? (int)$_GET['commande_id'] : 0;

if ($facture_id <= 0 || $commande_id <= 0) {
    header("Location: index_facture.php");
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE commandes SET facture_id = NULL WHERE id = ? AND facture_id = ?");
    $stmt->execute([$commande_id, $facture_id]);
    
    // Si plus de commandes dans cette facture, on la supprime
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE facture_id = ?");
    $stmt->execute([$facture_id]);
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $stmt = $pdo->prepare("DELETE FROM factures WHERE id = ?");
        $stmt->execute([$facture_id]);
        header("Location: index_facture.php");
    } else {
        header("Location: view_facture.php?id=$facture_id&success=removed");
    }
} catch (PDOException $e) {
    header("Location: view_facture.php?id=$facture_id&error=db");
}
exit();