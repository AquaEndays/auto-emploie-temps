<?php
// chat.php (version pour chat de groupe)
// ATTENTION : Supprimer htmlspecialchars() ouvre des vulnérabilités XSS.
// NE PAS UTILISER EN PRODUCTION SANS PROTECTION ALTERNATIVE.

session_start();

require_once '../db.php'; // Ajustez ce chemin si votre db.php n'est pas un dossier au-dessus

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirigez vers votre page de connexion
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_user_name = 'Inconnu'; // Valeur par défaut
$current_user_type = 'Inconnu'; // Valeur par défaut

try {
    // Assuming 'type_evenement' is a placeholder and you mean 'role' or similar for user type.
    // If 'type_evenement' is indeed what you use, keep it.
    // Otherwise, adjust the column name.
    $stmt_user_info = $pdo->prepare("SELECT nom, role FROM enseignants WHERE id = :id"); // Changed type_evenement to role
    $stmt_user_info->execute(['id' => $current_user_id]);
    $user_info = $stmt_user_info->fetch();

    if ($user_info) {
        $current_user_name = $user_info['nom'];
        // Use the 'role' column if available, otherwise fallback to session
        if (isset($user_info['role']) && !empty($user_info['role'])) {
            $current_user_type = $user_info['role'];
        }
    } else {
        // Fallback for students if they also use this chat and are not in 'enseignants' table
        // You might need a more robust way to determine user type from session or a combined user table.
        $stmt_user_info_etudiant = $pdo->prepare("SELECT nom FROM etudiants WHERE id = :id");
        $stmt_user_info_etudiant->execute(['id' => $current_user_id]);
        $etudiant_info = $stmt_user_info_etudiant->fetch();
        if ($etudiant_info) {
            $current_user_name = $etudiant_info['nom'];
            $current_user_type = 'Étudiant'; // Or whatever default role for students
        } else {
            $current_user_name = $_SESSION['nom'] ?? 'Utilisateur Inconnu';
            $current_user_type = $_SESSION['user_type'] ?? 'Inconnu';
        }
    }
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération des infos utilisateur dans chat.php: " . $e->getMessage());
    $current_user_name = 'Erreur Nom';
    $current_user_type = 'Erreur Type';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Interne (Groupe)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Color Palette & Variables */
        :root {
            --primary-blue: #007bff;
            --primary-dark-blue: #0056b3;
            --secondary-green: #28a745;
            --secondary-dark-green: #218838;
            --light-grey-bg: #f4f7f6;
            --dark-grey-text: #333;
            --medium-grey-text: #666;
            --light-border: #e0e0e0;
            --white: #ffffff;
            --bubble-sent: #DCF8C6; /* Light green for sent messages */
            --bubble-received: #E6E8EB; /* Light grey for received messages */
            --timestamp-color: #999;
            --shadow-light: rgba(0, 0, 0, 0.08);
            --shadow-medium: rgba(0, 0, 0, 0.15);
            --gradient-start: #6dd5ed;
            --gradient-end: #2193b0;
        }

        /* Base Styles */
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, var(--light-grey-bg), #dbe0e3);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden; /* Hide potential scrollbars from animations */
            color: var(--dark-grey-text);
            font-size: 16px;
        }

        /* Chat Container */
        .chat-container {
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: 0 10px 30px var(--shadow-medium);
            width: 100%;
            max-width: 800px; /* Increased max-width */
            display: flex;
            flex-direction: column;
            height: 90vh; /* Increased height */
            min-height: 500px;
            overflow: hidden;
            animation: fadeIn 0.8s ease-out forwards;
            position: relative; /* For the fancy border */
        }

        /* Animated Border */
        .chat-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 15px;
            padding: 3px; /* Controls border thickness */
            background: linear-gradient(45deg, var(--primary-blue), var(--secondary-green), var(--gradient-start));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Chat Header */
        .chat-header {
            background: linear-gradient(90deg, var(--primary-blue), var(--gradient-end));
            color: var(--white);
            padding: 20px;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            text-align: center;
            font-size: 1.4em;
            font-weight: 600;
            position: relative;
            box-shadow: 0 2px 8px var(--shadow-light);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .chat-header i {
            font-size: 1.2em;
            margin-right: 5px;
        }

        /* Chat Messages Area */
        .chat-messages {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #fcfdfe; /* Lighter background for messages */
            display: flex;
            flex-direction: column;
            scroll-behavior: smooth;
            border-bottom: 1px solid var(--light-border);
        }

        /* Message Bubbles */
        .message-bubble {
            max-width: 75%; /* Slightly wider */
            padding: 12px 18px; /* More padding */
            border-radius: 25px; /* More rounded */
            margin-bottom: 15px; /* More space between messages */
            line-height: 1.5;
            word-wrap: break-word;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); /* Subtle shadow */
            transition: transform 0.2s ease, background-color 0.2s ease;
        }
        .message-bubble:hover {
            transform: translateY(-2px); /* Lift on hover */
        }

        .message-bubble.sent {
            background-color: var(--bubble-sent);
            align-self: flex-end;
            border-bottom-right-radius: 5px; /* Pointy edge */
        }
        .message-bubble.received {
            background-color: var(--bubble-received);
            align-self: flex-start;
            border-bottom-left-radius: 5px; /* Pointy edge */
        }

        .message-sender {
            font-size: 0.85em;
            font-weight: 600;
            color: var(--primary-dark-blue); /* Distinct color for sender */
            margin-bottom: 5px;
        }
        .message-bubble.sent .message-sender {
            color: var(--secondary-dark-green);
        }

        .message-timestamp {
            font-size: 0.75em;
            color: var(--timestamp-color);
            text-align: right;
            margin-top: 8px;
            display: block; /* Ensure it takes full width */
        }
        .message-bubble.received .message-timestamp {
            text-align: left;
        }

        /* Reply Information */
        .message-reply-info {
            margin-bottom: 8px;
            color: var(--medium-grey-text);
            font-size: 0.8em;
            border-left: 4px solid var(--primary-blue); /* Thicker and colored border */
            padding-left: 8px;
            font-style: italic;
            background-color: rgba(0, 123, 255, 0.05); /* Very light blue background */
            border-radius: 5px;
            padding-top: 3px;
            padding-bottom: 3px;
        }

        /* Chat Input Area */
        .chat-input {
            display: flex;
            padding: 15px 20px;
            border-top: 1px solid var(--light-border);
            background-color: var(--white);
            align-items: flex-end; /* Align items to bottom for growing textarea */
        }
        .chat-input textarea {
            flex-grow: 1;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1em;
            margin-right: 15px;
            resize: none;
            min-height: 45px; /* Slightly taller */
            max-height: 120px; /* Allow more lines */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }
        .chat-input textarea:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .chat-input button {
            background-color: var(--secondary-green);
            color: var(--white);
            border: none;
            border-radius: 8px;
            padding: 12px 20px; /* Larger button */
            cursor: pointer;
            font-size: 1.05em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chat-input button:hover {
            background-color: var(--secondary-dark-green);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
        }
        .chat-input button:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(40, 167, 69, 0.15);
        }

        /* Chat Footer */
        .chat-footer {
            text-align: center;
            padding: 12px;
            background-color: #fcfdfe; /* Matches message background */
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
            border-top: 1px solid var(--light-border);
            font-size: 0.9em;
        }
        .chat-footer a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .chat-footer a:hover {
            color: var(--primary-dark-blue);
            text-decoration: underline;
        }

        /* Scrollbar Styling (Webkit - Chrome, Safari) */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }
        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .chat-messages::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .chat-container {
                width: 95%;
                height: 95vh;
                margin: 2.5vh auto;
            }
            .chat-header {
                font-size: 1.2em;
                padding: 15px;
            }
            .chat-messages {
                padding: 15px;
            }
            .message-bubble {
                max-width: 85%;
                padding: 10px 15px;
                margin-bottom: 10px;
            }
            .chat-input {
                padding: 10px 15px;
            }
            .chat-input textarea {
                min-height: 40px;
                padding: 10px 12px;
            }
            .chat-input button {
                padding: 10px 15px;
                font-size: 0.95em;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
                font-size: 14px;
            }
            .chat-container {
                border-radius: 10px;
            }
            .chat-header {
                font-size: 1em;
                padding: 12px;
            }
            .chat-messages {
                padding: 10px;
            }
            .message-bubble {
                max-width: 90%;
                font-size: 0.9em;
                padding: 8px 12px;
                margin-bottom: 8px;
                border-radius: 20px;
            }
            .message-sender {
                font-size: 0.8em;
            }
            .message-timestamp {
                font-size: 0.65em;
                margin-top: 5px;
            }
            .chat-input {
                flex-direction: column;
                padding: 10px;
                gap: 10px;
            }
            .chat-input textarea {
                margin-right: 0;
                width: 100%;
                max-height: 80px;
            }
            .chat-input button {
                width: 100%;
                padding: 10px;
                font-size: 1em;
            }
            .chat-footer {
                padding: 8px;
                font-size: 0.8em;
                border-bottom-left-radius: 10px;
                border-bottom-right-radius: 10px;
            }
        }
    </style>
