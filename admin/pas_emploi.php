<?php
// pas_emploi.php

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

$message = '';
$messageType = '';
$conn = null;

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Il est préférable de ne pas exposer directement le message d'erreur de la DB en production
    // et d'enregistrer l'erreur pour le débogage.
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Impossible de se connecter à la base de données. Veuillez réessayer plus tard.");
}

// Récupérer les données pour les listes déroulantes
// Assurez-vous que les tables 'enseignants', 'matieres', 'salles', 'salles_physiques' existent et sont remplies.
$enseignants = $conn->query("SELECT id, nom FROM enseignants ORDER BY nom")->fetchAll();
$matieres = $conn->query("SELECT id, nom FROM matieres ORDER BY nom")->fetchAll();
$classes = $conn->query("SELECT id, nom FROM salles ORDER BY nom")->fetchAll(); // 'salles' pour les classes
$salles_physiques = $conn->query("SELECT id, nom FROM salles_physiques ORDER BY nom")->fetchAll(); // 'salles_physiques' pour les lieux réels

// Traitement du formulaire d'ajout/modification
if (isset($_POST['submit_affectation'])) {
    $enseignant_id     = $_POST['enseignant_id'] ?? null;
    $matiere_id        = $_POST['matiere_id'] ?? null;
    $salle_physique_id = $_POST['salle_physique_id'] ?? null;
    $classe_id         = $_POST['classe_id'] ?? null;
    $affectation_id    = $_POST['affectation_id'] ?? null; // C'est l'ID de la pré-affectation à modifier

    // Validation des entrées : tous les champs sont requis
    if ($enseignant_id && $matiere_id && $salle_physique_id && $classe_id) {
        try {
            if ($affectation_id != '') {
                // Modification d'une pré-affectation existante
                $stmt = $conn->prepare("UPDATE donnees_emploi SET enseignant_id = ?, matiere_id = ?, salle_physique_id = ?, salle_id = ? WHERE id = ?");
                if ($stmt->execute([$enseignant_id, $matiere_id, $salle_physique_id, $classe_id, $affectation_id])) {
                    $message = "Pré-affectation modifiée avec succès.";
                    $messageType = "success";
                } else {
                    $message = "Erreur lors de la modification de la pré-affectation.";
                    $messageType = "error";
                }
            } else {
                // Ajout d'une nouvelle pré-affectation
                $stmt = $conn->prepare("INSERT INTO donnees_emploi (enseignant_id, matiere_id, salle_physique_id, salle_id) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$enseignant_id, $matiere_id, $salle_physique_id, $classe_id])) {
                    $message = "Pré-affectation ajoutée avec succès.";
                    $messageType = "success";
                } else {
                    $message = "Erreur lors de l'ajout de la pré-affectation.";
                    $messageType = "error";
                }
            }
        } catch (\PDOException $e) {
            // Gérer l'erreur de contrainte UNIQUE
            // Le code d'erreur 23000 est générique pour les violations de contraintes d'intégrité.
            // La vérification du message d'erreur est une bonne pratique pour cibler spécifiquement notre contrainte.
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'unique_pre_affectation') !== false) {
                $message = "Cette pré-affectation (Enseignant, Matière, Salle Physique, Classe) existe déjà. Impossible d'ajouter un doublon.";
                $messageType = "warning";
            } else {
                // Pour toute autre erreur PDO
                $message = "Une erreur inattendue est survenue lors de l'opération : " . $e->getMessage();
                $messageType = "error";
                error_log("Erreur PDO dans pas_emploi.php (submit_affectation): " . $e->getMessage()); // Enregistre l'erreur pour le débogage
            }
        }
    } else {
        $message = "Veuillez remplir tous les champs (enseignant, matière, salle physique, et classe).";
        $messageType = "warning";
    }

    // Rediriger pour éviter la soumission multiple du formulaire et afficher le message
    header("Location: pas_emploi.php?message=" . urlencode($message) . "&type=" . urlencode($messageType));
    exit();
}

// Traitement de la suppression
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM donnees_emploi WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Pré-affectation supprimée avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la suppression de la pré-affectation.";
            $messageType = "error";
        }
    } catch (\PDOException $e) {
        $message = "Erreur lors de la suppression : " . $e->getMessage();
        $messageType = "error";
        error_log("Erreur PDO dans pas_emploi.php (delete): " . $e->getMessage()); // Enregistre l'erreur
    }
    header("Location: pas_emploi.php?message=" . urlencode($message) . "&type=" . urlencode($messageType));
    exit();
}

// Récupérer les pré-affectations existantes pour affichage
$stmt_donnees = $conn->query("
    SELECT de.id, e.nom AS enseignant_nom, m.nom AS matiere_nom, sp.nom AS salle_physique_nom, s.nom AS classe_nom
    FROM donnees_emploi de
    JOIN enseignants e ON de.enseignant_id = e.id
    JOIN matieres m ON de.matiere_id = m.id
    JOIN salles_physiques sp ON de.salle_physique_id = sp.id
    JOIN salles s ON de.salle_id = s.id
    ORDER BY classe_nom, enseignant_nom, matiere_nom
");
$donnees_existantes = $stmt_donnees->fetchAll();

// Pour le mode édition : pré-remplir le formulaire si un ID d'édition est fourni
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt_edit = $conn->prepare("SELECT * FROM donnees_emploi WHERE id = ?");
    $stmt_edit->execute([$id]);
    $edit_data = $stmt_edit->fetch();
    if (!$edit_data) {
        // Si l'ID n'existe pas, effacer le paramètre edit_id et un message d'erreur
        $message = "L'affectation à modifier n'existe pas.";
        $messageType = "error";
        header("Location: pas_emploi.php?message=" . urlencode($message) . "&type=" . urlencode($messageType));
        exit();
    }
}

