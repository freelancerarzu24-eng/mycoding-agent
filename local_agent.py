import os
import json
import urllib.request
import zipfile
import shutil

# Local Ollama URL (No internet API required)
OLLAMA_URL = "http://localhost:11434/api/generate"
MODEL_NAME = "qwen2.5-coder:7b" # Feel free to change to deepseek-coder:7b or llama3

def load_system_prompt():
    try:
        with open("system_prompt.txt", "r", encoding="utf-8") as f:
            return f.read()
    except FileNotFoundError:
        print("Error: system_prompt.txt file not found!")
        exit(1)

def read_project_files(directory):
    print(f"\n[+] Scanning project files in: {directory}")
    project_context = "Here is the existing project codebase:\n\n"

    ignore_dirs = ['.git', 'node_modules', 'vendor', '__pycache__']
    valid_extensions = ('.php', '.html', '.css', '.js', '.sql', '.json', '.txt', '.md', '.env.example')

    for root, dirs, files in os.walk(directory):
        dirs[:] = [d for d in dirs if d not in ignore_dirs]
        for file in files:
            if file.endswith(valid_extensions):
                filepath = os.path.join(root, file)
                try:
                    with open(filepath, 'r', encoding='utf-8') as f:
                        content = f.read()
                        project_context += f"==================== FILE: {filepath} ====================\n"
                        project_context += f"{content}\n\n"
                except Exception as e:
                    print(f"Skipping {filepath}: {e}")

    return project_context

def chat_with_local_agent(system_prompt, project_context, user_instruction):
    full_prompt = f"{project_context}\n\nUSER INSTRUCTION:\n{user_instruction}"

    data = {
        "model": MODEL_NAME,
        "system": system_prompt,
        "prompt": full_prompt,
        "stream": True,
        "options": {
            "temperature": 0.1, # Low temperature for accurate coding tasks
            "num_ctx": 16384    # Large context window for reading full projects
        }
    }

    print("\n[+] Agent is analyzing the project and thinking locally...\n")
    print("-" * 80)

    req = urllib.request.Request(OLLAMA_URL, data=json.dumps(data).encode('utf-8'), headers={'Content-Type': 'application/json'})

    try:
        with urllib.request.urlopen(req) as response:
            for line in response:
                if line:
                    chunk = json.loads(line)
                    if "response" in chunk:
                        print(chunk["response"], end="", flush=True)
    except Exception as e:
        print(f"\n[Error connecting to local AI: {e}]")
        print("Please make sure Ollama is installed and running in the background!")

    print("\n" + "-" * 80)
    print("\n[+] Agent task completed locally based on your system rules.")

def main():
    print("=== OFFLINE AI FULL-STACK DEVELOPER AGENT ===")
    system_prompt = load_system_prompt()

    project_path = input("\nEnter the path to your project folder or ZIP file: ").strip()

    if project_path.endswith('.zip'):
        print("[+] Extracting ZIP file...")
        extract_dir = "agent_workspace"
        if os.path.exists(extract_dir):
            shutil.rmtree(extract_dir)
        with zipfile.ZipFile(project_path, 'r') as zip_ref:
            zip_ref.extractall(extract_dir)
        project_path = extract_dir

    project_context = read_project_files(project_path)

    user_instruction = input("\nWhat do you want me to do with this project? (e.g., 'Scan for bugs and fix them', 'Add a login system'): ")

    chat_with_local_agent(system_prompt, project_context, user_instruction)

if __name__ == "__main__":
    main()
