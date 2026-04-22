<?php
// src/Frontend/factures/print_facture.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('Facture non trouvée');
}

// Récupérer facture
$stmt = $pdo->prepare("SELECT * FROM factures WHERE id = ?");
$stmt->execute([$id]);
$facture = $stmt->fetch();

if (!$facture) {
    die('Facture non trouvée');
}

// Récupérer commandes
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.date_commande,
        c.total_ttc,
        CONCAT(cl.prenom, ' ', cl.nomc) as client_nom,
        cl.tel as client_tel,
        cl.adresse as client_adresse,
        GROUP_CONCAT(CONCAT(p.nomp, ' (', d.quantite, 'x ', d.prix_unitaire, ' FCFA)') SEPARATOR '<br>') as produits
    FROM commandes c
    LEFT JOIN clients cl ON c.client_id = cl.id
    LEFT JOIN detail_commande d ON c.id = d.commande_id
    LEFT JOIN produits p ON d.produit_id = p.id
    WHERE c.facture_id = ?
    GROUP BY c.id
");
$stmt->execute([$id]);
$commandes = $stmt->fetchAll();

$total_ttc = array_sum(array_column($commandes, 'total_ttc'));
$client = !empty($commandes) ? $commandes[0] : null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture <?= htmlspecialchars($facture['nomf']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none; }
            .page-break { page-break-before: always; }
        }
        body {
            font-family: 'DejaVu Sans', 'Segoe UI', Arial, sans-serif;
            background: white;
            padding: 30px;
        }
        .invoice-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #E66239;
        }
        .invoice-company h2 {
            color: #E66239;
            margin-bottom: 5px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-client {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .invoice-footer {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
        }
        .text-end {
            text-align: right;
        }
        .fw-bold {
            font-weight: bold;
        }
        .text-primary {
            color: #E66239;
        }
    </style>
</head>
<body>
<div class="invoice-container">
    <div class="invoice-header">
        <div class="invoice-company">
            <h2>POWERSTOCK</h2>
            <p>Bamako - Mali</p>
            <p>Tel: 77-90-34-44</p>
            <p>Email: contact@powerstock.com</p>
        </div>
        <div class="invoice-title">
            <h3>FACTURE</h3>
            <p><strong>N°</strong> <?= htmlspecialchars($facture['nomf']) ?></p>
            <p><strong>Date</strong> <?= date('d/m/Y H:i', strtotime($facture['datef'])) ?></p>
            <p><strong>Lieu</strong> Bamako</p>
        </div>
    </div>

    <?php if($client): ?>
    <div class="invoice-client">
        <h5>Client</h5>
        <p>
            <strong>Nom :</strong> <?= htmlspecialchars($client['client_nom']) ?><br>
            <strong>Adresse :</strong> <?= htmlspecialchars($client['client_adresse'] ?? 'Non renseignée') ?><br>
            <strong>Tél :</strong> <?= htmlspecialchars($client['client_tel'] ?? 'Non renseigné') ?>
        </p>
    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr><th>N°</th><th>Date commande</th><th>Produits</th><th class="text-end">Montant</th></tr>
        </thead>
        <tbody>
            <?php foreach($commandes as $index => $cmd): ?>
            <tr>
                <td class="text-center"><?= $index + 1 ?></td>
                <td><?= date('d/m/Y', strtotime($cmd['date_commande'])) ?></td>
                <td><?= $cmd['produits'] ?></td>
                <td class="text-end fw-bold"><?= number_format($cmd['total_ttc'], 0, ',', ' ') ?> FCFA</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end fw-bold fs-5">TOTAL</td>
                <td class="text-end fw-bold fs-4 text-primary"><?= number_format($total_ttc, 0, ',', ' ') ?> FCFA</td>
            </tr>
        </tfoot>
    </table>

    <div class="invoice-footer">
        <p>Merci de votre confiance !</p>
        <p><strong>PowerStock</strong> - Votre partenaire électroménager</p>
        <p class="mt-3">Arrêtée à la présente somme de <strong><?= number_format($total_ttc, 0, ',', ' ') ?> FCFA</strong></p>
    </div>

    <div class="no-print text-center mt-4">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimer</button>
        <button onclick="window.close()" class="btn btn-secondary">Fermer</button>
    </div>
</div>

<script>
    // Auto print
    window.onload = function() {
        setTimeout(() => window.print(), 500);
    }
</script>
</body>
</html>