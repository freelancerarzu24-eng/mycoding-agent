document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');
    const chatForm = document.getElementById('chatForm');
    const userInput = document.getElementById('userInput');
    const chatBox = document.getElementById('chatBox');

    let contextPath = '';

    // Handle File Upload
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const fileInput = document.getElementById('projectZip');
        if (!fileInput.files.length) {
            alert('Please select a ZIP file first.');
            return;
        }

        const formData = new FormData();
        formData.append('projectZip', fileInput.files[0]);

        uploadStatus.style.display = 'block';
        uploadStatus.innerHTML = '<div class="loader"></div> Uploading and extracting...';
        document.getElementById('uploadBtn').disabled = true;

        try {
            const response = await fetch('process_upload.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                uploadStatus.innerHTML = `<span class="text-success">✔ ${result.message}</span>`;
                contextPath = result.context_path;
                appendMessage('system', 'Project loaded successfully. You can now start asking questions!');
            } else {
                uploadStatus.innerHTML = `<span class="text-danger">✖ ${result.error}</span>`;
            }
        } catch (error) {
            uploadStatus.innerHTML = `<span class="text-danger">✖ An error occurred during upload.</span>`;
            console.error(error);
        } finally {
            document.getElementById('uploadBtn').disabled = false;
        }
    });

    // Configure marked to use highlight.js for syntax highlighting
    if (typeof marked !== 'undefined' && typeof hljs !== 'undefined') {
        marked.setOptions({
            highlight: function(code, lang) {
                const language = hljs.getLanguage(lang) ? lang : 'plaintext';
                return hljs.highlight(code, { language }).value;
            },
            langPrefix: 'hljs language-'
        });
    }

    // Auto-resize textarea
    userInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        if(this.value === '') {
            this.style.height = 'auto';
        }
    });

    // Submit on Enter (Shift+Enter for new line)
    userInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });

    // Handle Chat
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const message = userInput.value.trim();
        if (!message) return;

        const referenceUrl = document.getElementById('referenceUrl').value.trim();
        const uiImageInput = document.getElementById('uiImage');

        // Add user message to UI
        let userDisplayMessage = escapeHtml(message);
        if (referenceUrl) userDisplayMessage += `<br><small class="text-white-50"><i class="fa-solid fa-link"></i> ${escapeHtml(referenceUrl)}</small>`;
        if (uiImageInput.files.length) userDisplayMessage += `<br><small class="text-white-50"><i class="fa-regular fa-image"></i> Image Attached</small>`;

        appendMessage('user', userDisplayMessage);

        userInput.value = '';
        userInput.style.height = 'auto';
        document.getElementById('referenceUrl').value = '';

        const uploadedImage = uiImageInput.files[0];
        uiImageInput.value = '';

        const sendBtn = document.getElementById('sendBtn');
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';

        // Create AI message placeholder
        const aiMessageId = 'ai-msg-' + Date.now();
        appendMessage('ai', '<div class="loader"></div> Processing...', aiMessageId);

        const formData = new FormData();
        formData.append('user_instruction', message);
        if (contextPath) {
            formData.append('context_path', contextPath);
        }
        if (referenceUrl) {
            formData.append('reference_url', referenceUrl);
        }
        if (uploadedImage) {
            formData.append('ui_image', uploadedImage);
        }

        try {
            const response = await fetch('process_chat.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            // Stream response
            const reader = response.body.getReader();
            const decoder = new TextDecoder('utf-8');

            // Find the inner content container for the AI message
            const aiMessageContainer = document.getElementById(aiMessageId);
            const contentDiv = aiMessageContainer.querySelector('.message-content');
            contentDiv.innerHTML = '';

            let fullAiText = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value, { stream: true });
                fullAiText += chunk;

                // Parse markdown in real-time
                if (typeof marked !== 'undefined') {
                    contentDiv.innerHTML = marked.parse(fullAiText);
                } else {
                    contentDiv.textContent = fullAiText;
                }

                chatBox.scrollTop = chatBox.scrollHeight;
            }

            // Re-apply highlighting to the final block to ensure it caught everything
            if (typeof hljs !== 'undefined') {
                contentDiv.querySelectorAll('pre code').forEach((block) => {
                    hljs.highlightElement(block);
                });
            }

        } catch (error) {
            console.error(error);
            const aiMessageContainer = document.getElementById(aiMessageId);
            if(aiMessageContainer) {
                const contentDiv = aiMessageContainer.querySelector('.message-content');
                contentDiv.innerHTML = `<span class="text-danger"><i class="fa-solid fa-circle-exclamation"></i> Failed to get response. Is Ollama running locally?</span>`;
            }
        } finally {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
        }
    });

    function escapeHtml(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    function appendMessage(sender, text, id = null) {
        const div = document.createElement('div');
        div.className = `chat-message ${sender}`;
        if (id) {
            div.id = id;
        }

        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';

        if(sender === 'user') {
             contentDiv.innerHTML = text; // text already escaped before calling
        } else if (sender === 'ai' && !id) {
             contentDiv.innerHTML = typeof marked !== 'undefined' ? marked.parse(text) : escapeHtml(text);
        } else if (sender === 'system') {
             contentDiv.innerHTML = typeof marked !== 'undefined' ? marked.parse(text) : escapeHtml(text);
        } else if (id) {
             contentDiv.innerHTML = text; // loader HTML
        } else {
             contentDiv.textContent = text;
        }

        div.appendChild(contentDiv);
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
});