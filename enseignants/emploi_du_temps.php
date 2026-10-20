<?php
// enseignants/dashboard_enseignants.php

session_start(); // Démarre la session

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'enseignant') {
    // Si l'utilisateur n'est pas connecté ou n'est pas un enseignant,
    // le rediriger vers la page de connexion.
    header("Location: ../login.php"); // Remonter d'un répertoire pour atteindre login.php
    exit;
}

// Récupérer l'ID de l'enseignant connecté depuis la session
$enseignant_id = $_SESSION['user_id'];
$enseignant_nom = $_SESSION['nom'];

// 1. Configuration de la base de données
// Assurez-vous que ces informations de connexion sont correctes pour votre environnement
$host = 'localhost';
$db   = 'gilles_le_magnifique';
$user = 'root';
$pass = 'root'; // Utilisez le mot de passe correct pour votre base de données
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

// Définition des jours et créneaux horaires (sans dimanche)
$jours_semaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

$creneaux_horaires = [
    '08h00-12h00' => ['label' => '08h00 - 12h00', 'duration' => '4h'],
    '12h00-13h30' => ['label' => '12h00 - 13h30 (pause)', 'duration' => '1h30'],
    '13h30-17h30' => ['label' => '13h30 - 17h30', 'duration' => '4h'],
];

$emploi_du_temps_enseignant = [];

// Récupérer l'emploi du temps pour cet enseignant spécifique
$stmt_emploi = $conn->prepare("
    SELECT
        edt.jour,
        edt.creneau,
        m.nom AS matiere_nom,
        sp.nom AS salle_physique_nom,
        s.nom AS classe_nom
    FROM
        emploi_du_temps edt
    JOIN
        matieres m ON edt.matiere_id = m.id
    JOIN
        salles_physiques sp ON edt.salle_physique_id = sp.id
    JOIN
        salles s ON edt.classe_id = s.id
    WHERE
        edt.enseignant_id = ?
        AND edt.jour IN ('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi')
    ORDER BY
        FIELD(edt.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'),
        edt.heure_debut
");
$stmt_emploi->execute([$enseignant_id]);
$resultats_raw = $stmt_emploi->fetchAll();

// Organiser les résultats pour l'affichage dans le tableau
foreach ($resultats_raw as $slot) {
    $emploi_du_temps_enseignant[$slot['jour']][$slot['creneau']] = [
        'matiere' => $slot['matiere_nom'],
        'salle' => $slot['salle_physique_nom'],
        'classe' => $slot['classe_nom']
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Emploi du Temps - <?php echo htmlspecialchars($enseignant_nom); ?></title>
    <style>
        /* CSS existant pour le dashboard... */
        :root {
            --primary-color: #2196F3; /* Bleu pour les enseignants */
            --secondary-color: #78909C; /* Gris bleu foncé pour les créneaux */
            --background-light: #e3f2fd; /* Arrière-plan bleu très clair */
            --text-dark: #263238;
            --text-light: #455A64;
            --border-color: #BBDEFB;
            --box-shadow: rgba(0, 0, 0, 0.15);
            --pause-color: #FFC107; /* Jaune pour la pause */
            --slot-bg-color: #E3F2FD; /* Bleu très clair pour les slots */
            --slot-border-color: #90CAF9; /* Bleu clair pour les bords des slots */
            --header-bg: #2196F3;
            --header-text: white;
            --logout-button-bg: #f44336;
            --logout-button-hover: #d32f2f;

            /* NOUVELLES VARIABLES POUR LE BOUTON RETOUR */
            --back-button-bg: #607D8B; /* Gris bleu pour le bouton retour */
            --back-button-hover-bg: #455A64; /* Gris bleu plus foncé au survol */
            --back-button-text: white;
            --back-button-shadow: rgba(0, 0, 0, 0.2);
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

        .header-buttons { /* Conteneur pour les boutons en haut */
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex; /* Permet d'aligner les boutons côte à côte */
            gap: 10px; /* Espace entre les boutons */
        }

        .logout-button, .back-button {
            background-color: var(--logout-button-bg); /* Couleur par défaut pour logout */
            color: var(--header-text);
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            display: inline-flex; /* Pour centrer le texte/icône si besoin */
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px var(--back-button-shadow);
        }

        .logout-button:hover {
            background-color: var(--logout-button-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        /* NOUVEAUX STYLES POUR LE BOUTON RETOUR */
        .back-button {
            background-color: var(--back-button-bg); /* Couleur spécifique pour retour */
            left: 20px; /* Positionné à gauche */
            right: auto; /* Annule le right: 20px du conteneur parent */
        }

        .back-button:hover {
            background-color: var(--back-button-hover-bg);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .back-button::before { /* Pour une icône de flèche simple (vous pouvez utiliser des icônes réelles comme Font Awesome) */
            content: '←'; /* Caractère de flèche gauche */
            margin-right: 8px;
            font-weight: bold;
            font-size: 1.2em;
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
            background-color: var(--slot-bg-color);
            border: 1px solid var(--slot-border-color);
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
            background-color: #E0F7FA;
            color: #00BCD4;
            font-weight: bold;
            font-size: 1em;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            border: 1px dashed #B2EBF2;
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
            .header-buttons {
                flex-direction: column; /* Empile les boutons sur petits écrans */
                align-items: flex-end;
                gap: 5px;
                top: 10px;
                right: 10px;
            }
            .logout-button, .back-button {
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
                padding-right: 0; /* Pas de padding pour éviter le chevauchement */
            }
            .header-buttons {
                top: 5px;
                right: 5px;
                position: static; /* Supprime le positionnement absolu pour laisser le flux normal */
                width: 100%;
                justify-content: center; /* Centre les boutons */
                margin-top: 10px; /* Ajoute un peu d'espace */
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Mon Emploi du Temps</h1>
        <div class="header-buttons">
            <a href="dashboard_enseignants.php" class="back-button">Retour</a>
            <a href="../logout.php" class="logout-button">Déconnexion</a>
        </div>
    </header>

    <div class="container">
        <h2 style="text-align: center; color: var(--primary-color); margin-bottom: 20px; font-size: 2em;">
            Emploi du Temps de <?php echo htmlspecialchars($_SESSION['nom']); ?>
        </h2>

        <?php if (empty($emploi_du_temps_enseignant)): ?>
            <p style="text-align: center; padding: 20px; background-color: #ffe0b2; border-radius: 8px; color: #e65100; font-weight: bold;">
                Aucun cours n'est actuellement assigné à votre emploi du temps.
                Veuillez contacter l'administration.
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
                                    <?php break; ?>
                                <?php else: ?>
                                    <td>
                                        <?php if (isset($emploi_du_temps_enseignant[$jour][$creneau_value])): ?>
                                            <div class="slot-content">
                                                <p class="matiere"><?php echo htmlspecialchars($emploi_du_temps_enseignant[$jour][$creneau_value]['matiere']); ?></p>
                                                <p class="details">Classe: <?php echo htmlspecialchars($emploi_du_temps_enseignant[$jour][$creneau_value]['classe']); ?></p>
                                                <p class="details">Salle: <?php echo htmlspecialchars($emploi_du_temps_enseignant[$jour][$creneau_value]['salle']); ?></p>
                                            </div>
                                        <?php else: ?>
                                            <div class="slot-content no-slot">
                                                Libre
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