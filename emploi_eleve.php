<?php
// emploi_eleve.php

session_start(); // Démarre la session pour accéder aux variables de session

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: login.php"); // Redirige vers la page de connexion
    exit;
}

// Récupérer l'ID de la classe depuis la session
$selected_classe_id = $_SESSION['classe_id'] ?? null;
$etudiant_id = $_SESSION['user_id'] ?? null; // Récupérer l'ID de l'étudiant

// Vérifier si un ID de classe est disponible dans la session
if (!$selected_classe_id || !$etudiant_id) { // Ajout de la vérification de l'ID étudiant
    $_SESSION['erreur_login'] = "Votre classe ou votre identifiant n'a pas pu être identifié. Veuillez vous reconnecter.";
    header("Location: login.php");
    exit;
}

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

// Définition des jours et créneaux horaires (comme dans visualiser_emploi.php)
// CORRIGÉ : 'Dimanche' a été supprimé
$jours_semaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

$creneaux_horaires = [
    '08h00-12h00' => ['label' => '08h00 - 12h00', 'duration' => '4h'],
    '12h00-13h30' => ['label' => '12h00 - 13h30 (pause)', 'duration' => '1h30'],
    '13h30-17h30' => ['label' => '13h30 - 17h30', 'duration' => '4h'],
];

$emploi_du_temps_classe = [];
$classe_nom_affiche = "Classe Inconnue"; // Valeur par défaut

// Récupérer le nom de la classe sélectionnée (celle de l'étudiant)
$stmt_classe_nom = $conn->prepare("SELECT nom FROM salles WHERE id = ?");
$stmt_classe_nom->execute([$selected_classe_id]);
$classe_info = $stmt_classe_nom->fetch();
if ($classe_info) {
    $classe_nom_affiche = $classe_info['nom'];
}

