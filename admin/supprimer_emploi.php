<?php
session_start();
require_once '../db.php'; // Chemin vers votre fichier de connexion à la base de données

// Vérifier que l'utilisateur est connecté et qu'il a le rôle d'administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: ../login.php"); // Rediriger si non autorisé
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emploi_id'])) {
    $emploi_id = filter_var($_POST['emploi_id'], FILTER_SANITIZE_NUMBER_INT); // Nettoyer l'ID

    if (!empty($emploi_id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM donnees_emploi WHERE id = ?");
            $stmt->execute([$emploi_id]);

            if ($stmt->rowCount() > 0) {
                $message = "Le créneau d'emploi du temps a été supprimé avec succès.";
                $messageType = "success";
            } else {
                $message = "Aucun créneau trouvé avec cet ID ou suppression impossible.";
                $messageType = "error";
            }
        } catch (PDOException $e) {
            $message = "Erreur de base de données lors de la suppression : " . $e->getMessage();
            $messageType = "error";
            error_log("Erreur suppression emploi: " . $e->getMessage());
        }
    } else {
        $message = "ID de créneau invalide pour la suppression.";
        $messageType = "error";
    }
} else {
    $message = "Requête invalide pour la suppression.";
    $messageType = "error";
}

// Rediriger vers la page d'affichage de l'emploi du temps avec un message
$_SESSION['message'] = $message;
$_SESSION['message_type'] = $messageType;
header("Location: afficher_emploi.php"); // Assurez-vous que c'est le bon nom de fichier
exit;
?><?php
session_start();
require_once '../db.php'; // Chemin vers votre fichier de connexion à la base de données
// Si vous avez un fichier config.php pour les créneaux, jours, etc.
// require_once '../config.php';

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = '';
$messageType = '';

// Récupérer les messages de la session si redirigé depuis un autre script (ex: suppression, modification)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    // Nettoyer les messages de la session pour qu'ils ne s'affichent qu'une fois
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Récupérer les données de base pour les listes et l'affichage
try {
    // Récupérer les enseignants
    $stmt_enseignants = $pdo->query("SELECT id, nom FROM enseignants");
    $enseignants = [];
    while ($row = $stmt_enseignants->fetch(PDO::FETCH_ASSOC)) {
        $enseignants[$row['id']] = $row; // Stocke toute la ligne pour plus de flexibilité si besoin
    }

    // Récupérer les matières
    $stmt_matieres = $pdo->query("SELECT id, nom, duree FROM matieres");
    $matieres = [];
    while ($row = $stmt_matieres->fetch(PDO::FETCH_ASSOC)) {
        $matieres[$row['id']] = $row;
    }

    // Récupérer les salles
    $stmt_salles = $pdo->query("SELECT id, nom FROM salles");
    $salles = [];
    while ($row = $stmt_salles->fetch(PDO::FETCH_ASSOC)) {
        $salles[$row['id']] = $row;
    }

    // Récupérer l'emploi du temps généré
    // IL EST TRÈS IMPORTANT QUE CETTE REQUÊTE RÉCUPÈRE L'ID DE CHAQUE ENREGISTREMENT
    $stmt_emploi = $pdo->prepare("SELECT id, enseignant_id, matiere_id, salle_id, jour, creneau FROM donnees_emploi ORDER BY FIELD(jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), creneau");
    $stmt_emploi->execute();
    $emploiDuTemps = $stmt_emploi->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "Erreur de base de données lors du chargement des données : " . $e->getMessage();
    $messageType = "error";
    error_log("Erreur chargement emploi: " . $e->getMessage());
    $enseignants = []; // Initialiser pour éviter les erreurs si la connexion échoue
    $matieres = [];
    $salles = [];
    $emploiDuTemps = [];
}

// Créneaux fixes - DOIVENT CORRESPONDRE À CEUX UTILISÉS POUR LA GÉNÉRATION
// Si ces créneaux sont définis dans un fichier config.php, assurez-vous qu'il est inclus.
$creneauxFixes = [
    '08h00-12h00' => ['label' => 'Matin (08h00-12h00)', 'type' => 'cours', 'duration' => 4, 'alias' => 'matin'],
    '12h00-13h30' => ['label' => 'Pause déjeuner (12h00-13h30)', 'type' => 'pause', 'duration' => 1.5, 'alias' => 'pause_dejeuner'],
    '13h30-17h30' => ['label' => 'Après-midi (13h30-17h30)', 'type' => 'cours', 'duration' => 4, 'alias' => 'midi'],
    '18h00-21h00' => ['label' => 'Soir (18h00-21h00)', 'type' => 'cours', 'duration' => 3, 'alias' => 'soir']
];

