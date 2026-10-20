<?php
// admin/etudiants.php

session_start();
require_once '../db.php'; // Assurez-vous que ce chemin est correct

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$message_type = ''; // 'success' or 'error'

// --- GESTION DE L'AJOUT D'UN ÉTUDIANT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $mot_de_passe = trim($_POST['mot_de_passe']);
    $classe_id = $_POST['classe_id'];

    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($classe_id)) {
        $message = "Tous les champs sont obligatoires.";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format d'email invalide.";
        $message_type = 'error';
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM etudiants WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $message = "Un étudiant avec cet email existe déjà.";
                $message_type = 'error';
            } else {
                // Insérer le nouvel étudiant
                $stmt = $pdo->prepare("INSERT INTO etudiants (nom, prenom, email, mot_de_passe, classe_id) VALUES (?, ?, ?, ?, ?)");
                // Attention: mot_de_passe n'est PAS hashé ici. Pour une application réelle, utilisez password_hash().
                // Exemple : $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                // $stmt->execute([$nom, $prenom, $email, $hashed_password, $classe_id]);
                $stmt->execute([$nom, $prenom, $email, $mot_de_passe, $classe_id]);
                $message = "Étudiant ajouté avec succès !";
                $message_type = 'success';
                // Optionnel: Rediriger pour éviter le re-soumission du formulaire
                // header("Location: etudiants.php?message=" . urlencode($message) . "&type=" . $message_type);
                // exit;
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout de l'étudiant : " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// --- GESTION DE LA SUPPRESSION D'UN ÉTUDIANT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM etudiants WHERE id = ?");
        $stmt->execute([$student_id]);
        if ($stmt->rowCount() > 0) {
            $message = "Étudiant supprimé avec succès.";
            $message_type = 'success';
        } else {
            $message = "Aucun étudiant trouvé avec cet ID.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Erreur lors de la suppression de l'étudiant : " . $e->getMessage();
        $message_type = 'error';
    }
}


