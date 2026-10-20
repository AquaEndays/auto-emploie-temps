<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$salles = $pdo->query("SELECT id, nom FROM salles")->fetchAll();
$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$creneaux_order = ['Matin', 'Midi', 'Soir'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Emploi du Temps par Salle</title>
    <style>
        body { font-family: Arial; background: #f9f9f9; padding: 20px; }
        h2 { text-align: center; }
        .salle-block { margin-bottom: 40px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; vertical-align: top; }
        th { background: #2d3e50; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }
        a { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #2d3e50; }
    </style>
</head>
<body>
    <h2>Emploi du Temps par Salle</h2>

    <?php foreach ($salles as $salle): ?>
        <div class="salle-block">
            <h3><?= htmlspecialchars($salle['nom']) ?></h3>
            <table>
                <tr>
                    <th>Jour</th>
                    <th>Créneau</th>
                    <th>Classe</th>
                    <th>Matière</th>
                    <th>Enseignant</th>
                </tr>
                <?php
                // Charger les emplois du temps pour cette salle
                $stmt = $pdo->prepare("
                    SELECT edt.*, ens.nom AS enseignant 
                    FROM emplois_du_temps edt
                    JOIN enseignants ens ON edt.enseignant_id = ens.id
                    WHERE edt.salle_id = ?
                    ORDER BY 
                        FIELD(jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'),
                        FIELD(creneau, 'Matin', 'Midi', 'Soir')
                ");
                $stmt->execute([$salle['id']]);
                $entries = $stmt->fetchAll();

                // Grouper par jour
                $grouped = [];
                foreach ($entries as $entry) {
                    $grouped[$entry['jour']][] = $entry;
                }

                foreach ($jours as $jour) {
                    if (!isset($grouped[$jour])) continue;

                    $rows = $grouped[$jour];
                    $rowspan = count($rows);
                    $first = true;

                    foreach ($rows as $row) {
                        echo "<tr>";
                        if ($first) {
                            echo "<td rowspan=\"$rowspan\">" . htmlspecialchars($jour) . "</td>";
                            $first = false;
                        }
                        echo "<td>" . htmlspecialchars($row['creneau']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['classe']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['matiere']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['enseignant']) . "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </table>
        </div>
    <?php endforeach; ?>

    <a href="dashboard.php">⬅ Retour au tableau de bord</a>
</body>
</html>
