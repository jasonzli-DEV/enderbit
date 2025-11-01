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
        
        // Log detailed error for debugging
        error_log("Pterodactyl API Error: HTTP $httpCode - " . print_r($result, true));
        
        // Return user-friendly error
        $errorMessage = 'API request failed';
        if (isset($result['errors'][0]['detail'])) {
            $errorMessage = $result['errors'][0]['detail'];
        } elseif (isset($result['message'])) {
            $errorMessage = $result['message'];
        } elseif ($httpCode === 401 || $httpCode === 403) {
            $errorMessage = 'This action is unauthorized. Please check your Pterodactyl API key permissions.';
        }
        
        return ['success' => false, 'error' => $errorMessage, 'code' => $httpCode];
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
        
        // Get available allocation
        $allocation = self::getAvailableAllocation();
        if (!$allocation) {
            return ['success' => false, 'error' => 'No available server ports. Please contact support.'];
        }
        
        // Server creation payload (Pterodactyl API v1 format)
        $payload = [
            'name' => $serverName,
            'user' => $pterodactylUser['data']['id'],
            'nest' => self::getNestId($game),
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
                'allocations' => 1,
                'backups' => 2,
            ],
            'allocation' => [
                'default' => $allocation,
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
        // $userId is actually the user's email (from session)
        $userEmail = $userId;
        
        // Load user from main site
        $appConfig = require __DIR__ . '/config.php';
        $usersFile = $appConfig['users_file'];
        
        $user = null;
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true) ?? [];
            
            // Find user by email
            foreach ($users as $u) {
                if ($u['email'] === $userEmail) {
                    $user = $u;
                    break;
                }
            }
        }
        
        // If user not found in users.json, create basic user info from email
        if (!$user) {
            $user = [
                'email' => $userEmail,
                'username' => explode('@', $userEmail)[0], // Use part before @ as username
            ];
        }
        
        // Check if user exists in Pterodactyl
        $response = self::apiRequest('/users?filter[email]=' . urlencode($user['email']), 'GET', null, true);
        
        if ($response['success'] && !empty($response['data']['data'])) {
            return ['success' => true, 'data' => $response['data']['data'][0]['attributes']];
        }
        
        // Create user in Pterodactyl
        $payload = [
            'email' => $user['email'],
            'username' => $user['username'] ?? 'user_' . time(),
            'first_name' => $user['username'] ?? 'User',
            'last_name' => 'Account',
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
     * Get nest ID for game type
     */
    private static function getNestId($game) {
        self::init();
        return self::$config['nests'][$game] ?? 1;
    }
    
    /**
     * Get egg ID for game type
     */
    private static function getEggId($game) {
        self::init();
        return self::$config['eggs'][$game] ?? 1;
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
