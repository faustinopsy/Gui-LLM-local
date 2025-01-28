<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LLM local</title>
  <link rel="stylesheet" href="./assets/css/w3.css">
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

  <label for="modelSelect">Escolha o Modelo:</label>
  <select id="modelSelect" class="w3-select">
    <option value="gemma2:2b" >Gemma 2:2B</option>
    <option value="deepseek-r1">Deepseek R1</option>
    <option value="llama3" >Llama 3</option>
  </select>

  <label>
    <input type="checkbox" id="streamToggle" checked>
    Ativar Stream
  </label>

  <div id="output"></div>

  <div id="form">
    <input type="text" id="userInput" class="w3-input" placeholder="Digite sua mensagem..." />
    <button id="btnenvia" class="w3-button w3-teal">Enviar</button>
  </div>

  <script src="./assets/js/chatjs.js" defer></script>
</body>
</html>
