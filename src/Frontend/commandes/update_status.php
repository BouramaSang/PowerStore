<?php
// src/Frontend/commandes/update_status.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$new_status = isset($_GET['status']) ? $_GET['status'] : '';

$allowed = ['en_attente', 'livree', 'annulee'];

if ($id <= 0 || !in_array($new_status, $allowed)) {
    header('Location: index_commande.php');
    exit();
}

// Récupérer les infos de la commande
$stmt = $pdo->prepare("
    SELECT c.*, CONCAT(cl.prenom, ' ', cl.nomc) as client_nom
    FROM commandes c
    LEFT JOIN clients cl ON c.client_id = cl.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$commande = $stmt->fetch();

if (!$commande) {
    header('Location: index_commande.php');
    exit();
}

// Si ce n'est PAS un passage en livrée, on traite directement
if ($new_status != 'livree') {
    // Traitement direct pour annulation ou retour en attente
    try {
        $pdo->beginTransaction();
        
        $old_status = $commande['statut'];
        
        // Gestion du stock pour annulation
        if (($old_status === 'en_attente' && $new_status === 'annulee') ||
            ($old_status === 'livree' && $new_status === 'annulee')) {
            $stmt = $pdo->prepare("SELECT produit_id, quantite FROM detail_commande WHERE commande_id = ?");
            $stmt->execute([$id]);
            $details = $stmt->fetchAll();
            foreach ($details as $detail) {
                $pdo->prepare("UPDATE produits SET quantite = quantite + ? WHERE id = ?")
                    ->execute([$detail['quantite'], $detail['produit_id']]);
            }
        }
        
        // Gestion de la facture si on sort de "livree"
        if ($old_status === 'livree' && $new_status !== 'livree') {
            if ($commande['facture_id']) {
                $pdo->prepare("DELETE FROM factures WHERE id = ?")->execute([$commande['facture_id']]);
                $pdo->prepare("UPDATE commandes SET facture_id = NULL WHERE id = ?")->execute([$id]);
            }
        }
        
        // Mettre à jour le statut
        $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?")->execute([$new_status, $id]);
        
        $pdo->commit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
    }
    
    header('Location: view_commande.php?id=' . $id);
    exit();
}

// C'est un passage en livrée - on sauvegarde en session et on redirige vers le choix
session_start();
$_SESSION['pending_livraison'] = [
    'commande_id' => $id,
    'client_id' => $commande['client_id'],
    'client_nom' => $commande['client_nom'],
    'total_ttc' => $commande['total_ttc']
];

header("Location: choose_facture_option.php");
exit();