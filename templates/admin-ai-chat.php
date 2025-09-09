<?php
/**
 * AI Chat Page Template
 * 
 * @package WP_Tester
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get AI settings
$settings = get_option('wp_tester_settings', array());
$ai_model = $settings['ai_model'] ?? 'gpt-3.5-turbo';
$ai_api_key = $settings['ai_api_key'] ?? '';
?>

<div class="wp-tester-modern">
    <!-- Header -->
    <div class="wp-tester-header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo">
                    <img src="<?php echo WP_TESTER_ASSETS_URL; ?>images/wp-tester-logo.png" alt="WP Tester" style="height: 32px;">
                </div>
                <div class="header-info">
                    <h1 style="margin: 0; font-size: 1.5rem; font-weight: 600; color: #1e293b;">AI Chat Assistant</h1>
                    <p style="margin: 0; font-size: 0.875rem; color: #64748b;">Chat with AI to create custom test flows</p>
                </div>
            </div>
            <div class="header-actions">
                <div class="ai-model-selector" style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-size: 0.875rem; color: #64748b;">AI Model:</label>
                    <select id="ai-model-select" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; background: white; font-size: 0.875rem; min-width: 200px;">
                        <option value="">Loading models...</option>
                    </select>
                    <button id="refresh-models" class="modern-btn modern-btn-secondary modern-btn-small" style="padding: 0.5rem;">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="wp-tester-content" style="min-height: calc(100vh - 200px);">
        <div style="display: grid; grid-template-columns: 1fr 300px; gap: 1.5rem; min-height: 600px;">
            
            <!-- Chat Interface -->
            <div class="modern-card" style="display: flex; flex-direction: column; min-height: 600px;">
                <div class="card-header">
                    <h2 class="card-title">AI Assistant</h2>
                    <div class="status-badge info" id="connection-status">Ready</div>
                </div>
                
                <!-- Chat Messages -->
                <div id="chat-messages" style="flex: 1; overflow-y: auto; padding: 1rem; background: #f8fafc; border-radius: 8px; margin: 1rem; min-height: 400px; max-height: 500px;">
                    <div class="chat-message ai-message">
                        <div class="message-avatar">
                            <span class="dashicons dashicons-robot" style="color: #00265e;"></span>
                        </div>
                        <div class="message-content">
                            <div class="message-text">
                                <p>Hello! I'm your AI testing assistant. I can help you create custom test flows for your WordPress site.</p>
                                <p>Just describe what you want to test, and I'll help you create the appropriate test flow. For example:</p>
                                <ul>
                                    <li>"Create a test for user registration form"</li>
                                    <li>"Test the checkout process for an e-commerce site"</li>
                                    <li>"Create a test to verify admin login functionality"</li>
                                </ul>
                                <p>What would you like to test today?</p>
                            </div>
                            <div class="message-time"><?php echo date('H:i'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Chat Input -->
                <div style="padding: 1rem; border-top: 1px solid #e2e8f0;">
                    <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
                        <div style="flex: 1;">
                            <textarea id="chat-input" 
                                      placeholder="Describe what you want to test... (e.g., 'Create a test for user login with validation')" 
                                      style="width: 100%; min-height: 60px; max-height: 120px; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; resize: vertical; font-family: inherit; font-size: 0.875rem;"
                                      rows="2"></textarea>
                        </div>
                        <button id="send-message" class="modern-btn modern-btn-primary" style="height: 60px; padding: 0 1.5rem;">
                            <span class="dashicons dashicons-paperclip" style="margin-right: 0.5rem;"></span>
                            Send
                        </button>
                    </div>
                    <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #64748b;">
                        Press Ctrl+Enter to send message
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div style="display: flex; flex-direction: column; gap: 1rem; max-height: 600px; overflow-y: auto;">
                
                <!-- Quick Actions -->
                <div class="modern-card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div style="padding: 1rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <button class="modern-btn modern-btn-secondary modern-btn-small" id="clear-chat">
                                <span class="dashicons dashicons-trash"></span>
                                Clear Chat
                            </button>
                            <button class="modern-btn modern-btn-secondary modern-btn-small" id="export-chat">
                                <span class="dashicons dashicons-download"></span>
                                Export Chat
                            </button>
                            <button class="modern-btn modern-btn-secondary modern-btn-small" id="save-conversation">
                                <span class="dashicons dashicons-saved"></span>
                                Save Conversation
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- AI Settings -->
                <div class="modern-card">
                    <div class="card-header">
                        <h3 class="card-title">AI Settings</h3>
                    </div>
                    <div style="padding: 1rem;">
                        <!-- API Key Section (Hidden by default for free models) -->
                        <div id="api-key-section" style="margin-bottom: 1rem; display: none;">
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                                API Key <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="password" id="ai-api-key" 
                                   value="<?php echo esc_attr($ai_api_key); ?>"
                                   placeholder="Enter your API key"
                                   style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.875rem;">
                            <p id="api-key-help" style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: #64748b;">
                                API key is required for paid models.
                            </p>
                        </div>
                        
                        <!-- Model Description -->
                        <div id="model-description" style="margin-bottom: 1rem; padding: 0.75rem; background: #f8fafc; border-radius: 6px; border-left: 3px solid #00265e;">
                            <p style="margin: 0; font-size: 0.8125rem; color: #64748b;">
                                Choose your AI model. Free models work without API keys, paid models require API keys.
                            </p>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                                Temperature
                            </label>
                            <input type="range" id="ai-temperature" min="0" max="1" step="0.1" value="0.7"
                                   style="width: 100%;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                                <span>Focused</span>
                                <span id="temperature-value">0.7</span>
                                <span>Creative</span>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                                Max Tokens
                            </label>
                            <input type="number" id="ai-max-tokens" min="100" max="4000" value="2000"
                                   style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.875rem;">
                        </div>
                    </div>
                </div>
                
                <!-- Recent Flows -->
                <div class="modern-card">
                    <div class="card-header">
                        <h3 class="card-title">Recent AI Flows</h3>
                    </div>
                    <div style="padding: 1rem;">
                        <div id="recent-ai-flows">
                            <div class="empty-state" style="padding: 1rem; text-align: center;">
                                <span class="dashicons dashicons-lightbulb" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 0.5rem;"></span>
                                <p style="margin: 0; font-size: 0.875rem; color: #64748b;">No AI-generated flows yet</p>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Footer spacing -->
        <div style="height: 2rem;"></div>
    </div>
</div>

<style>
.chat-message {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
    animation: fadeIn 0.3s ease-in;
}

.chat-message.user-message {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    flex-shrink: 0;
}

.user-message .message-avatar {
    background: #00265e;
    color: white;
}

.message-content {
    flex: 1;
    max-width: 70%;
}

.user-message .message-content {
    text-align: right;
}

.message-text {
    background: white;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.user-message .message-text {
    background: #00265e;
    color: white;
    border-color: #00265e;
}

.message-text p {
    margin: 0 0 0.5rem 0;
    line-height: 1.5;
}

.message-text p:last-child {
    margin-bottom: 0;
}

.message-text ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.message-text li {
    margin-bottom: 0.25rem;
}

.message-time {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.25rem;
}

.user-message .message-time {
    text-align: right;
}

.typing-indicator {
    display: flex;
    gap: 0.25rem;
    align-items: center;
    padding: 0.75rem 1rem;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    max-width: 70px;
}

.typing-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #64748b;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
    0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
    40% { transform: scale(1); opacity: 1; }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.ai-model-selector {
    display: flex;
    align-items: center;
}

#chat-messages::-webkit-scrollbar {
    width: 6px;
}

#chat-messages::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

#chat-messages::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

#chat-messages::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Fix layout issues */
.wp-tester-content {
    padding-bottom: 2rem;
}

