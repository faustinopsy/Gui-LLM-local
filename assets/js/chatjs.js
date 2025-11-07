async function sendMessage() {
document.getElementById('loadingSpinner').style.display = 'block';
const inputField = document.getElementById('userInput');
const imageInput = document.getElementById('imageInput');
  const providerSelect = document.getElementById('providerSelect').value; // NOVO
const modelSelect = document.getElementById('modelSelect').value;
const streamToggle = document.getElementById('streamToggle').checked;
const userMessage = inputField.value.trim();

if (!userMessage && !imageInput.files.length) {
document.getElementById('loadingSpinner').style.display = 'none';
return;
}

const output = document.getElementById('output');
const userDiv = document.createElement('div');
userDiv.textContent = `EU: ${userMessage || '[imagem enviada]'}`;
output.appendChild(userDiv);

const botDiv = document.createElement('div');
  botDiv.className = 'bot-message'; // Adiciona uma classe para estilização futura
botDiv.textContent = '...';
output.appendChild(botDiv);

output.scrollTop = output.scrollHeight;

const formData = new FormData();
  formData.append("providerSelect", providerSelect); // NOVO
formData.append("userMessage", userMessage);
formData.append("modelSelect", modelSelect);
formData.append("streamToggle", streamToggle);
if (imageInput.files.length) {
formData.append("image", imageInput.files[0]);
}

inputField.value = '';
imageInput.value = '';

let botMessage = '';

try {
const response = await fetch('chatController.php', {
method: 'POST',
body: formData
});

if (!response.ok) {
        // Tenta ler o erro como JSON, que é o que nosso PHP agora envia
        let errorData;
        try {
            errorData = await response.json();
        } catch (e) {
            errorData = { error: "Erro desconhecido", details: await response.text() };
        }
const errorText = `${errorData.error} (Detalhes: ${errorData.details || 'N/A'})`;
botDiv.textContent = `Erro do servidor (${response.status}): ${errorText}`;
document.getElementById('loadingSpinner').style.display = 'none';
return;
}

let finalBotContent = '';

if (streamToggle) {
        // --- LÓGICA DE STREAMING (Permanece quase a mesma) ---
const reader = response.body.getReader();
const decoder = new TextDecoder();
botDiv.textContent = '';

while (true) {
const { done, value } = await reader.read();
if (done) break;

const chunk = decoder.decode(value, { stream: true });
botMessage += chunk;
// Não use formatMessage em cada chunk. Formate apenas no final.
botDiv.innerHTML = formatMessage(botMessage);
output.scrollTop = output.scrollHeight;
}
        finalBotContent = botMessage;

} else {
        // --- LÓGICA DE NÃO-STREAMING (MUITO MAIS SIMPLES) ---
        // Nosso back-end agora *sempre* envia um JSON limpo: {"response": "..."}
        try {
            const parsedJson = await response.json();
            if (parsedJson.response) {
                finalBotContent = parsedJson.response;
            } else if (parsedJson.error) {
                throw new Error(parsedJson.details || parsedJson.error);
            } else {
                throw new Error("Resposta JSON inválida do servidor.");
            }
        } catch (e) {
            console.error("Falha ao analisar JSON não-streaming:", e);
            botDiv.textContent = `Erro ao processar resposta: ${e.message}`;
            finalBotContent = "Erro.";
        }
}

// Formata a mensagem completa *apenas uma vez* no final
botDiv.innerHTML = formatMessage(finalBotContent);
hljs.highlightAll(); // Aplica o highlight após o conteúdo ser inserido

document.getElementById('loadingSpinner').style.display = 'none';

// Salva a mensagem final no histórico
saveMessageToHistory(userMessage || '[imagem enviada]', finalBotContent);

} catch (error) {
botDiv.textContent = `Erro na requisição: ${error}`;
document.getElementById('loadingSpinner').style.display = 'none';
}
}

document.getElementById("btnenvia").addEventListener("click", sendMessage);

function formatMessage(message) {
// Converte markdown de bloco de código para <pre><code>
message = message.replace(/```(\w+)?\n([\s\S]*?)```/g, (match, lang, code) => {
// A LINHA PROBLEMÁTICA FOI REMOVIDA
return `<pre><code class="${lang || 'plaintext'}">${escapeHtml(code)}</code></pre>`;
});

  // Converte markdown inline `code`
message = message.replace(/`([^`]+)`/g, '<code>$1</code>');
  // Converte markdown **bold**
message = message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
  // Converte markdown *italic*
message = message.replace(/\*(.*?)\*/g, '<em>$1</em>');

  // Converte quebras de linha em <br> (DEPOIS do <pre> para não afetar o código)
message = message.replace(/\n/g, '<br>');

return `<div>${message}</div>`;
}

function escapeHtml(unsafe) {
return unsafe.replace(/&/g, "&amp;")
 .replace(/</g, "&lt;")
 .replace(/>/g, "&gt;")
 .replace(/"/g, "&quot;")
 .replace(/'/g, "&#039;");
}

function saveMessageToHistory(userMessage, botMessage) {
let chatHistory = JSON.parse(localStorage.getItem("chatHistory")) || [];
chatHistory.push({ user: userMessage, bot: botMessage });
localStorage.setItem("chatHistory", JSON.stringify(chatHistory));
loadChatHistory();
}

function loadChatHistory() {
const historyList = document.getElementById("historyList");
historyList.innerHTML = "";
const chatHistory = JSON.parse(localStorage.getItem("chatHistory")) || [];

chatHistory.forEach((chat, index) => {
const listItem = document.createElement("li");
listItem.classList.add("list-group-item", "list-group-item-action");
// Mostra apenas o início da mensagem do usuário
      const previewText = chat.user.length > 40 ? chat.user.substring(0, 40) + "..." : chat.user;
listItem.textContent = `🔹 ${previewText}`;
listItem.onclick = () => restoreConversation(index);
historyList.appendChild(listItem);
});
}

function restoreConversation(index) {
const chatHistory = JSON.parse(localStorage.getItem("chatHistory")) || [];
if (!chatHistory[index]) return;

const output = document.getElementById("output");
output.innerHTML = "";

const userDiv = document.createElement("div");
userDiv.textContent = `Eu: ${chatHistory[index].user}`;
output.appendChild(userDiv);

const botDiv = document.createElement("div");
  botDiv.className = 'bot-message';
botDiv.innerHTML = formatMessage(chatHistory[index].bot);
output.appendChild(botDiv);
hljs.highlightAll(); // Aplica o highlight ao restaurar
}

document.addEventListener("DOMContentLoaded", loadChatHistory);