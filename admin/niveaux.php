<?php
// salles_physiques.php (nom suggéré pour plus de clarté)
session_start();
require_once '../db.php'; // Assurez-vous que ce chemin est correct et que $pdo est disponible.

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = ''; // Pour afficher des messages de succès ou d'erreur

// --- Logique d'ajout de salle physique ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = trim($_POST['nom']); // Supprime les espaces blancs inutiles
    $capacite = $_POST['capacite'];
    // Assumons que 'est_indisponible' est une checkbox ou un champ caché, par défaut à 0 (disponible)
    $est_indisponible = isset($_POST['est_indisponible']) ? 1 : 0; 

    if (empty($nom) || !is_numeric($capacite) || $capacite < 0) {
        $message = "Le nom et la capacité sont obligatoires et la capacité doit être un nombre positif.";
    } else {
        try {
            // Vérifier si la salle physique existe déjà
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM salles_physiques WHERE nom = ?");
            $stmt_check->execute([$nom]);
            if ($stmt_check->fetchColumn() > 0) {
                $message = "Erreur : Une salle physique avec ce nom existe déjà.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO salles_physiques (nom, capacite, est_indisponible) VALUES (?, ?, ?)");
                $stmt->execute([$nom, $capacite, $est_indisponible]);
                $message = "Salle physique ajoutée avec succès !";
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout de la salle physique : " . $e->getMessage();
        }
    }
}

// --- Logique de suppression de salle physique ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM salles_physiques WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Salle physique supprimée avec succès !";
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression de la salle physique : " . $e->getMessage();
        }
    }
    // Rediriger pour nettoyer l'URL et éviter les suppressions multiples au rechargement
    header("Location: salles_physiques.php"); // Redirige vers le nouveau nom de fichier
    exit;
}

// --- Logique de modification de salle physique (POST pour la soumission du formulaire de modification) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $id = intval($_POST['salle_id']); // Renommé de 'niveau_id' à 'salle_id'
    $nom = trim($_POST['nom']);
    $capacite = $_POST['capacite'];
    $est_indisponible = isset($_POST['est_indisponible']) ? 1 : 0;

    if (empty($nom) || !is_numeric($capacite) || $capacite < 0 || $id <= 0) {
        $message = "Erreur : Toutes les informations sont obligatoires pour la modification et la capacité doit être un nombre positif.";
    } else {
        try {
            // Vérifier si le nouveau nom existe déjà pour une AUTRE salle physique
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM salles_physiques WHERE nom = ? AND id != ?");
            $stmt_check->execute([$nom, $id]);
            if ($stmt_check->fetchColumn() > 0) {
                $message = "Erreur : Une autre salle physique avec ce nom existe déjà.";
            } else {
                $stmt = $pdo->prepare("UPDATE salles_physiques SET nom = ?, capacite = ?, est_indisponible = ? WHERE id = ?");
                $stmt->execute([$nom, $capacite, $est_indisponible, $id]);
                $message = "Salle physique modifiée avec succès !";
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de la modification de la salle physique : " . $e->getMessage();
        }
    }
}

