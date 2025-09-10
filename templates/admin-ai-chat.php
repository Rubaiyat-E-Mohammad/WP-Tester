<?php
/**
 * AI Chat Assistant Template
 * 
 * @package WP_Tester
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get AI settings from AI flow generator
$ai_model = get_option('wp_tester_ai_model', 'fallback-generator');
$ai_api_key = get_option('wp_tester_ai_api_key', '');
$ai_api_provider = get_option('wp_tester_ai_api_provider', 'openai');
?>

<div class="wp-tester-modern">
    <!-- Header -->
    <div class="wp-tester-header">
        <div class="header-content">
            <div class="header-info">
                <h1 style="margin: 0; font-size: 1.5rem; font-weight: 600; color: #1e293b;">AI Chat Assistant</h1>
                <p style="margin: 0; font-size: 0.875rem; color: #64748b;">Chat with AI to create custom test flows</p>
            </div>
            <div class="header-actions">
                <div class="ai-model-display" style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-size: 0.875rem; color: #64748b;">AI Model:</label>
                    <span id="current-ai-model" style="padding: 0.5rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.875rem; color: #00265e; font-weight: 500;">
                        <?php echo esc_html($ai_model); ?>
                    </span>
                    <button type="button" onclick="window.location.href='<?php echo admin_url('admin.php?page=wp-tester-ai-generator'); ?>'" class="modern-btn modern-btn-secondary modern-btn-small">
                        <span class="dashicons dashicons-admin-settings"></span>
                        Change Model
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="wp-tester-content" style="min-height: calc(100vh - 200px);">
        <div style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem; height: 100%;">
            <!-- Chat Area -->
            <div class="modern-card" style="display: flex; flex-direction: column; height: 100%;">
                <div class="card-header">
                    <h3 class="card-title">AI Chat</h3>
                </div>
                <div style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
                    <!-- Chat Messages -->
                    <div id="chat-messages" style="flex: 1; padding: 1rem; overflow-y: auto; min-height: 400px; max-height: 600px; border-bottom: 1px solid #e2e8f0;">
                        <div class="chat-message ai-message" style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 3px solid #00265e;">
                            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                <span class="dashicons dashicons-robot" style="color: #00265e; margin-right: 0.5rem;"></span>
                                <strong style="color: #00265e;">AI Assistant</strong>
                            </div>
                            <p style="margin: 0; color: #374151;">Hello! I'm your AI assistant. I can help you create custom test flows for your website. What functionality would you like to test today?</p>
                        </div>
                    </div>
                    
                    <!-- Chat Input -->
                    <div style="padding: 1rem; border-top: 1px solid #e2e8f0;">
                        <div style="display: flex; gap: 0.5rem;">
                            <textarea id="chat-input" placeholder="Type your message here... (Ctrl+Enter to send)" 
                                style="flex: 1; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; resize: none; min-height: 60px; max-height: 120px; font-family: inherit;"></textarea>
                            <button id="send-message" class="modern-btn modern-btn-primary" style="padding: 0.75rem 1.5rem; align-self: flex-end;">
                                <span class="dashicons dashicons-paperclip"></span>
                                Send
                            </button>
                        </div>
                    </div>
                    
                    <!-- AI Generated Flows -->
                    <div style="padding: 1rem; border-top: 1px solid #e2e8f0; background: #f8fafc;">
                        <h4 style="margin: 0 0 1rem 0; font-size: 0.875rem; color: #374151; font-weight: 600; display: flex; align-items: center;">
                            <span class="dashicons dashicons-admin-generic" style="color: #00265e; margin-right: 0.5rem;"></span>
                            AI Generated Flows
                        </h4>
                        <div id="ai-generated-flows" style="max-height: 200px; overflow-y: auto;">
                            <p style="margin: 0; font-size: 0.875rem; color: #64748b; text-align: center; padding: 1rem;">
                                No flows generated yet. Start chatting with AI to create flows!
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <!-- AI Settings -->
                <div class="modern-card">
                    <div class="card-header">
                        <h3 class="card-title">AI Settings</h3>
                    </div>
                    <div style="padding: 1rem;">
                        <!-- API Key Info -->
                        <div style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: #374151; font-weight: 600;">API Configuration</h4>
                            <p style="margin: 0; font-size: 0.75rem; color: #64748b;">
                                Using API key and model from <a href="<?php echo admin_url('admin.php?page=wp-tester-ai-generator'); ?>" style="color: #00265e; text-decoration: none;">AI Flow Generator settings</a>.
                            </p>
                            <?php if (!empty($ai_api_key)): ?>
                                <p style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: #059669;">
                                    <span class="dashicons dashicons-yes-alt" style="font-size: 12px; vertical-align: middle;"></span>
                                    API key configured
                                </p>
                            <?php else: ?>
                                <p style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: #dc2626;">
                                    <span class="dashicons dashicons-warning" style="font-size: 12px; vertical-align: middle;"></span>
                                    No API key configured
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Model Description -->
                        <div id="model-description" style="margin-bottom: 1rem; padding: 0.75rem; background: #f8fafc; border-radius: 6px; border-left: 3px solid #00265e;">
                            <p style="margin: 0; font-size: 0.8125rem; color: #64748b;">
                                Currently using: <strong><?php echo esc_html($ai_model); ?></strong><br>
                                To change the AI model, go to <a href="<?php echo admin_url('admin.php?page=wp-tester-ai-generator'); ?>" style="color: #00265e; text-decoration: none;">AI Flow Generator settings</a>.
                            </p>
                        </div>
                        
                        <!-- Temperature -->
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                                Temperature: <span id="temperature-value">0</span>
                            </label>
                            <input type="range" id="ai-temperature" min="0" max="1" step="0.1" value="0"
                                style="width: 100%; margin-bottom: 0.5rem;">
                            <p style="margin: 0; font-size: 0.75rem; color: #64748b;">
                                Controls randomness. Lower = more focused, Higher = more creative.
                            </p>
                        </div>
                        
                        <!-- Max Tokens -->
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                                Max Tokens
                            </label>
                            <input type="number" id="ai-max-tokens" min="100" value=""
                                placeholder="No limit" style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.875rem;">
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: #64748b;">
                                Maximum length of AI response. Leave empty for no limit.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="modern-card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div style="padding: 1rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <button id="clear-chat" class="modern-btn modern-btn-secondary" style="justify-content: flex-start;">
                                <span class="dashicons dashicons-trash"></span>
                                Clear Chat
                            </button>
                            <button id="export-chat" class="modern-btn modern-btn-secondary" style="justify-content: flex-start;">
                                <span class="dashicons dashicons-download"></span>
                                Export Chat
                            </button>
                            <button id="save-conversation" class="modern-btn modern-btn-secondary" style="justify-content: flex-start;">
                                <span class="dashicons dashicons-saved"></span>
                                Save Conversation
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.chat-message {
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 8px;
    animation: fadeInUp 0.3s ease-out;
}

.user-message {
    background: #00265e;
    color: white;
    margin-left: 2rem;
}

.ai-message {
    background: #f8fafc;
    border-left: 3px solid #00265e;
    margin-right: 2rem;
}

.typing-indicator {
    display: flex;
    align-items: center;
    padding: 1rem;
    color: #64748b;
    font-style: italic;
}

.typing-dots {
    display: inline-flex;
    gap: 2px;
    margin-left: 0.5rem;
}

.typing-dots span {
    width: 4px;
    height: 4px;
    background: #64748b;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.ai-model-display {
    display: flex;
    align-items: center;
}

/* Ensure proper spacing */
.ai-model-display span {
    min-width: 150px;
}

