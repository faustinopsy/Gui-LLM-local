<?php

namespace App\Services;

use App\Interfaces\LLMServiceProvider;
use Exception;

class OllamaProvider implements LLMServiceProvider
{
    private string $baseUrl;

    public function __construct(string $apiUrl = "http://localhost:11434")
    {
        $this->baseUrl = rtrim($apiUrl, '/');
    }

    public function setApiKey(string $apiKey): void
    {
        // Ollama local não usa chave
    }

    /**
     * Cria payload no formato do endpoint /api/chat
     */
    private function buildChatPayload(array $messages, string $model, ?array $images, bool $stream): array
    {
        $payload = [
            "model" => $model,
            "messages" => $messages,
            "stream" => $stream
        ];

        if (!empty($images)) {
            $payload["images"] = $images;
        }

        return $payload;
    }

    /**
     * Geração normal (sem streaming)
     */
    public function generate(array $messages, string $model, ?array $images): string
    {
        $payload = $this->buildChatPayload($messages, $model, $images, false);
        $jsonData = json_encode($payload);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "{$this->baseUrl}/api/chat",
            CURLOPT_RETURNTRANSFER => true,
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

        // A resposta vem em $data['message']['content']
        return $data['message']['content'] ?? '';
    }

    /**
     * Geração com streaming
     */
    public function generateStream(array $messages, string $model, ?array $images, callable $streamCallback): void
    {
        $payload = $this->buildChatPayload($messages, $model, $images, true);
        $jsonData = json_encode($payload);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "{$this->baseUrl}/api/chat",
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 0,
            CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use ($streamCallback) {
                $lines = explode("\n", trim($chunk));
                foreach ($lines as $line) {
                    if (empty($line)) continue;

                    $data = json_decode($line, true);
                    if (json_last_error() !== JSON_ERROR_NONE) continue;

                    if (isset($data['message']['content'])) {
                        call_user_func($streamCallback, $data['message']['content']);
                    }
                    if (isset($data['error'])) {
                        throw new Exception("Erro da API Ollama (stream): " . $data['error']);
                    }
                }
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
