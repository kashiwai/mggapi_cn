<?php
// MGGO API Provider for China - Render.com Version

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Router
switch ($uri) {
    case '/':
    case '/api':
    case '/api/':
        handleApiInfo();
        break;
        
    case '/api/verify-token':
        if ($method === 'POST') {
            handleVerifyToken();
        } else {
            sendError(405, "Method not allowed");
        }
        break;
        
    case '/api/launch/token/generate':
        if ($method === 'POST') {
            handleGenerateToken();
        } else {
            sendError(405, "Method not allowed");
        }
        break;
        
    default:
        sendError(404, "Endpoint not found");
}

function handleApiInfo() {
    echo json_encode([
        "status" => "success",
        "message" => "MGGO API Provider - China Edition",
        "version" => "1.0.0",
        "server_time" => date('c'),
        "endpoints" => [
            "GET /api/" => "API information",
            "POST /api/verify-token" => "Token verification",
            "POST /api/launch/token/generate" => "Generate game token"
        ]
    ]);
}

function handleVerifyToken() {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? '';
    
    if (empty($token)) {
        sendError(400, "Token is required");
        return;
    }
    
    // Simple token validation
    if (strpos($token, 'mggo_') === 0) {
        $parts = explode('_', $token);
        
        echo json_encode([
            "status" => "success",
            "valid" => true,
            "player" => [
                "id" => $parts[2] ?? 'test_001',
                "username" => 'Player_' . ($parts[2] ?? 'test'),
                "balance" => 10000,
                "currency" => "CNY",
                "operator" => $parts[1] ?? 'VP_TEST'
            ],
            "session" => [
                "id" => uniqid('session_'),
                "expires_at" => date('c', time() + 3600)
            ]
        ]);
    } else {
        sendError(401, "Invalid token");
    }
}

function handleGenerateToken() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $operator = $input['operator'] ?? 'VP_TEST';
    $userId = $input['user_id'] ?? 'test_001';
    $username = $input['username'] ?? 'TestUser';
    $currency = $input['currency'] ?? 'CNY';
    $timestamp = $input['timestamp'] ?? time();
    
    // Generate token
    $token = 'mggo_' . $operator . '_' . $userId . '_' . time();
    
    echo json_encode([
        "status" => "success",
        "data" => [
            "token" => $token,
            "game_url" => "https://" . ($_SERVER['HTTP_HOST'] ?? 'mggo-api.onrender.com') . "/game?token=" . $token,
            "expires_in" => 3600,
            "player" => [
                "id" => $userId,
                "username" => $username,
                "currency" => $currency,
                "operator" => $operator
            ]
        ]
    ]);
}

function sendError($code, $message) {
    http_response_code($code);
    echo json_encode([
        "status" => "error",
        "message" => $message
    ]);
}