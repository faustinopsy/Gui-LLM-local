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
//$ollamaApiUrl = "http://localhost:11434/api/generate";
$ollamaApiUrl = "http://127.0.0.1:1234/v1/chat/completions";

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['userMessage'] ?? '';
$selectedModel = $input['modelSelect'] ?? 'gemma-3-4b-it:2';
$streamEnabled = $input['streamToggle'] ?? true;
$message= [
    "model" => $selectedModel,
    "prompt" => $userMessage,
    "stream" => $streamEnabled 
];


if($selectedModel=="gemma-3-4b-it:2"){
    $message = [
        "model"=> "gemma-3-4b-it:2",
        "messages"=> [
            [ "role"=> "system", "content"=> "você é uma inteligencia artifical que responde qualquer pergunta com base no seu conhecimento" ],
            [ "role"=> "user", "content"=> $userMessage ]
        ],
        "temperature"=> 0.7,
        "max_tokens"=> -1,
        "stream"=> $streamEnabled 
    ];
}



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
    CURLOPT_POSTFIELDS => json_encode($message),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_WRITEFUNCTION => function ($ch, $chunk) {
        //echo $chunk;
        echo str_replace("data: ", "", $chunk);
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
 