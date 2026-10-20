<?php
session_start();
require_once '../db.php';

// Vérifie que l'utilisateur est bien un enseignant connecté
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jour = $_POST['jour'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];
    
    $enseignant_id = $_SESSION['user_id']; // ID sécurisé depuis la session

    // Insertion
    $stmt = $pdo->prepare("INSERT INTO disponibilites (enseignant_id, jour, heure_debut, heure_fin) VALUES (?, ?, ?, ?)");
    $stmt->execute([$enseignant_id, $jour, $heure_debut, $heure_fin]);

    echo "Disponibilité enregistrée avec succès pour l’enseignant ID $enseignant_id.";
}
?>

<form method="post">
    <label for="jour">Jour :</label>
    <select name="jour" required>
        <option value="Lundi">Lundi</option>
        <option value="Mardi">Mardi</option>
        <!-- etc -->
    </select><br>

    <label>Heure de début :</label>
    <input type="time" name="heure_debut" required><br>

    <label>Heure de fin :</label>
    <input type="time" name="heure_fin" required><br>

    <button type="submit">Enregistrer</button>
</form>
