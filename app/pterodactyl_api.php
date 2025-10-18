<?php
/**
 * Pterodactyl Panel API Integration
 * Handles server creation, management, and control via panel.enderbit.com
 */

class PterodactylAPI {
    private static $config;
    
    public static function init() {
        if (!self::$config) {
            $appConfig = require __DIR__ . '/config.php';
            self::$config = $appConfig['pterodactyl'];
        }
    }
    
    /**
     * Make API request to Pterodactyl
     */
    private static function apiRequest($endpoint, $method = 'GET', $data = null, $useAdminKey = false) {
        self::init();
        
        $apiKey = $useAdminKey ? self::$config['admin_api_key'] : self::$config['api_key'];
        $url = rtrim(self::$config['url'], '/') . '/api/application' . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $result];
        }
        
        return ['success' => false, 'error' => $result['errors'][0]['detail'] ?? 'API request failed', 'code' => $httpCode];
    }
    
    /**
     * Create a new server
     */
    public static function createServer($userId, $serverName, $game, $plan) {
        $appConfig = require __DIR__ . '/config.php';
        $pricing = $appConfig['server_pricing'][$game][$plan] ?? null;
        
        if (!$pricing) {
            return ['success' => false, 'error' => 'Invalid game or plan'];
        }
        
        // Get or create user in Pterodactyl
        $pterodactylUser = self::getOrCreateUser($userId);
        if (!$pterodactylUser['success']) {
            return $pterodactylUser;
        }
        
        // Server creation payload
        $payload = [
            'name' => $serverName,
            'user' => $pterodactylUser['data']['id'],
            'egg' => self::getEggId($game),
            'docker_image' => self::getDockerImage($game),
            'startup' => self::getStartupCommand($game),
            'environment' => self::getEnvironment($game),
            'limits' => [
                'memory' => $pricing['ram'],
                'swap' => 0,
                'disk' => $pricing['disk'],
                'io' => 500,
                'cpu' => $pricing['cpu'],
            ],
            'feature_limits' => [
                'databases' => 1,
                'backups' => 2,
            ],
            'allocation' => [
                'default' => self::getAvailableAllocation(),
            ],
        ];
        
        $result = self::apiRequest('/servers', 'POST', $payload, true);
        
        if ($result['success']) {
            // Save server to local database
            self::saveServerToDatabase($userId, $result['data']['attributes']['id'], $serverName, $game, $plan);
        }
        
        return $result;
    }
    
    /**
     * Get or create Pterodactyl user
     */
    private static function getOrCreateUser($userId) {
        // Load user from main site
        $appConfig = require __DIR__ . '/config.php';
        $usersFile = $appConfig['users_file'];
        $users = json_decode(file_get_contents($usersFile), true) ?? [];
        
        $user = null;
        foreach ($users as $u) {
            if ($u['id'] === $userId) {
                $user = $u;
                break;
            }
        }
        
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Check if user exists in Pterodactyl
        $response = self::apiRequest('/users?filter[email]=' . urlencode($user['email']), 'GET', null, true);
        
        if ($response['success'] && !empty($response['data']['data'])) {
            return ['success' => true, 'data' => $response['data']['data'][0]['attributes']];
        }
        
        // Create user in Pterodactyl
        $payload = [
            'email' => $user['email'],
            'username' => $user['username'] ?? 'user_' . $userId,
            'first_name' => $user['username'] ?? 'User',
            'last_name' => $userId,
        ];
        
        return self::apiRequest('/users', 'POST', $payload, true);
    }
    
    /**
     * Suspend a server
     */
    public static function suspendServer($serverId) {
        return self::apiRequest('/servers/' . $serverId . '/suspend', 'POST', null, true);
    }
    
    /**
     * Unsuspend a server
     */
    public static function unsuspendServer($serverId) {
        return self::apiRequest('/servers/' . $serverId . '/unsuspend', 'POST', null, true);
    }
    
    /**
     * Delete a server
     */
    public static function deleteServer($serverId) {
        return self::apiRequest('/servers/' . $serverId, 'DELETE', null, true);
    }
    
    /**
     * Get server details
     */
    public static function getServer($serverId) {
        return self::apiRequest('/servers/' . $serverId, 'GET', null, true);
    }
    
    /**
     * Save server to local database
     */
    private static function saveServerToDatabase($userId, $pterodactylId, $name, $game, $plan) {
        require_once __DIR__ . '/credits.php';
        $appConfig = require __DIR__ . '/config.php';
        $serversFile = $appConfig['servers_file'];
        $servers = json_decode(file_get_contents($serversFile), true) ?? [];
        
        $servers[] = [
            'id' => uniqid('srv_', true),
            'user_id' => $userId,
            'pterodactyl_id' => $pterodactylId,
            'name' => $name,
            'game' => $game,
            'plan' => $plan,
            'status' => 'active',
            'cost_per_hour' => EnderBitCredits::calculateHourlyCost($game, $plan),
            'created_at' => time(),
            'last_billed' => time(),
        ];
        
        file_put_contents($serversFile, json_encode($servers, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get egg ID for game type
     */
    private static function getEggId($game) {
        $eggMap = [
            'minecraft' => 1,
            'rust' => 2,
            'valheim' => 3,
            'terraria' => 4,
            'ark' => 5,
        ];
        return $eggMap[$game] ?? 1;
    }
    
    /**
     * Get Docker image for game
     */
    private static function getDockerImage($game) {
        $imageMap = [
            'minecraft' => 'ghcr.io/pterodactyl/yolks:java_17',
            'rust' => 'ghcr.io/pterodactyl/games:rust',
            'valheim' => 'ghcr.io/pterodactyl/games:valheim',
            'terraria' => 'ghcr.io/pterodactyl/games:terraria',
            'ark' => 'ghcr.io/pterodactyl/games:ark',
        ];
        return $imageMap[$game] ?? 'ghcr.io/pterodactyl/yolks:java_17';
    }
    
    /**
     * Get startup command for game
     */
    private static function getStartupCommand($game) {
        $commandMap = [
            'minecraft' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar server.jar',
            'rust' => './RustDedicated -batchmode +server.port {{SERVER_PORT}}',
            'valheim' => './valheim_server.x86_64 -name "{{SERVER_NAME}}" -port {{SERVER_PORT}}',
        ];
        return $commandMap[$game] ?? 'java -jar server.jar';
    }
    
    /**
     * Get environment variables for game
     */
    private static function getEnvironment($game) {
        return [
            'SERVER_JARFILE' => 'server.jar',
            'VANILLA_VERSION' => 'latest',
        ];
    }
    
    /**
     * Get available allocation (simplified - you'll need to implement proper allocation logic)
     */
    private static function getAvailableAllocation() {
        return 1; // Default allocation ID
    }
}
