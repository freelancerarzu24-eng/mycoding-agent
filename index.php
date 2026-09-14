<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus AI Developer Space</title>

    <!-- External Libraries for Professional UI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Code Highlighting and Markdown -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.3.0/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-container">
    <!-- Sidebar for Context & Tools -->
    <aside class="sidebar bg-dark text-white p-3">
        <div class="sidebar-header mb-4 text-center">
            <h5><i class="fa-solid fa-code-branch text-primary"></i> Nexus Agent</h5>
            <small class="text-muted">Autonomous AI Developer</small>
        </div>

        <div class="context-tools">
            <h6 class="text-uppercase text-secondary mb-3"><i class="fa-solid fa-folder-open"></i> Project Context</h6>

            <form id="uploadForm" enctype="multipart/form-data" class="mb-3">
                <div class="mb-2">
                    <label class="form-label small text-muted">Upload Project Source (.zip)</label>
                    <input type="file" class="form-control form-control-sm bg-secondary text-white border-0" id="projectZip" name="projectZip" accept=".zip" required>
                </div>
                <button class="btn btn-outline-primary btn-sm w-100" type="submit" id="uploadBtn">Load Context</button>
            </form>
            <div id="uploadStatus" class="mt-2 text-center small" style="display: none;"></div>

            <hr class="border-secondary my-4">

            <h6 class="text-uppercase text-secondary mb-3"><i class="fa-solid fa-eye"></i> Vision & URLs</h6>

            <div class="mb-3">
                <label class="form-label small text-muted">Reference Website</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-secondary border-0 text-white"><i class="fa-solid fa-link"></i></span>
                    <input type="url" id="referenceUrl" class="form-control bg-secondary text-white border-0" placeholder="https://example.com">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small text-muted">UI Mockup (Vision)</label>
                <input type="file" id="uiImage" class="form-control form-control-sm bg-secondary text-white border-0" accept="image/png, image/jpeg, image/webp">
                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Upload design to auto-generate code.</small>
            </div>

        </div>

        <div class="sidebar-footer mt-auto pt-4 text-center">
             <small class="text-muted"><i class="fa-solid fa-bolt text-warning"></i> Powered by Local LLM</small>
        </div>
    </aside>

    <!-- Main Chat Area -->
    <main class="chat-main">
        <header class="chat-header p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="m-0 text-dark">Developer Workspace</h5>
            <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-circle-check"></i> System Ready</span>
        </header>

        <div id="chatBox" class="chat-box p-4">
            <div class="chat-message system mb-4">
                <div class="message-content">
                    <h5>Welcome to your professional AI coding environment.</h5>
                    <p>I am your autonomous full-stack developer. You can:</p>
                    <ul>
                        <li>Upload a ZIP file in the sidebar to have me scan or update existing code.</li>
                        <li>Upload a design image to have me generate a complete frontend.</li>
                        <li>Provide a reference URL to extract content and structure.</li>
                    </ul>
                    <p>Just tell me what you want to build.</p>
                </div>
            </div>
        </div>

        <footer class="chat-input-area p-3 bg-white border-top">
            <form id="chatForm">
                <div class="input-group chat-input-group shadow-sm rounded-4 overflow-hidden">
                    <textarea id="userInput" class="form-control border-0 p-3" placeholder="Describe the feature or project you want to build..." rows="1" required></textarea>
                    <button class="btn btn-primary px-4" type="submit" id="sendBtn">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </footer>
    </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
