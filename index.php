<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LLM Hub</title> <link href="assets/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="./assets/css/dark.min.css">
<script src="./assets/js/highlight.min.js"></script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
#output { border: 1px solid #ccc; padding: 10px; height: 500px; overflow-y: scroll; background: #f9f9f9; }
pre { background: #282c34; color: white; padding: 10px; border-radius: 5px; overflow-x: auto; }
code { font-family: "Courier New", monospace; }
</style>
</head>
<body>

<div class="offcanvas offcanvas-start" tabindex="-1" id="historySidebar">
<div class="offcanvas-header">
<h5 class="offcanvas-title">📜 Histórico</h5>
<button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
<button class="btn btn-danger mt-2" id="clearHistoryBtn">🧹 Limpar Memória</button>

</div>
<div class="offcanvas-body">
<ul id="historyList" class="list-group"></ul>
</div>
</div>

<div class="row">
<div class="col-sm-9">
<div id="output"></div>
<div id="form">
<textarea name="userMessage" id="userInput" class="form-control" placeholder="Digite sua mensagem..." ></textarea>
<input type="file" id="imageInput" accept="image/*" class="form-control mt-2">
<button id="btnenvia" class="btn btn-outline-secondary">Enviar</button>
</div>
</div>
<div class="col-sm">
<button class="btn btn-primary m-2" data-bs-toggle="offcanvas" data-bs-target="#historySidebar">
📜 Histórico de Conversas
</button>
<hr>

    <label for="providerSelect" class="form-label">Provedor:</label>
    <select id="providerSelect" class="form-select mb-2">
    <option value="ollama" selected>Ollama (Local)</option>
      <option value="openai_local">LM Studio (Local)</option>
    <option value="gemini">Google Gemini (Nuvem)</option>
      <option value="openai_remote">OpenAI (Nuvem)</option>
    </select>
    
<label for="modelSelect" class="form-label">Modelo:</label>
<select id="modelSelect" class="form-select mb-2">
      <option value="gemma3:4b" >gemma3:4b (Ollama)</option>
<option value="gemma2:latest" >gemma2 (Ollama)</option>
<option value="llama3:8b-instruct-q4_K_M" >llama3 (Ollama)</option>
      <option value="gemini-1.5-pro-latest">gemini-1.5-pro (Gemini)</option>
      <option value="gpt-4o">gpt-4o (OpenAI)</option>
</select>

    <div class="form-check form-switch mb-2">
      <input class="form-check-input" type="checkbox" id="streamToggle" checked>
      <label class="form-check-label" for="streamToggle">Ativar Stream</label>
    </div>

    <div id="loadingSpinner" class="text-center my-3" style="display: none;">
<div class="spinner-border text-primary" role="status">
<span class="visually-hidden">Carregando...</span>
</div>
<p>Processando resposta...</p>
</div>
</div>
</div>

<script src="./assets/js/chatjs.js" defer></script> 
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>