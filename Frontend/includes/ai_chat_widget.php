<!-- AI Chat Widget - Floating Button -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* AI Chat Widget Styles */
.ai-chat-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Floating Chat Button */
.ai-chat-fab {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.ai-chat-fab:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 30px rgba(34, 197, 94, 0.5);
}

.ai-chat-fab i {
    font-size: 28px;
    color: white;
    transition: transform 0.3s ease;
}

.ai-chat-fab.open i {
    transform: rotate(90deg);
}

/* Pulse animation for notification */
.ai-chat-fab::before {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: rgba(34, 197, 94, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

/* Chat Window */
.ai-chat-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 400px;
    height: 580px;
    background: #FFFFFF;
    border-radius: 20px;
    box-shadow: 0 10px 60px rgba(0, 0, 0, 0.15);
    display: none;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.08);
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.ai-chat-window.open {
    display: flex;
}

/* Header */
.ai-chat-header {
    background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.ai-chat-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ai-chat-avatar {
    width: 44px;
    height: 44px;
    background: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.ai-chat-title {
    color: white;
    font-weight: 600;
    font-size: 16px;
}

.ai-chat-status {
    color: rgba(255, 255, 255, 0.85);
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ai-status-dot {
    width: 8px;
    height: 8px;
    background: #86EFAC;
    border-radius: 50%;
    animation: blink 1.5s infinite;
    flex-shrink: 0;
}

#ai-model-badge {
    font-weight: 500;
    color: rgba(255, 255, 255, 0.95);
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.ai-chat-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    color: white;
    cursor: pointer;
    transition: background 0.2s;
}

.ai-chat-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Quick Actions */
.ai-chat-quick {
    padding: 12px 16px;
    display: flex;
    gap: 8px;
    overflow-x: auto;
    scrollbar-width: none;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}

.ai-chat-quick::-webkit-scrollbar {
    display: none;
}

.ai-quick-btn {
    flex-shrink: 0;
    padding: 8px 14px;
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    font-size: 13px;
    color: #334155;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.ai-quick-btn:hover {
    background: #22C55E;
    color: white;
    border-color: #22C55E;
}

.ai-quick-btn i {
    font-size: 14px;
}

/* Messages Area */
.ai-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: #FAFAFA;
}

.ai-message {
    max-width: 85%;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.ai-message.user {
    align-self: flex-end;
}

.ai-message.assistant {
    align-self: flex-start;
}

.ai-message-bubble {
    padding: 14px 18px;
    border-radius: 18px;
    font-size: 14px;
    line-height: 1.5;
    white-space: pre-line;
}

.ai-message.user .ai-message-bubble {
    background: #22C55E;
    color: white;
    border-bottom-right-radius: 4px;
}

.ai-message.assistant .ai-message-bubble {
    background: white;
    color: #1E293B;
    border: 1px solid #E2E8F0;
    border-bottom-left-radius: 4px;
}

/* Typing indicator */
.ai-typing {
    display: flex;
    gap: 4px;
    padding: 14px 18px;
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 18px;
    border-bottom-left-radius: 4px;
}

.ai-typing-dot {
    width: 8px;
    height: 8px;
    background: #94A3B8;
    border-radius: 50%;
    animation: typingBounce 1.4s infinite;
}

.ai-typing-dot:nth-child(2) { animation-delay: 0.2s; }
.ai-typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typingBounce {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-8px); }
}

/* Input Area */
.ai-chat-input {
    padding: 16px;
    background: white;
    border-top: 1px solid #E2E8F0;
    display: flex;
    gap: 10px;
    align-items: center;
}

.ai-chat-input input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #E2E8F0;
    border-radius: 24px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}

.ai-chat-input input:focus {
    border-color: #22C55E;
}

