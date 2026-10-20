<?php
// chat/get_messages.php
// ATTENTION : Supprimer htmlspecialchars() ouvre des vulnérabilités XSS.
// NE PAS UTILISER EN PRODUCTION SANS PROTECTION ALTERNATIVE.

session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT
            m.id,
            m.sender_id,
            u.nom AS sender_name,
            m.message,
            m.timestamp,
            m.reply_to_message_id,
            r.message AS reply_to_message,
            ru.nom AS reply_to_sender_name
        FROM
            messages_chat m
        INNER JOIN
            enseignants u ON m.sender_id = u.id
        LEFT JOIN
            messages_chat r ON m.reply_to_message_id = r.id
        LEFT JOIN
            enseignants ru ON r.sender_id = ru.id
        ORDER BY
            m.timestamp ASC
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Suppression de htmlspecialchars() pour TOUS les champs
    $rawMessages = array_map(function($msg) {
        $msg['sender_name'] = $msg['sender_name'] ?? '';
        $msg['message'] = $msg['message'] ?? '';
        $msg['timestamp'] = $msg['timestamp'] ?? '';
        if (isset($msg['reply_to_message'])) {
            $msg['reply_to_message'] = $msg['reply_to_message'];
        }
        if (isset($msg['reply_to_sender_name'])) {
            $msg['reply_to_sender_name'] = $msg['reply_to_sender_name'];
        }
        return $msg;
    }, $messages);

    echo json_encode($rawMessages);

} catch (PDOException $e) {
    error_log("Erreur PDO dans get_messages.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur lors de la récupération des messages.']);
}
?>