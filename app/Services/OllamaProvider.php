<?php

namespace App\Services;

use App\Interfaces\LLMServiceProvider;
use Exception;

/**
 * Provedor de LLM para se conectar a um endpoint Ollama (local ou remoto).
 * Implementa a interface LLMServiceProvider.
 */
class OllamaProvider implements LLMServiceProvider
{
    private string $apiUrl;

    /**
     * Construtor. Define a URL da API do Ollama.
     *
     * @param string $apiUrl A URL base da API (ex: "http://localhost:11434")
     */
    public function __construct(string $apiUrl = "http://localhost:11434")
    {
        // Garante que a URL não tenha uma barra no final e adiciona o endpoint
        $this->apiUrl = rtrim($apiUrl, '/') . "/api/generate";
    }

    /**
     * O Ollama local não usa chaves de API, então este método é vazio.
     */
    public function setApiKey(string $apiKey): void
    {
        // Não é necessário para o Ollama local
    }

    /**
     * Constrói o payload específico do Ollama a partir do formato de mensagens genérico.
     */
    private function buildPayload(array $messages, string $model, ?array $images, bool $stream): array
    {
        // O Ollama /api/generate usa um "prompt" simples, não um array de mensagens.
        // Vamos pegar o conteúdo da última mensagem do usuário.
        // NOTA: Para um chat com contexto, você precisaria concatenar as mensagens anteriores.
        $lastUserMessage = '';
        foreach (array_reverse($messages) as $message) {
            if ($message['role'] === 'user') {
                $lastUserMessage = $message['content'];
                break;
            }
        }

        $payload = [
            "model" => $model,
            "prompt" => $lastUserMessage,
            "stream" => $stream,
        ];

        if (!empty($images)) {
            // Pega a primeira imagem (Ollama /generate espera um array de strings base64)
            $payload['images'] = $images;
        }

        return $payload;
    }

    /**
     * Gera uma resposta completa (não-streaming).
     */
    public function generate(array $messages, string $model, ?array $images): string
    {
        $payload = $this->buildPayload($messages, $model, $images, false);
        $jsonData = json_encode($payload);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true, // Capturar a resposta
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new Exception("Erro no cURL (Ollama): " . $error);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
             throw new Exception("Resposta inválida do Ollama (não-JSON): " . $response);
        }

        if (isset($data['response'])) {
            return $data['response']; // Retorna a string de texto final
        }

        if (isset($data['error'])) {
             throw new Exception("Erro da API Ollama: " . $data['error']);
        }
        
        return ""; // Retorno padrão
    }

    /**
     * Gera uma resposta em modo streaming.
     */
    public function generateStream(array $messages, string $model, ?array $images, callable $streamCallback): void
    {
        $payload = $this->buildPayload($messages, $model, $images, true);
        $jsonData = json_encode($payload);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => false, // Não retorne, envie direto para o callback
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 0, // Sem timeout para streaming
            
            // Esta é a função que processa cada "chunk" de dados
            CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use ($streamCallback) {
                // O Ollama envia um JSON por linha
                $lines = explode("\n", trim($chunk));
                
                foreach ($lines as $line) {
                    if (empty($line)) continue;

                    $data = json_decode($line, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Ignora chunks inválidos, mas loga o erro se possível
                        error_log("Chunk JSON inválido do Ollama: ". $line);
                        continue;
                    }

                    // Se 'response' existir, é um pedaço de texto. Envie-o.
                    if (isset($data['response'])) {
                        call_user_func($streamCallback, $data['response']);
                    }

                    // Se a API retornar um erro no meio do stream
                    if (isset($data['error'])) {
                         throw new Exception("Erro da API Ollama (stream): " . $data['error']);
                    }
                }
                
                // Retorna o número de bytes processados
                return strlen($chunk);
            }
        ]);
        $result = curl_exec($curl);

        if ($result === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception("Erro no cURL (Ollama Stream): " . $error);
        }

        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new Exception("Erro no cURL (Ollama Stream): " . $error);
        }
    }
}