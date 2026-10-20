<link rel="stylesheet" href="styliser.css">
<?php
session_start();
require_once '../db.php'; // Assurez-vous que ce chemin est correct

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// --- Récupération des données pour les listes déroulantes ---

// Récupération des salles pour la liste déroulante
$salles = [];
try {
    $stmtSalles = $pdo->query("SELECT id, nom FROM salles ORDER BY nom ASC");
    $salles = $stmtSalles->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des salles : " . $e->getMessage());
}

// Modification d’un enseignant
$enseignant_edit = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    try {
        // Sélectionner toutes les colonnes nécessaires, y compris 'classe'
        $edit_data = $pdo->prepare("SELECT id, nom, classe, volume_horaire, mot_de_passe, salle_id FROM enseignants WHERE id = ?");
        $edit_data->execute([$id_edit]);
        $enseignant_edit = $edit_data->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de l'enseignant à modifier : " . $e->getMessage());
    }
}

// Suppression d’un enseignant
if (isset($_GET['delete'])) {
    $id_delete = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM enseignants WHERE id = ?")->execute([$id_delete]);
        header("Location: enseignants.php");
        exit;
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression de l'enseignant : " . $e->getMessage());
        echo "<script>alert('Erreur lors de la suppression de l\'enseignant. Assurez-vous qu\'il n\'est pas lié à des emplois du temps existants.');</script>";
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $salle_id = (int)($_POST['salle_id'] ?? 0);
    $volume_horaire = (int)($_POST['volume_horaire'] ?? 0);
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

    // --- NOUVEAU : Récupérer le nom de la salle sélectionnée pour la colonne 'classe' ---
    $classe = ''; // Initialiser la variable
    if ($salle_id > 0) {
        try {
            $stmtSalleNom = $pdo->prepare("SELECT nom FROM salles WHERE id = ?");
            $stmtSalleNom->execute([$salle_id]);
            $salle_data = $stmtSalleNom->fetch(PDO::FETCH_ASSOC);
            if ($salle_data) {
                $classe = $salle_data['nom']; // Assignez le nom de la salle à la variable 'classe'
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du nom de la salle : " . $e->getMessage());
            // Gérer l'erreur, peut-être laisser $classe vide ou null
        }
    }
    // Si aucune salle n'est sélectionnée ou si la récupération échoue, $classe restera vide
    // Ce qui correspond à 'Aucun(e)' pour une colonne VARCHAR NOT NULL si elle accepte les chaînes vides.
    // Si 'classe' n'accepte pas les chaînes vides et n'a pas de valeur par défaut, cela posera toujours problème.
    // L'idéal est de rendre 'classe' NULLABLE dans la DB si elle peut être non renseignée.


    // Validation des champs présents dans le formulaire
    if (empty($nom) || empty($salle_id) || empty($mot_de_passe) || !is_numeric($volume_horaire) || $volume_horaire < 0) {
        echo "<script>alert('Tous les champs requis (Nom, Salle, Volume horaire, Mot de passe) doivent être remplis correctement.');</script>";
    } else {
        if (isset($_POST['id'])) {
            // Modification
            $id = (int)$_POST['id'];
            try {
                // Inclure 'classe' dans la requête UPDATE avec le nom de la salle sélectionnée
                $stmt = $pdo->prepare("UPDATE enseignants SET nom = ?, salle_id = ?, volume_horaire = ?, mot_de_passe = ?, classe = ? WHERE id = ?");
                $stmt->execute([$nom, $salle_id, $volume_horaire, $mot_de_passe, $classe, $id]);
                
                header("Location: enseignants.php");
                exit;
            } catch (PDOException $e) {
                error_log("Erreur lors de la modification de l'enseignant : " . $e->getMessage());
                echo "<script>alert('Erreur lors de la modification de l\'enseignant.');</script>";
            }
        } else {
            // Ajout : vérifier si un enseignant avec ce nom existe déjà pour éviter les doublons par nom
            try {
                $stmt_check_duplicate = $pdo->prepare("SELECT COUNT(*) FROM enseignants WHERE nom = ?");
                $stmt_check_duplicate->execute([$nom]);
                $count = $stmt_check_duplicate->fetchColumn();

                if ($count > 0) {
                    echo "<script>alert('Un enseignant avec ce nom existe déjà.');</script>";
                } else {
                    // Insertion : Inclure 'classe' avec le nom de la salle sélectionnée
                    $stmt = $pdo->prepare("INSERT INTO enseignants (nom, classe, salle_id, volume_horaire, mot_de_passe) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nom, $classe, $salle_id, $volume_horaire, $mot_de_passe]);
                    header("Location: enseignants.php");
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Erreur lors de l'ajout de l'enseignant : " . $e->getMessage());
                echo "<script>alert('Erreur lors de l\'ajout de l\'enseignant.');</script>";
            }
        }
    }
}

// Liste des enseignants
$enseignants = [];
try {
    // Récupérer la classe pour l'affichage
    $stmtEnseignants = $pdo->query("
        SELECT e.id, e.nom, e.volume_horaire, e.mot_de_passe, s.nom AS salle_nom, e.classe
        FROM enseignants e
        LEFT JOIN salles s ON e.salle_id = s.id
        ORDER BY e.nom ASC
    ");
    $enseignants = $stmtEnseignants->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération de la liste des enseignants : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Enseignants</title>
    <style>
        /* Styles CSS inchangés */
        body { font-family: sans-serif; margin: 20px; background: #f9f9f9; color: #333; }
        h1, h3 { color: #007bff; }
        form { margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], select {
            padding: 10px;
            width: calc(100% - 22px);
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button[type="submit"] {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        button[type="submit"]:hover { background-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; border: 1px solid #eee; text-align: left; }
        th { background: #e9ecef; font-weight: bold; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        a { color: #007bff; text-decoration: none; margin-right: 10px; }
        a:hover { text-decoration: underline; }
        .back-link { display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #6c757d; color: white; border-radius: 4px; text-decoration: none; transition: background-color 0.3s ease; }
        .back-link:hover { background-color: #5a6268; }
    </style>
    <script>
        function confirmDelete(nom) {
            return confirm("Confirmer la suppression de l'enseignant : " + nom + " ?");
        }
    </script>
</head>
<body>

    <h1>Gestion des Enseignants</h1>

    <form method="post">
        <h3><?= isset($enseignant_edit) ? "Modifier l'enseignant" : "Ajouter un enseignant" ?></h3>

        <?php if (isset($enseignant_edit)): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($enseignant_edit['id']) ?>">
        <?php endif; ?>

        <label for="nom">Nom :</label>
        <input type="text" id="nom" name="nom" placeholder="Nom de l'enseignant" required value="<?= htmlspecialchars($enseignant_edit['nom'] ?? '') ?>"><br>
        
        <label for="salle_id">Salle :</label>
        <select id="salle_id" name="salle_id" required>
            <option value="">-- Sélectionner une salle --</option>
            <?php foreach ($salles as $salle): ?>
                <option value="<?= htmlspecialchars($salle['id']) ?>" 
                    <?= (isset($enseignant_edit) && $enseignant_edit['salle_id'] == $salle['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($salle['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label for="volume_horaire">Volume horaire :</label>
        <input type="number" id="volume_horaire" name="volume_horaire" placeholder="Volume horaire" min="0" required value="<?= htmlspecialchars($enseignant_edit['volume_horaire'] ?? '') ?>"><br>
        
        <label for="mot_de_passe">Mot de passe :</label>
        <input type="text" id="mot_de_passe" name="mot_de_passe" placeholder="Mot de passe" required value="<?= htmlspecialchars($enseignant_edit['mot_de_passe'] ?? '') ?>"><br>

        <button type="submit"><?= isset($enseignant_edit) ? "Modifier" : "Ajouter" ?></button>
    </form>

    <h2>Liste des Enseignants</h2>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Classe (Salle)</th>   <th>Salle ID</th> <th>Volume horaire</th>
                <th>Mot de passe</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enseignants as $ens): ?>
            <tr>
                <td><?= htmlspecialchars($ens['nom']) ?></td>
                <td><?= htmlspecialchars($ens['classe'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($ens['salle_nom'] ?? 'Non attribuée') ?></td> <td><?= htmlspecialchars($ens['volume_horaire']) ?></td>
                <td><?= htmlspecialchars($ens['mot_de_passe']) ?></td>
                <td>
                    <a href="?edit=<?= htmlspecialchars($ens['id']) ?>">Modifier</a>
                    <a href="?delete=<?= htmlspecialchars($ens['id']) ?>" onclick="return confirmDelete('<?= htmlspecialchars($ens['nom']) ?>')">Supprimer</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="dashboard.php" class="back-link">⬅ Retour au tableau de bord</a></p>

</body>
</html>