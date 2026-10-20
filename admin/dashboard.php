<?php
session_start();
require_once '../db.php'; // Assurez-vous que ce chemin est correct

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Administrateur</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Définition des variables de couleurs pour un fond plus vibrant */
        :root {
            --primary-light: #8ec5fc; /* Peut être réutilisé pour d'autres éléments */
            --primary-dark: #e0c3fc;  /* Peut être réutilisé pour d'autres éléments */

            /* Couleurs pour le nouveau fond vibrant */
            --bg-color1: #a18cd1; /* Lavande */
            --bg-color2: #fbc2eb; /* Rose pâle */
            --bg-color3: #84fab0; /* Vert menthe */
            --bg-color4: #8fd3f4; /* Bleu ciel */
            --bg-color5: #a6c0fe; /* Bleu lavande */
            --bg-color6: #f68084; /* Corail */


            --button-bg-alpha: rgba(255, 255, 255, 0.22);
            --button-border-light: #b6c0e0;
            --button-border-dark: #a3aed6;
            --text-color-dark: #2d3559;
            --text-color-light: #1a1a2e;
            --shadow-light: rgba(60, 80, 120, 0.13);
            --shadow-dark: rgba(60, 80, 120, 0.18);
            --highlight-color: #a3aed6;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            /* Retire le background du body ici car il sera géré par ::before */
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            position: relative;
            overflow-x: hidden;
            background-color: var(--bg-color1); /* Couleur de secours si le dégradé ne charge pas */
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 0;
            /* Nouveau dégradé linéaire plus coloré et animé */
            background: linear-gradient(
                135deg,
                var(--bg-color1), /* Lavande */
                var(--bg-color2), /* Rose pâle */
                var(--bg-color3), /* Vert menthe */
                var(--bg-color4), /* Bleu ciel */
                var(--bg-color5), /* Bleu lavande */
                var(--bg-color6)  /* Corail */
            );
            background-size: 400% 400%; /* Permet une animation de dégradé fluide */
            opacity: 1; /* Pleine opacité pour le fond */
            pointer-events: none;
            animation: vibrantBackgroundShift 20s ease infinite alternate; /* Animation plus rapide */
        }

        @keyframes vibrantBackgroundShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        h1 {
            margin-top: 60px;
            margin-bottom: 40px;
            /* Ajustement de la couleur du texte pour mieux contraster avec le fond coloré */
            color: white; /* Blanc pour contraster avec les couleurs du fond */
            font-size: 2.8em;
            letter-spacing: 3px;
            font-weight: 700;
            text-align: center;
            text-shadow: 0 4px 20px rgba(0,0,0,0.4); /* Ombre plus forte pour la lisibilité */
            position: relative;
            z-index: 1;
            /* Pas de dégradé sur le texte pour le rendre plus lisible sur un fond coloré */
            /* Ou si vous voulez un dégradé, choisissez des couleurs qui ressortent bien */
            /* background: -webkit-linear-gradient(45deg, #FFF, #EEE); */
            /* -webkit-background-clip: text; */
            /* -webkit-text-fill-color: transparent; */
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 32px;
            width: 95%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px;
            z-index: 1;
        }

        .dashboard-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 40px 28px;
            /* Garde un fond semi-transparent pour laisser transparaître le fond coloré */
            background: rgba(255, 255, 255, 0.22); /* Légèrement plus transparent pour un effet "verre" */
            border-radius: 28px;
            /* Ombre plus douce pour s'intégrer au fond */
            box-shadow: 0 8px 30px 0 rgba(0,0,0,0.1), 0 2px 0 rgba(255,255,255,0.7);
            border: 2.5px solid transparent;
            font-size: 1.25em;
            font-weight: 600;
            color: white; /* Texte blanc pour contraster avec le fond */
            text-shadow: 0 1px 3px rgba(0,0,0,0.3); /* Ombre légère pour le texte */
            text-decoration: none;
            letter-spacing: 1.2px;
            position: relative;
            min-height: 120px;
            cursor: pointer;
            backdrop-filter: blur(15px) saturate(1.5); /* Effet de flou et de saturation plus prononcé */
            -webkit-backdrop-filter: blur(15px) saturate(1.5);
            transition:
                box-shadow 0.35s cubic-bezier(.4,2,.6,1),
                background 0.35s,
                color 0.3s,
                transform 0.25s,
                border 0.35s;
            overflow: hidden;
            border-image: linear-gradient(120deg, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0.3) 100%) 1; /* Bordure blanche transparente */
            isolation: isolate;
        }
        .dashboard-btn::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.4) 0%, transparent 70%); /* Glow interne plus lumineux */
            border-radius: 28px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease-out;
            z-index: -1;
        }
        .dashboard-btn:hover::before {
            opacity: 1;
        }


        .dashboard-btn .icon {
            font-size: 3em;
            transition:
                transform 0.4s cubic-bezier(.4,2,.6,1),
                filter 0.4s;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2));
            color: white; /* Icônes blanches par défaut */
        }

        /* Effet de bordure animée */
        .dashboard-btn::after {
            content: "";
            position: absolute;
            top: -5px; bottom: -5px; left: -5px; right: -5px;
            border-radius: 30px;
            background: conic-gradient(from 180deg at 50% 50%, var(--bg-color3) 0deg, var(--bg-color4) 90deg, var(--bg-color5) 180deg, var(--bg-color6) 270deg, var(--bg-color3) 360deg); /* Couleurs du fond pour la bordure */
            z-index: -2;
            opacity: 0;
            transition: opacity 0.4s ease-in-out, transform 0.4s ease-in-out;
            transform: scale(0.95);
        }

        .dashboard-btn:hover::after,
        .dashboard-btn:focus::after {
            opacity: 1;
            transform: scale(1.02);
            animation: rotateBorder 4s linear infinite;
        }

        @keyframes rotateBorder {
            from { transform: scale(1.02) rotate(0deg); }
            to { transform: scale(1.02) rotate(360deg); }
        }


        .dashboard-btn:hover, .dashboard-btn:focus {
            background: rgba(255,255,255,0.4); /* Plus opaque et lumineux au survol */
            color: #1a1a2e; /* Texte devient sombre au survol pour un meilleur contraste */
            box-shadow: 0 20px 60px 0 rgba(0,0,0,0.2), 0 3px 0 rgba(255,255,255,0.8);
            transform: translateY(-12px) scale(1.07);
            border-image: linear-gradient(120deg, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0.5) 100%) 1; /* Bordure plus lumineuse */
        }

        .dashboard-btn:hover .icon, .dashboard-btn:focus .icon {
            transform: scale(1.35) rotate(-10deg);
            filter: drop-shadow(0 0 25px rgba(0,0,0,0.4)); /* Ombre plus foncée pour les icônes */
            color: #1a1a2e; /* Icônes deviennent sombres au survol */
        }

        .dashboard-btn:active {
            background: rgba(220,225,240,0.5); /* Effet de clic */
            transform: translateY(-5px) scale(1.03);
        }
        
        /* Styles spécifiques pour le bouton de déconnexion */
        .dashboard-btn.logout {
            background-color: rgba(255, 99, 71, 0.22); /* Tomate transparent */
            color: white; /* Texte blanc */
            border-image: linear-gradient(120deg, rgba(255, 99, 71, 0.4) 0%, rgba(255, 140, 0, 0.3) 100%) 1;
        }
        .dashboard-btn.logout .icon {
            color: white; /* Icône blanche */
        }
        .dashboard-btn.logout:hover {
            background-color: rgba(255, 99, 71, 0.95);
            color: white;
        }
        .dashboard-btn.logout:hover .icon {
            color: white;
        }


        @media (max-width: 900px) {
            h1 { font-size: 2.2em; }
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
                gap: 24px;
                padding: 30px;
            }
            .dashboard-btn {
                padding: 30px 20px;
                font-size: 1.1em;
            }
            .dashboard-btn .icon {
                font-size: 2.5em;
            }
        }
        @media (max-width: 600px) {
            h1 { font-size: 1.8em; margin-top: 30px; margin-bottom: 20px; }
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 18px;
                padding: 20px;
            }
            .dashboard-btn {
                padding: 25px 15vw;
                font-size: 1em;
            }
            .dashboard-btn .icon {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <h1>Bienvenue sur le Tableau de Bord Administrateur</h1>
    <div class="dashboard-grid">
        <a class="dashboard-btn" href="enseignants.php"><span class="icon"><i class="fas fa-chalkboard-teacher"></i></span>Gérer les enseignants</a>
        <a class="dashboard-btn" href="etudiants.php"><span class="icon"><i class="fas fa-user-graduate"></i></span>Gérer les étudiants</a> <a class="dashboard-btn" href="matieres.php"><span class="icon"><i class="fas fa-book"></i></span>Gérer les matières</a>
        <a class="dashboard-btn" href="salles.php"><span class="icon"><i class="fas fa-school"></i></span>Gérer les salles</a>
        <a class="dashboard-btn" href="niveaux.php"><span class="icon"><i class="fas fa-graduation-cap"></i></span>Gérer les niveaux / classes</a>
        <a class="dashboard-btn" href="voir_disponibilites.php"><span class="icon"><i class="fas fa-calendar-alt"></i></span>Voir les disponibilités</a>
        <a class="dashboard-btn" href="pas_emploi.php"><span class="icon"><i class="fas fa-cogs"></i></span>Générer l'emploi du temps</a>
        <a class="dashboard-btn" href="visualiser_emploi.php"><span class="icon"><i class="fas fa-table"></i></span>Voir emploi du temps complet</a>
        <a class="dashboard-btn" href="tchat.php"><span class="icon"><i class="fas fa-table"></i></span>Tchat</a>
        <a class="dashboard-btn logout" href="../login.php"><span class="icon"><i class="fas fa-sign-out-alt"></i></span>Se déconnecter</a>
    </div>
</body>
</html>