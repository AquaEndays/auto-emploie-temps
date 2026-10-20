<?php
// Indique que la réponse sera du JSON
header('Content-Type: application/json');

// --- Configuration de la connexion à la base de données ---
$servername = "localhost";
$username = "root";
$password = "root"; // Ou "pwroot"
$dbname = "gilles_le_magnifique";

$connexion = mysqli_connect($servername, $username, $password, $dbname);

if (!$connexion) {
    // Si la connexion échoue, renvoyer une erreur JSON
    echo json_encode(['error' => 'Échec de la connexion à la base de données.']);
    exit(); // Arrêter l'exécution du script
}

// --- Récupérer l'ID de l'enseignant depuis la requête GET (c'est l'ID qui transite !) ---
// Utilisez isset() et is_numeric() pour valider l'entrée
$enseignantId = isset($_GET['enseignant_id']) ? intval($_GET['enseignant_id']) : 0; // Convertir en entier

$cours = []; // Initialiser le tableau de cours

if ($enseignantId > 0) { // S'assurer que l'ID est valide
    // Requête préparée pour la sécurité (évite les injections SQL)
    $stmt = mysqli_prepare($connexion, "SELECT id_cours, nom_cours FROM cours_enseignant WHERE id_enseignant = ? ORDER BY nom_cours ASC");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $enseignantId); // 'i' pour integer
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $cours[] = $row;
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    } else {
        // Gérer l'erreur de préparation de la requête
        error_log("Erreur de préparation de la requête: " . mysqli_error($connexion)); // Loguer l'erreur
    }
}

// Fermer la connexion
mysqli_close($connexion);

// Renvoyer les cours au format JSON
echo json_encode($cours);
?>