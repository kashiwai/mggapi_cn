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
        
    case '/game/launch':
        handleGameLaunch();
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

function handleGameLaunch() {
    $token = $_GET['token'] ?? '';
    
    if (empty($token)) {
        die('Token is required');
    }
    
    // Simple HTML that passes token via URL
    header("Content-Type: text/html; charset=UTF-8");
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MGGO Game</title>
    <style>
        body { margin: 0; overflow: hidden; font-family: Arial, sans-serif; }
        #loading { text-align: center; padding: 50px; }
    </style>
</head>
<body>
    <div id="loading">
        <h2>Loading MGGO Game...</h2>
        <p>Token: <?php echo htmlspecialchars(substr($token, 0, 20)); ?>...</p>
    </div>
    
    <script>
    // Simulate game loading with token
    const token = '<?php echo htmlspecialchars($token); ?>';
    
    // Store token in sessionStorage (not cookie)
    sessionStorage.setItem('mggo_token', token);
    
    // For parent window communication
    if (window.parent !== window) {
        window.parent.postMessage({
            type: 'mggo_game_loaded',
            token: token
        }, '*');
    }
    
    // Show game loaded message after 2 seconds
    setTimeout(() => {
        document.getElementById('loading').innerHTML = `
            <h2>✅ MGGO Game Loaded</h2>
            <p>Token authenticated via URL</p>
            <p>No cookies required!</p>
            <p>Player can now play the game.</p>
        `;
    }, 2000);
    </script>
</body>
</html>
    <?php
    exit();
}