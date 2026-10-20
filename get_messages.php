<?php
// get_messages.php

session_start();
require_once 'db.php'; // Assurez-vous que ce chemin est correct

if (!isset($_SESSION['user_id'])) {
    // Si non authentifié, renvoyer un tableau vide ou une erreur, selon votre préférence de sécurité.
    // Pour le chat, il est préférable de ne pas envoyer de données si l'utilisateur n'est pas censé les voir.
    echo json_encode([]);
    exit();
}

header('Content-Type: application/json'); // Indique que la réponse est du JSON

try {
    // Jointure avec la table `utilisateurs` pour obtenir le nom de l'expéditeur du message
    // et pour le message de réponse.
    $stmt = $pdo->prepare("
        SELECT
            mc.id,
            mc.sender_id,
            mc.sender_type,
            u.nom AS sender_name, -- Nom de l'expéditeur du message principal
            mc.message,
            mc.timestamp,
            mc.reply_to_message_id,
            reply_mc.message AS reply_to_message,
            reply_u.nom AS reply_to_sender_name -- Nom de l'expéditeur du message de réponse
        FROM
            messages_chat mc
        JOIN
            utilisateurs u ON mc.sender_id = u.id AND mc.sender_type = u.type_utilisateur
        LEFT JOIN
            messages_chat reply_mc ON mc.reply_to_message_id = reply_mc.id
        LEFT JOIN
            utilisateurs reply_u ON reply_mc.sender_id = reply_u.id AND reply_mc.sender_type = reply_u.type_utilisateur
        ORDER BY
            mc.timestamp ASC
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Appliquer htmlspecialchars pour échapper les caractères spéciaux UNE SEULE FOIS AVANT d'envoyer au client.
    foreach ($messages as &$msg) {
        // Échapper le contenu du message et les noms
        $msg['message'] = htmlspecialchars($msg['message'] ?? '', ENT_QUOTES, 'UTF-8');
        $msg['sender_name'] = htmlspecialchars($msg['sender_name'] ?? 'Inconnu', ENT_QUOTES, 'UTF-8');

        // Gérer les messages de réponse
        if (isset($msg['reply_to_message'])) {
            $msg['reply_to_message'] = htmlspecialchars($msg['reply_to_message'], ENT_QUOTES, 'UTF-8');
        } else {
            $msg['reply_to_message'] = null; // Assurer qu'il est null si pas de réponse
        }

        if (isset($msg['reply_to_sender_name'])) {
            $msg['reply_to_sender_name'] = htmlspecialchars($msg['reply_to_sender_name'], ENT_QUOTES, 'UTF-8');
        } else {
            $msg['reply_to_sender_name'] = 'Inconnu'; // Valeur par défaut si l'expéditeur de la réponse n'est pas trouvé
        }

        // Formater le timestamp
        try {
            $date = new DateTime($msg['timestamp']);
            $msg['timestamp'] = $date->format('d-m-Y H:i');
        } catch (Exception $e) {
            $msg['timestamp'] = 'Date invalide';
        }
    }

    echo json_encode($messages);

} catch (\PDOException $e) {
    error_log("Get messages PDO Error: " . $e->getMessage()); // Loguer l'erreur pour le débogage
    echo json_encode(['error' => 'Erreur lors du chargement des messages : ' . $e->getMessage()]);
}
?>