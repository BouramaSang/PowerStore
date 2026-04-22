<?php
// src/Frontend/factures/add_commande_to_facture.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();

$facture_id = isset($_POST['facture_id']) ? (int)$_POST['facture_id'] : 0;
$commande_id = isset($_POST['commande_id']) ? (int)$_POST['commande_id'] : 0;

if ($facture_id <= 0 || $commande_id <= 0) {
    header("Location: index_facture.php");
    exit();
}

try {
    // Vérifier que la commande est livrée et non facturée
    $stmt = $pdo->prepare("SELECT statut, facture_id FROM commandes WHERE id = ?");
    $stmt->execute([$commande_id]);
    $commande = $stmt->fetch();
    
    if ($commande && $commande['statut'] == 'livree' && is_null($commande['facture_id'])) {
        $stmt = $pdo->prepare("UPDATE commandes SET facture_id = ? WHERE id = ?");
        $stmt->execute([$facture_id, $commande_id]);
        header("Location: view_facture.php?id=$facture_id&success=added");
    } else {
        header("Location: view_facture.php?id=$facture_id&error=invalid");
    }
} catch (PDOException $e) {
    header("Location: view_facture.php?id=$facture_id&error=db");
}
exit();