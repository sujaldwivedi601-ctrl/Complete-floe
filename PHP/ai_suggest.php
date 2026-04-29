<?php
session_start();
include "db.php";
include "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];
$college_id = $_SESSION['college_id'] ?? 0;

$api_key = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : getenv('CLAUDE_API_KEY');

if (!$api_key) {
    echo json_encode(['success' => false, 'error' => 'Claude API key not configured']);
    exit();
}

// --- 1. Get user's goal ---
$goal_stmt = mysqli_prepare($conn, "SELECT goal_text FROM goals WHERE user_id = ?");
mysqli_stmt_bind_param($goal_stmt, "i", $user_id);
mysqli_stmt_execute($goal_stmt);
$goal_result = mysqli_stmt_get_result($goal_stmt);
$goal_row = mysqli_fetch_assoc($goal_result);
$user_goal = $goal_row['goal_text'] ?? 'No goal set';
mysqli_stmt_close($goal_stmt);

// --- 2. Get today's timetable ---
$timetable_stmt = mysqli_prepare($conn, "SELECT timetable_data FROM timetables WHERE college_id = ? AND is_public = 1 ORDER BY created_at DESC LIMIT 1");
mysqli_stmt_bind_param($timetable_stmt, "i", $college_id);
mysqli_stmt_execute($timetable_stmt);
$timetable_result = mysqli_stmt_get_result($timetable_stmt);
$timetable_data = [];
if ($t_row = mysqli_fetch_assoc($timetable_result)) {
    $timetable_data = json_decode($t_row['timetable_data'], true);
}
mysqli_stmt_close($timetable_stmt);

// --- 3. Get pending tasks ---
$tasks_stmt = mysqli_prepare($conn, "SELECT subject, title, due_date, priority FROM tasks WHERE student_id = ? AND status = 'pending' ORDER BY due_date ASC LIMIT 10");
mysqli_stmt_bind_param($tasks_stmt, "i", $user_id);
mysqli_stmt_execute($tasks_stmt);
$tasks_result = mysqli_stmt_get_result($tasks_stmt);
$tasks = [];
while ($t_row = mysqli_fetch_assoc($tasks_result)) {
    $tasks[] = $t_row;
}
mysqli_stmt_close($tasks_stmt);

// --- 4. Build prompt ---
$prompt = buildPrompt($user_goal, $timetable_data, $tasks);

// --- 5. Call Claude API ---
$response = callClaudeAPI($api_key, $prompt);

if ($response) {
    echo json_encode(['success' => true, 'suggestion' => $response]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to get AI suggestion']);
}

mysqli_close($conn);

// --- Helper Functions ---

function buildPrompt($goal, $timetable, $tasks) {
    $timetable_text = "No timetable available";
    
    if (!empty($timetable['schedules'])) {
        $schedules = $timetable['schedules'];
        $days = $timetable['days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $timeSlots = $timetable['timeSlots'] ?? ['10:30-11:30', '11:30-12:30', '12:30-1:30', '1:30-2:30', '2:30-3:00', '3:00-4:00', '4:00-5:00'];
        
        $lines = [];
        foreach ($days as $dIndex => $day) {
            $line = "$day: ";
            $daySlots = [];
            for ($sem = 1; $sem <= 6; $sem++) {
                if ($schedules[$sem]) {
                    $slot = $schedules[$sem][$dIndex] ?? [];
                    foreach ($slot as $s) {
                        if ($s && $s !== 'LUNCH') {
                            $daySlots[] = strip_tags($s);
                        }
                    }
                }
            }
            $line .= implode(', ', $daySlots) ?: 'Free';
            $lines[] = $line;
        }
        $timetable_text = implode("\n", $lines);
    }

    $tasks_text = "No pending tasks";
    if (!empty($tasks)) {
        $tasks_text = [];
        foreach ($tasks as $t) {
            $tasks_text[] = "- {$t['title']} ({$t['subject']}) due {$t['due_date']} [{$t['priority']} priority]";
        }
        $tasks_text = implode("\n", $tasks_text);
    }

    return "User's Goal: $goal\n\nToday's Timetable:\n$timetable_text\n\nPending Tasks:\n$tasks_text\n\nBased on this information, create a detailed daily study plan. Be specific about what to study and when. Consider the user's goal and prioritize tasks accordingly.";
}

function callClaudeAPI($api_key, $prompt) {
    $url = 'https://api.anthropic.com/v1/messages';
    
    $headers = [
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01',
        'Content-Type: application/json'
    ];
    
    $body = [
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => 1024,
        'system' => 'You are an expert academic advisor helping students achieve their goals. Based on their goal, timetable, and pending tasks, provide a practical, actionable daily study plan. Be specific, encouraging, and prioritize high-priority tasks.',
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
        if (isset($data['error'])) {
            error_log('Claude API Error: ' . json_encode($data['error']));
        }
    }
    
    return null;
}
?>