// Jours de la semaine
$joursSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

// Organiser l'emploi du temps par jour et créneau pour un affichage facile
$emploiOrganise = [];
foreach ($emploiDuTemps as $item) {
    $emploiOrganise[$item['jour']][$item['creneau']] = $item;
}

// Vérifier si l'utilisateur est un administrateur pour l'affichage des boutons d'action
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'administrateur');

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emploi du Temps Généré</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Votre CSS existant, plus les styles des boutons d'action */
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --success-color: #28a745;
            --error-color: #dc3545;
            --info-color: #17a2b8;
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
        }

        .container {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px var(--shadow-light);
            max-width: 1200px; /* Plus large pour l'emploi du temps */
            width: 100%;
            margin-bottom: 40px;
            box-sizing: border-box;
            text-align: center;
        }

        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-weight: 500;
            text-align: left;
            animation: fadeInSlideDown 0.5s forwards ease-out;
            word-break: break-word;
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
        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        @keyframes fadeInSlideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .emploi-du-temps-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .emploi-du-temps-table th,
        .emploi-du-temps-table td {
            border: 1px solid var(--border-color);
            padding: 12px 8px;
            text-align: center;
            vertical-align: top; /* Pour aligner le contenu en haut des cellules */
        }

        .emploi-du-temps-table th {
            background-color: #eaf1f8;
            font-weight: 600;
            color: var(--text-dark);
        }

        .emploi-du-temps-table td {
            background-color: #fdfdfd;
            font-size: 0.9em;
            line-height: 1.4;
        }

        .emploi-du-temps-table td div {
            margin-bottom: 3px;
        }

        .emploi-du-temps-table td div:last-child {
            margin-bottom: 0;
        }

        /* Styles pour les boutons d'action dans le tableau de l'emploi du temps */
        .action-buttons {
            display: flex;
            flex-direction: column; /* Boutons l'un en dessous de l'autre */
            gap: 5px; /* Espace entre les boutons */
            justify-content: center; /* Centre les boutons dans la cellule */
            margin-top: 10px; /* Un peu d'espace avec le contenu au-dessus */
        }

        .action-buttons .btn-action {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em; /* Plus petit pour s'intégrer */
            font-weight: 500;
            transition: background-color 0.2s ease, transform 0.2s ease;
            text-decoration: none; /* Pour les liens */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100px; /* Largeur fixe pour uniformiser */
            margin: 0 auto; /* Centrer le bouton */
        }

        .action-buttons .btn-edit {
            background-color: var(--primary-color); /* Bleu pour Modifier */
            color: white;
        }

        .action-buttons .btn-edit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .action-buttons .btn-delete {
            background-color: var(--error-color); /* Rouge pour Supprimer */
            color: white;
        }

        .action-buttons .btn-delete:hover {
            background-color: #c82333; /* Rouge plus foncé */
            transform: translateY(-1px);
        }

        input[type="submit"], .button-link {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.05em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            width: auto;
        }

        input[type="submit"]:hover, .button-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(0, 123, 255, 0.4);
        }

        .nav-links {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            width: 100%;
        }
        .nav-links .button-link.back {
            background: linear-gradient(45deg, #6c757d, #5a6268);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }
        .nav-links .button-link.back:hover {
            background: linear-gradient(45deg, #5a6268, #4e555b);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .emploi-du-temps-table th, .emploi-du-temps-table td {
                padding: 8px 4px;
                font-size: 0.8em;
            }
            .action-buttons .btn-action {
                font-size: 0.7em;
                padding: 5px 8px;
                width: 80px;
            }
        }
    </style>
</head>
<body>

    <h1>Emploi du Temps Généré</h1>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($emploiDuTemps)): ?>
            <p>Aucun emploi du temps généré pour le moment.</p>
            <?php if ($is_admin): ?>
                <div class="nav-links">
                    <a href="generer_emploi.php" class="button-link">Générer l'emploi du temps</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <table class="emploi-du-temps-table">
                <thead>
                    <tr>
                        <th>Créneau / Jour</th>
                        <?php foreach ($joursSemaine as $jour): ?>
                            <th><?= htmlspecialchars($jour) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($creneauxFixes as $creneau_key => $creneau_info): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($creneau_info['label']) ?></strong></td>
                            <?php foreach ($joursSemaine as $jour): ?>
                                <td>
                                    <?php
                                    // Vérifier si un créneau est attribué pour ce jour et ce créneau horaire
                                    if (isset($emploiOrganise[$jour][$creneau_key])) {
                                        $item = $emploiOrganise[$jour][$creneau_key];

                                        $matiere_nom = htmlspecialchars($matieres[$item['matiere_id']]['nom'] ?? 'N/A');
                                        $enseignant_nom = htmlspecialchars($enseignants[$item['enseignant_id']]['nom'] ?? 'N/A');
                                        $salle_nom = htmlspecialchars($salles[$item['salle_id']]['nom'] ?? 'N/A');
                                        $emploi_id = $item['id']; // L'ID unique de l'entrée dans donnees_emploi

                                        echo "<div>Matière: " . $matiere_nom . "</div>";
                                        echo "<div>Enseignant: " . $enseignant_nom . "</div>";
                                        echo "<div>Salle: " . $salle_nom . "</div>";

                                        // Affichage des boutons Modifier/Supprimer UNIQUEMENT pour les administrateurs
                                        if ($is_admin) {
                                            echo "<div class='action-buttons'>";
                                            // Bouton Modifier
                                            echo "<a href='modifier_emploi.php?id=" . $emploi_id . "' class='btn-action btn-edit'>Modifier</a>";

                                            // Bouton Supprimer (via formulaire POST)
                                            echo "<form action='supprimer_emploi.php' method='post' style='display:inline;'>";
                                            echo "<input type='hidden' name='emploi_id' value='" . $emploi_id . "'>";
                                            echo "<button type='submit' class='btn-action btn-delete' onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer ce créneau ?\");'>Supprimer</button>";
                                            echo "</form>";
                                            echo "</div>";
                                        }
                                    } else {
                                        echo "Libre"; // Ou toute autre indication si le créneau est vide
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($is_admin): ?>
                <div class="nav-links">
                    <a href="generer_emploi.php" class="button-link">Regénérer l'emploi du temps</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        // Ajout d'un bouton de retour pour les enseignants ou autres rôles s'ils n'ont pas accès à la génération
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'enseignant') {
            echo '<div class="nav-links"><a href="dashboard_enseignants.php" class="button-link back">Retour au Tableau de Bord</a></div>';
        } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'administrateur') {
             echo '<div class="nav-links"><a href="dashboard_admin.php" class="button-link back">Retour au Tableau de Bord Admin</a></div>';
        } else {
             // Redirection générique si le rôle n'est pas enseignant ou admin (par exemple, vers la page d'accueil)
             echo '<div class="nav-links"><a href="../index.php" class="button-link back">Retour à l\'Accueil</a></div>';
        }
        ?>
    </div>

