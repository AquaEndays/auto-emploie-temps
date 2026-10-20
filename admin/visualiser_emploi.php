<?php
// visualiser_emploi.php

// 1. Configuration de la base de données
$host = 'localhost';
$db   = 'gilles_le_magnifique';
$user = 'root';
$pass = 'root'; // Assurez-vous que ce mot de passe est correct
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$conn = null;
try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Définition des jours et créneaux horaires
$jours_semaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

$creneaux_horaires = [
    '08h00-12h00' => ['label' => '08h00 - 12h00', 'duration' => '4h'],
    '12h00-13h30' => ['label' => '12h00 - 13h30 (pause)', 'duration' => '1h30'],
    '13h30-17h30' => ['label' => '13h30 - 17h30', 'duration' => '4h'],
    // La ligne suivante a été supprimée pour enlever le créneau 18h00-21h00
    // '18h00-21h00' => ['label' => '18h00 - 21h00', 'duration' => '3h'],
];

// Récupérer la liste des classes (depuis la table 'salles')
$classes = $conn->query("SELECT id, nom FROM salles ORDER BY nom")->fetchAll();

$selected_classe_id = $_GET['classe_id'] ?? ($classes[0]['id'] ?? null); // Sélectionnez la première classe par défaut

$emploi_du_temps_classe = [];
$classe_nom_affiche = "Aucune Classe Sélectionnée";

