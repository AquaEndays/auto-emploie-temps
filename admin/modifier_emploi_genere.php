<?php
// modifier_emploi_genere.php

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

$emploi_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$current_emploi = null;

// Définir les jours de la semaine et les créneaux horaires
$jours_semaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$plages_horaires = [
    '08h00 - 12h00',
    '13h30 - 17h30',
    '18h00 - 21h00'
];

// Récupérer les listes pour les sélecteurs
$enseignants = $conn->query("SELECT id, nom FROM enseignants ORDER BY nom ASC")->fetchAll();
$matieres = $conn->query("SELECT id, nom FROM matieres ORDER BY nom ASC")->fetchAll();
$salles = $conn->query("SELECT id, nom FROM salles ORDER BY nom ASC")->fetchAll();


if ($emploi_id > 0) {
    try {
        // Récupérer les données de l'emploi du temps à modifier
        $stmt = $conn->prepare("
            SELECT
                eg.enseignant_id, eg.matiere_id, eg.salle_id, eg.jour, eg.creneau,
                e.nom AS nom_enseignant, m.nom AS nom_matiere, s.nom AS nom_salle
            FROM
                emplois_generes eg
            JOIN enseignants e ON eg.enseignant_id = e.id
            JOIN matieres m ON eg.matiere_id = m.id
            JOIN salles s ON eg.salle_id = s.id
            WHERE eg.id = ?
        ");
        $stmt->execute([$emploi_id]);
        $current_emploi = $stmt->fetch();

        if (!$current_emploi) {
            $message = "Entrée d'emploi du temps non trouvée.";
            $messageType = "error";
            $emploi_id = 0;
        }
    } catch (\PDOException $e) {
        $message = "Erreur lors du chargement de l'entrée : " . $e->getMessage();
        $messageType = "error";
    }
} else {
    $message = "ID d'emploi du temps invalide.";
    $messageType = "error";
}

// Traitement de la mise à jour
if (isset($_POST['update_emploi_genere']) && $emploi_id > 0) {
    $enseignant_id = filter_input(INPUT_POST, 'enseignant_id', FILTER_VALIDATE_INT);
    $matiere_id = filter_input(INPUT_POST, 'matiere_id', FILTER_VALIDATE_INT);
    $salle_id = filter_input(INPUT_POST, 'salle_id', FILTER_VALIDATE_INT);
    $jour = filter_input(INPUT_POST, 'jour', FILTER_SANITIZE_STRING);
    $creneau = filter_input(INPUT_POST, 'creneau', FILTER_SANITIZE_STRING);

    if ($enseignant_id && $matiere_id && $salle_id && $jour && $creneau) {
        try {
            // Vérifier les conflits avant la mise à jour
            $conflict = false;
            // Conflit enseignant
            $stmt_check_enseignant = $conn->prepare("SELECT COUNT(*) FROM emplois_generes WHERE enseignant_id = ? AND jour = ? AND creneau = ? AND id != ?");
            $stmt_check_enseignant->execute([$enseignant_id, $jour, $creneau, $emploi_id]);
            if ($stmt_check_enseignant->fetchColumn() > 0) {
                $message = "Conflit : L'enseignant est déjà assigné à ce créneau le même jour.";
                $messageType = "error";
                $conflict = true;
            }

            // Conflit salle (si pas déjà de conflit enseignant)
            if (!$conflict) {
                $stmt_check_salle = $conn->prepare("SELECT COUNT(*) FROM emplois_generes WHERE salle_id = ? AND jour = ? AND creneau = ? AND id != ?");
                $stmt_check_salle->execute([$salle_id, $jour, $creneau, $emploi_id]);
                if ($stmt_check_salle->fetchColumn() > 0) {
                    $message = "Conflit : La salle est déjà occupée à ce créneau le même jour.";
                    $messageType = "error";
                    $conflict = true;
                }
            }


            if (!$conflict) {
                // Mettre à jour l'entrée
                $stmt = $conn->prepare(
                    "UPDATE emplois_generes SET enseignant_id = ?, matiere_id = ?, salle_id = ?, jour = ?, creneau = ? WHERE id = ?"
                );
                if ($stmt->execute([$enseignant_id, $matiere_id, $salle_id, $jour, $creneau, $emploi_id])) {
                    $message = "Emploi du temps mis à jour avec succès !";
                    $messageType = "success";
                    header("Location: visualiser_emploi.php?message=" . urlencode($message) . "&type=" . urlencode($messageType));
                    exit();
                } else {
                    $message = "Erreur lors de la mise à jour de l'emploi du temps.";
                    $messageType = "error";
                }
            }
        } catch (\PDOException $e) {
            $message = "Erreur SQL lors de la mise à jour : " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
        $messageType = "error";
    }
}

// Gérer les messages de redirection
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Emploi du Temps</title>
    <style>
        /* Réutiliser le CSS des autres pages */
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
            max-width: 600px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-dark);
            font-size: 1.05em;
        }

        select, input[type="text"], input[type="submit"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1em;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #fff;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23007bff%22%20d%3D%22M287%2C197.3L159.2%2C69.5c-4.4-4.4-11.4-4.4-15.8%2C0L5.4%2C197.3c-4.4%2C4.4-4.4%2C11.4%2C0%2C15.8c4.4%2C4.4%2C11.4%2C4.4%2C15.8%2C0l130-130l130%2C130c4.4%2C4.4%2C11.4%2C4.4%2C15.8%2C0C291.4%2C208.7%2C291.4%2C201.7%2C287%2C197.3z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 15px top 50%;
            background-size: 10px;
        }
        select:focus, input[type="text"]:focus {
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
            margin-top: 20px;
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
    </style>
</head>
<body>

    <h1>Modifier un Emploi du Temps Généré</h1>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($current_emploi): ?>
            <form method="post" action="modifier_emploi_genere.php?id=<?php echo htmlspecialchars($emploi_id); ?>">
                <div class="form-group">
                    <label for="enseignant_id">Enseignant :</label>
                    <select id="enseignant_id" name="enseignant_id" required>
                        <?php foreach ($enseignants as $enseignant): ?>
                            <option value="<?php echo htmlspecialchars($enseignant['id']); ?>"
                                <?php echo ($enseignant['id'] == $current_emploi['enseignant_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($enseignant['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="matiere_id">Matière :</label>
                    <select id="matiere_id" name="matiere_id" required>
                        <?php foreach ($matieres as $matiere): ?>
                            <option value="<?php echo htmlspecialchars($matiere['id']); ?>"
                                <?php echo ($matiere['id'] == $current_emploi['matiere_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($matiere['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="salle_id">Salle :</label>
                    <select id="salle_id" name="salle_id" required>
                        <?php foreach ($salles as $salle): ?>
                            <option value="<?php echo htmlspecialchars($salle['id']); ?>"
                                <?php echo ($salle['id'] == $current_emploi['salle_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($salle['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jour">Jour :</label>
                    <select id="jour" name="jour" required>
                        <?php foreach ($jours_semaine as $jour_option): ?>
                            <option value="<?php echo htmlspecialchars($jour_option); ?>"
                                <?php echo ($jour_option == $current_emploi['jour']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($jour_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="creneau">Créneau :</label>
                    <select id="creneau" name="creneau" required>
                        <?php foreach ($plages_horaires as $creneau_option): ?>
                            <option value="<?php echo htmlspecialchars($creneau_option); ?>"
                                <?php echo ($creneau_option == $current_emploi['creneau']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($creneau_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="submit" name="update_emploi_genere" value="Mettre à jour l'Emploi du Temps">
            </form>
        <?php endif; ?>
    </div>

    <div class="button-container">
        <a href="visualiser_emploi.php" class="action-button button-retour">Retour à l'Emploi du Temps</a>
        <a href="dashboard.php" class="action-button button-retour">Retour au Tableau de Bord</a>
    </div>

</body>
</html>