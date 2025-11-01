<?php
/**
 * Quick script to check Pterodactyl nests and eggs
 * Run this on your server to see what nests/eggs are available
 */

$config = require __DIR__ . '/config.php';
$apiKey = $config['pterodactyl']['api_key'];
$panelUrl = $config['pterodactyl']['url'];

function apiRequest($url, $apiKey) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

echo "=== PTERODACTYL NESTS ===\n\n";

// Get all nests
$nestsResponse = apiRequest($panelUrl . '/api/application/nests', $apiKey);

if ($nestsResponse['code'] === 200 && isset($nestsResponse['data']['data'])) {
    foreach ($nestsResponse['data']['data'] as $nest) {
        $nestId = $nest['attributes']['id'];
        $nestName = $nest['attributes']['name'];
        
        echo "Nest ID: {$nestId} - {$nestName}\n";
        
        // Get eggs for this nest
        $eggsResponse = apiRequest($panelUrl . "/api/application/nests/{$nestId}/eggs", $apiKey);
        
        if ($eggsResponse['code'] === 200 && isset($eggsResponse['data']['data'])) {
            foreach ($eggsResponse['data']['data'] as $egg) {
                $eggId = $egg['attributes']['id'];
                $eggName = $egg['attributes']['name'];
                echo "  - Egg ID: {$eggId} - {$eggName}\n";
            }
        }
        echo "\n";
    }
} else {
    echo "Error: " . ($nestsResponse['data']['errors'][0]['detail'] ?? 'Unknown error') . "\n";
    echo "HTTP Code: " . $nestsResponse['code'] . "\n";
}

echo "\n=== SUGGESTED CODE MAPPING ===\n\n";
echo "Copy these values into pterodactyl_api.php:\n\n";
echo "private static function getNestId(\$game) {\n";
echo "    \$nestMap = [\n";
echo "        'minecraft' => 1,  // UPDATE THIS\n";
echo "        'rust' => 2,       // UPDATE THIS\n";
echo "        'valheim' => 2,    // UPDATE THIS\n";
echo "    ];\n";
echo "    return \$nestMap[\$game] ?? 1;\n";
echo "}\n\n";
echo "private static function getEggId(\$game) {\n";
echo "    \$eggMap = [\n";
echo "        'minecraft' => 1,  // UPDATE THIS\n";
echo "        'rust' => 2,       // UPDATE THIS\n";
echo "        'valheim' => 3,    // UPDATE THIS\n";
echo "    ];\n";
echo "    return \$eggMap[\$game] ?? 1;\n";
echo "}\n";
