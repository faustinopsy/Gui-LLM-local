# 🚀 Chat LLM com PHP e Streaming Token a Token

Este projeto implementa um **chat em tempo real** utilizando **PHP**, **JavaScript** e **Ollama**, permitindo conversação com modelos de LLM (**Large Language Models**) diretamente em sua máquina.

## 📌 Recursos
✅ **Streaming de Respostas Token a Token** (sem usar SSE).  
✅ **Suporte a Diferentes Modelos (Gemma, DeepSeek, Llama3, etc.).**  
✅ **Formatador de Código com Sintaxe Destacada (Highlight.js).**  
✅ **Suporte a Markdown (`**negrito**`, `*itálico*`, `\`código\``).**  

---
- os crecuros acima são importantes ao solicitar código os códigos serão formatados para melhor visualização e estrutura correta, espere o modelo escrever o codigo para a formação ser feita


## ⚡ **Pré-requisitos**
Antes de iniciar o projeto, certifique-se de que tem:
- **[PHP 8+](https://www.php.net/downloads.php)**
- **[Ollama](https://ollama.com/download)**
- **Modelos LLM instalados**

---

## 📥 **Instalando o Ollama**
O **Ollama** é um framework para rodar **LLMs localmente**.

- **[Ollama](https://ollama.com/)

Baixando os Modelos de LLM
O Ollama suporta vários modelos. Para este projeto, você pode escolher entre:

✅ Baixar o modelo Gemma 2B
```
ollama run  gemma2:2b
```
✅ Baixar o modelo DeepSeek-R1
```
ollama run deepseek-r1
```
✅ Baixar o modelo Llama 3
```
ollama run llama3
```

## 🚀 Rodando o Servidor Ollama
Antes de iniciar o PHP, o Ollama deve estar rodando em segundo plano.

Isso inicia um servidor local em http://localhost:11434 para processar solicitações do chat.

## 🌍 Rodando o Servidor PHP
Agora, inicie o servidor PHP para acessar o chat:

```
php -S localhost:8000
```

Acesse o chat via navegador em http://localhost:8000.

## 📌 Tecnologias Utilizadas
- PHP 8+ → Backend para gerenciar requisições do chat.
- JavaScript → Manipulação do DOM e exibição dinâmica do chat.
- Ollama → Framework para execução de modelos LLM localmente.
- Highlight.js → Destacar sintaxe de código no chat.
- w3-CSS → Para estilizar o frontend.
## 🚀 Futuras Melhorias
- Suporte a mais modelos LLM (GPT-4, Mistral, Falcon).
- Cache de respostas para otimizar a performance.
- Histórico de chat usando banco de dados SQLite.
## 👨‍💻 Contribuindo
Quer contribuir? Sinta-se à vontade para abrir um Pull Request ou criar uma Issue para melhorias. 🚀

## 📜 Licença
Este projeto está licenciado sob a MIT License. Sinta-se livre para usá-lo e modificá-lo. 🔥