</head>
<body>

    <div class="chat-container">
        <div class="chat-header">
            <i class="fas fa-comments"></i> Chat Interne (Connecté en tant que: **<?php echo htmlspecialchars($current_user_name); ?>** (<?php echo htmlspecialchars($current_user_type); ?>))
        </div>
        <div class="chat-messages" id="chatMessages">
        </div>
        <div class="chat-input">
            <textarea id="messageInput" placeholder="Tapez votre message..." rows="1"></textarea>
            <button id="sendMessageBtn"><i class="fas fa-paper-plane"></i> Envoyer</button>
        </div>
        <div class="chat-footer">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Retour au Tableau de Bord</a>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        const currentUserId = <?php echo json_encode($current_user_id); ?>;

        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function loadMessages() {
            fetch('get_messages.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(messages => {
                    const shouldScroll = chatMessages.scrollHeight - chatMessages.scrollTop <= chatMessages.clientHeight + 50; // Check if near bottom
                    chatMessages.innerHTML = '';
                    messages.forEach(msg => {
                        const messageBubble = document.createElement('div');
                        messageBubble.classList.add('message-bubble');
                        messageBubble.classList.add(msg.sender_id == currentUserId ? 'sent' : 'received');
                        messageBubble.dataset.messageId = msg.id;
                        // Use textContent to prevent XSS in data attributes if message content is untrusted
                        messageBubble.dataset.senderName = msg.sender_name;
                        messageBubble.dataset.messageContent = msg.message;

                        const senderName = document.createElement('div');
                        senderName.classList.add('message-sender');
                        senderName.textContent = msg.sender_name + " : "; // Use textContent for security

                        if (msg.reply_to_message_id && msg.reply_to_message) {
                            const replyToContainer = document.createElement('div');
                            replyToContainer.classList.add('message-reply-info');
                            // Sanitize reply content if it comes from user input, even if it's a reply
                            const repliedText = `@${msg.reply_to_sender_name}: "${msg.reply_to_message}"`;
                            replyToContainer.textContent = repliedText; // Use textContent here for safety
                            messageBubble.appendChild(replyToContainer);
                        }

                        const messageContent = document.createElement('div');
                        messageContent.textContent = msg.message; // Use textContent for security

                        const timestamp = document.createElement('div');
                        timestamp.classList.add('message-timestamp');
                        timestamp.textContent = msg.timestamp; // Use textContent for security

                        messageBubble.appendChild(senderName);
                        messageBubble.appendChild(messageContent);
                        messageBubble.appendChild(timestamp);

                        messageBubble.addEventListener('dblclick', () => {
                            // Ensure quoted message content is also sanitized for display purposes
                            const quotedMessageSender = messageBubble.dataset.senderName;
                            const quotedMessageContent = messageBubble.dataset.messageContent; // This is the raw message content

                            // You might want to display a *truncated* version of the message if it's very long
                            const displayContent = quotedMessageContent.length > 50 ?
                                quotedMessageContent.substring(0, 47) + '...' : quotedMessageContent;

                            const quotedMessage = `@${quotedMessageSender}: "${displayContent}"\n`;
                            messageInput.value = quotedMessage + messageInput.value;
                            messageInput.focus();
                            // Adjust textarea height
                            messageInput.style.height = 'auto';
                            messageInput.style.height = (messageInput.scrollHeight) + 'px';
                        });

                        chatMessages.appendChild(messageBubble);
                    });
                    if (shouldScroll) {
                        scrollToBottom();
                    }
                })
                .catch(error => console.error('Erreur lors du chargement des messages:', error));
        }

        sendMessageBtn.addEventListener('click', () => {
            const message = messageInput.value.trim();

            if (message === '') {
                alert('Veuillez taper un message.');
                return;
            }

            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `message=${encodeURIComponent(message)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    messageInput.style.height = 'auto'; // Reset height
                    loadMessages(); // Reload messages to see the new one
                } else {
                    alert('Erreur lors de l\'envoi du message: ' + (data.error || 'Erreur inconnue'));
                }
            })
            .catch(error => console.error('Erreur:', error));
        });

        // Initial load and polling for new messages
        loadMessages();
        setInterval(loadMessages, 3000); // Poll every 3 seconds

        // Auto-resize textarea
        messageInput.addEventListener('input', () => {
            messageInput.style.height = 'auto';
            messageInput.style.height = (messageInput.scrollHeight) + 'px';
        });

        // Optional: Send message on Enter key (Shift+Enter for new line)
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault(); // Prevent new line
                sendMessageBtn.click(); // Trigger send button click
            }
        });
    </script>

</body>
</html>