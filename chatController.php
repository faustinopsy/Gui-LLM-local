<?php
session_start();

ini_set('memory_limit', '512M');
set_time_limit(0);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');
error_reporting(E_ALL);

header('Content-Type: text/plain');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');
$message =[];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Método inválido"]);
    exit;
}

$userMessage = $_POST['userMessage'] ?? '';
$selectedModel = $_POST['modelSelect'] ?? 'gemma-3-4b-it:2';
$streamEnabled = $_POST['streamToggle'] === 'true' ? true : false;
$useOllama = $_POST['ollamaToggle'] === 'true' ? true : false;

$apiUrl = $useOllama ? "http://localhost:11434/api/generate" : "http://127.0.0.1:1234/v1/chat/completions";

$message = [];


$imageBase64 = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $imageData = file_get_contents($_FILES['image']['tmp_name']);
    $imageBase64 = base64_encode($imageData);
}

if ($useOllama) {
    $message = [
        "model" => $selectedModel,
        "prompt" => $userMessage,
        "stream" => $streamEnabled,
    ];
    if ($imageBase64) {
         $message['images'] = [$imageBase64];
    }

} else {
    $message = [
        "model" => $selectedModel,
        "messages" => [
            [ "role" => "system", "content" => "Você é uma inteligência artificial que responde qualquer pergunta com base no seu conhecimento." ],
            [ "role" => "user", "content" => $userMessage ]
        ],
        "temperature" => 0.7,
        "max_tokens" => -1,
        "stream" => $streamEnabled
    ];
    if ($imageBase64) {
        $message['messages'][1]['content'] = [
            ["type" => "text", "text" => $userMessage],
            ["type" => "image_url", "image_url" => [
                "url" => "data:image/jpeg;base64," . $imageBase64
            ]]
        ];
    }
}


if (!$userMessage) {
    echo json_encode(["error" => "Mensagem inválida"]);
    exit;
}

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $apiUrl ,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($message),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use ($useOllama) {
        if ($useOllama && $chunk !== "\n") {
            $data = json_decode($chunk, true);
            if (isset($data['response'])) {
                echo $data['response'];
            }
            if (isset($data['done']) && $data['done'] === true) {
            }
        } elseif (!$useOllama && strpos($chunk, 'data: ') === 0) {
             $data = json_decode(substr($chunk, 6), true);
             if (isset($data['choices'][0]['delta']['content'])) {
                 echo $data['choices'][0]['delta']['content'];
             }
             if (isset($data['choices'][0]['finish_reason']) && $data['choices'][0]['finish_reason'] !== null) {
             }

        } else {
             echo $chunk;
        }

        ob_flush();
        flush();
        return strlen($chunk);
    },
]);

curl_exec($curl);

if (curl_errno($curl)) {
    echo json_encode(["error" => curl_error($curl)]);
}

curl_close($curl);
exit;