.modern-card {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

/* Ensure proper spacing */
.ai-model-selector select {
    min-width: 150px;
}

/* Fix textarea resizing */
#chat-input {
    font-family: inherit;
    line-height: 1.5;
}

/* Improve button styling */
#send-message {
    white-space: nowrap;
    min-width: 80px;
}
</style>

<script>
jQuery(document).ready(function($) {
    let chatHistory = [];
    let isTyping = false;
    
    // Initialize
    updateTemperatureDisplay();
    loadRecentFlows();
    loadAvailableModels();
    
    // Send message
    $('#send-message').on('click', sendMessage);
    $('#chat-input').on('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // Auto-resize textarea
    $('#chat-input').on('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    
    // Temperature slider
    $('#ai-temperature').on('input', updateTemperatureDisplay);
    
    // Quick actions
    $('#clear-chat').on('click', clearChat);
    $('#export-chat').on('click', exportChat);
    $('#save-conversation').on('click', saveConversation);
    
    // Model selection change handler
    $('#ai-model-select').on('change', function() {
        updateApiKeySection();
    });
    
    // Refresh models button
    $('#refresh-models').on('click', function() {
        loadAvailableModels();
    });
    
    // AI Model Management
    let availableModels = {
        free_models: {},
        paid_models: {},
        models_by_provider: {}
    };
    
    // Load available models
    function loadAvailableModels() {
        const modelSelect = $('#ai-model-select');
        const refreshBtn = $('#refresh-models');
        
        // Show loading state
        modelSelect.html('<option value="">Loading models...</option>');
        refreshBtn.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            timeout: 10000, // 10 second timeout
            data: {
                action: 'wp_tester_get_available_ai_models',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                refreshBtn.prop('disabled', false);
                if (response.success) {
                    availableModels = response.data;
                    populateModelDropdown();
                } else {
                    console.error('Failed to load models:', response.data);
                    addFallbackModels();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading AI models:', error);
                console.error('XHR:', xhr);
                console.error('Status:', status);
                refreshBtn.prop('disabled', false);
                // Fallback: add some basic models
                addFallbackModels();
            },
            timeout: function() {
                console.error('Timeout loading AI models');
                refreshBtn.prop('disabled', false);
                addFallbackModels();
            }
        });
    }
    
    function addFallbackModels() {
        const modelSelect = $('#ai-model-select');
        modelSelect.empty();
        
        // Add some basic fallback models
        const fallbackModels = [
            { id: 'gpt-3.5-turbo', name: 'GPT-3.5 Turbo', provider: 'OpenAI', free: true },
            { id: 'gpt-4', name: 'GPT-4', provider: 'OpenAI', free: false },
            { id: 'gpt-4-turbo', name: 'GPT-4 Turbo', provider: 'OpenAI', free: false },
            { id: 'claude-3-sonnet', name: 'Claude 3 Sonnet', provider: 'Anthropic', free: false },
            { id: 'claude-3-opus', name: 'Claude 3 Opus', provider: 'Anthropic', free: false }
        ];
        
        fallbackModels.forEach(model => {
            const option = $('<option></option>')
                .attr('value', model.id)
                .attr('data-provider', model.provider)
                .attr('data-free', model.free.toString())
                .text(`${model.name} (${model.provider}) - ${model.free ? 'Free' : 'Paid'}`);
            modelSelect.append(option);
        });
        
        // Set default to first free model
        const firstFreeModel = modelSelect.find('option[data-free="true"]:first');
        if (firstFreeModel.length > 0) {
            firstFreeModel.prop('selected', true);
            updateApiKeySection();
        }
    }
    
    function populateModelDropdown() {
        const modelSelect = $('#ai-model-select');
        const description = $('#model-description p');
        
        // Clear all existing options
        modelSelect.empty();
        
        // Add free models first
        if (availableModels.free_models && Object.keys(availableModels.free_models).length > 0) {
            Object.keys(availableModels.free_models).forEach(modelId => {
                const model = availableModels.free_models[modelId];
                const option = $('<option></option>')
                    .attr('value', modelId)
                    .attr('data-provider', model.provider)
                    .attr('data-free', 'true')
                    .text(`${model.name} (${model.provider}) - Free`);
                modelSelect.append(option);
            });
        }
        
        // Add paid models
        if (availableModels.paid_models && Object.keys(availableModels.paid_models).length > 0) {
            Object.keys(availableModels.paid_models).forEach(modelId => {
                const model = availableModels.paid_models[modelId];
                const option = $('<option></option>')
                    .attr('value', modelId)
                    .attr('data-provider', model.provider)
                    .attr('data-free', 'false')
                    .text(`${model.name} (${model.provider}) - Paid`);
                modelSelect.append(option);
            });
        }
        
        // Set default to first free model
        const firstFreeModel = modelSelect.find('option[data-free="true"]:first');
        if (firstFreeModel.length > 0) {
            firstFreeModel.prop('selected', true);
            updateApiKeySection();
        }
        
        // Set current model if available
        const currentModel = '<?php echo esc_js($ai_model); ?>';
        if (currentModel && modelSelect.find(`option[value="${currentModel}"]`).length > 0) {
            modelSelect.val(currentModel);
            updateApiKeySection();
        }
    }
    
    function updateApiKeySection() {
        const selectedOption = $('#ai-model-select option:selected');
        const isFree = selectedOption.attr('data-free') === 'true';
        const provider = selectedOption.attr('data-provider');
        const apiKeySection = $('#api-key-section');
        const apiKeyHelp = $('#api-key-help');
        
        if (isFree) {
            apiKeySection.hide();
            apiKeyHelp.text('This model works without an API key.');
        } else {
            apiKeySection.show();
            
            // Update help text based on provider
            let helpText = 'Get your API key from the provider.';
            let apiUrl = '#';
            
            switch (provider) {
                case 'openai':
                    helpText = 'Get your OpenAI API key';
                    apiUrl = 'https://platform.openai.com/api-keys';
                    break;
                case 'anthropic':
                    helpText = 'Get your Anthropic API key';
                    apiUrl = 'https://console.anthropic.com/';
                    break;
                case 'google':
                    helpText = 'Get your Google AI API key';
                    apiUrl = 'https://makersuite.google.com/app/apikey';
                    break;
            }
            
            apiKeyHelp.html(`<a href="${apiUrl}" target="_blank" style="color: #00265e; text-decoration: none;">${helpText}</a>`);
        }
    }
    
    function sendMessage() {
        const message = $('#chat-input').val().trim();
        if (!message || isTyping) return;
        
        // Validate model and API key
        const selectedModel = $('#ai-model-select').val();
        const apiKey = $('#ai-api-key').val();
        
        if (!selectedModel) {
            addMessage('ai', 'Please select an AI model first.');
            return;
        }
        
        const selectedOption = $('#ai-model-select option:selected');
        const isFree = selectedOption.attr('data-free') === 'true';
        
        // For paid models, check if API key is provided
        if (!isFree && !apiKey.trim()) {
            addMessage('ai', 'API key is required for paid models. Please enter your API key in the settings.');
            return;
        }
        
        // Add user message
        addMessage('user', message);
        $('#chat-input').val('').css('height', 'auto');
        
        // Show typing indicator
        showTypingIndicator();
        
        // Send to AI
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_ai_chat',
                message: message,
                model: selectedModel,
                api_key: apiKey,
                temperature: $('#ai-temperature').val(),
                max_tokens: $('#ai-max-tokens').val(),
                chat_history: chatHistory,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                hideTypingIndicator();
                
                if (response.success) {
                    addMessage('ai', response.data.message);
                    
                    // Check if AI wants to create a flow
                    if (response.data.create_flow) {
                        showFlowCreationDialog(response.data.flow_data);
                    }
                } else {
                    addMessage('ai', 'Sorry, I encountered an error: ' + response.data);
                }
            },
            error: function() {
                hideTypingIndicator();
                addMessage('ai', 'Sorry, I\'m having trouble connecting. Please check your API key and try again.');
            }
        });
    }
    
    function addMessage(type, content) {
        const timestamp = new Date().toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: false 
        });
        
        const messageHtml = `
            <div class="chat-message ${type}-message">
                <div class="message-avatar">
                    <span class="dashicons dashicons-${type === 'user' ? 'admin-users' : 'robot'}"></span>
                </div>
                <div class="message-content">
                    <div class="message-text">${formatMessage(content)}</div>
                    <div class="message-time">${timestamp}</div>
                </div>
            </div>
        `;
        
        $('#chat-messages').append(messageHtml);
        scrollToBottom();
        
        // Add to chat history
        chatHistory.push({ type: type, content: content, timestamp: timestamp });
    }
    
    function formatMessage(content) {
        // Convert line breaks to HTML
        content = content.replace(/\n/g, '<br>');
        
        // Format code blocks
        content = content.replace(/```([\s\S]*?)```/g, '<pre style="background: #f1f5f9; padding: 0.75rem; border-radius: 6px; margin: 0.5rem 0; overflow-x: auto;"><code>$1</code></pre>');
        
        // Format inline code
        content = content.replace(/`([^`]+)`/g, '<code style="background: #f1f5f9; padding: 0.125rem 0.25rem; border-radius: 3px; font-size: 0.875rem;">$1</code>');
        
        return content;
    }
    
    function showTypingIndicator() {
        isTyping = true;
        const typingHtml = `
            <div class="chat-message ai-message" id="typing-indicator">
                <div class="message-avatar">
                    <span class="dashicons dashicons-robot" style="color: #00265e;"></span>
                </div>
                <div class="message-content">
                    <div class="typing-indicator">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            </div>
        `;
        $('#chat-messages').append(typingHtml);
        scrollToBottom();
    }
    
    function hideTypingIndicator() {
        isTyping = false;
        $('#typing-indicator').remove();
    }
    
    function scrollToBottom() {
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    function updateTemperatureDisplay() {
        $('#temperature-value').text($('#ai-temperature').val());
    }
    
    function clearChat() {
        if (confirm('Are you sure you want to clear the chat history?')) {
            $('#chat-messages').html(`
                <div class="chat-message ai-message">
                    <div class="message-avatar">
                        <span class="dashicons dashicons-robot" style="color: #00265e;"></span>
                    </div>
                    <div class="message-content">
                        <div class="message-text">
                            <p>Chat cleared! How can I help you create test flows today?</p>
                        </div>
                        <div class="message-time">${new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false })}</div>
                    </div>
                </div>
            `);
            chatHistory = [];
        }
    }
    
    function exportChat() {
        const chatData = {
            timestamp: new Date().toISOString(),
            messages: chatHistory
        };
        
        const blob = new Blob([JSON.stringify(chatData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `wp-tester-chat-${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        URL.revokeObjectURL(url);
    }
    
    function saveConversation() {
        // Save conversation to WordPress
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_save_conversation',
                chat_history: chatHistory
            },
            success: function(response) {
                if (response.success) {
                    alert('Conversation saved successfully!');
                } else {
                    alert('Failed to save conversation.');
                }
            }
        });
    }
    
    function showFlowCreationDialog(flowData) {
        // Show modal for flow creation
        const modalHtml = `
            <div id="flow-creation-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
                    <h3 style="margin: 0 0 1rem 0; color: #1e293b;">Create Flow from AI Suggestion</h3>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Flow Name:</label>
                        <input type="text" id="flow-name" value="${flowData.name || ''}" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Flow Description:</label>
                        <textarea id="flow-description" style="width: 100%; height: 100px; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; resize: vertical;">${flowData.description || ''}</textarea>
                    </div>
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                        <button id="cancel-flow" class="modern-btn modern-btn-secondary">Cancel</button>
                        <button id="create-flow" class="modern-btn modern-btn-primary">Create Flow</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        $('#cancel-flow, #flow-creation-modal').on('click', function(e) {
            if (e.target === this) {
                $('#flow-creation-modal').remove();
            }
        });
        
        $('#create-flow').on('click', function() {
            const flowName = $('#flow-name').val();
            const flowDescription = $('#flow-description').val();
            
            if (!flowName.trim()) {
                alert('Please enter a flow name.');
                return;
            }
            
            // Create the flow
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_tester_create_ai_flow',
                    flow_name: flowName,
                    flow_description: flowDescription,
                    flow_data: flowData
                },
                success: function(response) {
                    if (response.success) {
                        alert('Flow created successfully!');
                        $('#flow-creation-modal').remove();
                        loadRecentFlows();
                    } else {
                        alert('Failed to create flow: ' + response.data);
                    }
                }
            });
        });
    }
    
    function loadRecentFlows() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_get_ai_flows'
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let flowsHtml = '';
                    response.data.forEach(function(flow) {
                        flowsHtml += `
                            <div style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 0.5rem;">
                                <h4 style="margin: 0 0 0.25rem 0; font-size: 0.875rem; color: #1e293b;">${flow.name}</h4>
                                <p style="margin: 0; font-size: 0.75rem; color: #64748b;">${flow.description}</p>
                                <div style="margin-top: 0.5rem; display: flex; gap: 0.25rem;">
                                    <button class="modern-btn modern-btn-secondary modern-btn-small" onclick="window.open('${flow.edit_url}', '_blank')">Edit</button>
                                    <button class="modern-btn modern-btn-primary modern-btn-small" onclick="window.open('${flow.test_url}', '_blank')">Test</button>
                                </div>
                            </div>
                        `;
                    });
                    $('#recent-ai-flows').html(flowsHtml);
                }
            }
        });
    }
});
</script>
