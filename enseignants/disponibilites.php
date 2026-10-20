<?php
session_start();
require_once '../db.php'; // Assurez-vous que le chemin est correct pour votre fichier de connexion/config

// Vérifie que l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header("Location: ../login.php");
    exit;
}

$enseignant_id = $_SESSION['user_id'];
$nom_enseignant = $_SESSION['nom'];

// Les créneaux fixes (doivent correspondre à ceux définis dans votre configuration globale comme db.php)
// Le créneau de pause est retiré pour ne pas être sélectionnable par l'enseignant.
$creneauxFixes = [
    '08h00-12h00' => ['label' => 'Matin (08h00-12h00)'],
    // '12h00-13h30' => ['label' => 'Pause déjeuner (12h00-13h30)'], // Still commented out
    '13h30-17h30' => ['label' => 'Après-midi (13h30-17h30)'],
    // '18h00-21h00' => ['label' => 'Soir (18h00-21h00)'] - REMOVED
];

// Récupérer les jours de la semaine (doivent correspondre à ceux de votre configuration globale)
$joursSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];


$message = '';
$messageType = '';

// Traitement du formulaire de disponibilité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disponibilites'])) {
    $disponibilites_selectionnees = $_POST['disponibilites'] ?? [];

    // Vider les anciennes disponibilités de l'enseignant
    $delete_stmt = $pdo->prepare("DELETE FROM disponibilites WHERE enseignant_id = ?");
    $delete_stmt->execute([$enseignant_id]);

    // Insérer les nouvelles disponibilités
    $insert_stmt = $pdo->prepare("INSERT INTO disponibilites (enseignant_id, jour, creneau) VALUES (?, ?, ?)");

    foreach ($disponibilites_selectionnees as $jour_creneau) {
        list($jour, $creneau) = explode('|', $jour_creneau); // 'Lundi|08h00-12h00'

        // S'assurer que le jour et le créneau sont valides (par rapport aux créneaux affichés)
        if (in_array($jour, $joursSemaine) && array_key_exists($creneau, $creneauxFixes)) {
            $insert_stmt->execute([$enseignant_id, $jour, $creneau]);
        }
    }

    $message = "Vos disponibilités ont été mises à jour avec succès.";
    $messageType = "success";
}