if ($selected_classe_id) {
    // Récupérer le nom de la classe sélectionnée
    $stmt_classe_nom = $conn->prepare("SELECT nom FROM salles WHERE id = ?");
    $stmt_classe_nom->execute([$selected_classe_id]);
    $classe_info = $stmt_classe_nom->fetch();
    if ($classe_info) {
        $classe_nom_affiche = $classe_info['nom'];
    }

    // Récupérer l'emploi du temps pour la classe sélectionnée
    // Utilisation de JOIN, car nous ne voulons récupérer que les créneaux affectés à des cours
    $stmt_emploi = $conn->prepare("
        SELECT
            edt.jour,
            edt.creneau,
            e.nom AS enseignant_nom,
            m.nom AS matiere_nom,
            sp.nom AS salle_physique_nom
        FROM
            emploi_du_temps edt
        JOIN
            enseignants e ON edt.enseignant_id = e.id
        JOIN
            matieres m ON edt.matiere_id = m.id
        JOIN
            salles_physiques sp ON edt.salle_physique_id = sp.id
        WHERE
            edt.classe_id = ?
        ORDER BY
            FIELD(edt.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'),
            edt.heure_debut
    ");
    $stmt_emploi->execute([$selected_classe_id]);
    $resultats_raw = $stmt_emploi->fetchAll();

    // Organiser les résultats pour l'affichage dans le tableau
    foreach ($resultats_raw as $slot) {
        $emploi_du_temps_classe[$slot['jour']][$slot['creneau']] = [
            'enseignant' => $slot['enseignant_nom'],
            'matiere' => $slot['matiere_nom'],
            'salle' => $slot['salle_physique_nom']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualiser l'Emploi du Temps</title>
    <style>
        /* Le CSS est largement repris des fichiers précédents pour la cohérence */
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
            max-width: 1200px; /* Plus large pour l'emploi du temps */
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group label {
            font-weight: bold;
            margin-right: 10px;
            font-size: 1.2em; /* Rendre le label plus grand */
            color: var(--primary-color); /* Couleur distinctive pour le label */
        }
        .form-group select {
            padding: 12px 18px; /* Plus de padding pour un look plus grand */
            border: 2px solid var(--primary-color); /* Bordure plus épaisse et colorée */
            border-radius: 8px; /* Bordure plus arrondie */
            font-size: 1.1em; /* Police plus grande */
            width: auto; /* Laisser la largeur s'adapter au contenu */
            max-width: 80%; /* S'assurer qu'il ne dépasse pas trop sur mobile */
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #e9f5ff; /* Léger fond bleu clair */
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
        }
        .form-group select:hover {
            border-color: #0056b3;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        .form-group select:focus {
            outline: none;
            border-color: #0056b3;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.5);
        }


        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid var(--border-color);
            padding: 12px 8px;
            text-align: center;
            vertical-align: top;
            font-size: 0.9em;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            padding: 15px 8px;
        }

        td {
            background-color: #fefefe;
        }

        .creneau-horaire {
            background-color: var(--secondary-color);
            color: white;
            font-weight: bold;
            text-align: left;
            padding-left: 15px;
        }

        .pause-slot {
            background-color: #ffc107; /* Jaune pour la pause */
            color: var(--text-dark);
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .slot-content {
            padding: 5px;
            border-radius: 5px;
            margin: 3px;
            min-height: 80px; /* Pour une meilleure visibilité des cellules vides */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .slot-content p {
            margin: 0;
            line-height: 1.3;
        }

        .slot-content .matiere {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.1em;
            margin-bottom: 5px;
        }

        .slot-content .details {
            font-size: 0.85em;
            color: var(--text-light);
        }

        .no-slot {
            background-color: #d4edda; /* Vert clair, comme un slot TPE */
            color: #155724;
            font-weight: bold;
        }

        .button-container { /* Style pour contenir tous les boutons */
            margin-top: 30px;
            text-align: center;
            padding: 20px;
        }

        .action-button { /* Style générique pour les boutons d'action */
            display: inline-block;
            padding: 12px 25px;
            margin: 0 10px; /* Ajoute un espace entre les boutons */
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .button-pre-affectations { /* Bouton pour revenir aux pré-affectations */
            background-color: #17a2b8; /* Bleu clair pour l'info/pré-affectations */
            color: white;
        }
        .button-pre-affectations:hover {
            background-color: #138496;
            transform: translateY(-2px);
        }

        .button-generer-emploi { /* Nouveau bouton pour la génération */
            background-color: #28a745; /* Vert pour la génération */
            color: white;
        }
        .button-generer-emploi:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <h1>Emploi du Temps pour la Classe : <?php echo htmlspecialchars($classe_nom_affiche); ?></h1>

    <div class="container">
        <div class="form-group">
            <label for="classe_selector">Sélectionner une Classe :</label>
            <select id="classe_selector" onchange="window.location.href = 'visualiser_emploi.php?classe_id=' + this.value;">
                <?php foreach ($classes as $classe): ?>
                    <option value="<?php echo htmlspecialchars($classe['id']); ?>"
                        <?php echo ($selected_classe_id == $classe['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($classe['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (empty($emploi_du_temps_classe) && $selected_classe_id): ?>
            <p style="text-align: center; padding: 20px; background-color: #e9ecef; border-radius: 8px; color: var(--text-dark);">
                Aucun emploi du temps n'a été généré pour cette classe, ou aucun cours n'a pu être affecté.
                Veuillez vous assurer que des pré-affectations existent et que la génération a été lancée.
            </p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Plage Horaire</th>
                        <?php foreach ($jours_semaine as $jour): ?>
                            <th><?php echo htmlspecialchars($jour); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($creneaux_horaires as $creneau_value => $creneau_info): ?>
                        <tr>
                            <td class="creneau-horaire">
                                <?php echo htmlspecialchars($creneau_info['label']); ?>
                                <br><small>(<?php echo htmlspecialchars($creneau_info['duration']); ?>)</small>
                            </td>
                            <?php foreach ($jours_semaine as $jour): ?>
                                <?php if ($creneau_value === '12h00-13h30'): ?>
                                    <td class="pause-slot" colspan="<?php echo count($jours_semaine); ?>">PAUSE</td>
                                    <?php break; // Sortir de la boucle des jours pour le créneau de pause ?>
                                <?php else: ?>
                                    <td>
                                        <?php if (isset($emploi_du_temps_classe[$jour][$creneau_value])): ?>
                                            <div class="slot-content">
                                                <p class="matiere"><?php echo htmlspecialchars($emploi_du_temps_classe[$jour][$creneau_value]['matiere']); ?></p>
                                                <p class="details">Enseignant: <?php echo htmlspecialchars($emploi_du_temps_classe[$jour][$creneau_value]['enseignant']); ?></p>
                                                <p class="details">Salle: <?php echo htmlspecialchars($emploi_du_temps_classe[$jour][$creneau_value]['salle']); ?></p>
                                            </div>
                                        <?php else: ?>
                                            <div class="slot-content no-slot">
                                                TPE
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="button-container">
        <a href="generer_emploi.php" class="action-button button-generer-emploi">Retour à la generation</a>
        <a href="pas_emploi.php" class="action-button button-pre-affectations">Gérer les Pré-affectations</a>
    </div>

    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bouton Stylisé</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f0f2f5; /* Arrière-plan doux */
            margin: 0;
            padding: 20px;
        }

        .stylish-button {
            /* Styles de base du bouton */
            display: inline-flex; /* Permet l'alignement de l'icône et du texte */
            align-items: center; /* Centre verticalement l'icône et le texte */
            gap: 10px; /* Espace entre l'icône et le texte */
            padding: 15px 30px; /* Espacement interne */
            border: none; /* Pas de bordure */
            border-radius: 8px; /* Coins légèrement arrondis */
            cursor: pointer; /* Curseur pointeur au survol */
            font-size: 1.1em; /* Taille de police */
            font-weight: 600; /* Poids de la police */
            text-decoration: none; /* Pas de soulignement pour les liens */
            color: #ffffff; /* Couleur du texte */
            white-space: nowrap; /* Empêche le texte de passer à la ligne */

            /* Dégradé de fond */
            background-image: linear-gradient(to right, #4CAF50 0%, #66BB6A 100%); /* Vert clair à vert plus foncé */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Ombre portée douce */
            transition: all 0.3s ease; /* Transition douce pour les effets de survol */
        }

        .stylish-button:hover {
            /* Effets au survol */
            background-image: linear-gradient(to right, #66BB6A 0%, #4CAF50 100%); /* Inverse le dégradé */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); /* Ombre plus prononcée */
            transform: translateY(-3px); /* Élève légèrement le bouton */
        }

        .stylish-button:active {
            /* Effet au clic */
            transform: translateY(0); /* Retourne à la position initiale */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Ombre réduite pour simuler l'enfoncement */
        }

        /* Styles pour l'icône */
        .stylish-button i {
            font-size: 1.2em; /* Taille de l'icône */
            /* Pour s'assurer que l'icône est bien alignée si sa taille varie */
            vertical-align: middle;
        }

        /* Exemple de variation de couleur pour un bouton d'action différente (si vous en voulez d'autres) */
        .stylish-button.danger {
            background-image: linear-gradient(to right, #EF5350 0%, #E57373 100%);
        }
        .stylish-button.danger:hover {
            background-image: linear-gradient(to right, #E57373 0%, #EF5350 100%);
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="stylish-button">
        <i class="fas fa-arrow-left"></i> Retour au Tableau de Bord
    </a>

    </body>
</html>

</body>
</html>