<?php
// matieres.php
session_start();
require_once '../db.php'; // Assurez-vous que ce chemin est correct

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$messageType = '';

// --- Logique d'ajout de matière ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = trim($_POST['nom']); // Nom de la matière (ex: "Mathématiques")
    $duree = intval($_POST['duree']);
    $idEnseignant = isset($_POST['id_enseignant']) ? intval($_POST['id_enseignant']) : null;
    $nomEnseignant = ''; // Sera rempli à partir de l'ID si l'ID est valide

    // Valider les entrées
    if (empty($nom) || $duree <= 0 || empty($idEnseignant)) {
        $message = "Veuillez remplir tous les champs correctement (Nom de la matière, Durée et choisir un enseignant).";
        $messageType = "error";
    } else {
        // Récupérer le nom de l'enseignant à partir de son ID
        $stmtEnseignant = $pdo->prepare("SELECT nom FROM enseignants WHERE id = ?");
        $stmtEnseignant->execute([$idEnseignant]);
        $enseignantData = $stmtEnseignant->fetch(PDO::FETCH_ASSOC);

        if ($enseignantData) {
            $nomEnseignant = $enseignantData['nom'];

            // Préparer et exécuter l'insertion
            try {
                // Insertion dans la table 'matieres' avec l'enseignant associé
                $stmt = $pdo->prepare("INSERT INTO matieres (nom, duree, id_enseignant, nom_enseignant) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nom, $duree, $idEnseignant, $nomEnseignant]);
                $message = "Matière '" . htmlspecialchars($nom) . "' associée à " . htmlspecialchars($nomEnseignant) . " et ajoutée avec succès !";
                $messageType = "success";
            } catch (PDOException $e) {
                // Gérer les erreurs de la base de données
                if ($e->getCode() == 23000) { // Code d'erreur SQL pour les violations d'unicité (par ex. si vous aviez une contrainte UNIQUE sur nom + id_enseignant)
                    $message = "Erreur : Cette combinaison matière/enseignant existe peut-être déjà.";
                } else {
                    $message = "Erreur lors de l'ajout de la matière : " . $e->getMessage();
                }
                $messageType = "error";
            }
        } else {
            $message = "Erreur : Enseignant sélectionné invalide.";
            $messageType = "error";
        }
    }
}