// Gérer les messages de redirection (après une action réussie via header("Location: ..."))
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
    <title>Gestion des Pré-affectations</title>
    <style>
        /* CSS identique à vos fichiers précédents pour la cohérence */
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
            border: 1px solid transparent; /* Ajout d'une bordure transparente par défaut */
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

        .message.warning {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        th, td {
            border: 1px solid var(--border-color);
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: var(--secondary-color);
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.85em;
        }

        td {
            background-color: #fefefe;
            font-size: 0.9em;
        }

        .actions {
            white-space: nowrap; /* Empêche les boutons de passer à la ligne */
        }

        .action-button {
            display: inline-block;
            padding: 8px 12px;
            margin-right: 5px;
            font-size: 0.8em;
            font-weight: bold;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
            cursor: pointer;
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .action-edit {
            background-color: var(--action-edit);
            color: var(--text-dark);
        }

        .action-edit:hover {
            background-color: #e0a800;
            transform: translateY(-1px);
        }

        .action-delete {
            background-color: var(--action-delete);
            color: white;
        }

        .action-delete:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }

        .button-container {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
        }

        .button-retour {
            background-color: var(--secondary-color);
            color: white;
            padding: 12px 25px;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-right: 15px; /* Espace entre les boutons */
        }
        .button-retour:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .button-generer {
            background-color: var(--success-color); /* Nouvelle couleur pour le bouton Générer */
            color: white;
            padding: 12px 25px;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .button-generer:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <h1>Gestion des Pré-affectations Enseignants/Matières/Salles Physiques/Classes</h1>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <h2><?php echo ($edit_data ? 'Modifier' : 'Ajouter'); ?> une Pré-affectation</h2>
        <form method="post" action="pas_emploi.php">
            <input type="hidden" name="affectation_id" value="<?php echo htmlspecialchars($edit_data['id'] ?? ''); ?>">

            <div class="form-group">
                <label for="enseignant_id">Enseignant :</label>
                <select id="enseignant_id" name="enseignant_id" required>
                    <option value="">Sélectionnez un enseignant</option>
                    <?php foreach ($enseignants as $enseignant): ?>
                        <option value="<?php echo htmlspecialchars($enseignant['id']); ?>"
                            <?php echo (isset($edit_data['enseignant_id']) && $edit_data['enseignant_id'] == $enseignant['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($enseignant['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="matiere_id">Matière :</label>
                <select id="matiere_id" name="matiere_id" required>
                    <option value="">Sélectionnez une matière</option>
                    <?php foreach ($matieres as $matiere): ?>
                        <option value="<?php echo htmlspecialchars($matiere['id']); ?>"
                            <?php echo (isset($edit_data['matiere_id']) && $edit_data['matiere_id'] == $matiere['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($matiere['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="salle_physique_id">Salle Physique :</label>
                <select id="salle_physique_id" name="salle_physique_id" required>
                    <option value="">Sélectionnez une salle physique</option>
                    <?php foreach ($salles_physiques as $salle_physique): ?>
                        <option value="<?php echo htmlspecialchars($salle_physique['id']); ?>"
                            <?php echo (isset($edit_data['salle_physique_id']) && $edit_data['salle_physique_id'] == $salle_physique['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($salle_physique['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="classe_id">Classe (Groupe d'étudiants - "salle_id" existante) :</label>
                <select id="classe_id" name="classe_id" required>
                    <option value="">Sélectionnez une classe</option>
                    <?php foreach ($classes as $classe): ?>
                        <option value="<?php echo htmlspecialchars($classe['id']); ?>"
                            <?php echo (isset($edit_data['salle_id']) && $edit_data['salle_id'] == $classe['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($classe['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="submit" name="submit_affectation" value="<?php echo ($edit_data ? 'Modifier' : 'Ajouter'); ?> la Pré-affectation">
        </form>

        <h2>Pré-affectations Existantes</h2>
        <?php if (empty($donnees_existantes)): ?>
            <p style="text-align: center; color: var(--text-light);">Aucune pré-affectation enregistrée pour le moment.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Enseignant</th>
                        <th>Matière</th>
                        <th>Salle Physique</th>
                        <th>Classe</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donnees_existantes as $donnee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($donnee['enseignant_nom']); ?></td>
                            <td><?php echo htmlspecialchars($donnee['matiere_nom']); ?></td>
                            <td><?php echo htmlspecialchars($donnee['salle_physique_nom']); ?></td>
                            <td><?php echo htmlspecialchars($donnee['classe_nom']); ?></td>
                            <td class="actions">
                                <a href="pas_emploi.php?edit_id=<?php echo htmlspecialchars($donnee['id']); ?>" class="action-button action-edit">Modifier</a>
                                <a href="pas_emploi.php?delete_id=<?php echo htmlspecialchars($donnee['id']); ?>" class="action-button action-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette pré-affectation ?');">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="button-container">
        <a href="dashboard.php" class="button-retour">Retour au Tableau de Bord</a>
        <a href="generer_emploi.php" class="button-generer">Générer l'Emploi du Temps</a>
    </div>

</body>
</html>