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
$questions = $data['questions'] ?? [];
$answers = $data['answers'] ?? [];
$subject = $data['subject'] ?? '';
$task = $data['task'] ?? '';

$api_key = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : getenv('CLAUDE_API_KEY');

if (!$api_key || empty($questions) || empty($answers)) {
    echo json_encode(['success' => false, 'pass' => false]);
    exit();
}

// Build the quiz content for AI evaluation
$quiz_content = "Task: $task ($subject)\n\n";
for ($i = 0; $i < count($questions) && $i < count($answers); $i++) {
    $quiz_content .= "Q" . ($i + 1) . ": " . $questions[$i] . "\n";
    $quiz_content .= "A" . ($i + 1) . ": " . $answers[$i] . "\n\n";
}

$prompt = "You are a strict but friendly teacher. Evaluate the student's answers to these quiz questions about \"$task\" ($subject).\n\n" . $quiz_content . "\n\nCount how many answers are CORRECT or PARTIALLY CORRECT (at least 50% accurate). If they got 7 or more correct (out of 10), respond with exactly: PASS. Otherwise respond with exactly: FAIL. Do not add any other text.";

$response = callClaudeAPI($api_key, $prompt);

$pass = (strpos($response, 'PASS') !== false);

echo json_encode(['success' => true, 'pass' => $pass, 'ai_response' => $response]);

function callClaudeAPI($api_key, $prompt) {
    $url = 'https://api.anthropic.com/v1/messages';

    $headers = [
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01',
        'Content-Type: application/json'
    ];

    $body = [
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => 100,
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