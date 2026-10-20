<?php
// generer_emploi.php

// 1. Configuration de la base de données
$host = 'localhost';
$db   = 'gilles_le_magnifique';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$message = '';
$messageType = '';
$conn = null;

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Récupérer les classes disponibles pour le sélecteur (depuis la table 'salles')
$classes_disponibles = $conn->query("SELECT id, nom FROM salles ORDER BY nom")->fetchAll();

// Définition des jours et créneaux horaires
$jours_semaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

$creneaux_horaires = [
    '08h00-12h00' => ['debut' => '08:00', 'fin' => '12:00', 'label' => '08h00 - 12h00', 'duration' => '4h'],
    '12h00-13h30' => ['debut' => '12:00', 'fin' => '13:30', 'label' => '12h00 - 13h30 (pause)', 'duration' => '1h30'],
    '13h30-17h30' => ['debut' => '13:30', 'fin' => '17:30', 'label' => '13h30 - 17h30', 'duration' => '4h'],
    // La ligne suivante a été supprimée pour enlever le créneau 18h00-21h00
    // '18h00-21h00' => ['debut' => '18:00', 'fin' => '21:00', 'label' => '18h00 - 21h00', 'duration' => '3h'],
];

// 3. Logique de génération de l'emploi du temps
if (isset($_POST['generer_emploi_du_temps'])) {
    $selected_classe_id = $_POST['classe_id'] ?? null;
    $selected_classe_nom = ''; // Pour le message de succès/échec

    if ($selected_classe_id) {
        // Récupérer le nom de la classe pour les messages (depuis la table 'salles')
        $stmt_classe_nom = $conn->prepare("SELECT nom FROM salles WHERE id = ?");
        $stmt_classe_nom->execute([$selected_classe_id]);
        $classe_info = $stmt_classe_nom->fetch();
        if ($classe_info) {
            $selected_classe_nom = $classe_info['nom'];
        } else {
            $message = "Classe sélectionnée invalide.";
            $messageType = "error";
            // Redirection pour afficher le message sur la même page
            header("Location: generer_emploi.php?message=" . urlencode($message) . "&type=" . urlencode($messageType));
            exit();
        }

        try {
            $conn->beginTransaction();

            // Vider l'emploi du temps UNIQUEMENT pour la classe sélectionnée
            $stmt = $conn->prepare("DELETE FROM emploi_du_temps WHERE classe_id = ?");
            $stmt->execute([$selected_classe_id]);
            $message .= "Ancien emploi du temps vidé pour la classe " . htmlspecialchars($selected_classe_nom) . ".<br>";

            // Récupérer les pré-affectations UNIQUEMENT pour la classe sélectionnée
            $stmt_pre_affectations = $conn->prepare("
                SELECT de.id as pre_affectation_id, de.enseignant_id, de.matiere_id, de.salle_physique_id, de.salle_id AS classe_id
                FROM donnees_emploi de
                WHERE de.salle_id = ?
                ORDER BY de.enseignant_id, de.matiere_id
            ");
            $stmt_pre_affectations->execute([$selected_classe_id]);
            $pre_affectations = $stmt_pre_affectations->fetchAll();

            // --- NOUVEAU : Chargement des créneaux de DISPONIBILITÉ des enseignants ---
            // Si une entrée existe dans 'disponibilites', l'enseignant est disponible pour ce créneau.
            $disponibilites_enseignants = [];
            $stmt_dispo = $conn->query("SELECT enseignant_id, jour, creneau FROM disponibilites");
            while ($row = $stmt_dispo->fetch()) {
                $disponibilites_enseignants[$row['enseignant_id']][$row['jour']][$row['creneau']] = true;
            }

            // Initialiser les grilles d'occupation
            $occupation_salles_global = [];
            $occupation_enseignants_global = [];
            $occupation_classes_global = []; // Ajouté pour s'assurer qu'une classe n'est pas déjà affectée ailleurs

            // Charger les occupations existantes globales (d'autres classes ou déjà générées pour la classe actuelle si on ne vide pas tout)
            // On exclut la classe actuelle car on vient de la vider.
            $stmt_global_occupations = $conn->prepare("
                SELECT jour, creneau, salle_physique_id, enseignant_id, classe_id
                FROM emploi_du_temps
                WHERE classe_id != ?
            ");
            $stmt_global_occupations->execute([$selected_classe_id]);
            while ($row = $stmt_global_occupations->fetch()) {
                $occupation_salles_global[$row['jour']][$row['creneau']][$row['salle_physique_id']] = true;
                if ($row['enseignant_id'] !== null) {
                    $occupation_enseignants_global[$row['jour']][$row['creneau']][$row['enseignant_id']] = true;
                }
                $occupation_classes_global[$row['jour']][$row['creneau']][$row['classe_id']] = true; // Occupation d'autres classes
            }

            // Occupation pour la classe que nous sommes en train de générer (vide au début, remplie au fur et à mesure)
            $occupation_classe_courante = [];

            $affectations_reussies = 0;
            $affectations_echouees = 0;
            $affectations_non_placees_details = []; // Pour stocker les détails des échecs

            // Mélanger les pré-affectations pour une répartition plus "aléatoire"
            shuffle($pre_affectations);

            // Tenter d'affecter tous les cours basés sur les pré-affectations
            foreach ($pre_affectations as $pre_affectation) {
                $enseignant_id      = $pre_affectation['enseignant_id'];
                $matiere_id         = $pre_affectation['matiere_id'];
                $salle_physique_id  = $pre_affectation['salle_physique_id'];
                $classe_id          = $pre_affectation['classe_id']; // C'est la classe sélectionnée

                $found_slot = false;
                $current_pre_affectation_reasons = []; // Pour stocker les raisons d'échec pour cette pré-affectation

                foreach ($jours_semaine as $jour) {
                    foreach ($creneaux_horaires as $creneau_value => $times) {
                        if ($creneau_value === '12h00-13h30') { // Créneau de pause, pas de cours
                            continue;
                        }

                        // Réinitialiser les raisons pour ce créneau spécifique
                        $conflit_raison_creneau = [];

                        // Vérifications des conflits et disponibilités
                        $is_conflit = false;

                        // 1. Conflit Enseignant (dans l'emploi du temps en cours de construction ou globalement)
                        if (isset($occupation_enseignants_global[$jour][$creneau_value][$enseignant_id]) ||
                            isset($occupation_classe_courante[$jour][$creneau_value]['enseignant_occupied_by_this_class'][$enseignant_id])) {
                            $is_conflit = true;
                            $conflit_raison_creneau[] = "Enseignant déjà occupé sur un autre cours.";
                        }

                        // 2. Conflit Salle Physique (dans l'emploi du temps en cours de construction ou globalement)
                        if (isset($occupation_salles_global[$jour][$creneau_value][$salle_physique_id]) ||
                            isset($occupation_classe_courante[$jour][$creneau_value]['salle_physical_occupied_by_this_class'][$salle_physique_id])) {
                            $is_conflit = true;
                            $conflit_raison_creneau[] = "Salle physique déjà occupée.";
                        }

                        // 3. Conflit Classe (groupe d'étudiants - dans l'emploi du temps en cours de construction ou globalement)
                        // Note: $classe_id est toujours le $selected_classe_id ici
                        if (isset($occupation_classes_global[$jour][$creneau_value][$classe_id]) ||
                            isset($occupation_classe_courante[$jour][$creneau_value]['classe_occupied_by_this_class'][$classe_id])) {
                            $is_conflit = true;
                            $conflit_raison_creneau[] = "Classe déjà occupée.";
                        }

                        // 4. Disponibilité de l'enseignant (la table 'disponibilites' liste les moments OÙ il est disponible)
                        // Si l'entrée N'EXISTE PAS dans $disponibilites_enseignants, alors l'enseignant n'est PAS disponible.
                        if (!isset($disponibilites_enseignants[$enseignant_id][$jour][$creneau_value])) {
                            $is_conflit = true;
                            $conflit_raison_creneau[] = "Enseignant non disponible (pas de disponibilité enregistrée pour ce créneau).";
                        }

                        // Si aucun conflit, tenter l'insertion
                        if (!$is_conflit) {
                            $stmt_insert = $conn->prepare(
                                "INSERT INTO emploi_du_temps (enseignant_id, matiere_id, salle_physique_id, classe_id, jour, creneau, heure_debut, heure_fin)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                            );
                            if ($stmt_insert->execute([$enseignant_id, $matiere_id, $salle_physique_id, $classe_id, $jour, $creneau_value, $times['debut'], $times['fin']])) {
                                $found_slot = true;
                                $affectations_reussies++;

                                // Marquer comme occupés pour la classe courante et globalement pour la vérification des prochains cours
                                $occupation_classe_courante[$jour][$creneau_value]['classe_occupied_by_this_class'][$classe_id] = true;
                                $occupation_classe_courante[$jour][$creneau_value]['enseignant_occupied_by_this_class'][$enseignant_id] = true;
                                $occupation_classe_courante[$jour][$creneau_value]['salle_physical_occupied_by_this_class'][$salle_physique_id] = true;

                                // Mise à jour des globales aussi, car ces slots sont maintenant pris
                                $occupation_salles_global[$jour][$creneau_value][$salle_physique_id] = true;
                                $occupation_enseignants_global[$jour][$creneau_value][$enseignant_id] = true;
                                $occupation_classes_global[$jour][$creneau_value][$classe_id] = true;

                                break 2; // Sortir des boucles creneau et jour car le cours est placé
                            } else {
                                // Cette branche ne devrait normalement pas être atteinte si les vérifications sont bonnes.
                                // C'est un fallback pour des problèmes d'insertion PDO inattendus.
                                $conflit_raison_creneau[] = "Erreur SQL lors de l'insertion.";
                                $current_pre_affectation_reasons[$jour][$creneau_value] = $conflit_raison_creneau;
                            }
                        } else {
                            // Ajouter les raisons de conflit pour ce créneau et ce jour
                            $current_pre_affectation_reasons[$jour][$creneau_value] = $conflit_raison_creneau;
                        }
                    } // Fin foreach creneaux_horaires
                } // Fin foreach jours_semaine

                if (!$found_slot) {
                    $affectations_echouees++;
                    // Récupérer les noms pour un message plus lisible
                    $stmt_names = $conn->prepare("
                        SELECT e.nom AS enseignant_nom, m.nom AS matiere_nom, sp.nom AS salle_nom, s.nom AS classe_nom
                        FROM donnees_emploi de
                        JOIN enseignants e ON de.enseignant_id = e.id
                        JOIN matieres m ON de.matiere_id = m.id
                        JOIN salles_physiques sp ON de.salle_physique_id = sp.id
                        JOIN salles s ON de.salle_id = s.id
                        WHERE de.id = ?
                    ");
                    $stmt_names->execute([$pre_affectation['pre_affectation_id']]);
                    $names = $stmt_names->fetch();

                    $affectations_non_placees_details[] = [
                        'id' => $pre_affectation['pre_affectation_id'],
                        'enseignant' => $names['enseignant_nom'] ?? 'N/A',
                        'matiere' => $names['matiere_nom'] ?? 'N/A',
                        'classe' => $names['classe_nom'] ?? 'N/A',
                        'raisons_echec' => $current_pre_affectation_reasons // Toutes les raisons trouvées pour cette pré-affectation
                    ];
                }
            } // Fin foreach pre_affectations

            $conn->commit();
            $message = "Génération de l'emploi du temps terminée pour la classe " . htmlspecialchars($selected_classe_nom) . ".<br>" . $affectations_reussies . " cours affectés, " . $affectations_echouees . " échecs de cours. Les créneaux non affectés seront affichés comme 'TPE' ou 'PAUSE'.";

            if ($affectations_echouees > 0) {
                $messageType = "warning";
                $message .= "<br><span style='color: orange;'>Détails des pré-affectations non placées :</span><br>";
                $message .= "<div style='max-height: 250px; overflow-y: auto; text-align: left; background-color: #fcf8e3; padding: 10px; border-radius: 5px; margin-top: 10px; border: 1px solid #faebcc;'>";
                foreach ($affectations_non_placees_details as $detail) {
                    $message .= "- **ID " . $detail['id'] . "** : " . htmlspecialchars($detail['enseignant']) . " / " . htmlspecialchars($detail['matiere']) . " / " . htmlspecialchars($detail['classe']) . "<br>";
                    $message .= "&nbsp;&nbsp;Tentatives échouées :<br>";
                    foreach ($detail['raisons_echec'] as $jour_raison => $creneau_raisons) {
                        foreach ($creneau_raisons as $creneau_libelle_raison => $reasons_array) {
                             $message .= "&nbsp;&nbsp;&nbsp;&nbsp;- Pour le " . $jour_raison . " de " . $creneau_libelle_raison . " : " . implode(", ", $reasons_array) . "<br>";
                        }
                    }
                    $message .= "<br>";
                }
                $message .= "</div>";

            } else {
                $messageType = "success";
            }
            // Redirection pour éviter le re-soumission du formulaire et afficher le message
            header("Location: generer_emploi.php?message=" . urlencode($message) . "&type=" . urlencode($messageType) . "&selected_classe_id=" . urlencode($selected_classe_id));
            exit();

        } catch (\PDOException $e) {
            $conn->rollBack();
            $message = "Erreur SQL lors de la génération de l'emploi du temps : " . $e->getMessage();
            $messageType = "error";
            error_log("Erreur PDO dans generer_emploi.php: " . $e->getMessage()); // Pour le débogage sur le serveur
            // Redirection pour afficher le message d'erreur
            header("Location: generer_emploi.php?message=" . urlencode($message) . "&type=" . urlencode($messageType) . "&selected_classe_id=" . urlencode($selected_classe_id));
            exit();
        }
    } else {
        $message = "Veuillez sélectionner une classe pour générer l'emploi du temps.";
        $messageType = "warning";
    }
}

// Gérer les messages de redirection (après une génération ou une erreur)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}
$pre_selected_classe_id = $_GET['selected_classe_id'] ?? ''; // Pour maintenir la classe sélectionnée après redirection

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Génération de l'Emploi du Temps</title>
    <style>
        /* Le CSS reste le même que dans pas_emploi.php pour la cohérence */
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --action-edit: #ffc107;
            --action-delete: #dc3545;
            --bg-light: #f8f9fa;
            --text-dark: #343a40;
            --text-light: #6c757d;
            --border-color: #dee2e6;
            --box-shadow-light: rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 40px 20px;
            background-color: var(--bg-light);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.5em;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .container {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--box-shadow-light);
            width: 100%;
            max-width: 900px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .form-section {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-dark);
        }

        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 1em;
            background-color: #fff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }

        input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            cursor: pointer;
            border: none;
            font-weight: bold;
            letter-spacing: 0.5px;
            padding: 12px 15px;
            border-radius: 6px;
            margin-top: 20px;
            box-sizing: border-box;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.4);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .message.warning { /* Nouveau style pour les avertissements */
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .button-container {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
        }

        .action-button {
            display: inline-block;
            padding: 12px 25px;
            margin: 0 10px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .button-retour {
            background-color: #6c757d;
            color: white;
        }

        .button-retour:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .button-view-timetable {
            background-color: #28a745; /* Vert pour visualiser */
            color: white;
        }

        .button-view-timetable:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .button-pre-affectations { /* Style pour le bouton Retour aux Pré-affectations */
            background-color: #17a2b8; /* Bleu clair pour l'info/pré-affectations */
            color: white;
        }
        .button-pre-affectations:hover {
            background-color: #138496;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <h1>Génération Automatique de l'Emploi du Temps</h1>

    <div class="container form-section">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <h2>Sélectionner la Classe et Lancer la Génération</h2>
        <p style="text-align: center; font-style: italic; color: var(--text-light);">
            Choisissez une classe ci-dessous pour générer son emploi du temps.
            <br>
            **Attention :** Ceci effacera l'emploi du temps généré précédemment *pour cette classe uniquement*.
            Les créneaux non occupés par des pré-affectations seront automatiquement affichés comme "TPE" ou "PAUSE" (à l'affichage de l'emploi du temps).
        </p>
        <form method="post" action="generer_emploi.php">
            <div class="form-group">
                <label for="classe_id">Classe (Groupe d'étudiants - issue de la table 'salles') :</label>
                <select id="classe_id" name="classe_id" required>
                    <option value="">Sélectionnez une classe</option>
                    <?php foreach ($classes_disponibles as $classe): ?>
                        <option value="<?php echo htmlspecialchars($classe['id']); ?>"
                            <?php echo (isset($pre_selected_classe_id) && $pre_selected_classe_id == $classe['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($classe['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="submit" name="generer_emploi_du_temps" value="Générer l'Emploi du Temps">
        </form>
    </div>

    <div class="button-container">
        <a href="dashboard.php" class="action-button button-retour">Retour au Tableau de Bord</a>
        <a href="visualiser_emploi.php" class="action-button button-view-timetable">Voir l'Emploi du Temps</a>
        <a href="pas_emploi.php" class="action-button button-pre-affectations">Gérer les Pré-affectations</a>
    </div>

</body>
</html>