</body>
</html><?php
session_start();
require_once '../db.php'; // Chemin vers votre fichier de connexion à la base de données
// Si vous avez un fichier config.php pour les créneaux, jours, etc.
// require_once '../config.php';

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = '';
$messageType = '';

// Récupérer les messages de la session si redirigé depuis un autre script (ex: suppression, modification)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    // Nettoyer les messages de la session pour qu'ils ne s'affichent qu'une fois
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Récupérer les données de base pour les listes et l'affichage
try {
    // Récupérer les enseignants
    $stmt_enseignants = $pdo->query("SELECT id, nom FROM enseignants");
    $enseignants = [];
    while ($row = $stmt_enseignants->fetch(PDO::FETCH_ASSOC)) {
        $enseignants[$row['id']] = $row; // Stocke toute la ligne pour plus de flexibilité si besoin
    }

    // Récupérer les matières
    $stmt_matieres = $pdo->query("SELECT id, nom, duree FROM matieres");
    $matieres = [];
    while ($row = $stmt_matieres->fetch(PDO::FETCH_ASSOC)) {
        $matieres[$row['id']] = $row;
    }

    // Récupérer les salles
    $stmt_salles = $pdo->query("SELECT id, nom FROM salles");
    $salles = [];
    while ($row = $stmt_salles->fetch(PDO::FETCH_ASSOC)) {
        $salles[$row['id']] = $row;
    }

    // Récupérer l'emploi du temps généré
    // IL EST TRÈS IMPORTANT QUE CETTE REQUÊTE RÉCUPÈRE L'ID DE CHAQUE ENREGISTREMENT
    $stmt_emploi = $pdo->prepare("SELECT id, enseignant_id, matiere_id, salle_id, jour, creneau FROM donnees_emploi ORDER BY FIELD(jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), creneau");
    $stmt_emploi->execute();
    $emploiDuTemps = $stmt_emploi->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "Erreur de base de données lors du chargement des données : " . $e->getMessage();
    $messageType = "error";
    error_log("Erreur chargement emploi: " . $e->getMessage());
    $enseignants = []; // Initialiser pour éviter les erreurs si la connexion échoue
    $matieres = [];
    $salles = [];
    $emploiDuTemps = [];
}

