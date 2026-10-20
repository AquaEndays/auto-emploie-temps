<?php
// chat.php

session_start(); // DOIT être la toute première instruction PHP

// Vérifier si l'utilisateur est connecté et récupérer ses informations
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_type'])) {
    // Si l'utilisateur n'est pas connecté, rediriger vers la page de login temporaire
    session_destroy(); // Détruire toute session incomplète
    header('Location: login_test.php'); // Redirigez vers votre page de connexion test
    exit();
}

$current_user_id = $_SESSION['user_id'];
// Pas besoin de htmlspecialchars ici, user_name est échappé par get_messages.php ou à l'insertion si possible.
// Mais pour l'affichage immédiat dans le header PHP, mieux vaut l'échapper au cas où.
$current_user_name = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');
$current_user_type = htmlspecialchars($_SESSION['user_type'], ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Interne</title>
    <style>
        /* Styles Généraux */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(to right, #4facfe 0%, #00f2fe 100%);
            overflow: hidden;
        }

        .chat-container {
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            width: 95%;
            max-width: 800px;
            display: flex;
            flex-direction: column;
            height: 90vh;
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .chat-header {
            background: linear-gradient(to right, #007bff, #0056b3);
            color: white;
            padding: 20px;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            text-align: center;
            font-size: 1.5em;
            font-weight: bold;
            letter-spacing: 1px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .chat-messages {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            gap: 10px;
            scrollbar-width: thin;
            scrollbar-color: #007bff #f8f9fa;
        }
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }
        .chat-messages::-webkit-scrollbar-track {
            background: #f8f9fa;
        }
        .chat-messages::-webkit-scrollbar-thumb {
            background-color: #007bff;
            border-radius: 10px;
            border: 2px solid #f8f9fa;
        }

        .message-bubble {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 25px;
            margin-bottom: 5px;
            line-height: 1.5;
            word-wrap: break-word;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            opacity: 0;
            transform: translateY(10px);
            animation: messageAppear 0.3s forwards ease-out;
        }

        @keyframes messageAppear {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-bubble.sent {
            background: linear-gradient(to right, #28a745, #218838);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }
        .message-bubble.received {
            background-color: #e2e6ea;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }

        .message-sender {
            font-size: 0.9em;
            font-weight: bold;
            margin-bottom: 3px;
            color: #0056b3;
        }
        .message-bubble.sent .message-sender {
             color: rgba(255, 255, 255, 0.8);
        }
        .message-timestamp {
            font-size: 0.75em;
            color: rgba(0, 0, 0, 0.5);
            margin-top: 5px;
            text-align: right;
        }
        .message-bubble.sent .message-timestamp {
            color: rgba(255, 255, 255, 0.7);
        }

        .reply-to-display {
            background-color: rgba(0, 0, 0, 0.05);
            border-left: 3px solid #007bff;
            padding: 5px 10px;
            margin-bottom: 8px;
            border-radius: 5px;
            font-size: 0.85em;
            color: #444;
            max-height: 50px;
            overflow: hidden;
        }
        .message-bubble.sent .reply-to-display {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #ffffff;
            color: rgba(255, 255, 255, 0.8);
        }
        .reply-to-display .quoted-sender {
            font-weight: bold;
            color: #007bff;
        }
        .message-bubble.sent .reply-to-display .quoted-sender {
            color: #ffffff;
        }
        .reply-to-display .quoted-content {
            font-style: italic;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        .chat-input {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .reply-preview {
            background-color: #e9eff5;
            border-left: 5px solid #007bff;
            padding: 10px 15px;
            border-radius: 8px;
            position: relative;
            font-size: 0.95em;
            color: #555;
            display: none;
            animation: slideInFromTop 0.3s ease-out forwards;
        }
        @keyframes slideInFromTop {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .reply-preview .reply-to-text {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 3px;
        }
        .reply-preview .reply-content {
            font-style: italic;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: calc(100% - 30px);
        }
        .reply-preview .close-reply {
            position: absolute;
            top: 8px;
            right: 10px;
            cursor: pointer;
            font-weight: bold;
            color: #888;
            font-size: 1.2em;
            transition: color 0.2s ease;
        }
        .reply-preview .close-reply:hover {
            color: #dc3545;
        }

        .chat-input-wrapper {
            display: flex;
            width: 100%;
            align-items: center;
            gap: 10px;
        }
        .chat-input textarea {
            flex-grow: 1;
            border: 1px solid #cce0ff;
            border-radius: 20px;
            padding: 12px 18px;
            font-size: 1em;
            margin-right: 0;
            resize: none;
            min-height: 45px;
            max-height: 120px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.08);
        }
        .chat-input textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .chat-input button {
            background: linear-gradient(to right, #28a745, #218838);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 12px 25px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
            white-space: nowrap;
        }
        .chat-input button:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
        }
        .chat-input button:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(40, 167, 69, 0.2);
        }

        .chat-footer {
            text-align: center;
            padding: 15px;
            background-color: #f0f2f5;
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .chat-footer a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        .chat-footer a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="chat-container">
        <div class="chat-header">
            Chat Interne (Connecté en tant que: <?php echo $current_user_name; ?> (<?php echo $current_user_type; ?>))
        </div>
        <div class="chat-messages" id="chatMessages">
            </div>
        <div class="chat-input">
            <div class="reply-preview" id="replyPreview">
                <span class="close-reply" id="closeReplyBtn">&times;</span>
                <div class="reply-to-text">En réponse à <span id="replySenderName"></span></div>
                <div class="reply-content" id="replyContent"></div>
            </div>
            <div class="chat-input-wrapper">
                <textarea id="messageInput" placeholder="Tapez votre message..." rows="1"></textarea>
                <button id="sendMessageBtn">Envoyer</button>
            </div>
        </div>
        <div class="chat-footer">
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        const replyPreview = document.getElementById('replyPreview');
        const replySenderName = document.getElementById('replySenderName');
        const replyContent = document.getElementById('replyContent');
        const closeReplyBtn = document.getElementById('closeReplyBtn');
        // currentUserId est essentiel pour différencier les messages envoyés/reçus
        const currentUserId = <?php echo json_encode($current_user_id); ?>;

        let replyingToMessage = null;
        const displayedMessageIds = new Set(); // Pour éviter les doublons lors du rafraîchissement

        function scrollToBottom(animate = true) {
            if (animate) {
                chatMessages.scrollTo({
                    top: chatMessages.scrollHeight,
                    behavior: 'smooth'
                });
            } else {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        function createMessageBubble(msg) {
            // Vérifier si le message a déjà été ajouté pour éviter les doublons
            if (displayedMessageIds.has(msg.id)) {
                return;
            }

            const messageBubble = document.createElement('div');
            messageBubble.classList.add('message-bubble');
            // La classe 'sent' ou 'received' dépend de l'ID de l'expéditeur du message par rapport à l'ID de l'utilisateur connecté
            messageBubble.classList.add(msg.sender_id == currentUserId ? 'sent' : 'received');

            // Afficher la réponse à un message précédent
            if (msg.reply_to_message_id && msg.reply_to_message) {
                const replyToDiv = document.createElement('div');
                replyToDiv.classList.add('reply-to-display');
                // msg.reply_to_message et msg.reply_to_sender_name sont déjà échappés par get_messages.php
                const quotedText = msg.reply_to_message.length > 50 ? msg.reply_to_message.substring(0, 50) + '...' : msg.reply_to_message;
                replyToDiv.innerHTML = `
                    <span class="quoted-sender">@${msg.reply_to_sender_name}</span>
                    <span class="quoted-content">${quotedText}</span>
                `;
                messageBubble.appendChild(replyToDiv);
            }

            const senderName = document.createElement('div');
            senderName.classList.add('message-sender');
            // msg.sender_name est déjà échappé par get_messages.php
            senderName.textContent = msg.sender_name + " : ";
            messageBubble.appendChild(senderName);

            const messageContent = document.createElement('div');
            // msg.message est déjà échappé par get_messages.php
            messageContent.textContent = msg.message;
            messageContent.style.whiteSpace = 'pre-wrap'; // Pour préserver les sauts de ligne si l'utilisateur en entre
            messageBubble.appendChild(messageContent);

            const timestamp = document.createElement('div');
            timestamp.classList.add('message-timestamp');
            timestamp.textContent = msg.timestamp;
            messageBubble.appendChild(timestamp);

            messageBubble.addEventListener('dblclick', () => {
                replyingToMessage = msg; // Stocke l'objet message complet pour la réponse
                replySenderName.textContent = msg.sender_name;
                replyContent.textContent = msg.message.length > 70 ? msg.message.substring(0, 70) + '...' : msg.message;
                replyPreview.style.display = 'block';
                messageInput.focus();
                adjustTextareaHeight();
            });

            chatMessages.appendChild(messageBubble);
            displayedMessageIds.add(msg.id); // Ajouter l'ID au Set pour le suivi
        }

        function loadMessages() {
            fetch('get_messages.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText + ' (' + response.status + ')');
                    }
                    return response.json();
                })
                .then(messages => {
                    // Check if we are scrolled to the bottom before adding new messages
                    const isScrolledToBottom = chatMessages.scrollHeight - chatMessages.clientHeight <= chatMessages.scrollTop + 1;
                    let newMessagesAdded = false;

                    messages.forEach(msg => {
                        if (!displayedMessageIds.has(msg.id)) {
                            createMessageBubble(msg);
                            newMessagesAdded = true;
                        }
                    });

                    // Scroll to bottom only if we were already at the bottom or new messages were added
                    if (isScrolledToBottom || newMessagesAdded) {
                        scrollToBottom(true); // Animate scroll if new messages
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des messages:', error);
                    // Afficher un message d'erreur à l'utilisateur si nécessaire
                    // chatMessages.innerHTML = "<p style='color:red;'>Erreur de chargement des messages. Réessayez.</p>";
                });
        }

        function resetReplyMode() {
            replyingToMessage = null;
            replyPreview.style.display = 'none';
            replySenderName.textContent = '';
            replyContent.textContent = '';
            adjustTextareaHeight();
        }

        closeReplyBtn.addEventListener('click', resetReplyMode);

        sendMessageBtn.addEventListener('click', () => {
            const message = messageInput.value.trim();

            if (message === '') {
                alert('Veuillez taper un message.');
                return;
            }

            let postBody = `message=${encodeURIComponent(message)}`;

            if (replyingToMessage) {
                // S'assurer que l'ID du message de réponse est bien l'ID du message auquel on répond
                postBody += `&reply_to_message_id=${encodeURIComponent(replyingToMessage.id)}`;
            }

            sendMessageBtn.disabled = true;
            sendMessageBtn.textContent = 'Envoi...';

            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: postBody
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText + ' (' + response.status + ')');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    resetReplyMode();
                    loadMessages(); // Recharger tous les messages pour inclure le nouveau
                    scrollToBottom(true); // Scroll immédiat après envoi du message
                } else {
                    alert('Erreur lors de l\'envoi du message: ' + (data.error || 'Erreur inconnue.'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur de communication est survenue: ' + error.message);
            })
            .finally(() => {
                sendMessageBtn.disabled = false;
                sendMessageBtn.textContent = 'Envoyer';
            });
        });

        // Auto-redimensionnement du textarea
        function adjustTextareaHeight() {
            messageInput.style.height = 'auto'; // Réinitialiser la hauteur pour calculer la nouvelle
            messageInput.style.height = (messageInput.scrollHeight) + 'px';
        }
        messageInput.addEventListener('input', adjustTextareaHeight);

        // Charger les messages au démarrage et scroller en bas
        loadMessages();
        // Un petit délai pour s'assurer que le contenu est rendu avant de scroller
        setTimeout(() => scrollToBottom(false), 100);

        // Rafraîchir les messages toutes les 3 secondes
        setInterval(loadMessages, 3000);
    </script>

</body>
</html>