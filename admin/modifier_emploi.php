<?php
// modifier_emploi.php - Utilise la connexion PDO fournie par db.php

// Inclut le fichier de connexion à la base de données (qui doit fournir la variable $pdo)
// Le chemin est correct si db.php est dans le dossier parent de 'admin'
require_once __DIR__ . '/../db.php';

// Vérifie que la connexion PDO est bien établie
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erreur: La connexion PDO à la base de données n'a pas été établie par db.php.");
}

$message = "";
$messageType = "";

// Initialisation des variables pour le formulaire
$emploi_data = null;
$selectedEnseignantId = 0;
$selectedJour = '';
$selectedCreneau = '';
$selectedMatiereNom = '';
$selectedSalleId = 0;

// --- Récupérer les données pour les listes déroulantes (comme dans pas_emploi.php) ---
// Enseignants
$enseignants = [];
try {
    $requete_enseignants = "SELECT id, nom FROM enseignants ORDER BY nom ASC";
    $stmt_enseignants = $pdo->query($requete_enseignants);
    $enseignants = $stmt_enseignants->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des enseignants : " . $e->getMessage();
    $messageType = "error";
}

// Matières uniques (à partir de la colonne 'matiere' des enseignants, comme vous l'avez fait)
$matieres_uniques = [];
try {
    $requete_matieres_enseignants = "SELECT DISTINCT matiere FROM enseignants WHERE matiere IS NOT NULL AND matiere != '' ORDER BY matiere ASC";
    $stmt_matieres_enseignants = $pdo->query($requete_matieres_enseignants);
    while ($row = $stmt_matieres_enseignants->fetch(PDO::FETCH_ASSOC)) {
        $matieres_uniques[] = $row['matiere'];
    }
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des matières : " . $e->getMessage();
    $messageType = "error";
}

// Salles disponibles
$salles = [];
try {
    $requete_salles = "SELECT id, nom FROM salles WHERE est_indisponible = 0 ORDER BY nom ASC";
    $stmt_salles = $pdo->query($requete_salles);
    $salles = $stmt_salles->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des salles : " . $e->getMessage();
    $messageType = "error";
}


