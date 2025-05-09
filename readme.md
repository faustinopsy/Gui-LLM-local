# 🚀 Chat LLM Local e Offline com PHP, JavaScript, Ollama e LM Studio

Este projeto apresenta uma aplicação de **chat em tempo real** que demonstra o consumo de **APIs locais** fornecidas por **servidores de LLM (Large Language Models)** rodando diretamente em sua máquina. O foco é permitir a interação com diferentes modelos **offline, sem necessidade de conexão com a internet** após a configuração inicial.

A aplicação utiliza **PHP** no backend para gerenciar as requisições e se comunicar com os servidores LLM locais (Ollama e LM Studio), e **JavaScript** no frontend para a interface de chat dinâmica e o streaming de respostas.

## ✨ Conceito Central

O principal objetivo deste projeto é ilustrar como é possível **consumir APIs de LLMs que rodam localmente**. Diferentemente de soluções que dependem de APIs na nuvem (como a OpenAI), aqui você controla o ambiente e pode rodar modelos compatíveis com **Ollama** ou via a API compatível com OpenAI do **LM Studio**, alternando entre eles diretamente na aplicação. Isso permite **privacidade, uso offline** e a possibilidade de experimentar diversos modelos sem custos de API ou latência de rede externa.

## 📌 Recursos

✅ **Consumo de APIs Locais:** Interage com servidores LLM rodando no próprio computador (Ollama e LM Studio).
✅ **Operação Offline:** Não requer conexão com a internet para o chat após a instalação dos modelos.
✅ **Suporte Dual Server:** Configurado para alternar entre a API do Ollama (`/api/generate`) e a API compatível com OpenAI do LM Studio (`/v1/chat/completions`).
✅ **Streaming de Respostas Token a Token:** Exibe a resposta do LLM em tempo real conforme ela é gerada.
✅ **Suporte a Diferentes Modelos:** Capacidade de interagir com modelos rodando em Ollama (como DeepSeek, Llama3) e modelos rodando em LM Studio (como Gemma 3B IT).
✅ **Formatador de Código:** Reconhece blocos de código (` ``` `) e aplica destaque de sintaxe.
✅ **Suporte a Markdown:** Formata texto em negrito (`**`), itálico (`*`) e código inline (` ` `).

## ⚡ Pré-requisitos

Para rodar este projeto **como configurado**, você precisará ter os seguintes componentes instalados e rodando em sua máquina:

-   **[PHP 8+](https://www.php.net/downloads.php)**: O backend da aplicação.
-   **[Ollama](https://ollama.com/download)**: Framework para rodar modelos LLM localmente, usado aqui para modelos como **DeepSeek**, **Llama3**, etc.
-   **[LM Studio](https://lmstudio.ai/)**: Aplicação para baixar e rodar modelos LLM localmente, usado aqui especificamente para rodar o modelo **Gemma 3B IT** via sua API compatível com OpenAI.
-   **Modelos LLM instalados:** Os modelos específicos que você deseja usar, baixados via Ollama e/ou LM Studio.

## 📥 Instalação e Configuração

1.  **Instalar PHP 8+:** Siga as instruções no site oficial do PHP para o seu sistema operacional.

2.  **Instalar Ollama:** Baixe e instale o Ollama a partir do link fornecido nos pré-requisitos. O Ollama já inicia um servidor em `http://localhost:11434` por padrão.

3.  **Instalar LM Studio:** Baixe e instale o LM Studio a partir do link fornecido nos pré-requisitos. Dentro do LM Studio, inicie o servidor local na porta padrão (`http://127.0.0.1:1234`) clicando em "Start Server".

4.  **Baixar Modelos LLM:**
    * **Para Ollama (Ex: DeepSeek, Llama3):** Abra um terminal e utilize o comando `ollama run <nome_do_modelo>`. Por exemplo:
        ```bash
        ollama run deepseek-coder
        ollama run llama3
        ```
        Isso baixará e iniciará o modelo (você pode fechar a sessão de chat após o download).
    * **Para LM Studio (Ex: Gemma 3B IT):** Utilize a interface de busca e download do LM Studio para baixar o modelo desejado, como o `gemma-3b-it`.

## 🚀 Rodando os Servidores LLM

Antes de iniciar a aplicação PHP, certifique-se de que **ambos** os servidores, **Ollama** e **LM Studio**, estão rodando:

-   **Ollama:** Geralmente roda em segundo plano após a instalação, servindo a API em `http://localhost:11434`.
-   **LM Studio:** Abra o aplicativo LM Studio e inicie o servidor local (API compatível com OpenAI) na porta padrão `http://127.0.0.1:1234`.

## 🌍 Rodando o Servidor PHP

Com os servidores LLM locais ativos, inicie o servidor web do PHP no diretório raiz do projeto:

```bash
php -S localhost:8000