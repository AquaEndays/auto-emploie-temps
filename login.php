<?php
session_start();
require_once 'db.php'; // Assurez-vous que ce chemin est correct et que db.php initialise $pdo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $motdepasse = $_POST['motdepasse'];

    // --- Connexion Admin codée en dur (PAS RECOMMANDÉ pour la production) ---
    if ($nom === 'Gilles' && $motdepasse === 'admin24') {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'admin';
        $_SESSION['nom'] = 'Gilles';
        header("Location: admin/dashboard.php");
        exit;
    }

    // --- Connexion enseignant depuis la base de données ---
    try {
        $stmt = $pdo->prepare("SELECT id, nom, mot_de_passe FROM enseignants WHERE nom = ?");
        $stmt->execute([$nom]);
        $enseignant = $stmt->fetch();

        if ($enseignant && $motdepasse === $enseignant['mot_de_passe']) {
            $_SESSION['user_id'] = $enseignant['id'];
            $_SESSION['role'] = 'enseignant';
            $_SESSION['nom'] = $enseignant['nom'];
            header("Location: enseignants/dashboard_enseignants.php");
            exit;
        }

        // --- NOUVEAU ET CORRIGÉ : Connexion étudiant depuis la base de données ---
        // J'ai corrigé 'etudiant' en 'etudiants' ici
        $stmt = $pdo->prepare("SELECT id, nom, mot_de_passe, classe_id FROM etudiants WHERE nom = ?");
        $stmt->execute([$nom]);
        $etudiant = $stmt->fetch();

        if ($etudiant && $motdepasse === $etudiant['mot_de_passe']) {
            $_SESSION['user_id'] = $etudiant['id'];
            $_SESSION['role'] = 'etudiant';
            $_SESSION['nom'] = $etudiant['nom'];
            $_SESSION['classe_id'] = $etudiant['classe_id'];

            // Redirection vers l'emploi du temps de l'élève avec son classe_id
            header("Location: emploi_eleve.php?classe_id=" . $etudiant['classe_id']);
            exit;
        }

        // Si aucune correspondance n'est trouvée après toutes les vérifications
        $erreur = "Nom d'utilisateur ou mot de passe incorrect.";

    } catch (PDOException $e) {
        $erreur = "Erreur de base de données : " . $e->getMessage();
        error_log("Login PDO Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Démentielle</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color-start: #000428; /* Bleu très foncé presque noir */
            --bg-color-end: #004e92;   /* Bleu plus clair */
            --form-bg-glass: rgba(255, 255, 255, 0.1); /* Fond du formulaire transparent */
            --border-glass: rgba(255, 255, 255, 0.3); /* Bordure du formulaire */
            --text-light: #e0e0e0;
            --text-dark: #cccccc;
            --primary-accent: #00c6ff; /* Bleu électrique */
            --secondary-accent: #3f5efb; /* Bleu violet */
            --error-color: #ff6b6b; /* Rouge éclatant */
            --shadow-color: rgba(0, 0, 0, 0.4);
        }

        body {
            font-family: 'Orbitron', sans-serif; /* Police futuriste */
            background: linear-gradient(135deg, var(--bg-color-start), var(--bg-color-end));
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
            perspective: 1000px; /* Base pour les transformations 3D */
            position: relative;
        }

        /* Effet de fond : particules/bulles animées */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 15% 50%, var(--primary-accent) 0%, transparent 20%),
                        radial-gradient(circle at 85% 70%, var(--secondary-accent) 0%, transparent 20%);
            opacity: 0.2;
            mix-blend-mode: screen; /* Pour un effet de lumière additionnelle */
            animation: backgroundShift 20s infinite alternate ease-in-out;
            z-index: 0;
        }

        @keyframes backgroundShift {
            0% { transform: scale(1) translate(0, 0); opacity: 0.2; }
            50% { transform: scale(1.2) translate(20px, -20px); opacity: 0.3; }
            100% { transform: scale(1) translate(0, 0); opacity: 0.2; }
        }

        .login-container {
            background: var(--form-bg-glass);
            border-radius: 20px;
            border: 1px solid var(--border-glass); /* Bordure translucide */
            box-shadow: 0 20px 40px var(--shadow-color); /* Ombre plus forte */
            backdrop-filter: blur(15px); /* Effet verre dépoli */
            -webkit-backdrop-filter: blur(15px); /* Pour la compatibilité Webkit */
            padding: 50px;
            width: 400px;
            max-width: 90%;
            text-align: center;
            position: relative;
            z-index: 10; /* Au-dessus du fond animé */
            transform-style: preserve-3d; /* Indispensable pour les enfants 3D */
            animation: fadeInScale 1s ease-out forwards;
            transition: transform 0.3s ease-out;
        }

        /* Effet de rotation/profondeur au survol du conteneur */
        .login-container:hover {
            transform: rotateY(5deg) scale(1.02);
            box-shadow: 0 25px 50px var(--primary-accent); /* Ombre plus lumineuse au survol */
        }

        @keyframes fadeInScale {
            0% { opacity: 0; transform: translateY(-50px) rotateX(10deg) scale(0.8); }
            100% { opacity: 1; transform: translateY(0) rotateX(0deg) scale(1); }
        }

        h1 {
            color: var(--primary-accent);
            font-weight: 700;
            margin-bottom: 35px;
            letter-spacing: 2px;
            text-shadow: 0 0 10px rgba(0, 198, 255, 0.7); /* Effet néon */
            transform: translateZ(30px); /* Plus de profondeur pour le titre */
        }

        h1::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-accent), var(--secondary-accent));
            margin: 15px auto 0;
            border-radius: 5px;
            box-shadow: 0 0 15px var(--primary-accent);
            transform: translateZ(10px);
        }

        input {
            width: calc(100% - 30px);
            padding: 15px;
            margin-top: 20px;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.08); /* Fond input légèrement transparent */
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            font-size: 17px;
            color: var(--text-light);
            font-family: 'Share Tech Mono', monospace; /* Police mono pour les inputs */
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); /* Transition plus dynamique */
            box-sizing: border-box;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.2);
            transform: translateZ(10px); /* Profondeur */
        }

        input::placeholder {
            color: var(--text-dark);
            opacity: 0.7;
        }

        input:focus {
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 4px rgba(0, 198, 255, 0.4), 0 0 20px var(--primary-accent); /* Double ombre et néon */
            background: rgba(255, 255, 255, 0.15);
            outline: none;
            transform: translateZ(15px) scale(1.01); /* Léger zoom au focus */
        }

        button {
            background: linear-gradient(90deg, var(--primary-accent), var(--secondary-accent));
            color: var(--text-light);
            border: none;
            width: 100%;
            padding: 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 20px;
            letter-spacing: 1px;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 8px 25px var(--primary-accent);
            transform: translateZ(20px); /* Plus de profondeur pour le bouton */
            position: relative; /* Pour l'effet de brillance */
            overflow: hidden;
        }

        button:hover {
            background: linear-gradient(90deg, var(--secondary-accent), var(--primary-accent));
            box-shadow: 0 12px 35px var(--secondary-accent);
            transform: translateZ(30px) translateY(-3px) rotateX(5deg); /* Effet 3D plus prononcé au survol */
            text-shadow: 0 0 15px var(--text-light);
        }

        /* Effet de lumière sur le bouton au survol */
        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.3);
            transform: skewX(-20deg);
            transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        button:hover::before {
            left: 100%;
        }

        .error {
            color: var(--error-color);
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.5px;
            text-shadow: 0 0 8px rgba(255, 107, 107, 0.5);
            transform: translateZ(10px);
        }

        /* Effet de lueur et de particules autour du conteneur (purement décoratif) */
        .login-container::before,
        .login-container::after {
            content: '';
            position: absolute;
            width: 40px;
            height: 40px;
            background: radial-gradient(circle, var(--primary-accent) 0%, transparent 70%);
            border-radius: 50%;
            opacity: 0.6;
            filter: blur(15px);
            z-index: -1;
        }

        .login-container::before {
            top: -20px;
            left: -20px;
            animation: glowMove1 15s infinite alternate ease-in-out;
        }

        .login-container::after {
            bottom: -20px;
            right: -20px;
            background: radial-gradient(circle, var(--secondary-accent) 0%, transparent 70%);
            animation: glowMove2 18s infinite alternate-reverse ease-in-out;
        }

        @keyframes glowMove1 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, 30px) scale(1.1); }
            100% { transform: translate(0, 0) scale(1); }
        }

        @keyframes glowMove2 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-30px, -30px) scale(1.1); }
            100% { transform: translate(0, 0) scale(1); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Connexion</h1>
        <?php if (isset($erreur)) echo "<p class='error'>$erreur</p>"; ?>
        <form method="post">
            <input type="text" name="nom" placeholder="Nom d'utilisateur" required>
            <input type="password" name="motdepasse" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
    </div>

    <script>
        // Aucun besoin de JavaScript ici, les animations CSS sont gérées directement par les propriétés.
        // La seule JS précédente était pour l'animation d'entrée, maintenant gérée par un @keyframes sur le .login-container
    </script>
</body>
</html>