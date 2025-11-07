<?php

namespace App\Interfaces;

/**
 * Define o "contrato" que todo provedor de LLM (Ollama, OpenAI, Google, etc.)
 * deve seguir. Isso nos permite trocar de provedor sem alterar o controller.
 */
interface LLMServiceProvider
{
    /**
     * Define a chave de API para provedores que a exigem.
     *
     * @param string $apiKey A chave de API.
     */
    public function setApiKey(string $apiKey): void;

    /**
     * Gera uma resposta completa (não-streaming).
     *
     * @param array $messages O histórico de mensagens (formato OpenAI: [['role' => 'user', 'content' => '...']])
     * @param string $model O nome do modelo a ser usado.
     * @param array|null $images Uma lista de imagens em Base64 (strings).
     * @return string A resposta de texto completa do modelo.
     */
    public function generate(array $messages, string $model, ?array $images): string;

    /**
     * Gera uma resposta em modo streaming.
     *
     * @param array $messages O histórico de mensagens (formato OpenAI).
     * @param string $model O nome do modelo a ser usado.
     * @param array|null $images Uma lista de imagens em Base64 (strings).
     * @param callable $streamCallback A função que será chamada com cada "chunk" (pedaço) de texto recebido.
     */
    public function generateStream(array $messages, string $model, ?array $images, callable $streamCallback): void;
}