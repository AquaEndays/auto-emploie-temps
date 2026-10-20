<?php
// modifier_profil_eleve.php

session_start();

// Assurez-vous que ce chemin est correct vers votre fichier de connexion PDO
require_once 'db.php'; // Incluez votre fichier de connexion PDO

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: login.php");
    exit;
}

$etudiant_id = $_SESSION['user_id'];
$message = '';
$message_type = ''; // 'success' or 'error'

// --- 1. Récupérer les informations actuelles de l'étudiant ---
$etudiant_info = null;
try {
    $stmt = $pdo->prepare("SELECT nom, email, mot_de_passe, classe_id FROM etudiants WHERE id = ?");
    $stmt->execute([$etudiant_id]);
    $etudiant_info = $stmt->fetch();

    if (!$etudiant_info) {
        // Cet étudiant n'existe pas ou n'a pas pu être trouvé
        $_SESSION['erreur_login'] = "Vos informations n'ont pas pu être chargées. Veuillez vous reconnecter.";
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    die("Erreur lors du chargement des informations de l'étudiant : " . $e->getMessage());
}

// --- 2. Gérer la soumission du formulaire de mise à jour ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe']; // Le mot de passe peut être laissé vide s'il n'est pas modifié

    // L'ID de la classe ne doit PAS venir du formulaire pour un élève.
    // On utilise l'ID de la classe qui est déjà stocké pour l'étudiant.
    $new_classe_id = $etudiant_info['classe_id'];

    // Vérification basique des entrées
    if (empty($nom) || empty($email)) { // Suppression de la vérification de classe_id ici
        $message = "Le nom et l'email sont obligatoires.";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format d'email invalide.";
        $message_type = 'error';
    } else {
        try {
            // Vérifier si le nouvel email existe déjà pour un AUTRE étudiant
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM etudiants WHERE email = ? AND id != ?");
            $stmt->execute([$email, $etudiant_id]);
            if ($stmt->fetchColumn() > 0) {
                $message = "Cet email est déjà utilisé par un autre compte.";
                $message_type = 'error';
            } else {
                // Construire la requête de mise à jour
                // Nous incluons toujours classe_id mais avec sa valeur existante (non modifiable par l'utilisateur)
                $sql = "UPDATE etudiants SET nom = ?, email = ?, classe_id = ?";
                $params = [$nom, $email, $new_classe_id]; // $new_classe_id vient de $etudiant_info['classe_id']

                // Si le mot de passe est fourni, le mettre à jour
                if (!empty($mot_de_passe)) {
                    // ATTENTION TRÈS IMPORTANT: Hashage du mot de passe!
                    // Dans un système réel, vous DEVEZ faire ceci :
                    // $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                    // $sql .= ", mot_de_passe = ?";
                    // $params[] = $hashed_password;

                    // Pour cet exemple simplifie, SANS hashage (À NE PAS UTILISER EN PROD) :
                    $sql .= ", mot_de_passe = ?";
                    $params[] = $mot_de_passe;
                }

                $sql .= " WHERE id = ?";
                $params[] = $etudiant_id;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                // Si la mise à jour réussit, rafraîchir les informations affichées
                // et potentiellement les variables de session si elles sont utilisées pour l'affichage
                $stmt = $pdo->prepare("SELECT nom, email, mot_de_passe, classe_id FROM etudiants WHERE id = ?");
                $stmt->execute([$etudiant_id]);
                $etudiant_info = $stmt->fetch(); // Met à jour les données dans le formulaire

                // Si le nom de l'utilisateur est affiché dans le header ou ailleurs via la session, le mettre à jour
                $_SESSION['user_nom'] = $etudiant_info['nom'];

                $message = "Vos informations ont été mises à jour avec succès !";
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de la mise à jour : " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Les valeurs actuelles de l'étudiant pour pré-remplir le formulaire
$current_nom = htmlspecialchars($etudiant_info['nom'] ?? '');
$current_email = htmlspecialchars($etudiant_info['email'] ?? '');
$current_classe_id = $etudiant_info['classe_id'] ?? null; // ID de la classe

// Récupérer le nom de la classe pour l'affichage (non modifiable)
$current_classe_nom = "Non définie";
if ($current_classe_id) {
    try {
        $stmt_current_classe = $pdo->prepare("SELECT nom FROM salles WHERE id = ?");
        $stmt_current_classe->execute([$current_classe_id]);
        $current_classe_nom_db = $stmt_current_classe->fetchColumn();
        if ($current_classe_nom_db) {
            $current_classe_nom = htmlspecialchars($current_classe_nom_db);
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du nom de la classe : " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon Profil - Étudiant</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Modern & Vibrant Color Palette */
        :root {
            --primary-color: #6C63FF; /* Vibrant Purple */
            --primary-light: #8E88FF; /* Lighter Purple */
            --primary-dark: #5B4CAF; /* Darker Purple */
            --secondary-color: #FF6B6B; /* Coral Red */
            --success-color: #28A745; /* Green */
            --error-color: #DC3545; /* Red */
            --warning-color: #FFC107; /* Yellow */
            --info-color: #17A2B8; /* Cyan */

            --background-light: #F4F7FC; /* Very light grey-blue */
            --background-dark: #E0E4EB; /* Slightly darker grey-blue */

            --text-dark: #2C3E50; /* Darker blue-grey for text */
            --text-light: #7F8C8D; /* Lighter grey for secondary text */
            --text-white: #FFFFFF;

            --border-color: #DDEBF0; /* Light grey-blue border */
            --form-bg: #FFFFFF;
            --card-bg: #FFFFFF;

            --box-shadow-light: rgba(0, 0, 0, 0.08);
            --box-shadow-medium: rgba(0, 0, 0, 0.15);
            --box-shadow-strong: rgba(0, 0, 0, 0.25);

            --button-gradient-start: var(--primary-color);
            --button-gradient-end: var(--primary-light);

            --back-button-bg: #AAB7B8; /* Muted Grey */
            --back-button-hover-bg: #8D9A9B;
            --back-button-text: white;
            --back-button-shadow: rgba(0, 0, 0, 0.2);

            /* Typography */
            --font-main: 'Poppins', sans-serif;
            --font-heading: 'Montserrat', sans-serif;
        }

        /* Base Styles */
        body {
            font-family: var(--font-main);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, var(--background-light) 0%, var(--background-dark) 100%);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Container & Card Effect */
        .container {
            max-width: 700px;
            width: 90%;
            margin: 40px auto;
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px var(--box-shadow-medium);
            position: relative;
            border: none; /* Removed default border */
            overflow: hidden; /* For pseudo-elements */
            animation: slideIn 0.8s ease-out forwards;
            backdrop-filter: blur(5px); /* Subtle blur effect */
            background-clip: padding-box; /* Ensure background doesn't bleed through blur */
        }

        /* Background shapes for aesthetic appeal */
        .container::before, .container::after {
            content: '';
            position: absolute;
            z-index: -1;
            border-radius: 50%;
            opacity: 0.1;
            filter: blur(50px);
        }

        .container::before {
            width: 150px;
            height: 150px;
            background-color: var(--primary-light);
            top: -50px;
            left: -50px;
        }

        .container::after {
            width: 200px;
            height: 200px;
            background-color: var(--secondary-color);
            bottom: -80px;
            right: -80px;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Heading */
        h1 {
            font-family: var(--font-heading);
            text-align: center;
            color: var(--primary-dark);
            margin-bottom: 40px;
            font-size: 2.8em;
            position: relative;
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px var(--box-shadow-light);
        }
        h1::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 2px;
        }

        /* Messages */
        .message {
            padding: 18px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeInScale 0.6s ease-out;
            box-shadow: 0 4px 10px var(--box-shadow-light);
            border-left: 6px solid;
        }
        .message.success {
            background-color: #E6F7ED;
            color: var(--success-color);
            border-color: var(--success-color);
        }
        .message.error {
            background-color: #FEECEB;
            color: var(--error-color);
            border-color: var(--error-color);
        }
        .message i {
            font-size: 1.4em;
            flex-shrink: 0; /* Prevent icon from shrinking */
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: translateY(-20px) scale(0.9); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1.1em;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: calc(100% - 28px); /* Adjusted for padding and border */
            padding: 14px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
            box-sizing: border-box;
            background-color: var(--background-light);
            color: var(--text-dark);
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.25);
            background-color: var(--form-bg);
            outline: none;
        }

        .form-group small {
            display: block;
            margin-top: 8px;
            color: var(--text-light);
            font-size: 0.9em;
            padding-left: 5px;
        }

        /* Read-only class display */
        .form-group p {
            padding: 14px;
            border: 1px dashed var(--primary-light); /* Dashed border for non-editable */
            border-radius: 8px;
            background-color: var(--background-dark);
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 1.05em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group p i {
            color: var(--primary-color);
        }


        /* Submit Button */
        .btn-submit {
            display: block;
            width: 100%;
            padding: 16px 20px;
            background: linear-gradient(45deg, var(--button-gradient-start) 0%, var(--button-gradient-end) 100%);
            color: var(--text-white);
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 5px 15px rgba(108, 99, 255, 0.3);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .btn-submit:hover {
            background: linear-gradient(45deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(108, 99, 255, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(108, 99, 255, 0.2);
        }

        /* Back Button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background-color: var(--back-button-bg);
            color: var(--back-button-text);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            position: absolute;
            top: 25px;
            left: 25px;
            box-shadow: 0 3px 8px var(--back-button-shadow);
            z-index: 10; /* Ensure it's above the container's pseudo-elements */
            font-size: 0.95em;
        }

        .back-button:hover {
            background-color: var(--back-button-hover-bg);
            transform: translateX(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.35);
        }

        .back-button i {
            font-size: 1.2em;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 30px;
                margin: 30px auto;
            }
            h1 {
                font-size: 2.4em;
                margin-top: 50px;
            }
            .message {
                padding: 15px;
                font-size: 0.95em;
            }
            .form-group label {
                font-size: 1em;
            }
            .form-group input, .form-group select, .form-group p {
                padding: 12px;
                font-size: 0.95em;
            }
            .btn-submit {
                padding: 14px 18px;
                font-size: 1.1em;
            }
            .back-button {
                top: 15px;
                left: 15px;
                padding: 10px 16px;
                font-size: 0.9em;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
                margin: 20px auto;
                width: 95%;
                border-radius: 10px;
            }
            h1 {
                font-size: 2em;
                margin-bottom: 30px;
                margin-top: 40px;
            }
            h1::after {
                width: 50px;
                height: 3px;
                bottom: -10px;
            }
            .message {
                flex-direction: column;
                align-items: flex-start;
                font-size: 0.9em;
                gap: 5px;
            }
            .message i {
                font-size: 1.2em;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group input, .form-group select, .form-group p {
                padding: 10px;
                font-size: 0.9em;
            }
            .btn-submit {
                padding: 12px 15px;
                font-size: 1em;
            }
            .back-button {
                top: 10px;
                left: 10px;
                padding: 8px 12px;
                font-size: 0.8em;
                gap: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="emploi_eleve.php" class="back-button"><i class="fas fa-arrow-left"></i> Retour à l'emploi du temps</a>
        <h1>Modifier mon Profil</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php if ($message_type === 'success'): ?><i class="fas fa-check-circle"></i><?php endif; ?>
                <?php if ($message_type === 'error'): ?><i class="fas fa-exclamation-circle"></i><?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="modifier_profil_eleve.php" method="post">
            <div class="form-group">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" name="nom" value="<?php echo $current_nom; ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" value="<?php echo $current_email; ?>" required>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Nouveau Mot de passe (laisser vide pour ne pas changer) :</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="********">
                <small>Laissez vide si vous ne souhaitez pas modifier votre mot de passe.</small>
            </div>

            <div class="form-group">
                <label>Votre Classe Actuelle :</label>
                <p>
                    <i class="fas fa-school"></i> <?php echo $current_classe_nom; ?>
                </p>
                <small>Votre classe est gérée par l'administration et ne peut pas être modifiée ici.</small>
            </div>

            <button type="submit" name="update_profile" class="btn-submit">Mettre à jour le profil</button>
        </form>
    </div>
</body>
</html>