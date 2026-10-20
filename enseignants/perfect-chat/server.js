const express = require('express');
const http = require('http');
const socketIo = require('socket.io');

const app = express();
const server = http.createServer(app);
const io = socketIo(server);

// Serve static files from the 'public' directory
app.use(express.static('public'));

// Store active users and their sockets (optional, for more complex features)
const users = {}; // userId -> username
const sockets = {}; // socketId -> userId

io.on('connection', (socket) => {
    console.log(`Un utilisateur s'est connecté: ${socket.id}`);

    // --- User Management (Optional but good for a "perfect" chat) ---
    // When a user provides their username
    socket.on('register user', (username) => {
        if (!username || typeof username !== 'string' || username.trim() === '') {
            socket.emit('username error', 'Veuillez entrer un nom d\'utilisateur valide.');
            return;
        }

        const trimmedUsername = username.trim();
        // Check if username is already taken
        const existingUserSocketId = Object.keys(users).find(
            (id) => users[id].toLowerCase() === trimmedUsername.toLowerCase()
        );

        if (existingUserSocketId) {
            socket.emit('username error', `Le nom d'utilisateur "${trimmedUsername}" est déjà pris.`);
            return;
        }

        const userId = socket.id; // Using socket ID as user ID for simplicity
        users[userId] = trimmedUsername;
        sockets[userId] = socket.id; // Store socket ID if needed elsewhere

        socket.emit('username accepted', trimmedUsername); // Confirm username
        io.emit('user joined', trimmedUsername); // Announce new user to everyone
        io.emit('update user list', Object.values(users)); // Update user list for everyone
        console.log(`Utilisateur enregistré: ${trimmedUsername} (${userId})`);
    });

    // Handle incoming chat messages
    socket.on('chat message', (msg) => {
        const userId = socket.id;
        const username = users[userId] || 'Anonyme'; // Get username, default to 'Anonyme'

        if (msg.trim() === '') {
            // Optional: prevent empty messages
            return;
        }

        const messageData = {
            username: username,
            text: msg,
            timestamp: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
        };
        console.log(`[${username}] ${msg}`);
        // Emit the message to all connected clients
        io.emit('chat message', messageData);
    });

    // Handle user typing status
    socket.on('typing', (isTyping) => {
        const username = users[socket.id];
        if (username) {
            // Broadcast to all clients EXCEPT the sender
            socket.broadcast.emit('user typing', { username, isTyping });
        }
    });

    // Handle disconnection
    socket.on('disconnect', () => {
        const userId = socket.id;
        const username = users[userId];
        if (username) {
            delete users[userId];
            delete sockets[userId];
            io.emit('user left', username); // Announce user left
            io.emit('update user list', Object.values(users)); // Update user list for everyone
            console.log(`Utilisateur déconnecté: ${username} (${userId})`);
        } else {
            console.log(`Un utilisateur anonyme s'est déconnecté: ${userId}`);
        }
    });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Serveur de chat écoutant sur le port ${PORT}`);
});