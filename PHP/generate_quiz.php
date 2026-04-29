<?php
session_start();
include "db.php";
include "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$subject = $data['subject'] ?? 'General';
$task = $data['task'] ?? '';

$api_key = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : getenv('CLAUDE_API_KEY');

if (!$api_key) {
    echo json_encode(['success' => false, 'error' => 'AI not configured']);
    exit();
}

$prompt = "Generate exactly 10 short quiz questions about \"$task\" ($subject). Return ONLY a JSON array of strings, nothing else. Each question should be on its own line. Example format: [\"What is Python?\", \"What is a variable?\", ...]";

$response = callClaudeAPI($api_key, $prompt);

if ($response) {
    // Extract JSON array from response
    if (preg_match('/\[.*\]/', $response, $matches)) {
        $questions = json_decode($matches[0], true);
        if (is_array($questions) && count($questions) >= 5) {
            echo json_encode(['success' => true, 'questions' => array_slice($questions, 0, 10)]);
            exit();
        }
    }
    // Fallback: parse line by line
    $lines = explode("\n", $response);
    $questions = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^\d+[\.\)]\s*(.+)/', $line, $m)) {
            $questions[] = $m[1];
        } elseif (strlen($line) > 10) {
            $questions[] = $line;
        }
    }
    if (count($questions) > 0) {
        echo json_encode(['success' => true, 'questions' => array_slice($questions, 0, 10)]);
        exit();
    }
}

echo json_encode(['success' => false, 'error' => 'Failed to generate questions']);

function callClaudeAPI($api_key, $prompt) {
    $url = 'https://api.anthropic.com/v1/messages';

    $headers = [
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01',
        'Content-Type: application/json'
    ];

    $body = [
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => 800,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['content'])) {
            foreach ($data['content'] as $block) {
                if ($block['type'] === 'text') {
                    return $block['text'];
                }
            }
        }
    }
    return null;
}
?>
