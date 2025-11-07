<?php

namespace App\Services;

use App\Interfaces\LLMServiceProvider;
use Exception;

/**
 * Provedor de LLM para se conectar à API Google Gemini (Generative Language API).
 * Implementa a interface LLMServiceProvider.
 */
class GeminiProvider implements LLMServiceProvider
{
    private string $apiUrlBase = "https://generativelanguage.googleapis.com/v1beta/models";
    private ?string $apiKey = null;

    public function __construct()
    {
        // O construtor está vazio, pois a URL é construída dinamicamente com o nome do modelo.
    }

    /**
     * Define a chave de API (obrigatória para o Google).
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Constrói o payload específico do Google Gemini (formato 'contents').
     * Traduz de ['role' => '...', 'content' => '...']
     * para         ['role' => '...', 'parts' => [['text' => '...']]]
     */
    private function buildPayload(array $messages, ?array $images): array
    {
        $geminiContents = [];
        $systemPrompt = null;

        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];

            // 1. Armazena o prompt do sistema
            if ($role === 'system') {
                $systemPrompt = $content;
                continue;
            }

            // 2. Traduz as roles (OpenAI 'assistant' -> Gemini 'model')
            $geminiRole = ($role === 'user') ? 'user' : 'model';

            // 3. Anexa o prompt do sistema à primeira mensagem do usuário
            if ($systemPrompt && $geminiRole === 'user') {
                $content = $systemPrompt . "\n\n" . $content;
                $systemPrompt = null; // Garante que seja usado apenas uma vez
            }

            // 4. Cria a estrutura 'parts'
            $geminiContents[] = [
                'role' => $geminiRole,
                'parts' => [['text' => $content]] // Começa com a parte de texto
            ];
        }

        // 5. Adiciona imagens (se houver) à última mensagem do usuário
        if (!empty($images)) {
            // Encontra a chave da última mensagem 'user'
            $lastKey = null;
            foreach (array_reverse($geminiContents, true) as $key => $content) {
                if ($content['role'] === 'user') {
                    $lastKey = $key;
                    break;
                }
            }

            if ($lastKey !== null) {
                foreach ($images as $base64Image) {
                    // Adiciona as partes da imagem ao 'parts' da última mensagem do usuário
                    $geminiContents[$lastKey]['parts'][] = [
                        'inline_data' => [
                            // Assumindo JPEG. O ideal seria detectar o mime-type no upload.
                            'mime_type' => 'image/jpeg',
                            'data' => $base64Image
                        ]
                    ];
                }
            }
        }

        return ['contents' => $geminiContents];
    }

    /**
     * Constrói a URL completa da API, incluindo o método e a chave.
     */
    private function buildApiUrl(string $model, string $method): string
    {
        if (!$this->apiKey) {
            throw new Exception("A chave de API do Google (Gemini) não foi definida.");
        }
        // Ex: .../gemini-1.5-pro-latest:generateContent?key=SUA_CHAVE
        return "{$this->apiUrlBase}/{$model}:{$method}?key={$this->apiKey}";
    }

    /**
     * Gera uma resposta completa (não-streaming).
     */
    public function generate(array $messages, string $model, ?array $images): string
    {
        $url = $this->buildApiUrl($model, 'generateContent');
        $payload = $this->buildPayload($messages, $images);
        $jsonData = json_encode($payload);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
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
            throw new Exception("Erro no cURL (Gemini): " . $error);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
             throw new Exception("Resposta inválida do Gemini (não-JSON): " . $response);
        }

        // Caminho da resposta de texto
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        if (isset($data['error'])) {
             throw new Exception("Erro da API Gemini: " . $data['error']['message']);
        }

        return ""; // Retorno padrão
    }

    /**
     * Gera uma resposta em modo streaming.
     */
    public function generateStream(array $messages, string $model, ?array $images, callable $streamCallback): void
    {
        $url = $this->buildApiUrl($model, 'streamGenerateContent');
        $payload = $this->buildPayload($messages, $images);
        $jsonData = json_encode($payload);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 0,

            // A API de streaming do Gemini envia chunks de JSON delimitados por linha
            CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use ($streamCallback) {
                // A API pode enviar múltiplos JSONs em um único chunk
                $lines = explode("\n", trim($chunk));
                
                foreach ($lines as $line) {
                    if (empty($line)) continue;
                    
                    // A API do Gemini às vezes envia colchetes [ ] no stream
                    $line = trim($line, "[],\r\n ");

                    $data = json_decode($line, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("Chunk JSON inválido do Gemini: " . $line);
                        continue;
                    }

                    if (isset($data['error'])) {
                        throw new Exception("Erro da API Gemini (stream): " . $data['error']['message']);
                    }
                    
                    // Extrai o texto
                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        $textChunk = $data['candidates'][0]['content']['parts'][0]['text'];
                        call_user_func($streamCallback, $textChunk);
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
            throw new Exception("Erro no cURL (Gemini Stream): " . $error);
        }
    }
}