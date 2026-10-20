<?php
require_once '../db.php';

// Vider l'ancien emploi du temps
$pdo->exec("DELETE FROM emplois_du_temps");

// Obtenir les salles disponibles
$salles = $pdo->query("SELECT id FROM salles")->fetchAll(PDO::FETCH_COLUMN);
$salleIndex = 0;

// Fonction pour vérifier si une salle est occupée
function estOccupe($pdo, $jour, $creneau, $salle_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM emplois_du_temps WHERE jour = ? AND creneau = ? AND salle_id = ?");
    $stmt->execute([$jour, $creneau, $salle_id]);
    return $stmt->fetchColumn() > 0;
}

// Suivi des heures par enseignant
$heures_par_enseignant = [];

// Récupère toutes les disponibilités triées par enseignant
$stmt = $pdo->query("SELECT * FROM disponibilites ORDER BY enseignant_id");
$disponibilites = $stmt->fetchAll();

foreach ($disponibilites as $dispo) {
    $enseignant_id = $dispo['enseignant_id'];
    $jour = $dispo['jour'];
    $creneau = $dispo['creneau'];

    // Récupère l'enseignant
    $ensStmt = $pdo->prepare("SELECT * FROM enseignants WHERE id = ?");
    $ensStmt->execute([$enseignant_id]);
    $enseignant = $ensStmt->fetch();

    if (!$enseignant) continue;

    $volume_max = $enseignant['volume_horaire'];
    $matiere = $enseignant['matiere'];
    $classe = $enseignant['classe'];

    // Initialise le compteur si pas encore fait
    if (!isset($heures_par_enseignant[$enseignant_id])) {
        $heures_par_enseignant[$enseignant_id] = 0;
    }

    // Ne pas dépasser le volume horaire
    if ($heures_par_enseignant[$enseignant_id] >= $volume_max) continue;

    // Cherche une salle libre
    foreach ($salles as $salle_id) {
        if (!estOccupe($pdo, $jour, $creneau, $salle_id)) {
            // Insérer la séance
            $insert = $pdo->prepare("INSERT INTO emplois_du_temps (enseignant_id, jour, creneau, classe, matiere, salle_id) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([$enseignant_id, $jour, $creneau, $classe, $matiere, $salle_id]);
            $heures_par_enseignant[$enseignant_id]++;
            break; // Ne pas chercher d'autre salle pour ce créneau
        }
    }
}

echo "✅ Emploi du temps généré avec succès à partir des disponibilités.<br><a href='dashboard_admin.php'>Retour</a>";
?>
