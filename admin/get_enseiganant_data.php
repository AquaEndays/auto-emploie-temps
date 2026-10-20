<?php
// get_enseignant_data.php

// Activer l'affichage des erreurs PHP pour le débogage (à désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1); // Assurez-vous que les erreurs sont loggées
ini_set('error_log', __DIR__ . '/php_error.log'); // Chemin vers un fichier de log d'erreurs spécifique

// Configuration de la connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "root"; // Assurez-vous que ce mot de passe correspond à votre configuration
$dbname = "gilles_le_magnifique";

$connexion = mysqli_connect($servername, $username, $password, $dbname);

// Toujours définir le Content-Type avant toute sortie
header('Content-Type: application/json');

if (!$connexion) {
    $error_message = 'Échec de la connexion à la base de données : ' . mysqli_connect_error();
    error_log("get_enseignant_data.php DB Error: " . $error_message); // Log l'erreur
    echo json_encode(['error' => 'Erreur de connexion à la base de données.']);
    exit();
}

// Vérifier si l'ID de l'enseignant est bien reçu
if (isset($_GET['enseignant_id']) && is_numeric($_GET['enseignant_id']) && !isset($_GET['jour'])) {
    // Ceci est la requête initiale pour la matière et les jours
    $enseignantId = intval($_GET['enseignant_id']);
    error_log("get_enseignant_data.php - Enseignant ID reçu: " . $enseignantId);

    $response = [
        'matiere' => null,
        'jours' => []
    ];

    // --- 1. Récupérer la matière de l'enseignant depuis la table 'enseignants' ---
    $requete_matiere = "SELECT matiere FROM enseignants WHERE id = ?";
    $stmt_matiere = mysqli_prepare($connexion, $requete_matiere);
    if ($stmt_matiere) {
        mysqli_stmt_bind_param($stmt_matiere, "i", $enseignantId);
        if (!mysqli_stmt_execute($stmt_matiere)) {
            $error_message = "Erreur d'exécution de la requête matière: " . mysqli_stmt_error($stmt_matiere);
            error_log("get_enseignant_data.php - " . $error_message);
            // Ne pas renvoyer un exit() ici, la suite du script peut être utile
        } else {
            $result_matiere = mysqli_stmt_get_result($stmt_matiere);
            if ($row = mysqli_fetch_assoc($result_matiere)) {
                $response['matiere'] = htmlspecialchars($row['matiere']);
                error_log("get_enseignant_data.php - Matière trouvée: " . $response['matiere']);
            } else {
                error_log("get_enseignant_data.php - Aucune matière trouvée pour enseignant ID: " . $enseignantId);
            }
        }
        mysqli_stmt_close($stmt_matiere);
    } else {
        $error_message = "Erreur de préparation de la requête matière: " . mysqli_error($connexion);
        error_log("get_enseignant_data.php - " . $error_message);
    }

    // --- 2. Récupérer les jours de disponibilité de l'enseignant ---
    $requete_jours = "SELECT DISTINCT jour FROM disponibilites WHERE enseignant_id = ? ORDER BY jour ASC";
    $stmt_jours = mysqli_prepare($connexion, $requete_jours);
    if ($stmt_jours) {
        mysqli_stmt_bind_param($stmt_jours, "i", $enseignantId);
        if (!mysqli_stmt_execute($stmt_jours)) {
            $error_message = "Erreur d'exécution de la requête jours: " . mysqli_stmt_error($stmt_jours);
            error_log("get_enseignant_data.php - " . $error_message);
        } else {
            $result_jours = mysqli_stmt_get_result($stmt_jours);
            while ($row = mysqli_fetch_assoc($result_jours)) {
                $response['jours'][] = htmlspecialchars($row['jour']);
            }
            error_log("get_enseignant_data.php - Jours trouvés: " . implode(', ', $response['jours']));
        }
        mysqli_stmt_close($stmt_jours);
    } else {
        $error_message = "Erreur de préparation de la requête jours: " . mysqli_error($connexion);
        error_log("get_enseignant_data.php - " . $error_message);
    }

    echo json_encode($response);

} elseif (isset($_GET['enseignant_id']) && isset($_GET['jour'])) {
    // Ceci est la requête pour les créneaux, qui est faite après que l'enseignant ET le jour sont sélectionnés.
    $enseignantId = intval($_GET['enseignant_id']);
    $jour = $_GET['jour'];
    error_log("get_enseignant_data.php - Requête créneaux pour Enseignant ID: " . $enseignantId . ", Jour: " . $jour);

    $creneaux_disponibles = [];
    $requete_creneaux_jour = "
        SELECT DISTINCT d.creneau
        FROM disponibilites d
        LEFT JOIN donnees_emploi de ON d.enseignant_id = de.enseignant_id
                                    AND d.jour = de.jour
                                    AND d.creneau = de.creneau
        WHERE d.enseignant_id = ?
          AND d.jour = ?
          AND de.id IS NULL -- Exclut les créneaux qui ont déjà un enregistrement dans donnees_emploi
        ORDER BY d.creneau ASC
    ";
    $stmt_creneaux = mysqli_prepare($connexion, $requete_creneaux_jour);
    if ($stmt_creneaux) {
        mysqli_stmt_bind_param($stmt_creneaux, "is", $enseignantId, $jour);
        if (!mysqli_stmt_execute($stmt_creneaux)) {
            $error_message = "Erreur d'exécution de la requête créneaux: " . mysqli_stmt_error($stmt_creneaux);
            error_log("get_enseignant_data.php - " . $error_message);
        } else {
            $result_creneaux = mysqli_stmt_get_result($stmt_creneaux);
            while ($row = mysqli_fetch_assoc($result_creneaux)) {
                $creneaux_disponibles[] = htmlspecialchars($row['creneau']);
            }
            error_log("get_enseignant_data.php - Créneaux trouvés: " . implode(', ', $creneaux_disponibles));
            mysqli_free_result($result_creneaux);
        }
        mysqli_stmt_close($stmt_creneaux);
    } else {
        $error_message = "Erreur de préparation de la requête créneaux: " . mysqli_error($connexion);
        error_log("get_enseignant_data.php - " . $error_message);
    }
    echo json_encode(['creneaux' => $creneaux_disponibles]);

} else {
    // ID d'enseignant manquant ou invalide
    $error_message = 'Paramètres manquants ou invalides dans la requête GET.';
    error_log("get_enseignant_data.php - Erreur: " . $error_message . " GET: " . json_encode($_GET));
    echo json_encode(['error' => 'Paramètres de requête manquants ou invalides.']);
}

mysqli_close($connexion);
?>