// Récupérer toutes les salles physiques après toutes les opérations CRUD pour un affichage à jour
$salles_physiques = $pdo->query("SELECT * FROM salles_physiques ORDER BY nom ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Salles Physiques</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

        :root {
            --primary-color: #4A90E2;
            --secondary-color: #50E3C2;
            --accent-color: #FFC107;
            --danger-color: #D0021B;
            --success-color: #7ED321;
            --text-dark: #333;
            --text-light: #f4f4f4;
            --bg-light: #f4f7f6;
            --bg-card: #ffffff;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            --border-radius: 10px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(to right bottom, #edf2f7, #e2e8f0); /* Doux dégradé de fond */
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.8em;
            font-weight: 700;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.1);
        }

        h2 {
            color: var(--text-dark);
            text-align: center;
            margin-top: 30px;
            margin-bottom: 25px;
            font-size: 2em;
            font-weight: 600;
        }

        form {
            background-color: var(--bg-card);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: center;
        }

        form input[type="text"],
        form input[type="number"],
        .edit-form input[type="text"],
        .edit-form input[type="number"] {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            min-width: 200px; /* Pour éviter que les champs soient trop petits */
        }

        form input[type="text"]:focus,
        form input[type="number"]:focus,
        .edit-form input[type="text"]:focus,
        .edit-form input[type="number"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
            outline: none;
        }

        form label {
            display: flex;
            align-items: center;
            font-size: 0.95em;
            cursor: pointer;
        }

        form input[type="checkbox"] {
            margin-right: 8px;
            transform: scale(1.2); /* Agrandir la checkbox */
        }

        button {
            padding: 12px 25px;
            background: linear-gradient(45deg, var(--primary-color), #6B8EFA);
            color: var(--text-light);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(74, 144, 226, 0.3);
        }

        button:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(74, 144, 226, 0.4);
        }
        
        button[name="ajouter"] {
            background: linear-gradient(45deg, var(--primary-color), #6B8EFA);
        }

        .actions button {
            background: var(--primary-color); /* Vert pour Modifier */
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
        }
        .actions button:hover {
            background: #218838;
        }

        .actions button.delete {
            background: var(--danger-color); /* Rouge pour Supprimer */
            box-shadow: 0 2px 8px rgba(208, 2, 27, 0.2);
        }
        .actions button.delete:hover {
            background: #c82333;
        }

        table {
            width: 100%;
            max-width: 1000px;
            border-collapse: separate; /* Pour border-radius */
            border-spacing: 0;
            margin-top: 30px;
            background-color: var(--bg-card);
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            overflow: hidden; /* Pour que le border-radius s'applique */
        }

        th, td {
            border: none; /* Supprimer les bordures individuelles pour un look plus propre */
            padding: 15px;
            text-align: left;
        }

        th {
            background: linear-gradient(90deg, var(--primary-color), #6B8EFA);
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0;
        }

        tr:nth-child(even) {
            background-color: #f9fbfb;
        }

        tr:hover {
            background-color: #eef1f3;
            transition: background-color 0.2s ease;
        }
        
        td {
            border-bottom: 1px solid #eee; /* Lignes séparatrices horizontales */
        }
        tr:last-child td {
            border-bottom: none; /* Pas de bordure sur la dernière ligne */
        }


        .message {
            padding: 15px 25px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            font-weight: 600;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 800px;
            text-align: center;
            opacity: 1;
            transition: opacity 1s ease-out;
        }

        .message.success {
            background-color: var(--success-color);
            color: #fff;
            border: 1px solid #6cbf1d;
        }

        .message.error {
            background-color: var(--danger-color);
            color: #fff;
            border: 1px solid #b70016;
        }

        .edit-form {
            margin-top: 10px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fcfdfe;
            box-shadow: inset 0 1px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: center;
        }

        .edit-form h3 {
            width: 100%;
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.4em;
        }

        .edit-form form {
            box-shadow: none; /* Pas d'ombre supplémentaire pour le formulaire interne */
            padding: 0;
            background-color: transparent;
            margin-bottom: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        
        .edit-form button {
            padding: 10px 20px;
            font-size: 0.9em;
        }
        .edit-form button[name="modifier"] {
            background: linear-gradient(45deg, #28a745, #218838); /* Vert plus foncé pour modifier */
        }
        .edit-form button[name="modifier"]:hover {
            background: linear-gradient(45deg, #218838, #1e7e34);
        }
        .edit-form button[type="button"] { /* Annuler */
            background: #6c757d;
        }
        .edit-form button[type="button"]:hover {
            background: #5a6268;
        }

        p a {
            display: block;
            margin-top: 30px;
            text-align: center;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        p a:hover {
            background-color: var(--primary-color);
            color: var(--text-light);
            box-shadow: 0 5px 15px rgba(74, 144, 226, 0.3);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            h1 {
                font-size: 2em;
                margin-bottom: 20px;
            }
            h2 {
                font-size: 1.6em;
                margin-bottom: 15px;
            }
            form {
                padding: 20px;
                flex-direction: column;
                align-items: stretch;
            }
            form input[type="text"],
            form input[type="number"],
            .edit-form input[type="text"],
            .edit-form input[type="number"] {
                width: 100%;
                min-width: unset;
            }
            button {
                width: 100%;
            }
            table {
                font-size: 0.9em;
            }
            th, td {
                padding: 10px;
            }
            .actions button {
                padding: 8px 12px;
                font-size: 0.85em;
                margin-bottom: 5px;
            }
        }
        @media (max-width: 480px) {
            .actions button {
                width: 100%;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <h1>Gestion des Salles Physiques</h1>

    <?php if (!empty($message)): ?>
        <div class="message <?= (strpos($message, 'Erreur') === 0) ? 'error' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <h2>Ajouter une nouvelle salle physique</h2>
    <form method="post">
        <input type="text" name="nom" placeholder="Nom de la salle (ex: Amphi A, Salle 101)" required>
        <input type="number" name="capacite" placeholder="Capacité (nombre de places)" required min="0">
        <label>
            <input type="checkbox" name="est_indisponible" value="1"> Est indisponible
        </label>
        <button type="submit" name="ajouter">Ajouter</button>
    </form>

    <h2>Liste des Salles Physiques</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Capacité</th>
                <th>Indisponible</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($salles_physiques)): ?>
                <tr><td colspan="4">Aucune salle physique enregistrée.</td></tr>
            <?php else: ?>
                <?php foreach ($salles_physiques as $salle): ?>
                    <tr>
                        <td><?= htmlspecialchars($salle['nom']) ?></td>
                        <td><?= htmlspecialchars($salle['capacite']) ?></td>
                        <td><?= $salle['est_indisponible'] ? 'Oui' : 'Non' ?></td>
                        <td class="actions">
                            <button type="button" onclick="showEditForm(<?= $salle['id'] ?>, '<?= htmlspecialchars(addslashes($salle['nom'])) ?>', <?= $salle['capacite'] ?>, <?= $salle['est_indisponible'] ?>)">Modifier</button>
                            <button type="button" class="delete" onclick="confirmDelete(<?= $salle['id'] ?>, '<?= htmlspecialchars(addslashes($salle['nom'])) ?>')">Supprimer</button>
                        </td>
                    </tr>
                    <tr id="edit-form-<?= $salle['id'] ?>" style="display:none;">
                        <td colspan="4">
                            <div class="edit-form">
                                <h3>Modifier la salle physique "<?= htmlspecialchars($salle['nom']) ?>"</h3>
                                <form method="post">
                                    <input type="hidden" name="salle_id" value="<?= $salle['id'] ?>">
                                    <input type="text" name="nom" value="<?= htmlspecialchars($salle['nom']) ?>" required>
                                    <input type="number" name="capacite" value="<?= htmlspecialchars($salle['capacite']) ?>" required min="0">
                                    <label>
                                        <input type="checkbox" name="est_indisponible" value="1" <?= $salle['est_indisponible'] ? 'checked' : '' ?>> Est indisponible
                                    </label>
                                    <button type="submit" name="modifier">Enregistrer les modifications</button>
                                    <button type="button" onclick="hideEditForm(<?= $salle['id'] ?>)">Annuler</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <p><a href="dashboard.php">Retour au tableau de bord</a></p>

    <script>
        function confirmDelete(id, nom) {
            if (confirm("Êtes-vous sûr de vouloir supprimer la salle physique '" + nom + "' ?")) {
                window.location.href = 'salles_physiques.php?action=supprimer&id=' + id; // Redirige vers le nouveau nom de fichier
            }
        }

        function showEditForm(id, nom, capacite, est_indisponible) {
            document.querySelectorAll('[id^="edit-form-"]').forEach(form => {
                form.style.display = 'none';
            });

            const formRow = document.getElementById('edit-form-' + id);
            if (formRow) {
                formRow.style.display = 'table-row';
                // Pré-remplir la checkbox 'est_indisponible'
                const checkbox = formRow.querySelector('input[name="est_indisponible"]');
                if (checkbox) {
                    checkbox.checked = (est_indisponible === 1);
                }
            }
        }

        function hideEditForm(id) {
            const formRow = document.getElementById('edit-form-' + id);
            if (formRow) {
                formRow.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const messageDiv = document.querySelector('.message');
            if (messageDiv) {
                setTimeout(() => {
                    messageDiv.style.opacity = '0';
                    messageDiv.style.transition = 'opacity 1s ease-out';
                    setTimeout(() => messageDiv.remove(), 1000);
                }, 5000);
            }
        });
    </script>
</body>
</html>