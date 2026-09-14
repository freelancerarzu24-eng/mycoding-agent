<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Coding Agent System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Local AI Full-Stack Developer Agent</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center">Upload a project ZIP file and chat with your local AI developer.</p>

                    <div class="mb-4">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="input-group">
                                <input type="file" class="form-control" id="projectZip" name="projectZip" accept=".zip" required>
                                <button class="btn btn-outline-secondary" type="submit" id="uploadBtn">Upload Project</button>
                            </div>
                        </form>
                        <div id="uploadStatus" class="mt-2 text-center" style="display: none;"></div>
                    </div>

                    <hr>

                    <div id="chatBox" class="chat-box p-3 mb-3 border rounded bg-white" style="height: 400px; overflow-y: auto;">
                        <div class="chat-message system mb-2">
                            <strong>System:</strong> Welcome! Upload a project and give me instructions. I will analyze your files locally.
                        </div>
                    </div>

                    <form id="chatForm" enctype="multipart/form-data">
                        <div class="mb-2">
                            <input type="url" id="referenceUrl" class="form-control" placeholder="Reference URL (Optional)">
                        </div>
                        <div class="mb-2">
                            <input type="file" id="uiImage" class="form-control" accept="image/png, image/jpeg, image/webp" aria-label="Upload UI Design Image (Optional)">
                            <small class="text-muted">Upload a UI design image to auto-generate code (Requires vision model like llava).</small>
                        </div>
                        <div class="input-group">
                            <textarea id="userInput" class="form-control" placeholder="Ask the AI to do something... e.g. 'Build a website like this image'" rows="2" required></textarea>
                            <button class="btn btn-primary" type="submit" id="sendBtn">Send</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
