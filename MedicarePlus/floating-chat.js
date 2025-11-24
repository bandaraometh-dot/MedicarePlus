// Simple Floating Chat Functionality
document.addEventListener('DOMContentLoaded', function() {
    const chatToggleBtn = document.getElementById('chat-toggle-btn');
    const chatContainer = document.getElementById('chat-container');
    const closeChat = document.getElementById('close-chat');
    const startChatBtn = document.getElementById('start-chat-btn');
    const messageInput = document.getElementById('message-input');
    const sendMessageBtn = document.getElementById('send-message');
    const messagesArea = document.getElementById('messages-area');
    const welcomeScreen = document.getElementById('welcome-screen');
    const activeChat = document.getElementById('active-chat');
    const backToDoctors = document.getElementById('back-to-doctors');

    let isChatOpen = false;

    // Toggle chat
    chatToggleBtn.addEventListener('click', function() {
        if (!isChatOpen) {
            chatContainer.classList.add('open');
            isChatOpen = true;
            // Clear notification when opened
            document.getElementById('message-count').style.display = 'none';
        } else {
            chatContainer.classList.remove('open');
            isChatOpen = false;
        }
    });

    // Close chat
    closeChat.addEventListener('click', function() {
        chatContainer.classList.remove('open');
        isChatOpen = false;
    });

    // Start chat
    startChatBtn.addEventListener('click', function() {
        welcomeScreen.style.display = 'none';
        activeChat.style.display = 'flex';
    });

    // Back to doctors list
    backToDoctors.addEventListener('click', function() {
        activeChat.style.display = 'none';
        welcomeScreen.style.display = 'flex';
    });

    // Send message
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && this.value.trim()) {
            sendMessage(this.value);
            this.value = '';
        }
    });

    sendMessageBtn.addEventListener('click', function() {
        if (messageInput.value.trim()) {
            sendMessage(messageInput.value);
            messageInput.value = '';
        }
    });

    function sendMessage(text) {
        // Add patient message
        addMessage('patient', text);
        
        // Simulate doctor response
        setTimeout(() => {
            simulateDoctorResponse();
        }, 1000 + Math.random() * 2000);
    }

    function addMessage(sender, text) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;
        
        const now = new Date();
        const time = now.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });

        if (sender === 'doctor') {
            messageDiv.innerHTML = `
                <div class="message-avatar">
                    <img src="https://ui-avatars.com/api/?name=Dr+Sarah+Smith&background=2a7de1&color=fff" alt="Doctor">
                </div>
                <div class="message-content">
                    <p>${text}</p>
                    <span class="message-time">${time}</span>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="message-content">
                    <p>${text}</p>
                    <span class="message-time">${time}</span>
                </div>
            `;
        }

        messagesArea.appendChild(messageDiv);
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    function simulateDoctorResponse() {
        const responses = [
            "I understand your concern. How can I assist you further?",
            "Thank you for sharing that information.",
            "I recommend scheduling an appointment to discuss this in detail.",
            "That's a common concern. Let me provide some guidance.",
            "I appreciate you reaching out. Is there anything else you'd like to know?"
        ];
        
        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
        addMessage('doctor', randomResponse);
    }

    // Quick options functionality
    document.querySelectorAll('.quick-option').forEach(option => {
        option.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            if (action === 'emergency') {
                alert('For emergencies, please call 911 immediately or visit the nearest emergency room.');
            } else if (action === 'appointment') {
                window.location.href = 'appointments.html';
            } else if (action === 'prescription') {
                alert('Please login to your account to request prescription refills.');
            }
        });
    });
});