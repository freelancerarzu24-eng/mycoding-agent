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

    // Handle Chat
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const message = userInput.value.trim();
        if (!message) return;

        const referenceUrl = document.getElementById('referenceUrl').value.trim();
        const uiImageInput = document.getElementById('uiImage');

        // Add user message to UI
        let userDisplayMessage = escapeHtml(message);
        if (referenceUrl) userDisplayMessage += `<br><small><i>Ref URL: ${escapeHtml(referenceUrl)}</i></small>`;
        if (uiImageInput.files.length) userDisplayMessage += `<br><small><i>[Image Attached]</i></small>`;

        appendMessage('user', userDisplayMessage);

        userInput.value = '';
        document.getElementById('referenceUrl').value = '';
        // don't clear the image input immediately in case they want to ask follow ups, but let's clear it for UI consistency
        const uploadedImage = uiImageInput.files[0];
        uiImageInput.value = '';

        document.getElementById('sendBtn').disabled = true;

        // Create AI message placeholder
        const aiMessageId = 'ai-msg-' + Date.now();
        appendMessage('ai', '<div class="loader"></div> Thinking...', aiMessageId);

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
            const aiMessageElement = document.getElementById(aiMessageId);
            aiMessageElement.innerHTML = ''; // clear loader

            let isFirstChunk = true;

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value, { stream: true });

                // Extremely basic parsing of chunked output. In reality, Ollama sends newline separated JSONs.
                // We will try to parse them if possible. Our backend process_chat.php handles parsing and sending raw text if done right,
                // but let's assume the backend just streams raw text for simplicity.
                aiMessageElement.textContent += chunk;

                if (isFirstChunk) {
                    isFirstChunk = false;
                }

                chatBox.scrollTop = chatBox.scrollHeight;
            }

        } catch (error) {
            console.error(error);
            const aiMessageElement = document.getElementById(aiMessageId);
            if(aiMessageElement) {
                aiMessageElement.innerHTML = `<span class="text-danger">✖ Failed to get response. Is Ollama running?</span>`;
            }
        } finally {
            document.getElementById('sendBtn').disabled = false;
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

        if(sender === 'user') {
             // text can contain our HTML tags for images/URLs, so we need to be careful
             // The incoming text is already built with some HTML, but the original message might have XSS.
             // We handled the message directly earlier, but let's be safe.
             // Actually, since we control what is passed to appendMessage('user', userDisplayMessage),
             // and userDisplayMessage has the raw message + HTML. It's better to escape the message before building the HTML.
             div.innerHTML = `<strong>You:</strong><br>${text}`;
        } else if (sender === 'ai' && !id) {
             div.innerHTML = `<strong>AI:</strong><br>${escapeHtml(text)}`;
        } else if (sender === 'system') {
             div.innerHTML = `<strong>System:</strong> ${escapeHtml(text)}`;
        } else if (id) {
             div.innerHTML = text; // loader HTML
        } else {
             div.textContent = text;
        }

        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
});