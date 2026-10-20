<link rel="stylesheet" href="salles.css">
<?php
require_once '../db.php';

// Ajouter une salle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = trim($_POST['nom']);
    $capacite = intval($_POST['capacite']);
    $stmt = $pdo->prepare("INSERT INTO salles (nom, capacite) VALUES (?, ?)");
    $stmt->execute([$nom, $capacite]);
    header("Location: salles.php");
    exit;
}

// Marquer une salle comme disponible/indisponible
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $salle = $pdo->prepare("SELECT est_indisponible FROM salles WHERE id = ?");
    $salle->execute([$id]);
    $etat = $salle->fetchColumn();
    $nouvelEtat = $etat ? 0 : 1;
    $pdo->prepare("UPDATE salles SET est_indisponible = ? WHERE id = ?")->execute([$nouvelEtat, $id]);
    header("Location: salles.php");
    exit;
}

// Supprimer une salle
if (isset($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM salles WHERE id = ?")->execute([intval($_GET['supprimer'])]);
    header("Location: salles.php");
    exit;
}

// Liste des salles
$salles = $pdo->query("SELECT * FROM salles")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestion des Salles</title>
   
</head>
<body>
    <div class="container">
        <h2>Ajouter une Salle</h2>
        <form method="post">
            <input type="text" name="nom" placeholder="Nom de la salle" required>
            <input type="number" name="capacite" placeholder="Capacité" required>
            <button type="submit" name="ajouter">Ajouter</button>
        </form>

        <h2>Liste des Salles</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Capacité</th>
                <th>Indisponible ?</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($salles as $salle): ?>
                <tr>
                    <td><?= $salle['id'] ?></td>
                    <td><?= htmlspecialchars($salle['nom']) ?></td>
                    <td><?= $salle['capacite'] ?></td>
                    <td><?= $salle['est_indisponible'] ? 'Oui' : 'Non' ?></td>
                    <td class="actions">
                        <a href="?toggle=<?= $salle['id'] ?>">
                            <?= $salle['est_indisponible'] ? '✅ Disponible' : '🚫 Indisponible' ?>
                        </a>
                        <a href="?supprimer=<?= $salle['id'] ?>" onclick="return confirm('Supprimer cette salle ?')">🗑 Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a class="back-link" href="dashboard.php">← Retour au tableau de bord</a>
    </div>
</body>
</html>