// Récupérer l'emploi du temps pour la classe de l'étudiant
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
        AND edt.jour IN ('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi') -- Ajout de cette condition pour être sûr
    ORDER BY
        FIELD(edt.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), -- CORRIGÉ ici aussi
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Emploi du Temps - <?php echo htmlspecialchars($classe_nom_affiche); ?></title>
    <style>
        /* Votre CSS existant... */
        :root {
            --primary-color: #4CAF50; /* Vert un peu plus doux */
            --secondary-color: #607D8B; /* Gris bleu pour les créneaux */
            --background-light: #e8f5e9; /* Arrière-plan vert très clair */
            --text-dark: #333;
            --text-light: #555;
            --border-color: #ddd;
            --box-shadow: rgba(0, 0, 0, 0.15);
            --pause-color: #FFC107; /* Jaune pour la pause */
            --tpe-color: #DCEDC8; /* Vert très clair pour TPE */
            --header-bg: #4CAF50;
            --header-text: white;
            --logout-button-bg: #f44336;
            --logout-button-hover: #d32f2f;
            --profile-button-bg: #2196F3; /* Nouveau: bleu pour le profil */
            --profile-button-hover: #1976D2;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: var(--background-light);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        header {
            width: 100%;
            background-color: var(--header-bg);
            color: var(--header-text);
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
        }

        header h1 {
            margin: 0;
            font-size: 2.8em;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }

        .header-actions { /* Nouveau conteneur pour les boutons */
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex; /* Permet d'aligner les boutons côte à côte */
            gap: 10px; /* Espace entre les boutons */
        }

        .action-button { /* Style générique pour les boutons d'action */
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            transition: background-color 0.3s ease;
            color: var(--header-text); /* Couleur du texte par défaut pour les boutons d'action */
        }

        .logout-button {
            background-color: var(--logout-button-bg);
        }

        .logout-button:hover {
            background-color: var(--logout-button-hover);
        }

        .profile-button { /* Nouveau style pour le bouton de profil */
            background-color: var(--profile-button-bg);
        }

        .profile-button:hover {
            background-color: var(--profile-button-hover);
        }


        .container {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px var(--box-shadow);
            width: 100%;
            max-width: 1300px;
            margin-top: 30px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 2em;
            text-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        th, td {
            border: 1px solid var(--border-color);
            padding: 14px 10px;
            text-align: center;
            vertical-align: top;
            font-size: 1em;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            padding: 18px 10px;
            font-size: 1.1em;
        }

        td {
            background-color: #ffffff;
        }

        .creneau-horaire {
            background-color: var(--secondary-color);
            color: white;
            font-weight: bold;
            text-align: left;
            padding-left: 20px;
            font-size: 1.1em;
            vertical-align: middle;
        }

        .pause-slot {
            background-color: var(--pause-color);
            color: var(--text-dark);
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            font-size: 1.2em;
            letter-spacing: 1px;
        }

        .slot-content {
            padding: 8px;
            border-radius: 8px;
            margin: 4px;
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #f7fcf7;
            border: 1px solid #e0f2e0;
        }

        .slot-content p {
            margin: 0;
            line-height: 1.4;
        }

        .slot-content .matiere {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.2em;
            margin-bottom: 5px;
        }

        .slot-content .details {
            font-size: 0.95em;
            color: var(--text-light);
        }

        .no-slot {
            background-color: var(--tpe-color);
            color: #4CAF50;
            font-weight: bold;
            font-size: 1em;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            border: 1px dashed #A5D6A7;
        }

        /* Responsive design */
        @media (max-width: 900px) {
            .container {
                padding: 20px;
            }
            th, td {
                padding: 10px 5px;
                font-size: 0.85em;
            }
            .creneau-horaire {
                padding-left: 10px;
                font-size: 1em;
            }
            .slot-content {
                min-height: 70px;
            }
            header h1 {
                font-size: 2em;
            }
            h2 {
                font-size: 1.6em;
            }
            .header-actions {
                top: 10px;
                right: 10px;
                gap: 5px;
            }
            .action-button {
                padding: 8px 12px;
                font-size: 0.9em;
            }
        }

        @media (max-width: 600px) {
            body {
                padding: 0;
            }
            .container {
                padding: 15px;
                margin-top: 20px;
            }
            table {
                font-size: 0.8em;
            }
            th, td {
                padding: 8px 3px;
            }
            .creneau-horaire {
                font-size: 0.9em;
                padding-left: 5px;
            }
            .slot-content {
                min-height: 60px;
                padding: 5px;
            }
            .slot-content .matiere {
                font-size: 1em;
            }
            .slot-content .details {
                font-size: 0.8em;
            }
            header h1 {
                font-size: 1.8em;
                padding-right: 0; /* Réinitialiser pour éviter le décalage */
            }
            .header-actions {
                position: static; /* Supprime le positionnement absolu */
                display: flex;
                flex-direction: column; /* Empile les boutons */
                align-items: center;
                gap: 5px;
                margin-top: 10px;
            }
            .action-button {
                width: calc(100% - 20px); /* Ajuste la largeur */
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Mon Emploi du Temps</h1>
        <div class="header-actions">
            <a href="modifier_profil_eleve.php" class="action-button profile-button">Modifier mon profil</a>
            <a href="login.php" class="action-button logout-button">Déconnexion</a>
        </div>
    </header>

    <div class="container">
        <h2 style="text-align: center; color: var(--primary-color); margin-bottom: 20px; font-size: 2em;">
            Emploi du Temps de la classe : <?php echo htmlspecialchars($classe_nom_affiche); ?>
        </h2>

        <?php if (empty($emploi_du_temps_classe)): ?>
            <p style="text-align: center; padding: 20px; background-color: #ffe0b2; border-radius: 8px; color: #e65100; font-weight: bold;">
                L'emploi du temps n'est pas encore disponible pour votre classe ou n'a pas été généré.
                Veuillez contacter l'administration de l'établissement.
            </p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Plage Horaire</th>
                        <?php foreach ($jours_semaine as $jour): // Ici, $jours_semaine est déjà sans Dimanche ?>
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
                                    <?php break; ?>
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

</body>
</html>