/**
 * Client-side script for Raiseque Gemini AI Chatbot.
 * Uses vanilla Javascript (no jQuery/frameworks) for high compatibility and speed.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Ensure settings exist from wp_localize_script
    if (typeof rqChatbotSettings === 'undefined') {
        console.error('Raiseque Chatbot: Settings not found.');
        return;
    }

    const settings = rqChatbotSettings;
    const storageKey = 'rq_chatbot_history';

    // 2. Create and inject HTML elements into the page
    const chatbotHTML = `
        <!-- Floating Chat Icon -->
        <div id="rq-chatbot-launcher" class="rq-chatbot-launcher rq-pos-${settings.position}" aria-label="Open Chat">
            <!-- Bot Icon (Visible when closed) -->
            <svg class="rq-icon-bot" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="4" y="9" width="16" height="11" rx="2"></rect>
                <path d="M12 9V5"></path>
                <circle cx="12" cy="4" r="1.5" fill="currentColor"></circle>
                <circle cx="9" cy="14" r="1.5" fill="currentColor"></circle>
                <circle cx="15" cy="14" r="1.5" fill="currentColor"></circle>
                <path d="M12 17h.01"></path>
                <path d="M2 14h2"></path>
                <path d="M20 14h2"></path>
            </svg>
            <!-- Close Icon (Visible when active) -->
            <svg class="rq-icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>

        <!-- Chat Window -->
        <div id="rq-chatbot-window" class="rq-chatbot-window rq-pos-${settings.position}">
            <!-- Header -->
            <div class="rq-chatbot-header">
                <div class="rq-chatbot-header-info">
                    <div class="rq-chatbot-avatar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width: 22px; height: 22px; display: block;">
                            <rect x="4" y="9" width="16" height="11" rx="2"></rect>
                            <path d="M12 9V5"></path>
                            <circle cx="12" cy="4" r="1.2" fill="currentColor"></circle>
                            <circle cx="9" cy="14" r="1" fill="currentColor"></circle>
                            <circle cx="15" cy="14" r="1" fill="currentColor"></circle>
                            <path d="M12 17h.01"></path>
                            <path d="M2 14h2"></path>
                            <path d="M20 14h2"></path>
                        </svg>
                    </div>
                    <div class="rq-chatbot-details">
                        <h4 class="rq-chatbot-title">${settings.botTitle}</h4>
                        <span class="rq-chatbot-status">Online</span>
                    </div>
                </div>
                <button id="rq-chatbot-close" class="rq-chatbot-close" aria-label="Close Chat">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                    </svg>
                </button>
            </div>

            <!-- Message Area -->
            <div id="rq-chatbot-messages" class="rq-chatbot-messages"></div>

            <!-- Input Area -->
            <form id="rq-chatbot-form" class="rq-chatbot-input-area">
                <input type="text" id="rq-chatbot-input" class="rq-chatbot-input" placeholder="Type your message..." autocomplete="off" required />
                <button type="submit" id="rq-chatbot-send" class="rq-chatbot-send" aria-label="Send Message">
                    <svg viewBox="0 0 24 24">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </form>

            <!-- Branding -->
            <div class="rq-chatbot-branding">
                Powered by <a href="https://raiseque.com" target="_blank" rel="noopener">Raiseque</a>
            </div>
        </div>
    `;

    // Inject to body.
    const container = document.createElement('div');
    container.id = 'rq-chatbot-wrapper';
    container.innerHTML = chatbotHTML;
    document.body.appendChild(container);

    // Get elements.
    const launcher = document.getElementById('rq-chatbot-launcher');
    const chatWindow = document.getElementById('rq-chatbot-window');
    const closeBtn = document.getElementById('rq-chatbot-close');
    const messagesContainer = document.getElementById('rq-chatbot-messages');
    const form = document.getElementById('rq-chatbot-form');
    const input = document.getElementById('rq-chatbot-input');
    const sendBtn = document.getElementById('rq-chatbot-send');

    // 3. Conversation History state
    let history = [];

    // Load history from session storage if exists
    try {
        const stored = sessionStorage.getItem(storageKey);
        if (stored) {
            history = JSON.parse(stored);
        }
    } catch (e) {
        console.warn('Raiseque Chatbot: Failed to parse storage history.', e);
    }

    // Initialize UI messages
    initMessages();

    // 4. Event Listeners
    launcher.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', closeChat);
    form.addEventListener('submit', handleSendMessage);

    // 5. Functions
    function toggleChat() {
        launcher.classList.toggle('rq-active');
        chatWindow.classList.toggle('rq-active');
        if (chatWindow.classList.contains('rq-active')) {
            input.focus();
            scrollToBottom();
        }
    }

    function closeChat() {
        launcher.classList.remove('rq-active');
        chatWindow.classList.remove('rq-active');
    }

    function initMessages() {
        messagesContainer.innerHTML = '';
        
        // Add Welcome Message if no history.
        if (history.length === 0) {
            appendMessageBubble('bot', settings.welcomeMsg, false);
        } else {
            // Render loaded history.
            history.forEach(item => {
                appendMessageBubble(item.sender, item.text, false);
            });
        }
        scrollToBottom();
    }

    function appendMessageBubble(sender, text, animate = true) {
        const bubble = document.createElement('div');
        bubble.className = `rq-chat-msg rq-msg-${sender}`;
        
        // Parse basic markdown (bold, lists, links).
        bubble.innerHTML = formatMarkdown(text);
        
        if (!animate) {
            bubble.style.animation = 'none';
        }
        
        messagesContainer.appendChild(bubble);
        scrollToBottom();
    }

    function showTypingIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'rq-chatbot-typing';
        indicator.className = 'rq-chat-msg rq-msg-bot rq-typing-indicator';
        indicator.innerHTML = `
            <div class="rq-typing-dot"></div>
            <div class="rq-typing-dot"></div>
            <div class="rq-typing-dot"></div>
        `;
        messagesContainer.appendChild(indicator);
        scrollToBottom();
    }

    function removeTypingIndicator() {
        const indicator = document.getElementById('rq-chatbot-typing');
        if (indicator) {
            indicator.remove();
        }
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Safe basic markdown parsing.
    function formatMarkdown(text) {
        // Safe escape to prevent raw HTML XSS injections.
        let html = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // Match markdown links: [label](url)
        html = html.replace(/\[(.*?)\]\((.*?)\)/g, function(match, label, url) {
            return `<a href="${url}" target="_blank" rel="noopener">${label}</a>`;
        });

        // Match bold text: **text**
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // Parse lists (line-by-line helper)
        const lines = html.split('\n');
        let insideList = false;
        const processedLines = [];

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();

            if (line.startsWith('* ') || line.startsWith('- ')) {
                if (!insideList) {
                    processedLines.push('<ul>');
                    insideList = 'ul';
                }
                processedLines.push(`<li>${line.substring(2)}</li>`);
            } else if (/^\d+\.\s/.test(line)) {
                if (!insideList) {
                    processedLines.push('<ol>');
                    insideList = 'ol';
                }
                const content = line.replace(/^\d+\.\s/, '');
                processedLines.push(`<li>${content}</li>`);
            } else {
                if (insideList) {
                    processedLines.push(insideList === 'ul' ? '</ul>' : '</ol>');
                    insideList = false;
                }
                if (line !== '') {
                    processedLines.push(`<p>${line}</p>`);
                }
            }
        }

        if (insideList) {
            processedLines.push(insideList === 'ul' ? '</ul>' : '</ol>');
        }

        return processedLines.join('');
    }

    // 6. Asynchronous API Sending
    async function handleSendMessage(e) {
        e.preventDefault();
        
        const messageText = input.value.trim();
        if (!messageText) return;

        // Clear input.
        input.value = '';
        input.disabled = true;
        sendBtn.disabled = true;

        // Append user bubble.
        appendMessageBubble('user', messageText);
        
        // Add to local history list.
        history.push({ sender: 'user', text: messageText });
        saveHistory();

        // Show bot thinking state.
        showTypingIndicator();

        try {
            // Request backend proxy.
            const response = await fetch(settings.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': settings.nonce
                },
                body: JSON.stringify({
                    message: messageText,
                    history: history.slice(0, -1) // Send history excluding the current message.
                })
            });

            removeTypingIndicator();

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            const reply = data.reply || "I'm sorry, I couldn't understand that.";

            // Append bot response bubble.
            appendMessageBubble('bot', reply);

            // Add bot reply to history.
            history.push({ sender: 'bot', text: reply });
            saveHistory();

        } catch (error) {
            console.error('Raiseque Chatbot Error:', error);
            removeTypingIndicator();
            appendMessageBubble('bot', "Sorry, I am experiencing temporary issues. Please check your connection or contact the support team.");
        } finally {
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
        }
    }

    function saveHistory() {
        try {
            // Cap history to keep only last 10 messages to avoid payload size limit issues.
            if (history.length > 10) {
                history = history.slice(-10);
            }
            sessionStorage.setItem(storageKey, JSON.stringify(history));
        } catch (e) {
            console.warn('Raiseque Chatbot: Session writing blocked.', e);
        }
    }
});
