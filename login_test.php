<?php
session_start();
require_once 'db.php'; // Assurez-vous que ce chemin est correct

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $motdepasse = $_POST['motdepasse'];

    try {
        // Cette requête cherche dans la table `utilisateurs` créée ci-dessus.
        $stmt = $pdo->prepare("SELECT id, nom, mot_de_passe, type_utilisateur FROM utilisateurs WHERE nom = ?");
        $stmt->execute([$nom]);
        $user = $stmt->fetch();

        if ($user && $motdepasse === $user['mot_de_passe']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nom']; // Stocke le nom directement
            $_SESSION['user_type'] = $user['type_utilisateur']; // 'admin', 'enseignant', 'etudiant'

            // Redirection vers le chat une fois connecté
            header("Location: chat.php");
            exit();
        } else {
            $erreur = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    } catch (PDOException $e) {
        $erreur = "Erreur de base de données : " . $e->getMessage();
        error_log("Login error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Test Chat</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        h2 { color: #333; margin-bottom: 20px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .error { color: red; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Connexion au Chat Test</h2>
        <?php if ($erreur) echo "<p class='error'>$erreur</p>"; ?>
        <form method="POST">
            <input type="text" name="nom" placeholder="Nom d'utilisateur (ex: Administrateur Principal, P, EleveTest)" required>
            <input type="password" name="motdepasse" placeholder="Mot de passe (ex: adminpass, enseignantpass, etudiantpass)" required>
            <button type="submit">Se Connecter au Chat</button>
        </form>
    </div>
</body>
</html>