async function sendMessage() {
    const inputField = document.getElementById('userInput');
    const modelSelect = document.getElementById('modelSelect').value;
    const streamToggle = document.getElementById('streamToggle').checked;
    const userMessage = inputField.value.trim();
    if (!userMessage) return;
  
    inputField.value = '';
  
    const output = document.getElementById('output');
    const userDiv = document.createElement('div');
    userDiv.textContent = `EU: ${userMessage}`;
    output.appendChild(userDiv);
  
    const botDiv = document.createElement('div');
    output.appendChild(botDiv);
  
    try {
      const response = await fetch('chatController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userMessage, modelSelect,streamToggle})
      });
  
      const reader = response.body.getReader();
      const decoder = new TextDecoder();
      let botMessage = '';
  
      while (true) {
        const { done, value } = await reader.read();
        if (done) break;
  
        const chunk = decoder.decode(value, { stream: true });
  
        try {
          const jsonData = JSON.parse(chunk);
          if (jsonData.response) {
            botMessage += jsonData.response;
            botDiv.innerHTML = formatMessage(botMessage);
            output.scrollTop = output.scrollHeight;
            hljs.highlightAll(); 
          }
        } catch (e) {
          console.warn("Recebeu um fragmento inválido do servidor:", chunk);
        }
      }
      saveMessageToHistory(userMessage, botMessage);
    } catch (error) {
      botDiv.textContent = `Erro ao enviar mensagem: ${error}`;
    }

  }
  document.getElementById("btnenvia").addEventListener("click",sendMessage);
  function formatMessage(message) {
    message = message.replace(/```(\w+)?\n([\s\S]*?)```/g, (match, lang, code) => {
      return `<pre><code class="${lang || 'plaintext'}">${escapeHtml(code)}</code></pre>`;
    });
    message = message.replace(/`([^`]+)`/g, '<code>$1</code>');
    message = message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    message = message.replace(/\*(.*?)\*/g, '<em>$1</em>');
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
        listItem.textContent = `🔹 ${chat.user}`;
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
    botDiv.innerHTML = formatMessage(chatHistory[index].bot);
    output.appendChild(botDiv);
}

document.addEventListener("DOMContentLoaded", loadChatHistory);