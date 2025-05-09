async function sendMessage() {
  document.getElementById('loadingSpinner').style.display = 'block';
  const inputField = document.getElementById('userInput');
  const imageInput = document.getElementById('imageInput');
  const modelSelect = document.getElementById('modelSelect').value;
  const streamToggle = document.getElementById('streamToggle').checked;
  const ollamaToggle = document.getElementById('ollamaToggle').checked;
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
  botDiv.textContent = '...';
  output.appendChild(botDiv);

  output.scrollTop = output.scrollHeight;

  const formData = new FormData();
  formData.append("userMessage", userMessage);
  formData.append("modelSelect", modelSelect);
  formData.append("streamToggle", streamToggle);
  formData.append("ollamaToggle", ollamaToggle);
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
          const errorText = await response.text();
           botDiv.textContent = `Erro do servidor (${response.status}): ${errorText}`;
           document.getElementById('loadingSpinner').style.display = 'none';
           return;
      }

      const reader = response.body.getReader();
      const decoder = new TextDecoder();

      botDiv.textContent = '';

      while (true) {
          const { done, value } = await reader.read();
          if (done) {
              break;
          }

          const chunk = decoder.decode(value, { stream: true });
          botMessage += chunk;
          botDiv.textContent = botMessage;
          output.scrollTop = output.scrollHeight;
      }

      let finalBotContent = botMessage;

      if (!streamToggle) {
          try {
               const parsedJson = JSON.parse(botMessage);

               if (ollamaToggle) {
                   if (parsedJson && typeof parsedJson.response === 'string') {
                       finalBotContent = parsedJson.response;
                   } else {
                       console.warn("Ollama non-streaming response did not match expected JSON structure:", parsedJson);
                   }
               } else {
                    if (parsedJson && parsedJson.choices && parsedJson.choices[0] && parsedJson.choices[0].message && typeof parsedJson.choices[0].message.content === 'string') {
                       finalBotContent = parsedJson.choices[0].message.content;
                   } else {
                       console.warn("LM Studio non-streaming response did not match expected JSON structure:", parsedJson);
                   }
               }
          } catch (e) {
               console.warn("Failed to parse non-streaming response as JSON. Treating as raw text.", e);
          }
      }

      botDiv.innerHTML = formatMessage(finalBotContent);
      hljs.highlightAll();

      document.getElementById('loadingSpinner').style.display = 'none';

      saveMessageToHistory(userMessage, finalBotContent);

  } catch (error) {
      botDiv.textContent = `Erro na requisição: ${error}`;
      document.getElementById('loadingSpinner').style.display = 'none';
  }
}

document.getElementById("btnenvia").addEventListener("click", sendMessage);

function formatMessage(message) {
  message = message.replace(/```(\w+)?\n([\s\S]*?)```/g, (match, lang, code) => {
      code = code.replace(/\n/g, '&#10;');
      return `<pre><code class="${lang || 'plaintext'}">${escapeHtml(code)}</code></pre>`;
  });
  message = message.replace(/`([^`]+)`/g, '<code>$1</code>');
  message = message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
  message = message.replace(/\*(.*?)\*/g, '<em>$1</em>');

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
  hljs.highlightAll();
}

document.addEventListener("DOMContentLoaded", loadChatHistory);