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

$ollamaApiUrl = "http://localhost:11434/api/generate";

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['userMessage'] ?? '';
$selectedModel = $input['modelSelect'] ?? 'gemma2:2b';
$streamEnabled = $input['streamToggle'] ?? true;

if (!$userMessage) {
    echo json_encode(["error" => "Mensagem inválida"]);
    exit;
}

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $ollamaApiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
        "model" => $selectedModel,
        "prompt" => $userMessage,
        "stream" => $streamEnabled 
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_WRITEFUNCTION => function ($ch, $chunk) {
        echo $chunk;
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
 