// --- 1. Récupérer l'ID de l'emploi du temps à modifier ---
if (isset($_GET['id'])) {
    $id_emploi = intval($_GET['id']);

    if ($id_emploi > 0) {
        $requete_emploi = "
            SELECT
                edt.id,
                edt.enseignant_id,
                edt.matiere_id,
                edt.salle_id,
                edt.jour,
                edt.creneau,
                e.nom AS nom_enseignant,
                m.nom AS matiere_nom,
                s.nom AS salle_nom
            FROM
                donnees_emploi edt
            JOIN
                enseignants e ON edt.enseignant_id = e.id
            JOIN
                matieres m ON edt.matiere_id = m.id
            JOIN
                salles s ON edt.salle_id = s.id
            WHERE
                edt.id = :id_emploi
        ";
        try {
            $stmt_emploi = $pdo->prepare($requete_emploi);
            $stmt_emploi->bindParam(':id_emploi', $id_emploi, PDO::PARAM_INT);
            $stmt_emploi->execute();
            $emploi_data = $stmt_emploi->fetch(PDO::FETCH_ASSOC);

            if ($emploi_data) {
                // Pré-remplir les variables de sélection avec les données existantes
                $selectedEnseignantId = $emploi_data['enseignant_id'];
                $selectedJour = $emploi_data['jour'];
                $selectedCreneau = $emploi_data['creneau'];
                $selectedMatiereNom = $emploi_data['matiere_nom']; // Utilisez le nom de la matière
                $selectedSalleId = $emploi_data['salle_id'];
            } else {
                $message = "Aucun emploi du temps trouvé avec cet ID.";
                $messageType = "error";
                $id_emploi = 0; // Invalider l'ID pour ne pas afficher le formulaire
            }
        } catch (PDOException $e) {
            $message = "Erreur de récupération de l'emploi du temps : " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "ID d'emploi du temps invalide.";
        $messageType = "error";
    }
} else {
    $message = "Aucun ID d'emploi du temps spécifié pour la modification.";
    $messageType = "error";
}

// --- Logique pour peupler les listes dépendantes (Jours et Créneaux) au chargement ou après sélection enseignant ---
$joursDisponibles = [];
if ($selectedEnseignantId > 0) {
    $requete_jours_enseignant = "SELECT DISTINCT jour FROM disponibilites WHERE enseignant_id = :enseignant_id ORDER BY jour ASC";
    try {
        $stmt_jours = $pdo->prepare($requete_jours_enseignant);
        $stmt_jours->bindParam(':enseignant_id', $selectedEnseignantId, PDO::PARAM_INT);
        $stmt_jours->execute();
        $joursDisponibles = $stmt_jours->fetchAll(PDO::FETCH_COLUMN); // FETCH_COLUMN pour une liste simple
    } catch (PDOException $e) {
        $message = "Erreur lors de la récupération des jours disponibles : " . $e->getMessage();
        $messageType = "error";
    }
}

$creneauxDisponibles = [];
if ($selectedEnseignantId > 0 && !empty($selectedJour)) {
    // Exclure les créneaux déjà utilisés dans 'donnees_emploi' pour cet enseignant et ce jour,
    // MAIS INCLURE le créneau de l'emploi du temps actuel si c'est celui que l'on modifie
    $requete_creneaux_jour = "
        SELECT DISTINCT d.creneau
        FROM disponibilites d
        LEFT JOIN donnees_emploi de ON d.enseignant_id = de.enseignant_id
                                    AND d.jour = de.jour
                                    AND d.creneau = de.creneau
        WHERE d.enseignant_id = :enseignant_id
          AND d.jour = :jour
          AND (de.id IS NULL OR de.id = :id_emploi) -- Inclut le créneau si c'est l'enregistrement que nous modifions
        ORDER BY d.creneau ASC
    ";
    try {
        $stmt_creneaux = $pdo->prepare($requete_creneaux_jour);
        $stmt_creneaux->bindParam(':enseignant_id', $selectedEnseignantId, PDO::PARAM_INT);
        $stmt_creneaux->bindParam(':jour', $selectedJour, PDO::PARAM_STR);
        $stmt_creneaux->bindParam(':id_emploi', $id_emploi, PDO::PARAM_INT);
        $stmt_creneaux->execute();
        $creneauxDisponibles = $stmt_creneaux->fetchAll(PDO::FETCH_COLUMN); // FETCH_COLUMN pour une liste simple
    } catch (PDOException $e) {
        $message = "Erreur lors de la récupération des créneaux disponibles : " . $e->getMessage();
        $messageType = "error";
    }
}


// --- 2. Gérer la soumission du formulaire de modification ---
if (isset($_POST['modifier_emploi'])) {
    $id_emploi_modifie = intval($_POST['id_emploi']);
    $newEnseignantId = isset($_POST['enseignant_id']) ? intval($_POST['enseignant_id']) : 0;
    $newMatiereNom = isset($_POST['matiere_nom']) ? htmlspecialchars(trim($_POST['matiere_nom'])) : '';
    $newSalleId = isset($_POST['salle_id']) ? intval($_POST['salle_id']) : 0;
    $newJour = isset($_POST['jour_disponibilite']) ? htmlspecialchars(trim($_POST['jour_disponibilite'])) : '';
    $newCreneau = isset($_POST['creneau_disponibilite']) ? htmlspecialchars(trim($_POST['creneau_disponibilite'])) : '';

    if ($id_emploi_modifie > 0 && $newEnseignantId > 0 && !empty($newMatiereNom) && $newSalleId > 0 && !empty($newJour) && !empty($newCreneau)) {

        $matiere_id_pour_update = 0;
        $requete_matiere_id = "SELECT id FROM matieres WHERE nom = :nom_matiere";
        try {
            $stmt_matiere_id = $pdo->prepare($requete_matiere_id);
            $stmt_matiere_id->bindParam(':nom_matiere', $newMatiereNom, PDO::PARAM_STR);
            $stmt_matiere_id->execute();
            $row_matiere_id = $stmt_matiere_id->fetch(PDO::FETCH_ASSOC);
            if ($row_matiere_id) {
                $matiere_id_pour_update = $row_matiere_id['id'];
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de la récupération de l'ID de la matière : " . $e->getMessage();
            $messageType = "error";
        }

        if ($matiere_id_pour_update > 0) {
            $update_query = "
                UPDATE donnees_emploi
                SET enseignant_id = :enseignant_id, matiere_id = :matiere_id, salle_id = :salle_id, jour = :jour, creneau = :creneau
                WHERE id = :id_emploi
            ";
            try {
                $stmt_update = $pdo->prepare($update_query);
                $stmt_update->bindParam(':enseignant_id', $newEnseignantId, PDO::PARAM_INT);
                $stmt_update->bindParam(':matiere_id', $matiere_id_pour_update, PDO::PARAM_INT);
                $stmt_update->bindParam(':salle_id', $newSalleId, PDO::PARAM_INT);
                $stmt_update->bindParam(':jour', $newJour, PDO::PARAM_STR);
                $stmt_update->bindParam(':creneau', $newCreneau, PDO::PARAM_STR);
                $stmt_update->bindParam(':id_emploi', $id_emploi_modifie, PDO::PARAM_INT);

                if ($stmt_update->execute()) {
                    $message = "Emploi du temps mis à jour avec succès !";
                    $messageType = "success";
                    // Rediriger vers pas_emploi.php
                    header("Location: pas_emploi.php?message=" . urlencode($message) . "&type=" . $messageType);
                    exit();

                } else {
                    // PDO ne donne pas directement les numéros d'erreur comme MySQLi, mais on peut vérifier SQLSTATE
                    $errorInfo = $stmt_update->errorInfo();
                    if ($errorInfo[0] == '23000' && $errorInfo[1] == 1062) { // 23000 est le SQLSTATE pour erreur d'intégrité, 1062 est le code MySQL pour doublon
                        $message = "Erreur : Cet enregistrement d'emploi du temps existe déjà. Impossible d'ajouter un doublon.";
                        $messageType = "error";
                    } else {
                        $message = "Erreur lors de la mise à jour : " . (isset($errorInfo[2]) ? $errorInfo[2] : "Erreur inconnue.");
                        $messageType = "error";
                    }
                }
            } catch (PDOException $e) {
                 $message = "Erreur PDO lors de la mise à jour : " . $e->getMessage();
                 $messageType = "error";
            }
        } else {
            $message = "Erreur : La matière sélectionnée n'a pas pu être trouvée dans la table 'matieres'.";
            $messageType = "error";
        }
    } else {
        $message = "Veuillez remplir tous les champs (Enseignant, Jour, Créneau, Matière, Salle) pour modifier.";
        $messageType = "error";
    }
    // Si la mise à jour échoue ou si des champs sont manquants, les variables sont mises à jour ici
    // pour que le formulaire puisse les réafficher avec les valeurs soumises.
    $selectedEnseignantId = $newEnseignantId;
    $selectedJour = $newJour;
    $selectedCreneau = $newCreneau;
    $selectedMatiereNom = $newMatiereNom;
    $selectedSalleId = $newSalleId;
}

// Récupérer le message de redirection si présent
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $messageType = htmlspecialchars($_GET['type']);
}

// Pas besoin de mysqli_close($connexion); car PDO gère la fermeture de la connexion automatiquement à la fin du script
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Emploi du Temps</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --success-color: #28a745;
            --error-color: #dc3545;
            --bg-light: #f4f7fa;
            --card-bg: #ffffff;
            --text-dark: #333;
            --text-light: #666;
            --border-color: #e0e6ed;
            --shadow-light: rgba(0, 0, 0, 0.08);
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
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .container {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px var(--shadow-light);
            max-width: 600px; /* Un peu plus petit pour le formulaire de modification */
            width: 100%;
            margin-bottom: 40px;
            box-sizing: border-box;
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

        select, input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            color: var(--text-dark);
            background-color: #fdfdfd;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23666%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13.2-5.4H18.6c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.7c0%204.6%201.7%208.7%205.2%2012.3l128%20127.9c3.6%203.6%207.6%205.4%2012.3%205.4s8.7-1.8%2012.3-5.4l128-127.9c3.5-3.6%205.2-7.8%205.2-12.3.1-4.7-1.7-8.8-5.2-12.4z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 12px;
            padding-right: 35px;
        }

        select:focus, input[type="text"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        input[type="submit"] {
            display: block;
            width: 100%;
            padding: 15px 25px;
            background: linear-gradient(45deg, var(--primary-color), #00aaff);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 25px;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }

        input[type="submit"]:hover {
            background: linear-gradient(45deg, var(--primary-hover), #0099ee);
            box-shadow: 0 7px 20px rgba(0, 123, 255, 0.4);
            transform: translateY(-2px);
        }

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
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message::before {
            content: '✓';
            font-size: 1.2em;
            margin-right: 10px;
            font-weight: bold;
        }
        .message.error::before {
            content: '✕';
        }

        @keyframes fadeInSlideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        .back-link:hover {
            color: var(--primary-hover);
        }
    </style>
</head>
<body>

    <h1>Modifier un Emploi du Temps</h1>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($id_emploi > 0 && $emploi_data): ?>
            <form method="post" action="modifier_emploi.php?id=<?php echo htmlspecialchars($id_emploi); ?>">
                <input type="hidden" name="id_emploi" value="<?php echo htmlspecialchars($id_emploi); ?>">

                <div class="form-group">
                    <label for="selectEnseignant">Enseignant :</label>
                    <select id="selectEnseignant" name="enseignant_id" onchange="this.form.submit()">
                        <option value="">-- Choisissez un enseignant --</option>
                        <?php
                        if (!empty($enseignants)) {
                            foreach ($enseignants as $enseignant) {
                                $selected = ($enseignant['id'] == $selectedEnseignantId) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($enseignant['id']) . '" ' . $selected . '>'
                                   . htmlspecialchars($enseignant['nom'])
                                   . '</option>';
                            }
                        } else {
                            echo '<option value="">Aucun enseignant trouvé</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="selectJour">Jours de disponibilité :</label>
                    <select id="selectJour" name="jour_disponibilite" onchange="this.form.submit()">
                        <?php
                        if ($selectedEnseignantId == 0) {
                            echo '<option value="">-- Sélectionnez un enseignant ci-dessus --</option>';
                        } elseif (empty($joursDisponibles)) {
                            echo '<option value="">-- Aucune disponibilité de jour trouvée pour cet enseignant --</option>';
                        } else {
                            echo '<option value="">-- Sélectionnez un jour --</option>';
                            foreach ($joursDisponibles as $jour) {
                                $selected = ($jour == $selectedJour) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($jour) . '" ' . $selected . '>'
                                   . htmlspecialchars($jour)
                                   . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="selectCreneau">Créneaux de disponibilité :</label>
                    <select id="selectCreneau" name="creneau_disponibilite">
                        <?php
                        if ($selectedEnseignantId == 0 || empty($selectedJour)) {
                            echo '<option value="">-- Sélectionnez un enseignant et un jour --</option>';
                        } elseif (empty($creneauxDisponibles)) {
                            echo '<option value="">-- Aucun créneau disponible pour ce jour (déjà utilisé ou non défini) --</option>';
                        } else {
                            echo '<option value="">-- Sélectionnez un créneau --</option>';
                            foreach ($creneauxDisponibles as $creneau) {
                                $selected = ($creneau == $selectedCreneau) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($creneau) . '" ' . $selected . '>'
                                   . htmlspecialchars($creneau)
                                   . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="selectMatiere">Matière :</label>
                    <select id="selectMatiere" name="matiere_nom">
                        <option value="">-- Veuillez choisir une matière --</option>
                        <?php
                        if (!empty($matieres_uniques)) {
                            foreach ($matieres_uniques as $matiere_nom_from_db) {
                                $selected = ($matiere_nom_from_db == $selectedMatiereNom) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($matiere_nom_from_db) . '" ' . $selected . '>'
                                   . htmlspecialchars($matiere_nom_from_db)
                                   . '</option>';
                            }
                        } else {
                            echo '<option value="">Aucune matière trouvée</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="selectSalle">Salle :</label>
                    <select id="selectSalle" name="salle_id">
                        <option value="">-- Choisissez une salle --</option>
                        <?php
                        if (!empty($salles)) {
                            foreach ($salles as $salle) {
                                $selected = ($salle['id'] == $selectedSalleId) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($salle['id']) . '" ' . $selected . '>'
                                   . htmlspecialchars($salle['nom'])
                                   . '</option>';
                            }
                        } else {
                            echo '<option value="">Aucune salle disponible trouvée</option>';
                        }
                        ?>
                    </select>
                </div>

                <input type="submit" name="modifier_emploi" value="Modifier l'Emploi du Temps">
            </form>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-light);">Impossible d'afficher le formulaire de modification.</p>
        <?php endif; ?>

        <a href="pas_emploi.php" class="back-link">Retour à la liste des emplois du temps</a>
    </div>

</body>
</html>