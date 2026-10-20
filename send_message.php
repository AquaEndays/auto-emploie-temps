<?php
// send_message.php

session_start();
require_once 'db.php'; // Assurez-vous que ce chemin est correct

// Vérifier si l'utilisateur est connecté et si son ID et type sont en session
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || !isset($_SESSION['user_type']) || empty($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non authentifié ou informations de session incomplètes.']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$sender_type = $_SESSION['user_type']; // Récupère le type d'utilisateur de la session

// Récupérer et nettoyer le contenu du message
$message_content = $_POST['message'] ?? '';
// Enlever les espaces blancs inutiles
$message_content = trim($message_content);

// L'ID du message auquel on répond, peut être null si ce n'est pas une réponse
$reply_to_message_id = $_POST['reply_to_message_id'] ?? null;

if (empty($message_content)) {
    echo json_encode(['success' => false, 'error' => 'Contenu du message manquant.']);
    exit();
}

try {
    // Insérer le message. Le sender_id et sender_type sont ceux de l'utilisateur ACTUELLEMENT CONNECTÉ.
    // PDO::prepare gère l'échappement des données pour l'insertion, ce qui prévient les injections SQL.
    $stmt = $pdo->prepare("INSERT INTO messages_chat (sender_id, sender_type, message, reply_to_message_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$sender_id, $sender_type, $message_content, $reply_to_message_id]);

    echo json_encode(['success' => true]);
} catch (\PDOException $e) {
    error_log("Send message PDO Error: " . $e->getMessage()); // Loguer l'erreur pour le débogage
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'enregistrement du message : ' . $e->getMessage()]);
}
?>