// --- RÉCUPÉRATION DES ÉTUDIANTS ET DES CLASSES POUR L'AFFICHAGE ---
$etudiants = [];
try {
    $stmt = $pdo->query("
        SELECT 
            e.id, 
            e.nom, 
            e.prenom, 
            e.email, 
            s.nom AS classe_nom 
        FROM 
            etudiants e
        JOIN 
            salles s ON e.classe_id = s.id
        ORDER BY 
            e.nom, e.prenom
    ");
    $etudiants = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Erreur lors du chargement des étudiants : " . $e->getMessage();
    $message_type = 'error';
}

$classes = [];
try {
    $stmt = $pdo->query("SELECT id, nom FROM salles ORDER BY nom");
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    // Si la table salles n'existe pas ou erreur, cela n'empêchera pas le reste de fonctionner
    // mais le sélecteur de classe sera vide.
    error_log("Erreur de chargement des classes : " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Étudiants - Administrateur</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50; /* Vert pour les succès et éléments principaux */
            --danger-color: #f44336; /* Rouge pour les erreurs et suppressions */
            --info-color: #2196F3; /* Bleu pour les infos */
            --background-light: #f0f2f5;
            --text-dark: #333;
            --text-light: #555;
            --border-color: #ddd;
            --form-bg: #fff;
            --table-header-bg: #e2e6ea;
            --hover-row-bg: #f9f9f9;

            /* Variables pour le bouton retour (ajustées pour cette page si besoin) */
            --back-button-bg: #6c757d; /* Gris doux */
            --back-button-hover-bg: #5a6268;
            --back-button-text: white;
            --back-button-shadow: rgba(0, 0, 0, 0.2);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative; /* Pour le positionnement du bouton retour */
        }

        h1 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 2.5em;
            position: relative;
        }
        h1::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -10px;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.5s ease-out;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-section {
            background-color: var(--form-bg);
            padding: 25px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            margin-bottom: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .form-section h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.8em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: calc(100% - 20px);
            padding: 12px 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
            outline: none;
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 15px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-submit:hover {
            background-color: #43a047;
            transform: translateY(-2px);
        }

        .table-section {
            margin-top: 40px;
            background-color: var(--form-bg);
            padding: 25px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table-section h2 {
            color: var(--info-color);
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.8em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid var(--border-color);
            padding: 12px 10px;
            text-align: left;
        }

        table th {
            background-color: var(--table-header-bg);
            color: var(--text-dark);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        table tr:nth-child(even) {
            background-color: #f8f8f8;
        }

        table tr:hover {
            background-color: var(--hover-row-bg);
        }

        .btn-action {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-right: 5px;
        }

        .btn-action.edit {
            background-color: #03A9F4; /* Bleu vif */
            color: white;
        }
        .btn-action.edit:hover {
            background-color: #039BE5;
            transform: translateY(-1px);
        }

        .btn-action.delete {
            background-color: var(--danger-color);
            color: white;
        }
        .btn-action.delete:hover {
            background-color: #e53935;
            transform: translateY(-1px);
        }
        
        /* Bouton Retour */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background-color: var(--back-button-bg);
            color: var(--back-button-text);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            position: absolute;
            top: 30px;
            left: 30px;
            box-shadow: 0 2px 5px var(--back-button-shadow);
        }

        .back-button:hover {
            background-color: var(--back-button-hover-bg);
            transform: translateX(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .back-button i {
            font-size: 1.1em;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
            }
            h1 {
                font-size: 2em;
            }
            .form-section h2, .table-section h2 {
                font-size: 1.5em;
            }
            .form-group input, .form-group select, .btn-submit {
                font-size: 0.95em;
                padding: 10px;
            }
            table th, table td {
                padding: 8px;
                font-size: 0.85em;
            }
            .btn-action {
                padding: 6px 10px;
                font-size: 0.8em;
            }
            .back-button {
                top: 15px;
                left: 15px;
                padding: 8px 15px;
                font-size: 0.9em;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.8em;
                margin-top: 50px; /* Plus d'espace pour le bouton retour */
            }
            .back-button {
                top: 10px;
                left: 10px;
            }
            .form-section, .table-section {
                padding: 15px;
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            table, thead, tbody, th, td, tr {
                display: block;
            }
            table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            table tr {
                border: 1px solid var(--border-color);
                margin-bottom: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            }
            table td {
                border: none;
                border-bottom: 1px solid var(--border-color);
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            table td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
                color: var(--text-dark);
            }
            table td:last-child {
                border-bottom: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_admin.php" class="back-button"><i class="fas fa-arrow-left"></i> Retour</a>
        <h1>Gérer les Étudiants</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php if ($message_type === 'success'): ?><i class="fas fa-check-circle"></i><?php endif; ?>
                <?php if ($message_type === 'error'): ?><i class="fas fa-exclamation-circle"></i><?php endif; ?>
                <?php if ($message_type === 'info'): ?><i class="fas fa-info-circle"></i><?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <h2>Ajouter un nouvel étudiant</h2>
            <form action="etudiants.php" method="post">
                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" id="nom" name="nom" required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom :</label>
                    <input type="text" id="prenom" name="prenom" required>
                </div>
                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe :</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>
                <div class="form-group">
                    <label for="classe_id">Classe :</label>
                    <select id="classe_id" name="classe_id" required>
                        <option value="">Sélectionnez une classe</option>
                        <?php foreach ($classes as $classe): ?>
                            <option value="<?php echo htmlspecialchars($classe['id']); ?>">
                                <?php echo htmlspecialchars($classe['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($classes)): ?>
                        <p style="color: var(--danger-color); font-size: 0.9em; margin-top: 5px;">
                            Aucune classe disponible. Veuillez en ajouter dans "Gérer les niveaux / classes".
                        </p>
                    <?php endif; ?>
                </div>
                <button type="submit" name="add_student" class="btn-submit">Ajouter l'étudiant</button>
            </form>
        </div>

        <div class="table-section">
            <h2>Liste des Étudiants</h2>
            <?php if (empty($etudiants)): ?>
                <p class="message info">Aucun étudiant enregistré pour le moment.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Classe</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($etudiants as $etudiant): ?>
                            <tr>
                                <td data-label="Nom"><?php echo htmlspecialchars($etudiant['nom']); ?></td>
                                <td data-label="Prénom"><?php echo htmlspecialchars($etudiant['prenom']); ?></td>
                                <td data-label="Email"><?php echo htmlspecialchars($etudiant['email']); ?></td>
                                <td data-label="Classe"><?php echo htmlspecialchars($etudiant['classe_nom']); ?></td>
                                <td data-label="Actions">
                                    <form action="etudiants.php" method="post" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet étudiant ?');">
                                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($etudiant['id']); ?>">
                                        <button type="submit" name="delete_student" class="btn-action delete">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>