.ai-chat-input button {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.ai-voice-btn {
    background: #F1F5F9;
    color: #64748B;
}

.ai-voice-btn:hover {
    background: #E2E8F0;
    color: #22C55E;
}

.ai-voice-btn.recording {
    background: #FEE2E2;
    color: #EF4444;
    animation: pulseRecord 1s infinite;
}

@keyframes pulseRecord {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
}

.ai-send-btn {
    background: #22C55E;
    color: white;
}

.ai-send-btn:hover {
    background: #16A34A;
}

/* WhatsApp Float */
.ai-whatsapp-float {
    position: absolute;
    bottom: 100px;
    right: 0;
    display: none;
}

.ai-whatsapp-float.show {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ai-whatsapp-btn {
    width: 56px;
    height: 56px;
    background: #25D366;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
    box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4);
    transition: transform 0.2s;
}

.ai-whatsapp-btn:hover {
    transform: scale(1.1);
}

/* Language Toggle */
.ai-lang-toggle {
    position: absolute;
    top: 70px;
    right: 0;
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 8px;
    display: none;
    gap: 4px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.ai-lang-toggle.show {
    display: flex;
}

.ai-lang-btn {
    padding: 6px 12px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
}

.ai-lang-btn.active {
    background: #22C55E;
    color: white;
}

.ai-lang-btn:hover:not(.active) {
    background: #F1F5F9;
}

/* Metadata bar */
.ai-meta-bar {
    padding: 6px 16px;
    display: flex;
    gap: 16px;
    background: #F8FAFC;
    border-top: 1px solid #E2E8F0;
    font-size: 11px;
    color: #94A3B8;
}

.ai-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.ai-meta-item::before {
    content: '';
    width: 6px;
    height: 6px;
    background: #CBD5E1;
    border-radius: 50%;
}

/* Response time animation */
.ai-response-time {
    font-size: 11px;
    color: #94A3B8;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.ai-response-time::before {
    content: '⚡';
    font-size: 10px;
}

/* Input wrapper */
.ai-chat-input-wrap {
    padding: 12px 16px;
    background: white;
    border-top: 1px solid #E2E8F0;
}

.ai-chat-input {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #F1F5F9;
    border-radius: 24px;
    padding: 6px 6px 6px 16px;
}

/* Mode Toggle */
.ai-mode-toggle {
    display: flex;
    gap: 4px;
    margin-top: 8px;
    justify-content: center;
}

.ai-mode-btn {
    padding: 6px 14px;
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    background: white;
    color: #64748B;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 5px;
}

.ai-mode-btn:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
}

.ai-mode-btn.active {
    background: #1E293B;
    color: white;
    border-color: #1E293B;
}

.ai-mode-btn i {
    font-size: 11px;
}

/* Build mode locked indicator */
.ai-mode-btn.locked {
    opacity: 0.6;
    position: relative;
}

.ai-mode-btn.locked::after {
    content: '\f023';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 9px;
    margin-left: 2px;
}

/* Upgrade Modal */
.ai-upgrade-modal {
    display: none;
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    top: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 100;
    justify-content: center;
    align-items: center;
    border-radius: 20px;
}

.ai-upgrade-modal.show {
    display: flex;
}

.ai-upgrade-content {
    background: white;
    border-radius: 16px;
    padding: 28px;
    text-align: center;
    max-width: 300px;
    margin: 20px;
    animation: slideUp 0.3s ease;
}

.ai-upgrade-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}

.ai-upgrade-icon i {
    font-size: 24px;
    color: white;
}

.ai-upgrade-content h3 {
    margin: 0 0 8px;
    color: #1E293B;
    font-size: 18px;
}

.ai-upgrade-content p {
    margin: 0 0 8px;
    color: #64748B;
    font-size: 14px;
    line-height: 1.5;
}

.ai-upgrade-sub {
    font-size: 13px !important;
    color: #94A3B8 !important;
}

.ai-upgrade-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 16px;
}

.ai-upgrade-btn {
    padding: 10px 20px;
    background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    display: block;
}

.ai-upgrade-close {
    padding: 8px 16px;
    background: transparent;
    color: #94A3B8;
    border: none;
    font-size: 13px;
    cursor: pointer;
}

.ai-upgrade-close:hover {
    color: #64748B;
}

