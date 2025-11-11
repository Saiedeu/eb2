<?php
// Prevent direct access
if (!defined('ALLOW_ACCESS')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden");
}

// Include our simplified Google API client
require_once __DIR__ . '/../../includes/Google/Client.php';
require_once __DIR__ . '/../../includes/Google/Service/Oauth2.php';

class GoogleOAuth {
    private $client;
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->initClient();
    }
    
    private function getOAuthSettings() {
        // Get OAuth settings from database
        $settings = $this->db->getRow(
            "SELECT * FROM oauth_settings WHERE provider = 'google' AND enabled = 1"
        );
        
        if (!$settings) {
            throw new Exception("Google OAuth is not configured");
        }
        
        return $settings;
    }
    
    private function initClient() {
        $settings = $this->getOAuthSettings();
        
        $this->client = new Google_Client([
            'client_id' => $settings['client_id'],
            'client_secret' => $settings['client_secret'],
            'redirect_uri' => SITE_URL . '/users/google-auth.php'
        ]);
        
        $this->client->setScopes(['email', 'profile']);
        $this->client->setAccessType('offline');
        $this->client->setIncludeGrantedScopes(true);
        $this->client->setApprovalPrompt('force');
    }
    
    /**
     * Generate the Google login URL
     */
    public function getAuthUrl() {
        try {
            return $this->client->createAuthUrl();
        } catch (Exception $e) {
            error_log('Error creating auth URL: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Handle the OAuth callback
     */
    public function handleCallback($code) {
        try {
            // Exchange the auth code for tokens
            $tokens = $this->client->fetchAccessTokenWithAuthCode($code);
            
            // Get the ID token from the response
            $idToken = $tokens['id_token'] ?? null;
            if (!$idToken) {
                throw new Exception('No ID token in response');
            }
            
            // Verify the ID token and get user info
            $payload = $this->client->verifyIdToken($idToken);
            if (!$payload) {
                throw new Exception('Invalid ID token');
            }
            
            // Get user info from the payload
            $userInfo = [
                'id' => $payload['sub'],
                'email' => $payload['email'],
                'name' => $payload['name'] ?? '',
                'given_name' => $payload['given_name'] ?? '',
                'family_name' => $payload['family_name'] ?? '',
                'picture' => $payload['picture'] ?? '',
                'locale' => $payload['locale'] ?? 'en',
                'verified_email' => $payload['email_verified'] ?? false
            ];
            
            return $userInfo;
            
        } catch (Exception $e) {
            error_log('Google OAuth Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Revoke the user's token and sign them out
     */
    public function revokeToken($token = null) {
        if ($token === null) {
            $token = $this->client->getAccessToken();
        }
        
        if (is_array($token) && isset($token['access_token'])) {
            $this->client->revokeToken($token['access_token']);
        }
        
        // Clear any stored tokens
        $this->client->setAccessToken(null);
    }
    
    /**
     * Check if the user is authenticated with Google
     */
    public function isAuthenticated() {
        return $this->client->getAccessToken() !== null;
    }
    
    /**
     * Get the Google client instance
     */
    public function getClient() {
        return $this->client;
    }
    
    /**
     * Update OAuth settings in the database
     */
    public function updateSettings($clientId, $clientSecret, $redirectUri = null, $enabled = true) {
        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'enabled' => $enabled ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($redirectUri !== null) {
            $data['redirect_uri'] = $redirectUri;
        }
        
        $existing = $this->db->getRow(
            "SELECT id FROM oauth_settings WHERE provider = 'google'"
        );
        
        if ($existing) {
            $this->db->update('oauth_settings', $data, 'provider = ?', ['google']);
        } else {
            $data['provider'] = 'google';
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('oauth_settings', $data);
        }
        
        // Reinitialize the client with new settings
        $this->initClient();
        
        return true;
    }
}
