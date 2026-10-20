<?php
session_start();
require_once '../db.php';

// Vérifie que l'utilisateur est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Récupère toutes les disponibilités avec les noms d'enseignants
$sql = "SELECT d.id, e.nom, d.jour, d.creneau
        FROM disponibilites d
        JOIN enseignants e ON d.enseignant_id = e.id
        ORDER BY e.nom, 
                 FIELD(d.jour, 'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'),
                 FIELD(d.creneau, 'Matin','Midi','Soir')";

$stmt = $pdo->query($sql);
$disponibilites = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Disponibilités des Enseignants</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(120deg, #e0e7ef 0%, #f8fafc 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: rgba(255,255,255,0.97);
            border-radius: 18px;
            box-shadow: 0 8px 32px 0 rgba(31,38,135,0.13);
            padding: 32px 24px 24px 24px;
        }
        h1 {
            text-align: center;
            color: #2d3559;
            margin-top: 0;
            margin-bottom: 32px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 6px 24px 0 rgba(31,38,135,0.10);
            margin-bottom: 24px;
        }
        th, td {
            padding: 14px 12px;
            text-align: center;
            font-size: 1.05rem;
        }
        th {
            background: linear-gradient(90deg, #2575fc 0%, #6a11cb 100%);
            color: #fff;
            letter-spacing: 1px;
        }
        tr:nth-child(even) td {
            background: #f3f6fd;
        }
        tr:hover td {
            background: #e0e7ff;
            transition: background 0.2s;
        }
        .btn-delete {
            color: #fff;
            background: linear-gradient(90deg, #ff5858 0%, #f09819 100%);
            padding: 7px 16px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: 500;
            margin: 0 2px;
            box-shadow: 0 2px 8px rgba(255,88,88,0.10);
            transition: background 0.3s, color 0.2s, box-shadow 0.2s;
            display: inline-block;
        }
        .btn-delete:hover {
            background: linear-gradient(90deg, #f09819 0%, #ff5858 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(255,88,88,0.18);
        }
        .back-link {
            display: inline-block;
            margin: 0 auto;
            padding: 10px 22px;
            border-radius: 8px;
            background: linear-gradient(90deg, #2575fc 0%, #6a11cb 100%);
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(80,80,200,0.10);
            transition: background 0.3s, color 0.2s, box-shadow 0.2s;
        }
        .back-link:hover {
            background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%);
            color: #f3f6fd;
            box-shadow: 0 6px 20px rgba(80,80,200,0.18);
        }
        @media (max-width: 700px) {
            .container {
                padding: 12px 2vw;
            }
            table, th, td {
                font-size: 0.97rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Disponibilités des Enseignants</h1>
        <table>
            <thead>
                <tr>
                    <th>Nom de l'enseignant</th>
                    <th>Jour</th>
                    <th>Période</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($disponibilites as $dispo): ?>
                    <tr>
                        <td><?= htmlspecialchars($dispo['nom']) ?></td>
                        <td><?= htmlspecialchars($dispo['jour']) ?></td>
                        <td><?= htmlspecialchars($dispo['creneau']) ?></td>
                        <td>
                            <a class="btn-delete" href="supprimer_disponibilite.php?id=<?= $dispo['id'] ?>" onclick="return confirm('Supprimer cette disponibilité ?')">🗑 Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="text-align:center;">
            <a class="back-link" href="dashboard.php">← Retour au tableau de bord</a>
        </div>
    </div>
</body>
</html>