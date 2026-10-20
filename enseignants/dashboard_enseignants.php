<?php
session_start();
require_once '../db.php';

// Vérifie que l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header("Location: ../login.php");
    exit;
}

$nom = $_SESSION['nom'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Enseignant</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Variables de couleurs pour un fond plus vibrant */
        :root {
            --bg-color1: #a18cd1; /* Lavande */
            --bg-color2: #fbc2eb; /* Rose pâle */
            --bg-color3: #84fab0; /* Vert menthe */
            --bg-color4: #8fd3f4; /* Bleu ciel */
            --bg-color5: #a6c0fe; /* Bleu lavande */
            --bg-color6: #f68084; /* Corail */

            /* Couleurs pour les boutons et le texte */
            --text-dark: #1a1a2e;
            --text-light: #f0f0f0;

            /* Couleurs spécifiques pour les dégradés des boutons */
            --btn-dispo-start: #43cea2;
            --btn-dispo-end: #185a9d;
            --btn-emploi-start: #ff9966;
            --btn-emploi-end: #ff5e62;
            --btn-logout-start: #ee0979;
            --btn-logout-end: #ff6a00;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; /* Centrer verticalement le contenu */
            position: relative;
            overflow: hidden; /* Cache le débordement de l'animation */
            background-color: var(--bg-color1); /* Fallback color */
        }

        /* Fond dégradé animé derrière tout */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1; /* Place derrière le contenu */
            background: linear-gradient(
                135deg,
                var(--bg-color1), /* Lavande */
                var(--bg-color2), /* Rose pâle */
                var(--bg-color3), /* Vert menthe */
                var(--bg-color4), /* Bleu ciel */
                var(--bg-color5), /* Bleu lavande */
                var(--bg-color6)  /* Corail */
            );
            background-size: 400% 400%; /* Rend le dégradé plus grand pour l'animation */
            animation: vibrantBackgroundShift 20s ease infinite alternate; /* Animation fluide */
        }

        @keyframes vibrantBackgroundShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            max-width: 480px; /* Légèrement plus large pour les boutons */
            width: 90%; /* Pour une meilleure adaptation mobile */
            margin: 60px auto;
            background: rgba(255, 255, 255, 0.2); /* Fond blanc semi-transparent */
            border-radius: 25px; /* Coins plus arrondis */
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.2); /* Ombre plus prononcée */
            padding: 40px 35px; /* Plus de padding */
            text-align: center;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px) saturate(1.8); /* Effet verre dépoli fort */
            -webkit-backdrop-filter: blur(20px) saturate(1.8);
            border: 1px solid rgba(255, 255, 255, 0.3); /* Bordure légère */
            animation: fadeInScale 0.8s ease-out forwards; /* Animation d'apparition */
            transform: scale(0.95);
            opacity: 0;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        h1 {
            color: var(--text-dark); /* Couleur par défaut */
            margin-bottom: 40px;
            font-size: 2.2em; /* Plus grand */
            font-weight: 700;
            position: relative;
            z-index: 1;
            letter-spacing: 1.5px;
            text-shadow: 0 4px 15px rgba(0,0,0,0.2); /* Ombre pour la lisibilité */

            /* Dégradé sur le texte pour un effet plus stylé */
            background: linear-gradient(45deg, #FFF, #EEE); /* Couleurs claires pour le texte principal */
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        h1 span {
            font-size: 0.7em; /* Taille légèrement réduite */
            font-weight: 600;
            /* Utilisation d'un dégradé pour le rôle aussi */
            background: linear-gradient(90deg, var(--bg-color4), var(--bg-color3));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block; /* Pour mettre sur une nouvelle ligne */
            margin-top: 5px;
        }
        
        ul {
            list-style: none;
            padding: 0;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        li {
            margin-bottom: 25px; /* Plus d'espace entre les boutons */
        }

        /* Styles de base pour tous les boutons */
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px; /* Espace plus grand entre icône et texte */
            padding: 18px 25px; /* Plus de padding */
            color: var(--text-light); /* Texte clair par défaut */
            border-radius: 15px; /* Coins plus arrondis */
            text-decoration: none;
            font-size: 1.25em; /* Plus grand */
            font-weight: 600;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1); /* Ombre par défaut */
            position: relative;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(.4,2,.6,1); /* Transition plus fluide et rebondissante */
            backdrop-filter: blur(15px); /* Effet verre dépoli */
            -webkit-backdrop-filter: blur(15px);
            border: 2px solid transparent; /* Bordure transparente pour l'animation */
            isolation: isolate; /* Pour que le pseudo-élément ::after fonctionne correctement */
        }

        /* Effet de brillance/glissement au survol */
        .btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.15); /* Couleur de la brillance */
            transform: skewX(-20deg);
            transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            z-index: 0; /* Place la brillance sous le texte */
        }

        .btn:hover::before {
            left: 100%;
        }

        /* Bordure animée colorée sur le contour des boutons */
        .btn::after {
            content: "";
            position: absolute;
            top: -3px; bottom: -3px; left: -3px; right: -3px; /* Bordure externe */
            border-radius: 18px; /* Correspond au bouton */
            background: conic-gradient(from 180deg at 50% 50%, var(--bg-color3) 0deg, var(--bg-color4) 90deg, var(--bg-color5) 180deg, var(--bg-color6) 270deg, var(--bg-color3) 360deg);
            z-index: -2; /* Sous le bouton mais au-dessus du fond */
            opacity: 0;
            transition: opacity 0.4s ease-in-out, transform 0.4s ease-in-out;
            transform: scale(0.98);
            filter: blur(2px); /* Léger flou pour un effet glow */
        }

        .btn:hover::after,
        .btn:focus::after {
            opacity: 1;
            transform: scale(1.02);
            animation: rotateBorder 4s linear infinite; /* Animation de rotation de la bordure */
        }

        @keyframes rotateBorder {
            from { transform: scale(1.02) rotate(0deg); }
            to { transform: scale(1.02) rotate(360deg); }
        }

        .btn .icon {
            font-size: 1.6em; /* Taille des icônes */
            transition: transform 0.3s ease-out, color 0.3s ease-out;
            color: var(--text-light); /* Couleur des icônes par défaut */
            position: relative;
            z-index: 1;
        }

        /* Effets de survol pour tous les boutons */
        .btn:hover, .btn:focus {
            transform: translateY(-8px) scale(1.08); /* Effet de "pop" plus prononcé */
            box-shadow: 0 18px 40px rgba(0,0,0,0.3); /* Ombre plus grande */
            color: var(--text-dark); /* Texte devient sombre au survol */
            /* Suppression du background statique au hover pour laisser le ::after briller */
            background: rgba(255, 255, 255, 0.5); /* Légèrement plus opaque et blanc au survol */
        }
        .btn:hover .icon, .btn:focus .icon {
            transform: scale(1.15) rotate(-5deg); /* Icône tourne légèrement */
            color: var(--text-dark); /* Icône devient sombre au survol */
        }

        /* Styles spécifiques aux couleurs de fond des boutons */
        .btn-dispo { background: rgba(var(--btn-dispo-start-rgb), 0.2); border-image: linear-gradient(120deg, rgba(67,206,162,0.4) 0%, rgba(24,90,157,0.3) 100%) 1; }
        .btn-emploi { background: rgba(var(--btn-emploi-start-rgb), 0.2); border-image: linear-gradient(120deg, rgba(255,153,102,0.4) 0%, rgba(255,94,98,0.3) 100%) 1; }
        .btn-logout { background: rgba(var(--btn-logout-start-rgb), 0.2); border-image: linear-gradient(120deg, rgba(238,9,121,0.4) 0%, rgba(255,106,0,0.3) 100%) 1; }

        /* Définition des RGB pour les backgrounds des boutons pour la transparence */
        :root {
            --btn-dispo-start-rgb: 67,206,162;
            --btn-emploi-start-rgb: 255,153,102;
            --btn-logout-start-rgb: 238,9,121;
        }


        @media (max-width: 600px) {
            .container {
                max-width: 95vw;
                padding: 30px 20px;
                margin: 30px auto;
            }
            h1 {
                font-size: 1.8em;
                margin-bottom: 30px;
            }
            h1 span {
                font-size: 0.6em;
            }
            .btn {
                font-size: 1.1em;
                padding: 15px 15px;
                gap: 10px;
            }
            .btn .icon {
                font-size: 1.4em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bienvenue <?= htmlspecialchars($nom) ?> <br><span>(Enseignant)</span></h1>
        <ul>
            <li>
                <a class="btn btn-dispo" href="disponibilites.php">
                    <span class="icon"><i class="fas fa-calendar-alt"></i></span>
                    Définir mes disponibilités
                </a>
            </li>
            <li>
                <a class="btn btn-emploi" href="emploi_du_temps.php">
                    <span class="icon"><i class="fas fa-table"></i></span>
                    Voir mon emploi du temps
                </a>
            </li>
            <li>
                <a class="btn btn-logout" href="../login.php">
                    <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                    Déconnexion
                </a>
            </li>
            <li>
                <a class="btn btn-dispo" href="tchat">
                    <span class="icon"><i class="action-button button-chat"></i></span>
                    Tchat
                </a>
            </li>
        </ul>
    </div>
</body>
</html>