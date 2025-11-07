<?php

namespace App\Services;

use App\Interfaces\LLMServiceProvider;
use Exception;

/**
 * Provedor de LLM para se conectar a qualquer API compatível com OpenAI
 * (como LM Studio, Vercel AI SDK, ou a própria OpenAI).
 * Implementa a interface LLMServiceProvider.
 */
class OpenAIProvider implements LLMServiceProvider
{
    private string $apiUrl;
    private ?string $apiKey = null;

    /**
     * Construtor. Define a URL da API OpenAI-compatível.
     *
     * @param string $apiUrl A URL base da API (ex: "http://127.0.0.1:1234")
     */
    public function __construct(string $apiUrl = "http://127.0.0.1:1234")
    {
        // Garante que a URL não tenha uma barra no final e adiciona o endpoint
        $this->apiUrl = rtrim($apiUrl, '/') . "/v1/chat/completions";
    }

    /**
     * Define a chave de API (necessária para a OpenAI real, opcional para LM Studio).
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Constrói o payload específico do OpenAI.
     * Esta é a parte mais complexa, pois precisa formatar corretamente as imagens.
     */
    private function buildPayload(array $messages, string $model, ?array $images, bool $stream): array
    {
        $finalMessages = $messages;

        // Se houver imagens, precisamos modificar a última mensagem do usuário
        if (!empty($images)) {
            // Encontra a chave da última mensagem de 'user'
            $lastKey = null;
            foreach (array_reverse($messages, true) as $key => $message) {
                if ($message['role'] === 'user') {
                    $lastKey = $key;
                    break;
                }
            }

            if ($lastKey !== null) {
                // Pega o texto original
                $originalText = $finalMessages[$lastKey]['content'];
                
                // Constrói o novo 'content' em formato de array (multimodal)
                $newContent = [
                    ["type" => "text", "text" => $originalText]
                ];

                // Adiciona todas as imagens
                foreach ($images as $base64Image) {
                    $newContent[] = [
                        "type" => "image_url",
                        "image_url" => [
                            // Assumindo JPEG, mas a API aceita outros.
                            // O front-end pode precisar enviar o mime-type correto.
                            "url" => "data:image/jpeg;base64," . $base64Image
                        ]
                    ];
                }
                
                // Substitui o 'content' antigo pelo novo array multimodal
                $finalMessages[$lastKey]['content'] = $newContent;
            }
        }

        return [
            "model" => $model,
            "messages" => $finalMessages,
            "stream" => $stream,
            "temperature" => 0.7, // Do seu script original
            "max_tokens" => -1,     // Do seu script original
        ];
    }

    /**
     * Constrói os cabeçalhos HTTP, incluindo a chave de API se ela existir.
     */
    private function buildCurlHeaders(): array
    {
        $headers = [
            'Content-Type: application/json'
        ];

        if ($this->apiKey) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        return $headers;
    }

    /**
     * Gera uma resposta completa (não-streaming).
     */
    public function generate(array $messages, string $model, ?array $images): string
    {
        $payload = $this->buildPayload($messages, $model, $images, false);
        $jsonData = json_encode($payload);
        $headers = $this->buildCurlHeaders();

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true, // Capturar a resposta
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new Exception("Erro no cURL (OpenAI): " . $error);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
             throw new Exception("Resposta inválida do OpenAI (não-JSON): " . $response);
        }
        
        // Formato da resposta não-streaming
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        if (isset($data['error'])) {
             throw new Exception("Erro da API OpenAI: " . $data['error']['message']);
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
        $headers = $this->buildCurlHeaders();

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => false, // Não retorne, envie direto para o callback
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 0, // Sem timeout para streaming

            // Processa a resposta em Server-Sent Events (SSE)
            CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use ($streamCallback) {
                // O stream do OpenAI envia prefixos "data: " e pode enviar [DONE]
                $events = explode("\n\n", trim($chunk));

                foreach ($events as $event) {
                    if (empty($event)) continue;
                    
                    // Remove o prefixo "data: "
                    $dataLine = trim(substr($event, 5));
                    
                    if (empty($dataLine)) continue;

                    // Verifica o sinal de fim
                    if ($dataLine === '[DONE]') {
                        return strlen($chunk); // Stream concluído
                    }
                    
                    $data = json_decode($dataLine, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("Chunk JSON inválido do OpenAI: " . $dataLine);
                        continue;
                    }

                    // Se for um erro da API
                    if (isset($data['error'])) {
                         throw new Exception("Erro da API OpenAI (stream): " . $data['error']['message']);
                    }

                    // Pega o pedaço de texto (delta)
                    if (isset($data['choices'][0]['delta']['content'])) {
                        $textChunk = $data['choices'][0]['delta']['content'];
                        // O chunk pode ser uma string vazia, não há problema
                        call_user_func($streamCallback, $textChunk);
                    }
                }
                
                return strlen($chunk); // Processamos este pedaço
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
            throw new Exception("Erro no cURL (OpenAI Stream): " . $error);
        }
    }
}