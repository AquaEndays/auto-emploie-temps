<?php
// get_matiere_enseignant.php - Script AJAX pour récupérer la matière d'un enseignant

// Inclut le fichier de connexion à la base de données (qui doit fournir la variable $pdo)
require_once __DIR__ . '/../db.php';

// Vérifie que la connexion PDO est bien établie
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Si la connexion PDO n'est pas valide, renvoie une erreur JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => 'La connexion à la base de données n\'a pas été établie.']);
    exit();
}

// Vérifie si l'ID de l'enseignant est passé en paramètre GET
if (isset($_GET['enseignant_id'])) {
    $enseignant_id = intval($_GET['enseignant_id']);

    // Modifiez cette condition ou retirez-la complètement.
    // L'ID doit juste être valide (supérieur à 0).
    if ($enseignant_id > 0) { // <-- MODIFICATION ICI : Remplacez '> 4' par '> 0'
        try {
            // Prépare la requête pour récupérer la matière de l'enseignant
            // Nous récupérons 'matiere' de la table 'enseignants'
            $stmt = $pdo->prepare("SELECT matiere FROM enseignants WHERE id = :enseignant_id");
            $stmt->bindParam(':enseignant_id', $enseignant_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Définit l'en-tête pour indiquer que la réponse est du JSON
            header('Content-Type: application/json');

            if ($result) {
                // Renvoie la matière en format JSON
                echo json_encode(['matiere' => $result['matiere']]);
            } else {
                // Renvoie une matière vide si non trouvée ou erreur
                echo json_encode(['matiere' => '']);
            }
        } catch (PDOException $e) {
            // En cas d'erreur de base de données, renvoie une erreur JSON
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Erreur de base de données : ' . $e->getMessage()]);
        }
    } else {
        // ID enseignant invalide (<= 0)
        header('Content-Type: application/json');
        echo json_encode(['matiere' => '']); // Renvoie vide pour un ID invalide
    }
} else {
    // Si enseignant_id n'est pas fourni
    header('Content-Type: application/json');
    echo json_encode(['matiere' => '']); // Renvoie vide si le paramètre est manquant
}
exit();
?>