// --- Récupérer tous les enseignants pour la liste déroulante ---
$enseignants = $pdo->query("SELECT id, nom FROM enseignants ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- Récupérer toutes les matières avec leurs enseignants associés ---
$matieres = $pdo->query("
    SELECT
        m.id,
        m.nom,
        m.duree,
        m.nom_enseignant -- AJOUTÉ : Sélectionne la colonne nom_enseignant de la table matieres
    FROM
        matieres m
    ORDER BY
        m.nom ASC, m.nom_enseignant ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Matières</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Variables CSS pour les couleurs et espacements */
        :root {
            --primary-color: #4A90E2; /* Bleu plus doux */
            --primary-hover: #357ABD;
            --success-color: #28a745;
            --error-color: #dc3545;
            --bg-light: #f8faff; /* Arrière-plan très clair */
            --card-bg: #ffffff;
            --text-dark: #34495e; /* Gris foncé pour le texte */
            --text-light: #7f8c8d; /* Gris moyen */
            --border-color: #e0e6ed;
            --shadow-light: rgba(0, 0, 0, 0.08);
            --shadow-medium: rgba(0, 0, 0, 0.15);
        }

        /* Styles généraux du corps de la page */
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
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
            text-align: center;
            letter-spacing: 0.5px;
            font-size: 2.5em; /* Titre plus grand */
        }

        /* Style des conteneurs (formulaire et tableau) */
        .container {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px var(--shadow-light);
            max-width: 800px; /* Largeur ajustée */
            width: 100%;
            margin-bottom: 40px;
            box-sizing: border-box;
            transition: all 0.3s ease; /* Transition pour un effet doux */
        }

        .container:hover {
            box-shadow: 0 12px 35px var(--shadow-medium); /* Ombre plus prononcée au survol */
        }

        .form-section {
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.95em;
        }

        /* Styles des champs de formulaire */
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            color: var(--text-dark);
            background-color: #fdfdfd;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.25); /* Ombre lumineuse ajustée */
            outline: none;
        }

        /* Personnalisation de la flèche de sélection */
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23666%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13.2-5.4H18.6c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.7c0%204.6%201.7%208.7%205.2%2012.3l128%20127.9c3.6%203.6%207.6%205.4%2012.3%205.4s8.7-1.8%2012.3-5.4l128-127.9c3.5-3.6%205.2-7.8%205.2-12.3.1-4.7-1.7-8.8-5.2-12.4z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 12px;
            padding-right: 35px;
        }

        /* Style du bouton de soumission */
        button[type="submit"] {
            display: block;
            width: 100%;
            padding: 15px 25px;
            background: linear-gradient(45deg, var(--primary-color), #6dd5ed); /* Dégradé frais */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 25px;
            box-shadow: 0 5px 15px rgba(74, 144, 226, 0.3);
            transition: all 0.3s ease;
        }

        button[type="submit"]:hover {
            background: linear-gradient(45deg, var(--primary-hover), #4cb8cc);
            box-shadow: 0 7px 20px rgba(74, 144, 226, 0.4);
            transform: translateY(-2px);
        }

        /* Styles des messages de feedback */
        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            opacity: 0;
            transform: translateY(-10px);
            animation: fadeInSlideDown 0.5s forwards ease-out;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); /* Ombre légère pour les messages */
        }

        .message.success {
            background-color: #e6ffed; /* Vert très clair */
            color: var(--success-color);
            border: 1px solid #b7e4c7;
        }

        .message.error {
            background-color: #ffe6e6; /* Rouge très clair */
            color: var(--error-color);
            border: 1px solid #f2b7b7;
        }

        .message::before {
            font-size: 1.2em;
            margin-right: 10px;
            font-weight: bold;
        }
        .message.success::before {
            content: '✓';
        }
        .message.error::before {
            content: '✕';
        }

        @keyframes fadeInSlideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Styles du tableau des matières */
        .table-section {
            margin-top: 40px;
            padding: 30px;
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 30px var(--shadow-light);
            max-width: 800px;
            width: 100%;
            box-sizing: border-box;
        }

        .table-section h2 {
            margin-top: 0;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
            letter-spacing: 0.3px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background-color: var(--card-bg);
        }

        th, td {
            border: 1px solid var(--border-color);
            padding: 12px 15px;
            text-align: left;
            font-size: 0.95em;
            vertical-align: middle;
        }

        th {
            background-color: #eaf1f8;
            color: var(--text-dark);
            font-weight: 600;
            white-space: nowrap;
        }

        tr:nth-child(even) {
            background-color: #f9fbfd;
        }

        tr:hover {
            background-color: #e6f7ff;
            transition: background-color 0.2s ease;
        }

        /* Liens de navigation */
        .navigation-links {
            margin-top: 30px;
            text-align: center;
        }

        .navigation-links a {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .navigation-links a:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Réactivité */
        @media (max-width: 768px) {
            .container, .table-section {
                padding: 15px;
                margin-left: 10px;
                margin-right: 10px;
                border-radius: 8px;
            }
            body {
                padding: 15px 10px;
            }
            h1 {
                font-size: 2em;
            }
            input[type="text"], input[type="number"], select, button[type="submit"] {
                font-size: 0.9em;
                padding: 10px 12px;
            }
            th, td {
                padding: 10px;
                font-size: 0.8em;
            }
        }
    </style>
</head>
<body>
    <h1>Gestion des Matières et Enseignants</h1>

    <div class="container form-section">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="nomMatiere">Nom de la matière :</label>
                <input type="text" id="nomMatiere" name="nom" placeholder="Ex: Mathématiques, Histoire..." required>
            </div>

            <div class="form-group">
                <label for="dureeMatiere">Durée (en heures) :</label>
                <input type="number" id="dureeMatiere" name="duree" placeholder="Ex: 2, 3" min="1" required>
            </div>

            <div class="form-group">
                <label for="selectEnseignant">Enseignant responsable :</label>
                <select id="selectEnseignant" name="id_enseignant" required>
                    <option value="">-- Sélectionnez un enseignant --</option>
                    <?php
                    if (!empty($enseignants)) {
                        foreach ($enseignants as $enseignant) {
                            echo '<option value="' . htmlspecialchars($enseignant['id']) . '">'
                               . htmlspecialchars($enseignant['nom'])
                               . '</option>';
                        }
                    } else {
                        echo '<option value="">Aucun enseignant trouvé</option>';
                    }
                    ?>
                </select>
            </div>

            <button type="submit" name="ajouter">Ajouter la Matière</button>
        </form>
    </div>

    <div class="container table-section">
        <h2>Liste des Matières Enregistrées</h2>
        <?php if (empty($matieres)): ?>
            <p style="text-align: center; color: var(--text-light);">Aucune matière n'a encore été enregistrée.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nom de la Matière</th>
                            <th>Durée (heures)</th>
                            <th>Enseignant Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matieres as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['nom']) ?></td>
                                <td><?= htmlspecialchars($m['duree']) ?></td>
                                <td><?= htmlspecialchars($m['nom_enseignant'] ?: 'Non assigné') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="navigation-links">
        <p><a href="dashboard.php">Retour au tableau de bord</a></p>
    </div>
</body>
</html>