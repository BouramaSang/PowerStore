<?php
// src/Frontend/clients/delete_client.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index_client.php');
    exit();
}

try {
    $pdo->beginTransaction();
    
    // 1. Récupérer les commandes du client pour restaurer le stock
    $stmt = $pdo->prepare("SELECT id FROM commandes WHERE client_id = ?");
    $stmt->execute([$id]);
    $commandes = $stmt->fetchAll();
    
    foreach ($commandes as $commande) {
        // Restaurer le stock pour chaque produit des commandes
        $stmt = $pdo->prepare("SELECT produit_id, quantite FROM detail_commande WHERE commande_id = ?");
        $stmt->execute([$commande['id']]);
        $details = $stmt->fetchAll();
        
        foreach ($details as $detail) {
            $stmt = $pdo->prepare("UPDATE produits SET quantite = quantite + ? WHERE id = ?");
            $stmt->execute([$detail['quantite'], $detail['produit_id']]);
        }
        
        // Supprimer les détails des commandes
        $stmt = $pdo->prepare("DELETE FROM detail_commande WHERE commande_id = ?");
        $stmt->execute([$commande['id']]);
    }
    
    // 2. Supprimer les commandes
    $stmt = $pdo->prepare("DELETE FROM commandes WHERE client_id = ?");
    $stmt->execute([$id]);
    
    // 3. Supprimer le client
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    
    $pdo->commit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
}

header('Location: index_client.php');
exit();