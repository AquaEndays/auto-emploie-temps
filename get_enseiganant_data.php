<?php
// get_enseignant_data.php

// Configuration de la connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "GILLES26"; // Assurez-vous que ce mot de passe correspond à votre configuration
$dbname = "gilles_le_magnifique";

$connexion = mysqli_connect($servername, $username, $password, $dbname);

if (!$connexion) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Échec de la connexion à la base de données']);
    exit();
}

// Assurez-vous que la réponse est au format JSON
header('Content-Type: application/json');

// Vérifier si l'ID de l'enseignant est bien reçu
if (isset($_GET['enseignant_id']) && is_numeric($_GET['enseignant_id'])) {
    $enseignantId = intval($_GET['enseignant_id']);

    $response = [
        'matiere' => null,
        'jours' => [],
        'creneaux' => [] // Ces créneaux seront initialement vides, ils seront chargés si un jour est sélectionné
    ];

    // --- 1. Récupérer la matière de l'enseignant ---
    $requete_matiere = "SELECT matiere FROM enseignants WHERE id = ?";
    $stmt_matiere = mysqli_prepare($connexion, $requete_matiere);
    if ($stmt_matiere) {
        mysqli_stmt_bind_param($stmt_matiere, "i", $enseignantId);
        mysqli_stmt_execute($stmt_matiere);
        $result_matiere = mysqli_stmt_get_result($stmt_matiere);
        if ($row = mysqli_fetch_assoc($result_matiere)) {
            $response['matiere'] = htmlspecialchars($row['matiere']);
        }
        mysqli_stmt_close($stmt_matiere);
    }

    // --- 2. Récupérer les jours de disponibilité de l'enseignant ---
    $requete_jours = "SELECT DISTINCT jour FROM disponibilites WHERE enseignant_id = ? ORDER BY jour ASC";
    $stmt_jours = mysqli_prepare($connexion, $requete_jours);
    if ($stmt_jours) {
        mysqli_stmt_bind_param($stmt_jours, "i", $enseignantId);
        mysqli_stmt_execute($stmt_jours);
        $result_jours = mysqli_stmt_get_result($stmt_jours);
        while ($row = mysqli_fetch_assoc($result_jours)) {
            $response['jours'][] = htmlspecialchars($row['jour']);
        }
        mysqli_stmt_close($stmt_jours);
    }
    // Note : Les créneaux ne sont pas récupérés ici, car ils dépendent du jour sélectionné.
    // Une autre requête AJAX sera nécessaire lorsque le jour est sélectionné.

    echo json_encode($response);

} elseif (isset($_GET['enseignant_id']) && isset($_GET['jour'])) {
    // Ceci est la requête pour les créneaux, qui est faite après que l'enseignant ET le jour sont sélectionnés.
    $enseignantId = intval($_GET['enseignant_id']);
    $jour = $_GET['jour'];

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
        mysqli_stmt_execute($stmt_creneaux);
        $result_creneaux = mysqli_stmt_get_result($stmt_creneaux);
        while ($row = mysqli_fetch_assoc($result_creneaux)) {
            $creneaux_disponibles[] = htmlspecialchars($row['creneau']);
        }
        mysqli_free_result($result_creneaux);
        mysqli_stmt_close($stmt_creneaux);
    }
    echo json_encode(['creneaux' => $creneaux_disponibles]);

} else {
    // ID d'enseignant manquant ou invalide
    echo json_encode(['error' => 'Paramètres manquants ou invalides']);
}

mysqli_close($connexion);
?>