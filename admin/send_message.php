<?php
// chat/send_message.php
// ATTENTION : Supprimer htmlspecialchars() ouvre des vulnérabilités XSS.
// NE PAS UTILISER EN PRODUCTION SANS PROTECTION ALTERNATIVE.

session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
// Suppression de htmlspecialchars() ici
$message = $_POST['message'] ?? ''; 
$reply_to_message_id = $_POST['reply_to_message_id'] ?? null;

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Le message ne peut pas être vide.']);
    exit();
}

try {
    $sql = "INSERT INTO messages_chat (sender_id, message, reply_to_message_id) VALUES (:sender_id, :message, :reply_to_message_id)";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':sender_id', $current_user_id, PDO::PARAM_INT);
    $stmt->bindParam(':message', $message, PDO::PARAM_STR); // Le message brut est lié ici.
    $stmt->bindValue(':reply_to_message_id', $reply_to_message_id, PDO::PARAM_INT);

    $stmt->execute();

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Erreur PDO dans send_message.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()]);
}
?>