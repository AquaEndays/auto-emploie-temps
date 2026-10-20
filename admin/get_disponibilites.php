<?php
// ENLEVEZ OU COMMENTEZ CETTE LIGNE TEMPORAIREMENT POUR VOIR LES ERREURS PHP
// header('Content-Type: application/json');

// --- Configuration de la connexion à la base de données ---
$servername = "localhost";
$username = "root";
$password = "root"; // VÉRIFIEZ ENCORE UNE FOIS CE MOT DE PASSE ! SOUVENT, C'EST VIDE ("")
$dbname = "gilles_le_magnifique";

echo "DEBUG: Tentative de connexion à la DB...<br>";
$connexion = mysqli_connect($servername, $username, $password, $dbname);

if (!$connexion) {
    die("DEBUG: Échec FATAL de la connexion à la base de données : " . mysqli_connect_error());
}
echo "DEBUG: Connexion DB réussie !<br>";

// --- Récupérer l'ID de l'enseignant depuis la requête GET ---
$enseignantId = isset($_GET['enseignant_id']) ? intval($_GET['enseignant_id']) : 0;

echo "DEBUG: ID de l'enseignant reçu : " . $enseignantId . "<br>";

$disponibilites = [];

if ($enseignantId > 0) {
    // VÉRIFIEZ BIEN LES NOMS DES COLONNES ET DE LA TABLE ICI !
    // D'après votre image, c'est bien 'disponibilites' et les colonnes 'id' et 'jour'
    $requete_disponibilites = "SELECT id, jour FROM disponibilites WHERE enseignant_id = ? ORDER BY jour ASC";

    echo "DEBUG: Requête SQL préparée : " . $requete_disponibilites . "<br>";

    $stmt = mysqli_prepare($connexion, $requete_disponibilites);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $enseignantId);
        echo "DEBUG: Paramètre ID lié : " . $enseignantId . "<br>";

        mysqli_stmt_execute($stmt);
        echo "DEBUG: Requête exécutée.<br>";

        $result = mysqli_stmt_get_result($stmt);

        if ($result) { // Vérifie si mysqli_stmt_get_result a retourné un résultat
            $num_rows = mysqli_num_rows($result);
            echo "DEBUG: Nombre de lignes trouvées : " . $num_rows . "<br>";

            if ($num_rows > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $disponibilites[] = $row;
                    echo "DEBUG: Disponibilité récupérée : " . print_r($row, true) . "<br>";
                }
                mysqli_free_result($result);
            } else {
                echo "DEBUG: Aucune disponibilité trouvée pour cet enseignant.<br>";
            }
        } else {
            echo "DEBUG: Erreur lors de l'obtention du résultat : " . mysqli_error($connexion) . "<br>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "DEBUG: Erreur de préparation de la requête: " . mysqli_error($connexion) . "<br>";
    }
} else {
    echo "DEBUG: ID enseignant invalide ou non fourni.<br>";
}

mysqli_close($connexion);
echo "DEBUG: Connexion DB fermée.<br>";

// Normalement, vous feriez echo json_encode($disponibilites); ici.
// Pour le débogage, nous allons juste afficher le tableau final.
echo "DEBUG: Contenu final du tableau \$disponibilites avant JSON : <pre>";
print_r($disponibilites);
echo "</pre><br>";

// NE PAS REMETTRE LE header('Content-Type: application/json'); AVANT D'AVOIR FINI CE DÉBOGAGE.
// echo json_encode($disponibilites); // Cette ligne doit être la seule sortie JSON à la fin

?>