/* Mobile Responsive */
@media (max-width: 480px) {
    .ai-chat-window {
        width: 100%;
        height: 100%;
        position: fixed;
        bottom: 0;
        right: 0;
        border-radius: 0;
    }
}
</style>

<!-- Chat Widget HTML -->
<div class="ai-chat-widget">
    <!-- WhatsApp Float -->
    <div class="ai-whatsapp-float" id="aiWhatsappFloat">
        <a href="https://wa.me/254700000000?text=Habari!%20Ninataka%20msaada%20wa%20kilimo%20kutoka%20Wangari" 
           target="_blank" 
           class="ai-whatsapp-btn"
           title="Chat on WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>
    
    <!-- Language Toggle -->
    <div class="ai-lang-toggle" id="aiLangToggle">
        <button class="ai-lang-btn active" data-lang="en">EN</button>
        <button class="ai-lang-btn" data-lang="sw">SW</button>
    </div>
    
    <!-- Chat Window -->
    <div class="ai-chat-window" id="aiChatWindow">
        <!-- Header -->
        <div class="ai-chat-header">
            <div class="ai-chat-header-info">
                <div class="ai-chat-avatar">🌾</div>
                <div>
                    <div class="ai-chat-title">Wangari AI</div>
                    <div class="ai-chat-status">
                        <span class="ai-status-dot"></span>
                        <span id="ai-model-badge">Wangari - Ox Alpha</span>
                    </div>
                </div>
            </div>
            <button class="ai-chat-close" id="aiChatClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Quick Actions -->
        <div class="ai-chat-quick">
            <button class="ai-quick-btn" data-action="feeding">
                <i class="fas fa-utensils"></i> Feeding
            </button>
            <button class="ai-quick-btn" data-action="health">
                <i class="fas fa-heartbeat"></i> Health
            </button>
            <button class="ai-quick-btn" data-action="vaccine">
                <i class="fas fa-syringe"></i> Vaccination
            </button>
            <button class="ai-quick-btn" data-action="prices">
                <i class="fas fa-tag"></i> Prices
            </button>
            <button class="ai-quick-btn" data-action="weather">
                <i class="fas fa-cloud-sun"></i> Weather
            </button>
            <button class="ai-quick-btn" data-action="mpesa">
                <i class="fas fa-mobile-alt"></i> M-PESA
            </button>
        </div>
        
        <!-- Messages -->
        <div class="ai-chat-messages" id="aiChatMessages">
            <!-- Welcome message -->
            <div class="ai-message assistant">
                <div class="ai-message-bubble">
👋 Habari! I'm Wangari AI, your farm assistant.

I can help you with:
🐔 Feeding schedules
🏥 Health & vaccines
🏠 Housing tips
💰 Costs & profits
🌤️ Weather advice
📈 Market prices
📱 M-PESA

How can I help you today?
                </div>
            </div>
        </div>
        
        <!-- Input -->
        <div class="ai-chat-input-wrap">
            <div class="ai-chat-input">
                <button class="ai-chat-input-btn ai-voice-btn" id="aiVoiceBtn" title="Voice input">
                    <i class="fas fa-microphone"></i>
                </button>
                <input type="text" id="aiChatInput" placeholder="Ask Wangari anything...">
                <button class="ai-chat-input-btn ai-send-btn" id="aiSendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="ai-mode-toggle">
                <button class="ai-mode-btn active" data-mode="plan" id="aiModePlan">
                    <i class="fas fa-comment-dots"></i> Plan
                </button>
                <button class="ai-mode-btn" data-mode="build" id="aiModeBuild">
                    <i class="fas fa-hammer"></i> Build
                </button>
            </div>
        </div>
        
        <!-- Upgrade Modal -->
        <div class="ai-upgrade-modal" id="aiUpgradeModal">
            <div class="ai-upgrade-content">
                <div class="ai-upgrade-icon"><i class="fas fa-lock"></i></div>
                <h3>Upgrade to Plus</h3>
                <p>Build mode lets Wangari take action on your farm — create records, generate reports, and automate tasks.</p>
                <p class="ai-upgrade-sub">Available on <strong>Plus</strong> and <strong>Custom</strong> plans.</p>
                <div class="ai-upgrade-actions">
                    <a href="/Frontend/pages/pricing.php" class="ai-upgrade-btn">View Plans</a>
                    <button class="ai-upgrade-close" id="aiUpgradeClose">Maybe Later</button>
                </div>
            </div>
        </div>
        
        <!-- Metadata bar -->
        <div class="ai-meta-bar" id="aiMetaBar">
            <span class="ai-meta-item" id="aiResponseTime"></span>
            <span class="ai-meta-item" id="aiTokensUsed"></span>
        </div>
    </div>
    
    <!-- FAB Button -->
    <button class="ai-chat-fab" id="aiChatFab">
        <i class="fas fa-comments"></i>
    </button>
