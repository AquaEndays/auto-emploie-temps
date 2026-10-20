<?php
require_once '../db.php';
$emplois = $pdo->query("
    SELECT e.nom, emp.jour, emp.heure, emp.classe, emp.matiere
    FROM emplois_temps emp
    JOIN enseignants e ON e.id = emp.enseignant_id
    ORDER BY emp.jour, emp.heure
")->fetchAll();
?>

<h2>Emploi du temps généré</h2>
<table border="1">
    <tr>
        <th>Enseignant</th><th>Jour</th><th>Heure</th><th>Classe</th><th>Matière</th>
    </tr>
    <?php foreach ($emplois as $emp): ?>
        <tr>
            <td><?= htmlspecialchars($emp['nom']) ?></td>
            <td><?= htmlspecialchars($emp['jour']) ?></td>
            <td><?= htmlspecialchars($emp['heure']) ?></td>
            <td><?= htmlspecialchars($emp['classe']) ?></td>
            <td><?= htmlspecialchars($emp['matiere']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