// Créneaux fixes - DOIVENT CORRESPONDRE À CEUX UTILISÉS POUR LA GÉNÉRATION
// Si ces créneaux sont définis dans un fichier config.php, assurez-vous qu'il est inclus.
$creneauxFixes = [
    '08h00-12h00' => ['label' => 'Matin (08h00-12h00)', 'type' => 'cours', 'duration' => 4, 'alias' => 'matin'],
    '12h00-13h30' => ['label' => 'Pause déjeuner (12h00-13h30)', 'type' => 'pause', 'duration' => 1.5, 'alias' => 'pause_dejeuner'],
    '13h30-17h30' => ['label' => 'Après-midi (13h30-17h30)', 'type' => 'cours', 'duration' => 4, 'alias' => 'midi'],
    '18h00-21h00' => ['label' => 'Soir (18h00-21h00)', 'type' => 'cours', 'duration' => 3, 'alias' => 'soir']
];

// Jours de la semaine
$joursSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

// Organiser l'emploi du temps par jour et créneau pour un affichage facile
$emploiOrganise = [];
foreach ($emploiDuTemps as $item) {
    $emploiOrganise[$item['jour']][$item['creneau']] = $item;
}

// Vérifier si l'utilisateur est un administrateur pour l'affichage des boutons d'action
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'administrateur');

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emploi du Temps Généré</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Votre CSS existant, plus les styles des boutons d'action */
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --success-color: #28a745;
            --error-color: #dc3545;
            --info-color: #17a2b8;
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
        }

        .container {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px var(--shadow-light);
            max-width: 1200px; /* Plus large pour l'emploi du temps */
            width: 100%;
            margin-bottom: 40px;
            box-sizing: border-box;
            text-align: center;
        }

        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-weight: 500;
            text-align: left;
            animation: fadeInSlideDown 0.5s forwards ease-out;
            word-break: break-word;
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
        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        @keyframes fadeInSlideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .emploi-du-temps-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .emploi-du-temps-table th,
        .emploi-du-temps-table td {
            border: 1px solid var(--border-color);
            padding: 12px 8px;
            text-align: center;
            vertical-align: top; /* Pour aligner le contenu en haut des cellules */
        }

        .emploi-du-temps-table th {
            background-color: #eaf1f8;
            font-weight: 600;
            color: var(--text-dark);
        }

        .emploi-du-temps-table td {
            background-color: #fdfdfd;
            font-size: 0.9em;
            line-height: 1.4;
        }

        .emploi-du-temps-table td div {
            margin-bottom: 3px;
        }

        .emploi-du-temps-table td div:last-child {
            margin-bottom: 0;
        }

        /* Styles pour les boutons d'action dans le tableau de l'emploi du temps */
        .action-buttons {
            display: flex;
            flex-direction: column; /* Boutons l'un en dessous de l'autre */
            gap: 5px; /* Espace entre les boutons */
            justify-content: center; /* Centre les boutons dans la cellule */
            margin-top: 10px; /* Un peu d'espace avec le contenu au-dessus */
        }

        .action-buttons .btn-action {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em; /* Plus petit pour s'intégrer */
            font-weight: 500;
            transition: background-color 0.2s ease, transform 0.2s ease;
            text-decoration: none; /* Pour les liens */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100px; /* Largeur fixe pour uniformiser */
            margin: 0 auto; /* Centrer le bouton */
        }

        .action-buttons .btn-edit {
            background-color: var(--primary-color); /* Bleu pour Modifier */
            color: white;
        }

        .action-buttons .btn-edit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .action-buttons .btn-delete {
            background-color: var(--error-color); /* Rouge pour Supprimer */
            color: white;
        }

        .action-buttons .btn-delete:hover {
            background-color: #c82333; /* Rouge plus foncé */
            transform: translateY(-1px);
        }

        input[type="submit"], .button-link {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.05em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            width: auto;
        }

        input[type="submit"]:hover, .button-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(0, 123, 255, 0.4);
        }

        .nav-links {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            width: 100%;
        }
        .nav-links .button-link.back {
            background: linear-gradient(45deg, #6c757d, #5a6268);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }
        .nav-links .button-link.back:hover {
            background: linear-gradient(45deg, #5a6268, #4e555b);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .emploi-du-temps-table th, .emploi-du-temps-table td {
                padding: 8px 4px;
                font-size: 0.8em;
            }
            .action-buttons .btn-action {
                font-size: 0.7em;
                padding: 5px 8px;
                width: 80px;
            }
        }
    </style>
</head>
<body>

    <h1>Emploi du Temps Généré</h1>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($emploiDuTemps)): ?>
            <p>Aucun emploi du temps généré pour le moment.</p>
            <?php if ($is_admin): ?>
                <div class="nav-links">
                    <a href="generer_emploi.php" class="button-link">Générer l'emploi du temps</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <table class="emploi-du-temps-table">
                <thead>
                    <tr>
                        <th>Créneau / Jour</th>
                        <?php foreach ($joursSemaine as $jour): ?>
                            <th><?= htmlspecialchars($jour) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($creneauxFixes as $creneau_key => $creneau_info): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($creneau_info['label']) ?></strong></td>
                            <?php foreach ($joursSemaine as $jour): ?>
                                <td>
                                    <?php
                                    // Vérifier si un créneau est attribué pour ce jour et ce créneau horaire
                                    if (isset($emploiOrganise[$jour][$creneau_key])) {
                                        $item = $emploiOrganise[$jour][$creneau_key];

                                        $matiere_nom = htmlspecialchars($matieres[$item['matiere_id']]['nom'] ?? 'N/A');
                                        $enseignant_nom = htmlspecialchars($enseignants[$item['enseignant_id']]['nom'] ?? 'N/A');
                                        $salle_nom = htmlspecialchars($salles[$item['salle_id']]['nom'] ?? 'N/A');
                                        $emploi_id = $item['id']; // L'ID unique de l'entrée dans donnees_emploi

                                        echo "<div>Matière: " . $matiere_nom . "</div>";
                                        echo "<div>Enseignant: " . $enseignant_nom . "</div>";
                                        echo "<div>Salle: " . $salle_nom . "</div>";

                                        // Affichage des boutons Modifier/Supprimer UNIQUEMENT pour les administrateurs
                                        if ($is_admin) {
                                            echo "<div class='action-buttons'>";
                                            // Bouton Modifier
                                            echo "<a href='modifier_emploi.php?id=" . $emploi_id . "' class='btn-action btn-edit'>Modifier</a>";

                                            // Bouton Supprimer (via formulaire POST)
                                            echo "<form action='supprimer_emploi.php' method='post' style='display:inline;'>";
                                            echo "<input type='hidden' name='emploi_id' value='" . $emploi_id . "'>";
                                            echo "<button type='submit' class='btn-action btn-delete' onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer ce créneau ?\");'>Supprimer</button>";
                                            echo "</form>";
                                            echo "</div>";
                                        }
                                    } else {
                                        echo "Libre"; // Ou toute autre indication si le créneau est vide
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($is_admin): ?>
                <div class="nav-links">
                    <a href="generer_emploi.php" class="button-link">Regénérer l'emploi du temps</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        // Ajout d'un bouton de retour pour les enseignants ou autres rôles s'ils n'ont pas accès à la génération
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'enseignant') {
            echo '<div class="nav-links"><a href="dashboard_enseignants.php" class="button-link back">Retour au Tableau de Bord</a></div>';
        } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'administrateur') {
             echo '<div class="nav-links"><a href="dashboard_admin.php" class="button-link back">Retour au Tableau de Bord Admin</a></div>';
        } else {
             // Redirection générique si le rôle n'est pas enseignant ou admin (par exemple, vers la page d'accueil)
             echo '<div class="nav-links"><a href="../index.php" class="button-link back">Retour à l\'Accueil</a></div>';
        }
        ?>
    </div>

</body>
</html>