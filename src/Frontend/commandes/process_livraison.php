<?php
// src/Frontend/commandes/process_livraison.php
require_once '../../config/app.php';
requireAdmin();

session_start();

$pdo = getPDO();

$commande_id = isset($_GET['commande_id']) ? (int)$_GET['commande_id'] : 0;
$option = isset($_GET['option']) ? $_GET['option'] : '';
$facture_id = isset($_GET['facture_id']) ? (int)$_GET['facture_id'] : 0;

if ($commande_id <= 0 || !in_array($option, ['existing', 'new', 'none'])) {
    header('Location: index_commande.php');
    exit();
}

// Récupérer les infos de la commande
$stmt = $pdo->prepare("SELECT client_id, total_ttc, statut FROM commandes WHERE id = ?");
$stmt->execute([$commande_id]);
$commande = $stmt->fetch();

if (!$commande) {
    header('Location: index_commande.php');
    exit();
}

// Vérifier qu'elle n'est pas déjà livrée
if ($commande['statut'] === 'livree') {
    header('Location: view_commande.php?id=' . $commande_id . '&error=already');
    exit();
}

$client_id = $commande['client_id'];

try {
    $pdo->beginTransaction();
    
    // 1. Mettre à jour le statut de la commande
    $stmt = $pdo->prepare("UPDATE commandes SET statut = 'livree' WHERE id = ?");
    $stmt->execute([$commande_id]);
    
    // 2. Gérer la facture selon l'option choisie
    if ($option == 'existing' && $facture_id > 0) {
        // Ajouter à une facture existante
        $stmt = $pdo->prepare("UPDATE commandes SET facture_id = ? WHERE id = ?");
        $stmt->execute([$facture_id, $commande_id]);
        
    } elseif ($option == 'new') {
        // Créer une nouvelle facture
        $stmt = $pdo->query("SELECT COUNT(*) FROM factures");
        $count = $stmt->fetchColumn();
        $numero = 'FACT-' . date('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("INSERT INTO factures (nomf, datef, etatf) VALUES (?, NOW(), 0)");
        $stmt->execute([$numero]);
        $nouvelle_facture_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("UPDATE commandes SET facture_id = ? WHERE id = ?");
        $stmt->execute([$nouvelle_facture_id, $commande_id]);
        
    } elseif ($option == 'none') {
        // Livrer sans facture - rien à faire
    }
    
    $pdo->commit();
    
    // Nettoyer la session
    unset($_SESSION['pending_livraison']);
    
    // Redirection vers la vue de la commande avec message
    $redirect = 'view_commande.php?id=' . $commande_id;
    if ($option == 'new' && isset($nouvelle_facture_id)) {
        $redirect .= '&facture=1';
    } elseif ($option == 'existing' && $facture_id) {
        $redirect .= '&added_to_facture=' . $facture_id;
    }
    header("Location: $redirect");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: view_commande.php?id=' . $commande_id . '&error=db');
}
exit();