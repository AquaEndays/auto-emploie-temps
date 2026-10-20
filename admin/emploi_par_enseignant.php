<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$enseignants = $pdo->query("SELECT * FROM enseignants")->fetchAll();
$cours = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enseignant_id'])) {
    $enseignant_id = $_POST['enseignant_id'];
    $stmt = $pdo->prepare("
        SELECT c.jour, c.heure_debut, c.heure_fin, m.nom AS matiere, n.nom AS niveau, s.nom AS salle
        FROM cours c
        JOIN matieres m ON c.matiere_id = m.id
        JOIN niveaux n ON c.niveau_id = n.id
        JOIN salles s ON c.salle_id = s.id
        WHERE c.enseignant_id = ?
        ORDER BY c.jour, c.heure_debut
    ");
    $stmt->execute([$enseignant_id]);
    $cours = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du Temps par Enseignant</title>
</head>
<body>
    <h1>Voir l'emploi du temps par enseignant</h1>
    
    <form method="post">
        <label for="enseignant_id">Choisir un enseignant :</label>
        <select name="enseignant_id" id="enseignant_id" required>
            <option value="">-- Sélectionner --</option>
            <?php foreach ($enseignants as $ens): ?>
                <option value="<?= $ens['id'] ?>"><?= $ens['nom'] . ' ' . $ens['prenom'] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Voir</button>
    </form>

    <?php if (!empty($cours)): ?>
        <h2>Résultats</h2>
        <table border="1">
            <tr>
                <th>Jour</th>
                <th>Heure Début</th>
                <th>Heure Fin</th>
                <th>Matière</th>
                <th>Classe</th>
                <th>Salle</th>
            </tr>
            <?php foreach ($cours as $c): ?>
                <tr>
                    <td><?= $c['jour'] ?></td>
                    <td><?= $c['heure_debut'] ?></td>
                    <td><?= $c['heure_fin'] ?></td>
                    <td><?= $c['matiere'] ?></td>
                    <td><?= $c['niveau'] ?></td>
                    <td><?= $c['salle'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <p>Aucun cours trouvé pour cet enseignant.</p>
    <?php endif; ?>

    <p><a href="dashboard.php">Retour au tableau de bord</a></p>
</body>
</html>