</div>

<script>
// AI Chat Widget Script
(function() {
    const fab = document.getElementById('aiChatFab');
    const chatWindow = document.getElementById('aiChatWindow');
    const closeBtn = document.getElementById('aiChatClose');
    const messagesContainer = document.getElementById('aiChatMessages');
    const chatInput = document.getElementById('aiChatInput');
    const sendBtn = document.getElementById('aiSendBtn');
    const voiceBtn = document.getElementById('aiVoiceBtn');
    const quickBtns = document.querySelectorAll('.ai-quick-btn');
    const langToggle = document.getElementById('aiLangToggle');
    const whatsappFloat = document.getElementById('aiWhatsappFloat');
    
    let isRecording = false;
    let recognition = null;
    let currentLang = 'en';
    let currentMode = 'plan'; // 'plan' or 'build'
    
    // Subscription status (set from PHP)
    const userSubStatus = '<?= $_SESSION["subscription_status"] ?? "trial" ?>';
    const userTrialEnd = '<?= $_SESSION["trial_end_date"] ?? "" ?>';
    const isTrialActive = userSubStatus === 'trial' && (userTrialEnd === '' || new Date(userTrialEnd) > new Date());
    const canUseBuild = isTrialActive || userSubStatus === 'plus' || userSubStatus === 'custom';
    
    // Initialize Speech Recognition
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = 'en-US';
        
        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            chatInput.value = transcript;
            sendMessage(transcript);
            stopRecording();
        };
        
        recognition.onerror = (event) => {
            console.error('Speech recognition error:', event.error);
            stopRecording();
        };
        
        recognition.onend = () => {
            stopRecording();
        };
    }
    
    // Toggle Chat Window
    fab.addEventListener('click', () => {
        chatWindow.classList.toggle('open');
        fab.classList.toggle('open');
        langToggle.classList.toggle('show');
        whatsappFloat.classList.toggle('show');
        
        if (chatWindow.classList.contains('open')) {
            chatInput.focus();
        }
    });
    
    closeBtn.addEventListener('click', () => {
        chatWindow.classList.remove('open');
        fab.classList.remove('open');
        langToggle.classList.remove('show');
        whatsappFloat.classList.remove('show');
    });
    
    // Language Toggle
    document.querySelectorAll('.ai-lang-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.ai-lang-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentLang = btn.dataset.lang;
            
            if (recognition) {
                recognition.lang = currentLang === 'sw' ? 'sw-KE' : 'en-US';
            }
            
            if (currentLang === 'sw') {
                addMessage('assistant', '🔗(change language to Swahili. Mimi niko tayari kukusaidia kwa Kiswahili! Niulize maswali yako.');
            }
        });
    });
    
    // Send Message
    function sendMessage(text) {
        if (!text.trim()) return;
        
        // Add user message
        addMessage('user', text);
        chatInput.value = '';
        
        // Show typing indicator
        showTyping();
        
        // Send to backend
        fetch('Backend/api/ai_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                message: text,
                lang: currentLang,
                mode: currentMode
            })
        })
        .then(response => response.json())
        .then(data => {
            hideTyping();
            addMessage('assistant', data.response || "I'm sorry, I couldn't process that. Please try again.", data.metadata);
        })
        .catch(error => {
            hideTyping();
            addMessage('assistant', "⚠️ Connection error. Please check your internet and try again.");
        });
    }
    
    // Add Message to Chat
    function addMessage(role, content, metadata) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `ai-message ${role}`;
        
        let metaHtml = '';
        if (role === 'assistant' && metadata) {
            const responseTime = metadata.response_time_ms ? `${metadata.response_time_ms}ms` : '';
            const tokens = metadata.tokens_used !== undefined ? `${metadata.tokens_used}/${metadata.tokens_limit}` : '';
            const model = metadata.model || '';
            
            if (responseTime || tokens) {
                metaHtml = `<div class="ai-response-time">
                    ${responseTime ? `<span>${responseTime}</span>` : ''}
                    ${tokens ? `<span>• ${tokens} tokens</span>` : ''}
                    ${model ? `<span>• ${model}</span>` : ''}
                </div>`;
            }
        }
        
        messageDiv.innerHTML = `<div class="ai-message-bubble">${content}</div>${metaHtml}`;
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Update model badge if metadata available
        if (metadata && metadata.model) {
            const badge = document.getElementById('ai-model-badge');
            if (badge) badge.textContent = `Wangari - ${metadata.model}`;
        }
    }
    
    // Show/Hide Typing
    function showTyping() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'ai-message assistant';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = `
            <div class="ai-typing">
                <div class="ai-typing-dot"></div>
                <div class="ai-typing-dot"></div>
                <div class="ai-typing-dot"></div>
            </div>
        `;
        messagesContainer.appendChild(typingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    function hideTyping() {
        const typing = document.getElementById('typingIndicator');
        if (typing) typing.remove();
    }
    
    // Quick Actions
    quickBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const action = btn.dataset.action;
            const messages = {
                'feeding': 'Tell me about feeding chickens',
                'health': 'What are common chicken diseases?',
                'vaccine': 'What is the vaccination schedule?',
                'prices': 'What are current market prices?',
                'weather': 'What is the weather forecast?',
                'mpesa': 'How do I use M-PESA?'
            };
            sendMessage(messages[action] || action);
        });
    });
    
    // Voice Input
    voiceBtn.addEventListener('click', () => {
        if (!recognition) {
            addMessage('assistant', '⚠️ Voice input is not supported in your browser. Please type your question.');
            return;
        }
        
        if (isRecording) {
            recognition.stop();
            stopRecording();
        } else {
            recognition.start();
            voiceBtn.classList.add('recording');
            voiceBtn.innerHTML = '<i class="fas fa-stop"></i>';
            isRecording = true;
            
            if (currentLang === 'sw') {
                recognition.lang = 'sw-KE';
            }
        }
    });
    
    function stopRecording() {
        voiceBtn.classList.remove('recording');
        voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        isRecording = false;
    }    // Mode Toggle
    const modeBtns = document.querySelectorAll('.ai-mode-btn');
    const upgradeModal = document.getElementById('aiUpgradeModal');
    const upgradeClose = document.getElementById('aiUpgradeClose');
    
    modeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            
            if (mode === 'build' && !canUseBuild) {
                // Show upgrade modal
                upgradeModal.classList.add('show');
                return;
            }
            
            currentMode = mode;
            modeBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Update placeholder
            if (mode === 'build') {
                chatInput.placeholder = 'Tell Wangari what to do...';
            } else {
                chatInput.placeholder = 'Ask Wangari anything...';
            }
        });
    });
    
    // Close upgrade modal
    if (upgradeClose) {
        upgradeClose.addEventListener('click', () => {
            upgradeModal.classList.remove('show');
        });
    }
    
    // Mark build button as locked if user can't use it
    if (!canUseBuild) {
        const buildBtn = document.getElementById('aiModeBuild');
        if (buildBtn) buildBtn.classList.add('locked');
    }
    
    // Send on Enter
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage(chatInput.value);
        }
    });
    
    sendBtn.addEventListener('click', () => {
        sendMessage(chatInput.value);
    });
})();</script>