// Récupérer les disponibilités actuelles de l'enseignant pour pré-cocher les cases
$current_disponibilites = [];
$query_dispo = $pdo->prepare("SELECT jour, creneau FROM disponibilites WHERE enseignant_id = ?");
$query_dispo->execute([$enseignant_id]);
while ($row = $query_dispo->fetch(PDO::FETCH_ASSOC)) {
    // We filter out any '18h00-21h00' entries if they were previously saved,
    // ensuring only currently available slots are pre-checked.
    if (array_key_exists($row['creneau'], $creneauxFixes)) {
        $current_disponibilites[] = $row['jour'] . '|' . $row['creneau'];
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Disponibilités - Enseignant</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Modern CSS Styling */
        :root {
            --primary-color: #4CAF50; /* A fresh green */
            --primary-dark: #388E3C;
            --primary-light: #81C784;
            --secondary-color: #2196F3; /* A vibrant blue */
            --secondary-dark: #1976D2;
            --bg-light: #e8f5e9; /* Lighter green background */
            --card-bg: #ffffff;
            --text-dark: #333333;
            --text-light: #666666;
            --border-color: #e0e0e0;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --success-color: #4CAF50;
            --error-color: #f44336;
            --button-hover-shadow: rgba(76, 175, 80, 0.4);
            --back-button-color: #757575;
            --back-button-hover: #616161;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            padding: 30px 20px;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
            background: linear-gradient(135deg, var(--bg-light) 0%, #c8e6c9 100%); /* Subtle gradient */
        }

        h1 {
            color: var(--primary-dark);
            margin-bottom: 30px;
            font-weight: 700; /* Bolder for impact */
            text-align: center;
            letter-spacing: 0.5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
        }

        h1 small {
            color: var(--text-light);
            font-weight: 400;
            display: block;
            margin-top: 5px;
            font-size: 0.6em; /* Smaller relative to H1 */
        }

        .container {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 15px; /* More rounded corners */
            box-shadow: 0 15px 40px var(--shadow-light); /* More pronounced shadow */
            max-width: 900px; /* Wider for better table display */
            width: 100%;
            margin-bottom: 40px;
            box-sizing: border-box;
            text-align: center;
            position: relative; /* For potential pseudo-elements */
            overflow: hidden; /* Ensures shadows and borders behave well */
        }

        /* Subtle container border animation */
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            animation: slideInTop 0.8s ease-out;
        }

        @keyframes slideInTop {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .message {
            padding: 15px 25px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-weight: 500;
            text-align: center; /* Centered message */
            animation: fadeInScale 0.6s forwards ease-out; /* New animation */
            word-break: break-word;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .message.success {
            background-color: #e6ffe6; /* Lighter green success */
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .message.error {
            background-color: #ffe6e6; /* Lighter red error */
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .dispo-table {
            width: 100%;
            border-collapse: separate; /* Allows border-radius on cells */
            border-spacing: 0; /* Remove space between borders */
            margin-top: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border-radius: 10px; /* Rounded corners for the whole table */
            overflow: hidden; /* Ensures content respects border-radius */
        }

        .dispo-table th, .dispo-table td {
            border: 1px solid var(--border-color);
            padding: 15px 10px;
            text-align: center;
            vertical-align: middle; /* Center content vertically */
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .dispo-table th {
            background-color: var(--primary-light); /* Lighter primary color for header */
            color: white; /* White text on header */
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-color: var(--primary-color);
        }
        
        .dispo-table tr:first-child th:first-child { border-top-left-radius: 10px; }
        .dispo-table tr:first-child th:last-child { border-top-right-radius: 10px; }
        .dispo-table tr:last-child td:first-child { border-bottom-left-radius: 10px; }
        .dispo-table tr:last-child td:last-child { border-bottom-right-radius: 10px; }


        .dispo-table td {
            background-color: var(--card-bg);
            color: var(--text-dark);
        }

        .dispo-table tr:hover td {
            background-color: #f0fdf0; /* Subtle hover effect on rows */
        }

        .dispo-table label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px; /* More space */
            cursor: pointer;
            font-size: 1em;
            color: var(--text-dark);
            height: 100%;
            user-select: none; /* Prevent text selection on label */
            padding: 5px; /* Add some padding for better click area */
        }

        .dispo-table input[type="checkbox"] {
            appearance: none; /* Hide default checkbox */
            -webkit-appearance: none;
            width: 22px; /* Custom size */
            height: 22px;
            border: 2px solid var(--primary-color);
            border-radius: 6px; /* Slightly rounded checkbox */
            position: relative;
            cursor: pointer;
            outline: none;
            transition: all 0.2s ease-in-out;
            flex-shrink: 0; /* Prevent shrinking */
        }

        .dispo-table input[type="checkbox"]:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transform: scale(1.1); /* Pop effect on check */
        }

        .dispo-table input[type="checkbox"]:checked::after {
            content: '✔'; /* Checkmark symbol */
            color: white;
            font-size: 14px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: checkboxCheck 0.2s ease-out;
        }

        @keyframes checkboxCheck {
            from { transform: translate(-50%, -50%) scale(0); opacity: 0; }
            to { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }

        .dispo-table input[type="checkbox"]:focus {
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.3); /* Focus ring */
        }

        input[type="submit"], .button-link {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(45deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px; /* More rounded buttons */
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
            box-shadow: 0 6px 20px var(--button-hover-shadow);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); /* Smoother transition */
            text-align: center;
            text-decoration: none;
            width: auto;
            min-width: 200px; /* Ensure minimum width */
        }

        input[type="submit"]:hover, .button-link:hover {
            transform: translateY(-3px); /* More pronounced lift */
            box-shadow: 0 10px 25px var(--button-hover-shadow);
            background: linear-gradient(45deg, var(--primary-dark), var(--primary-color)); /* Invert gradient on hover */
        }

        .nav-links {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px; /* Increased gap */
            justify-content: center;
            width: 100%;
        }

        .nav-links .button-link.back {
            background: linear-gradient(45deg, var(--back-button-color), var(--back-button-hover));
            box-shadow: 0 5px 15px rgba(117, 117, 117, 0.3);
        }
        .nav-links .button-link.back:hover {
            background: linear-gradient(45deg, var(--back-button-hover), var(--back-button-color));
            box-shadow: 0 7px 20px rgba(117, 117, 117, 0.4);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }
            h1 {
                font-size: 1.8em;
            }
            .container {
                padding: 20px;
                border-radius: 10px;
            }
            .dispo-table th, .dispo-table td {
                padding: 10px 5px;
                font-size: 0.9em;
            }
            .dispo-table label {
                font-size: 0.85em;
                gap: 5px;
            }
            .dispo-table input[type="checkbox"] {
                width: 18px;
                height: 18px;
            }
            .dispo-table input[type="checkbox"]:checked::after {
                font-size: 12px;
            }
            input[type="submit"], .button-link {
                padding: 12px 20px;
                font-size: 1em;
                min-width: unset; /* Remove min-width on small screens */
                width: 100%; /* Full width buttons */
                margin-top: 20px;
            }
            .nav-links {
                flex-direction: column; /* Stack buttons vertically */
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.5em;
            }
            .dispo-table {
                font-size: 0.8em;
            }
            .dispo-table th, .dispo-table td {
                padding: 8px 3px;
            }
            .dispo-table label {
                font-size: 0.75em;
            }
        }
    </style>
</head>
<body>

    <h1>Définir mes Disponibilités <br><small>(<?= htmlspecialchars($nom_enseignant) ?>)</small></h1>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <table class="dispo-table">
                <thead>
                    <tr>
                        <th>Jour / Créneau</th>
                        <?php foreach ($creneauxFixes as $creneau_key => $creneau_info): ?>
                            <th><?= htmlspecialchars($creneau_info['label']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($joursSemaine as $jour): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($jour) ?></strong></td>
                            <?php foreach ($creneauxFixes as $creneau_key => $creneau_info): ?>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="disponibilites[]"
                                               value="<?= htmlspecialchars($jour . '|' . $creneau_key) ?>"
                                               <?= in_array($jour . '|' . $creneau_key, $current_disponibilites) ? 'checked' : '' ?>>
                                        <span></span> </label>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <input type="submit" value="Enregistrer les disponibilités">
        </form>

        <div class="nav-links">
            <a href="dashboard_enseignants.php" class="button-link back">Retour au tableau de bord</a>
        </div>
    </div>

</body>
</html>