/* Modern button styles */
.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.modern-btn-primary {
    background: #00265e;
    color: white;
}

.modern-btn-primary:hover {
    background: #001a3d;
}

.modern-btn-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.modern-btn-secondary:hover {
    background: #e2e8f0;
}

.modern-btn-small {
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
}

/* Ensure proper spacing */
.wp-tester-content {
    padding: 2rem;
}

.modern-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.card-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}

#chat-input {
    font-family: inherit;
    line-height: 1.5;
}

#send-message {
    white-space: nowrap;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Define ajaxurl for AJAX calls
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    // Debug: Log plugin loading
    console.log('WP Tester AI Chat loaded');
    console.log('AJAX URL:', ajaxurl);
    console.log('WordPress AJAX URL should be:', '<?php echo admin_url('admin-ajax.php'); ?>');
    
    let chatHistory = [];
    let isTyping = false;
    
    // Initialize
    updateTemperatureDisplay();
    loadAIGeneratedFlows();
    
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
    
    // AI Model Management - Use model from AI flow generator settings
    const currentAiModel = '<?php echo esc_js($ai_model); ?>';
    const currentApiKey = '<?php echo esc_js($ai_api_key); ?>';
    const currentApiProvider = '<?php echo esc_js($ai_api_provider); ?>';
    
    function sendMessage() {
        const message = $('#chat-input').val().trim();
        if (!message || isTyping) return;
        
        // Use model from AI flow generator settings
        const selectedModel = '<?php echo esc_js($ai_model); ?>';
        const apiKey = '<?php echo esc_js($ai_api_key); ?>';
        
        if (!selectedModel) {
            addMessage('ai', 'No AI model configured. Please configure your AI model in the AI Flow Generator settings.');
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
                max_tokens: $('#ai-max-tokens').val() || null,
                chat_history: chatHistory,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                hideTypingIndicator();
                
                if (response.success) {
                    addMessage('ai', response.data.message);
                    
                    // Check if AI wants to create a flow
                    if (response.data.create_flow) {
                        createFlowFromAI(response.data.flow_data);
                    }
                } else {
                    addMessage('ai', 'Sorry, I encountered an error: ' + response.data);
                }
            },
            error: function() {
                hideTypingIndicator();
                addMessage('ai', 'Sorry, I encountered a network error. Please try again.');
            }
        });
    }
    
    function addMessage(type, content) {
        const messagesContainer = $('#chat-messages');
        const messageClass = type === 'user' ? 'user-message' : 'ai-message';
        const icon = type === 'user' ? 'dashicons-admin-users' : 'dashicons-robot';
        const name = type === 'user' ? 'You' : 'AI Assistant';
        const color = type === 'user' ? 'white' : '#00265e';
        
        const messageHtml = `
            <div class="chat-message ${messageClass}">
                <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                    <span class="dashicons ${icon}" style="color: ${color}; margin-right: 0.5rem;"></span>
                    <strong style="color: ${color};">${name}</strong>
                </div>
                <div style="color: ${type === 'user' ? 'white' : '#374151'};">
                    ${content.replace(/\n/g, '<br>')}
                </div>
            </div>
        `;
        
        messagesContainer.append(messageHtml);
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        
        // Add to chat history
        chatHistory.push({ type: type, content: content });
    }
    
    function showTypingIndicator() {
        isTyping = true;
        const messagesContainer = $('#chat-messages');
        const typingHtml = `
            <div class="typing-indicator">
                <span class="dashicons dashicons-robot" style="color: #00265e; margin-right: 0.5rem;"></span>
                <span>AI is typing</span>
                <div class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        messagesContainer.append(typingHtml);
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }
    
    function hideTypingIndicator() {
        isTyping = false;
        $('.typing-indicator').remove();
    }
    
    function updateTemperatureDisplay() {
        const value = $('#ai-temperature').val();
        $('#temperature-value').text(value);
    }
    
    function clearChat() {
        if (confirm('Are you sure you want to clear the chat history?')) {
            $('#chat-messages').html(`
                <div class="chat-message ai-message" style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 3px solid #00265e;">
                    <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                        <span class="dashicons dashicons-robot" style="color: #00265e; margin-right: 0.5rem;"></span>
                        <strong style="color: #00265e;">AI Assistant</strong>
                    </div>
                    <p style="margin: 0; color: #374151;">Hello! I'm your AI assistant. I can help you create custom test flows for your website. What functionality would you like to test today?</p>
                </div>
            `);
            chatHistory = [];
        }
    }
    
    function exportChat() {
        if (chatHistory.length === 0) {
            alert('No chat history to export.');
            return;
        }
        
        const exportData = {
            timestamp: new Date().toISOString(),
            model: currentAiModel,
            chat_history: chatHistory
        };
        
        const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `wp-tester-chat-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    function saveConversation() {
        if (chatHistory.length === 0) {
            alert('No conversation to save.');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_save_conversation',
                chat_history: chatHistory,
                model: currentAiModel,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Conversation saved successfully!');
                } else {
                    alert('Failed to save conversation: ' + response.data);
                }
            },
            error: function() {
                alert('Network error while saving conversation.');
            }
        });
    }
    
    function loadAIGeneratedFlows() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_get_ai_flows',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    const flowsHtml = response.data.slice(0, 5).map(flow => `
                        <div style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 0.5rem; background: white;">
                            <div style="display: flex; justify-content: between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: #00265e; font-size: 0.875rem; margin-bottom: 0.25rem;">${flow.name}</div>
                                    <div style="color: #64748b; font-size: 0.75rem; margin-bottom: 0.5rem;">${flow.description || 'AI Generated Flow'}</div>
                                    <div style="color: #64748b; font-size: 0.75rem;">Created: ${new Date(flow.created_at).toLocaleDateString()}</div>
                                </div>
                                <div style="display: flex; gap: 0.25rem; margin-left: 0.5rem;">
                                    <a href="${flow.edit_url}" class="modern-btn modern-btn-secondary modern-btn-small" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <span class="dashicons dashicons-edit" style="font-size: 12px;"></span>
                                        Edit
                                    </a>
                                    <a href="${flow.test_url}" class="modern-btn modern-btn-primary modern-btn-small" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <span class="dashicons dashicons-play" style="font-size: 12px;"></span>
                                        Test
                                    </a>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    
                    $('#ai-generated-flows').html(flowsHtml);
                } else {
                    $('#ai-generated-flows').html(`
                        <p style="margin: 0; font-size: 0.875rem; color: #64748b; text-align: center; padding: 1rem;">
                            No flows generated yet. Start chatting with AI to create flows!
                        </p>
                    `);
                }
            },
            error: function() {
                $('#ai-generated-flows').html(`
                    <p style="margin: 0; font-size: 0.875rem; color: #dc2626; text-align: center; padding: 1rem;">
                        Error loading AI generated flows.
                    </p>
                `);
            }
        });
    }
    
    function createFlowFromAI(flowData) {
        if (!flowData || !flowData.name) {
            console.log('Invalid flow data:', flowData);
            addMessage('ai', '❌ Invalid flow data received from AI. Please try again.');
            return;
        }
        
        // Show creating message
        addMessage('ai', `🔄 Creating flow: "${flowData.name}"...`);
        
        // Debug: Log the flow data being sent
        console.log('Creating flow with data:', flowData);
        console.log('AJAX URL:', ajaxurl);
        
        // First test if basic AJAX works at all
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wp_tester_simple_test'
            },
            success: function(testResponse) {
                console.log('AJAX test successful:', testResponse);
                // Now try to create the flow
                createFlowActual(flowData);
            },
            error: function(xhr, status, error) {
                console.error('Basic AJAX test failed:', xhr, status, error);
                console.error('Response text:', xhr.responseText);
                console.error('Response headers:', xhr.getAllResponseHeaders());
                addMessage('ai', `❌ Basic AJAX test failed. WordPress AJAX is not working at all. Check console for details.`);
            }
        });
    }
    
    function createFlowActual(flowData) {
        // Create the flow automatically
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wp_tester_create_ai_flow',
                flow_name: flowData.name,
                flow_description: flowData.description || 'Generated by AI Chat Assistant',
                flow_data: flowData,
                model: currentAiModel,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                console.log('Flow creation response:', response);
                if (response.success) {
                    // Show success message
                    addMessage('ai', `✅ Flow "${flowData.name}" created successfully! You can find it in the AI Generated Flows section below.`);
                    
                    // Refresh the AI generated flows list
                    loadAIGeneratedFlows();
                } else {
                    console.error('Flow creation failed:', response);
                    addMessage('ai', `❌ Failed to create flow: ${response.data}`);
                }
            },
            error: function(xhr, status, error) {
                console.error('Flow creation AJAX error:', xhr, status, error);
                console.error('Response text:', xhr.responseText);
                console.error('Response headers:', xhr.getAllResponseHeaders());
                
                // Try to parse the response to see what we got
                let responseText = xhr.responseText;
                if (responseText.startsWith('<')) {
                    addMessage('ai', `❌ Server returned HTML instead of JSON. This usually means the AJAX endpoint wasn't found. Check console for details.`);
                } else {
                    addMessage('ai', `❌ Network error while creating flow. Please try again. (Error: ${error})`);
                }
            }
        });
    }
    
    function showFlowCreationDialog(flowData) {
        // Keep this function for manual flow creation if needed
        if (!flowData || !flowData.name) {
            console.log('Invalid flow data:', flowData);
            return;
        }
        
        // Show confirmation dialog
        const confirmMessage = `AI has generated a flow: "${flowData.name}"\n\nWould you like to create this flow?`;
        
        if (confirm(confirmMessage)) {
            createFlowFromAI(flowData);
        }
    }
});
</script>
