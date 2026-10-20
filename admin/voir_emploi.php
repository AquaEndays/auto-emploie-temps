<?php
// --- Configuration de la connexion à la base de données ---
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "gilles_le_magnifique";

$connexion = mysqli_connect($servername, $username, $password, $dbname);

if (!$connexion) {
    die("Échec de la connexion à la base de données : " . mysqli_connect_error());
}

$emplois_generes = [];
$message = "";
$messageType = "";

// --- Valeurs codées en dur pour la durée et le temps restant ---
$duree_cours_par_defaut = "3h"; // Durée fixe pour chaque cours
$heures_max_disponibles_initiales = 29; // Total initial d'heures "restantes"

// Pour un affichage simplifié sans calcul dynamique, nous allons juste afficher la valeur initiale.
// Si vous voulez un calcul simple du "temps restant" basé sur les CRENEAUX alloués (et non sur une durée de matière), on pourrait le faire.
// Par exemple: Chaque créneau '08h00-12h00' = 4h, '13h30-17h30' = 4h.
// Pour l'instant, je vais laisser les 29h fixes comme demandé.
$heures_restantes_a_afficher = $heures_max_disponibles_initiales; // Valeur fixe à afficher

// --- Logique d'exportation CSV (non modifiée pour la durée, car elle n'est pas en DB) ---
if (isset($_POST['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=emploi_du_temps_genere.csv');
    $output = fopen('php://output', 'w');

    // Entêtes CSV
    // Note: 'Duree Heures' ici affichera N/A ou la valeur par défaut car elle n'est pas tirée de la DB
    fputcsv($output, ['ID', 'Enseignant', 'Matiere', 'Salle', 'Jour', 'Creneau', 'Date Generation']);

    // Récupérer les données de la table emploi_du_temps
    $requete_export = "
        SELECT
            edt.id,
            COALESCE(e.nom, 'N/A') AS nom_enseignant,
            COALESCE(m.nom, 'N/A') AS matiere_nom,
            COALESCE(s.nom, 'N/A') AS salle_nom,
            edt.jour,
            edt.creneau,
            edt.date_generation
        FROM
            emploi_du_temps edt
        LEFT JOIN
            enseignants e ON edt.enseignant_id = e.id
        LEFT JOIN
            matieres m ON edt.matiere_id = m.id
        LEFT JOIN
            salles s ON edt.salle_id = s.id
        ORDER BY
            FIELD(edt.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'),
            FIELD(edt.creneau, '08h00-12h00', '12h00-13h30', '13h30-17h30', '18h00-21h00'),
            e.nom ASC
    ";
    $result_export = mysqli_query($connexion, $requete_export);

    if ($result_export && mysqli_num_rows($result_export) > 0) {
        while ($row = mysqli_fetch_assoc($result_export)) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit;
}

// --- Récupérer tous les emplois du temps générés pour l'affichage ---
$requete_emplois_generes = "
    SELECT
        edt.id,
        COALESCE(e.nom, 'N/A') AS nom_enseignant,
        COALESCE(m.nom, 'N/A') AS matiere_nom,
        COALESCE(s.nom, 'N/A') AS salle_nom,
        edt.jour,
        edt.creneau,
        edt.date_generation
    FROM
        emploi_du_temps edt
    LEFT JOIN
        enseignants e ON edt.enseignant_id = e.id
    LEFT JOIN
        matieres m ON edt.matiere_id = m.id
    LEFT JOIN
        salles s ON edt.salle_id = s.id
    ORDER BY
        FIELD(edt.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'),
        FIELD(edt.creneau, '08h00-12h00', '12h00-13h30', '13h30-17h30', '18h00-21h00'),
        e.nom ASC
";
$result_emplois_generes = mysqli_query($connexion, $requete_emplois_generes);

if ($result_emplois_generes) {
    while ($row = mysqli_fetch_assoc($result_emplois_generes)) {
        $emplois_generes[] = $row;
    }
    mysqli_free_result($result_emplois_generes);
} else {
    $message = "Erreur lors de la récupération des emplois du temps : " . mysqli_error($connexion);
    $messageType = "error";
}

if (empty($emplois_generes) && empty($message)) {
    $message = "Aucun emploi du temps n'a encore été généré automatiquement. Veuillez retourner à la page de génération pour en créer un.";
    $messageType = "info";
}

// Pour afficher l'emploi du temps sous forme de tableau visuel (comme l'image)
$tableau_edt = [];
$joursSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$creneauxAffichage = ['08h00-12h00', '12h00-13h30', '13h30-17h30', '18h00-21h00'];

foreach ($joursSemaine as $jour) {
    foreach ($creneauxAffichage as $creneau) {
        $tableau_edt[$jour][$creneau] = [
            'enseignant' => 'N/A',
            'matiere' => 'N/A',
            'salle' => 'N/A',
            'duree_heures' => $duree_cours_par_defaut // Affectation de la durée par défaut ici
        ]; // Initialiser toutes les cases
    }
}

foreach ($emplois_generes as $emploi) {
    $jour = $emploi['jour'];
    $creneau = $emploi['creneau'];

    if (isset($tableau_edt[$jour][$creneau])) {
        $tableau_edt[$jour][$creneau]['enseignant'] = htmlspecialchars($emploi['nom_enseignant']);
        $tableau_edt[$jour][$creneau]['matiere'] = htmlspecialchars($emploi['matiere_nom']);
        $tableau_edt[$jour][$creneau]['salle'] = htmlspecialchars($emploi['salle_nom']);
        // La durée est déjà affectée par défaut dans la boucle d'initialisation,
        // donc pas besoin de la récupérer de la DB ici.
    }
}

mysqli_close($connexion);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emploi du Temps Généré</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
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
            --shadow-medium: rgba(0, 0, 0, 0.15);
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); margin: 0; padding: 30px 20px; color: var(--text-dark); display: flex; flex-direction: column; align-items: center; min-height: 100vh; box-sizing: border-box; }
        h1 { color: var(--primary-color); margin-bottom: 30px; font-weight: 600; text-align: center; letter-spacing: 0.5px; }
        .container { background-color: var(--card-bg); padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px var(--shadow-light); max-width: 1200px; width: 100%; margin-bottom: 40px; box-sizing: border-box; }
        .message { padding: 15px 20px; margin-bottom: 25px; border-radius: 8px; font-weight: 500; display: flex; align-items: center; opacity: 0; transform: translateY(-10px); animation: fadeInSlideDown 0.5s forwards ease-out; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .message::before { content: '✓'; font-size: 1.2em; margin-right: 10px; font-weight: bold; }
        .message.error::before { content: '✕'; }
        .message.info::before { content: 'ℹ️'; }
        @keyframes fadeInSlideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        /* Styles pour le tableau de l'emploi du temps */
        .emploi-du-temps-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .emploi-du-temps-table th, .emploi-du-temps-table td {
            border: 1px solid var(--border-color);
            padding: 10px 15px;
            text-align: center;
            vertical-align: middle;
            font-size: 0.9em;
            position: relative;
        }
        .emploi-du-temps-table thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 12px 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .emploi-du-temps-table tbody th {
            background-color: #eaf1f8;
            color: var(--text-dark);
            font-weight: 600;
            white-space: nowrap;
        }
        .emploi-du-temps-table td {
            background-color: var(--card-bg);
            min-width: 120px; /* Largeur minimale pour les cellules */
        }

        /* Couleurs pour les types de créneaux / contenus */
        .creneau-matiere { background-color: #e6f7ff; } /* Bleu très clair */
        .creneau-tpe { background-color: #f0f0f0; } /* Gris clair pour TPE */
        .creneau-pause { background-color: #ffeccb; font-weight: 600; color: #8a6d3b; } /* Orange très clair pour pause */

        .cell-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 60px; /* Hauteur minimale pour chaque cellule de contenu */
        }
        .cell-content strong { color: var(--primary-color); margin-bottom: 3px; font-size: 1em; }
        .cell-content span { font-size: 0.85em; color: var(--text-light); }
        .cell-content .duree {
            font-size: 0.8em;
            color: var(--info-color); /* Couleur pour la durée */
            margin-top: 5px;
            font-weight: 600;
        }

        /* Affichage des heures restantes */
        .heures-restantes {
            background-color: #d1ecf1; /* Fond bleu clair pour les infos */
            color: #0c5460;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border: 1px solid #bee5eb;
        }


        /* Boutons d'action */
        .button-group {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .button-group form, .button-group .button-link {
            flex: 1;
            min-width: 200px;
            max-width: 280px;
        }
        .button-group input[type="submit"], .button-group .button-link {
            padding: 15px 25px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            color: white;
        }
        .button-group input[type="submit"]:hover, .button-group .button-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.3);
        }
        .button-export {
            background: linear-gradient(45deg, #6f42c1, #8a2be2);
        }
        .button-export:hover {
            background: linear-gradient(45deg, #563d7c, #7b10d0);
        }
        .button-back {
            background: linear-gradient(45deg, #6c757d, #5a6268);
        }
        .button-back:hover {
            background: linear-gradient(45deg, #5a6268, #4e555b);
        }
        .button-back.home {
            background: linear-gradient(45deg, #5bc0de, #46b8da); /* Bleu clair pour le retour à l'accueil */
        }
        .button-back.home:hover {
            background: linear-gradient(45deg, #46b8da, #31b0d5);
        }

        @media (max-width: 1024px) {
            .emploi-du-temps-table th, .emploi-du-temps-table td { padding: 8px 10px; font-size: 0.85em; }
            .emploi-du-temps-table thead th { padding: 10px 12px; }
            .cell-content { min-height: 50px; }
            .cell-content strong { font-size: 0.9em; }
            .cell-content span { font-size: 0.75em; }
        }
        @media (max-width: 768px) {
            .container { padding: 20px; }
            .emploi-du-temps-table { display: block; overflow-x: auto; white-space: nowrap; } /* Permet le défilement horizontal */
            .emploi-du-temps-table thead, .emploi-du-temps-table tbody, .emploi-du-temps-table th, .emploi-du-temps-table td, .emploi-du-temps-table tr {
                display: block; /* Chaque élément prend toute la largeur */
            }
            .emploi-du-temps-table tr { margin-bottom: 10px; border: 1px solid var(--border-color); display: flex; flex-direction: column; }
            .emploi-du-temps-table thead th { display: none; } /* Cacher les en-têtes de colonne */
            .emploi-du-temps-table tbody th { text-align: left; background-color: var(--primary-color); color: white; padding: 10px; margin-bottom: 5px; } /* Les en-têtes de ligne deviennent des titres de bloc */
            .emploi-du-temps-table td { text-align: left; padding: 8px 10px; border: none; border-bottom: 1px solid var(--border-color); }
            .emploi-du-temps-table td::before { /* Afficher le nom de la colonne */
                content: attr(data-label);
                font-weight: 600;
                margin-right: 10px;
                color: var(--text-dark);
                min-width: 80px;
                display: inline-block;
            }
            .cell-content { align-items: flex-start; min-height: unset; }
            .button-group { flex-direction: column; }
            .button-group form, .button-group .button-link { width: 100%; }
        }
    </style>
</head>
<body>

    <h1>Emploi du Temps Généré Automatiquement</h1>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="heures-restantes">
            Heures de cours restantes à attribuer : **<?php echo $heures_restantes_a_afficher; ?>h** sur 29h
        </div>

        <?php if (empty($emplois_generes) && $messageType != 'error'): ?>
            <p style="text-align: center; color: var(--text-light);">Veuillez générer l'emploi du temps depuis la page dédiée.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="emploi-du-temps-table">
                    <thead>
                        <tr>
                            <th>Plage Horaire</th>
                            <?php foreach ($joursSemaine as $jour): ?>
                                <th><?php echo htmlspecialchars($jour); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($creneauxAffichage as $creneau): ?>
                            <tr>
                                <th><?php echo htmlspecialchars($creneau); ?></th>
                                <?php foreach ($joursSemaine as $jour):
                                    $data = $tableau_edt[$jour][$creneau];
                                    $class = '';
                                    if ($creneau == '12h00-13h30') { // Nouvelle plage de pause
                                        $class = 'creneau-pause';
                                    } elseif ($data['matiere'] == 'TPE') {
                                        $class = 'creneau-tpe';
                                    } elseif ($data['matiere'] != 'N/A') {
                                        $class = 'creneau-matiere';
                                    }
                                ?>
                                    <td class="<?php echo $class; ?>"
                                        data-label="Matière:">
                                        <div class="cell-content">
                                            <?php if ($creneau == '12h00-13h30'): ?>
                                                <span>PAUSE</span>
                                            <?php else: ?>
                                                <?php if ($data['matiere'] != 'N/A'): ?>
                                                    <strong><?php echo $data['matiere']; ?></strong>
                                                    <span class="duree">(<?php echo $data['duree_heures']; ?>)</span>
                                                <?php endif; ?>
                                                <?php if ($data['enseignant'] != 'N/A' && $data['enseignant'] != '0'): ?>
                                                    <span>Enseignant: <?php echo $data['enseignant']; ?></span>
                                                <?php endif; ?>
                                                <?php if ($data['salle'] != 'N/A' && $data['salle'] != '0'): ?>
                                                    <span>Salle: <?php echo $data['salle']; ?></span>
                                                <?php endif; ?>
                                                <?php if ($data['matiere'] == 'N/A' && $data['enseignant'] == 'N/A' && $data['salle'] == 'N/A'): ?>
                                                    <span>Non attribué</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="button-group">
            <?php if (!empty($emplois_generes)): ?>
                <form method="post" action="voir_emploi.php">
                    <input type="submit" name="export_csv" value="Exporter en CSV" class="button-export">
                </form>
            <?php endif; ?>
            <a href="generer_emploi.php" class="button-link button-back">Retour à la génération</a>
            <a href="pas_emploi.php" class="button-link button-back home">Retour à la saisie manuelle</a>
        </div>
    </div>

</body>
</html>