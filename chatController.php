<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';

use App\Interfaces\LLMServiceProvider;
use App\Services\OllamaProvider;
use App\Services\OpenAIProvider;
use App\Services\GeminiProvider;

// --- Configuração de Erros e Ambiente ---
ini_set('display_errors', 1); // Em produção, mude para 0
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');
error_reporting(E_ALL);

set_time_limit(0); // Permite que o stream dure o tempo necessário
ini_set('memory_limit', '512M');

// --- Variáveis de Ambiente (Opcional, mas recomendado) ---
// Para carregar chaves de API de um arquivo .env, use uma biblioteca como vlucas/phpdotenv
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();


/**
 * Cria e retorna uma instância do provedor de LLM solicitado.
 * Esta é a "fábrica" que torna nosso sistema "plug and play".
 *
 * @param string $providerName O nome do provedor (ex: "ollama", "gemini")
 * @return LLMServiceProvider A instância do provedor
 * @throws Exception Se o provedor for desconhecido
 */
function getProvider(string $providerName): LLMServiceProvider
{
    
    switch ($providerName) {
        case 'ollama':
            // Conecta-se ao Ollama rodando localmente
            return new OllamaProvider("http://localhost:11434");

        case 'openai_local':
            // Conecta-se ao LM Studio ou similar
            return new OpenAIProvider("http://127.0.0.1:1234");
            
        case 'gemini':
            // Conecta-se à API do Google Gemini
            // É ALTAMENTE recomendado guardar chaves em variáveis de ambiente
            $apiKey = getenv('GEMINI_API_KEY') ?: 'SUA_CHAVE_API_GEMINI_AQUI';
            $provider = new GeminiProvider();
            $provider->setApiKey($apiKey);
            return $provider;

        case 'openai_remote':
            // Conecta-se à API real da OpenAI
            $apiKey = getenv('OPENAI_API_KEY') ?: 'SUA_CHAVE_API_OPENAI_AQUI';
            $provider = new OpenAIProvider("https://api.openai.com");
            $provider->setApiKey($apiKey);
            return $provider;

        default:
            throw new Exception("Provedor de LLM desconhecido: " . htmlspecialchars($providerName));
    }
}


try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método de requisição inválido.");
    }

    // 1. Coletar e Validar Entradas
    // Este é o novo campo que adicionaremos no front-end (Fase 5)
    $providerName = $_POST['providerSelect'] ?? 'ollama'; 
    $userMessage = $_POST['userMessage'] ?? '';
    $selectedModel = $_POST['modelSelect'] ?? 'gemma3:4b';
    $streamEnabled = ($_POST['streamToggle'] ?? 'true') === 'true';

    if (empty($userMessage) && empty($_FILES['image'])) {
         throw new Exception("Mensagem ou imagem é obrigatória.");
    }

    // 2. Obter o Provedor "Plugado"
    $provider = getProvider($providerName);

    // 3. Preparar Dados Genéricos (Histórico e Imagens)
    // TODO: Implementar um histórico de chat real na sessão
    $messages = [
        ["role" => "system", "content" => "Você é um assistente de IA prestativo e direto."],
        ["role" => "user", "content" => $userMessage]
    ];

    $imagesBase64 = [];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($_FILES['image']['tmp_name']);
        $imagesBase64[] = base64_encode($imageData);
    }

    // 4. Executar (Stream ou Não-Stream)
    
    if ($streamEnabled) {
        // --- MODO STREAMING ---
        header('Content-Type: text/plain'); // Ou text/event-stream se usar o formato SSE
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        
        // Define o callback que será executado para cada pedaço de texto
        $streamCallback = function ($chunk) {
            echo $chunk;
            // Força o PHP a enviar a saída para o navegador imediatamente
            ob_flush();
            flush();
        };
        
        // Chama o método de stream. O provedor cuida de todo o resto.
        $provider->generateStream($messages, $selectedModel, $imagesBase64, $streamCallback);

    } else {
        // --- MODO NÃO-STREAMING ---
        header('Content-Type: application/json');

        // Chama o método de geração completa
        $fullResponse = $provider->generate($messages, $selectedModel, $imagesBase64);

        // Envia uma resposta JSON limpa e consistente para o front-end
        echo json_encode(["response" => $fullResponse]);
    }

} catch (Exception $e) {
    // --- Tratamento de Erros ---
    http_response_code(500); // Erro interno do servidor
    header('Content-Type: application/json');
    echo json_encode([
        "error" => "Um erro ocorreu no servidor.",
        "details" => $e->getMessage()
    ]);
    error_log($e->getMessage()); // Loga o erro real
}

exit;