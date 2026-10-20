<?php
// get_matiere_enseignant.php

// Configuration de la connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "GILLES26"; // Assurez-vous que ce mot de passe correspond à votre configuration
$dbname = "gilles_le_magnifique";

$connexion = mysqli_connect($servername, $username, $password, $dbname);

if (!$connexion) {
    // En cas d'échec, renvoyer une erreur JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Échec de la connexion à la base de données']);
    exit();
}

// Vérifier si l'ID de l'enseignant est bien reçu
if (isset($_GET['enseignant_id']) && is_numeric($_GET['enseignant_id'])) {
    $enseignantId = intval($_GET['enseignant_id']);

    // Préparer et exécuter la requête pour récupérer la matière de l'enseignant
    // Assurez-vous que la colonne est bien 'matiere' dans votre table 'enseignants'
    $requete_matiere = "SELECT matiere FROM enseignants WHERE id = ?";
    $stmt = mysqli_prepare($connexion, $requete_matiere);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $enseignantId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $matiereEnseignant = null;
        if ($row = mysqli_fetch_assoc($result)) {
            $matiereEnseignant = htmlspecialchars($row['matiere']); // Sécuriser la sortie
        }
        mysqli_stmt_close($stmt);

        // Renvoyer la matière (ou null si non trouvée) en JSON
        header('Content-Type: application/json');
        echo json_encode(['matiere' => $matiereEnseignant]);

    } else {
        // Erreur de préparation de la requête
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Erreur de préparation de la requête : ' . mysqli_error($connexion)]);
    }

} else {
    // ID d'enseignant manquant ou invalide
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID enseignant manquant ou invalide']);
}

mysqli_close($connexion);
?>