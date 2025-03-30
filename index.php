<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LLM local</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
  </div>
  <div class="offcanvas-body">
    <ul id="historyList" class="list-group"></ul>
  </div>
</div>

<div class="row">
  <div class="col-sm-9">
    <div id="output"></div>
    <div id="form">
      <input type="text" id="userInput" class="form-control" placeholder="Digite sua mensagem..." >
      <button id="btnenvia" class="btn btn-outline-secondary">Enviar</button>
    </div>
  </div>
  <div class="col-sm">
    <button class="btn btn-primary m-2" data-bs-toggle="offcanvas" data-bs-target="#historySidebar">
    📜 Histórico de Conversas
    </button>
    <hr>
    <label for="modelSelect">Escolha o Modelo:</label>
    <select id="modelSelect" class="form-select">
    <option value="gemma-3-4b-it:2" >Gemma 3:4B</option>
      <option value="gemma2:2b" >Gemma 2:2B</option>
      <option value="gemma2" >Gemma 2:9B</option>
      <option value="deepseek-r1">Deepseek R1</option>
      <option value="llama3" >Llama 3</option>
    </select>

  <label>
    <input type="checkbox" id="streamToggle" checked class="form-check-input">
    Ativar Stream
  </label>
  </div>
</div>

<script src="./assets/js/chatjs.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
