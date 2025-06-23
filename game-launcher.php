<?php
// MGGO Game Launcher with URL Token
header("Content-Type: text/html; charset=UTF-8");

$token = $_GET['token'] ?? '';
$gameUrl = $_GET['game_url'] ?? 'https://mggo-game.example.com';

if (empty($token)) {
    die('Token is required');
}

// Verify token via API
$ch = curl_init('https://mggapi-cn.onrender.com/api/verify-token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['token' => $token]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if (!$result || $result['status'] !== 'success' || !$result['valid']) {
    die('Invalid token');
}

$player = $result['player'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MGGO Game Launcher</title>
    <style>
        body { margin: 0; padding: 0; overflow: hidden; }
        #game-container { width: 100vw; height: 100vh; }
        iframe { width: 100%; height: 100%; border: none; }
    </style>
</head>
<body>
    <div id="game-container">
        <iframe id="game-frame" src="about:blank" allow="autoplay; fullscreen"></iframe>
    </div>
    
    <script>
    // Pass token via URL to avoid cookie issues
    const gameFrame = document.getElementById('game-frame');
    const gameUrl = '<?php echo htmlspecialchars($gameUrl); ?>';
    const token = '<?php echo htmlspecialchars($token); ?>';
    const playerId = '<?php echo htmlspecialchars($player['id']); ?>';
    
    // Build game URL with token
    const separator = gameUrl.includes('?') ? '&' : '?';
    const fullGameUrl = `${gameUrl}${separator}token=${token}&player_id=${playerId}`;
    
    // Load game
    gameFrame.src = fullGameUrl;
    
    // Handle postMessage for cross-origin communication
    window.addEventListener('message', function(e) {
        if (e.data.type === 'mggo_request_token') {
            e.source.postMessage({
                type: 'mggo_token_response',
                token: token,
                player: <?php echo json_encode($player); ?>
            }, '*');
        }
    });
    
    // iOS Safari specific handling
    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
        // Force reload if needed
        setTimeout(() => {
            if (!gameFrame.contentWindow) {
                location.reload();
            }
        }, 5000);
    }
    </script>
</body>
</html>