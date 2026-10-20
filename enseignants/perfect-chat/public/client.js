document.addEventListener('DOMContentLoaded', () => {
    const socket = io(); // Connects to the Socket.IO server

    // DOM Elements
    const welcomeScreen = document.getElementById('welcome-screen');
    const usernameInput = document.getElementById('username-input');
    const joinChatButton = document.getElementById('join-chat-button');
    const usernameError = document.getElementById('username-error');

    const chatApp = document.getElementById('chat-app');
    const currentUsernameDisplay = document.getElementById('current-username');
    const messagesList = document.getElementById('messages');
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    const usersList = document.getElementById('users-list');
    const userCountDisplay = document.getElementById('user-count');
    const typingIndicator = document.getElementById('typing-indicator');

    let currentUsername = ''; // To store the user's chosen username
    let typingTimeout; // To manage typing indicator

    // --- Welcome Screen Logic ---
    joinChatButton.addEventListener('click', () => {
        const username = usernameInput.value.trim();
        if (username) {
            socket.emit('register user', username);
        } else {
            usernameError.textContent = 'Veuillez entrer un nom d\'utilisateur.';
        }
    });

    usernameInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            joinChatButton.click();
        }
    });

    socket.on('username error', (message) => {
        usernameError.textContent = message;
    });

    socket.on('username accepted', (username) => {
        currentUsername = username;
        currentUsernameDisplay.textContent = username;
        welcomeScreen.classList.add('hidden'); // Hide welcome screen
        chatApp.classList.remove('hidden'); // Show chat app
        messageInput.focus(); // Focus on message input
    });

    // --- Chat Application Logic ---

    // Handle sending messages
    messageForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Prevent page reload
        if (messageInput.value && currentUsername) {
            socket.emit('chat message', messageInput.value);
            messageInput.value = ''; // Clear input
            socket.emit('typing', false); // Stop typing after sending
        }
    });

    // Handle receiving messages
    socket.on('chat message', (data) => {
        const item = document.createElement('li');
        item.classList.add('message-item'); // Add a class for styling

        // Check if the message is from the current user
        if (data.username === currentUsername) {
            item.classList.add('my-message');
        }

        item.innerHTML = `
            <div class="message-meta">
                <span class="username">${data.username}</span>
                <span class="timestamp">${data.timestamp}</span>
            </div>
            <div class="message-text">${data.text}</div>
        `;
        messagesList.appendChild(item);
        scrollToBottom();
    });

    // Handle user list updates
    socket.on('update user list', (users) => {
        usersList.innerHTML = ''; // Clear existing list
        users.forEach(user => {
            const li = document.createElement('li');
            li.textContent = user;
            usersList.appendChild(li);
        });
        userCountDisplay.textContent = users.length; // Update user count
    });

    // Announce user joined/left (optional, but good for chat feedback)
    socket.on('user joined', (username) => {
        const notification = document.createElement('li');
        notification.classList.add('notification');
        notification.textContent = `${username} a rejoint le chat.`;
        messagesList.appendChild(notification);
        scrollToBottom();
    });

    socket.on('user left', (username) => {
        const notification = document.createElement('li');
        notification.classList.add('notification');
        notification.textContent = `${username} a quitté le chat.`;
        messagesList.appendChild(notification);
        scrollToBottom();
    });

    // --- Typing Indicator ---
    messageInput.addEventListener('input', () => {
        if (currentUsername) {
            socket.emit('typing', true); // Tell server I'm typing
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                socket.emit('typing', false); // Tell server I stopped typing after a delay
            }, 1500); // 1.5 seconds without typing
        }
    });

    socket.on('user typing', (data) => {
        // Only show if it's not the current user typing
        if (data.username !== currentUsername) {
            if (data.isTyping) {
                typingIndicator.textContent = `${data.username} est en train d'écrire...`;
                typingIndicator.classList.add('visible');
            } else {
                // Wait a moment before clearing to avoid flickering if typing pauses briefly
                setTimeout(() => {
                    typingIndicator.classList.remove('visible');
                    // Clear text only if no one else is typing
                    // This simple example just clears it, for multiple typers,
                    // you'd need a more complex state management
                    typingIndicator.textContent = '';
                }, 500);
            }
        }
    });

    // --- Utility Function ---
    function scrollToBottom() {
        messagesList.scrollTop = messagesList.scrollHeight;
    }
});