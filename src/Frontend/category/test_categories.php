<?php
// test_categories.php
require_once '../../config/app.php';
requireAdmin();

$pdo = getPDO();

// Afficher toutes les catégories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id");
$categories = $stmt->fetchAll();

echo "<h2>Contenu de la table categories</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>nomcat</th></tr>";
foreach($categories as $cat) {
    echo "<tr>";
    echo "<td>" . $cat['id'] . "</td>";
    echo "<td>" . htmlspecialchars($cat['nomcat']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Nombre total de catégories : " . count($categories) . "</h3>";
?>