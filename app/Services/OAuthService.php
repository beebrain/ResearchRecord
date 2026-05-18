<?php

namespace App\Services;

class OAuthService
{
    protected $config;

    public function __construct()
    {
        // OAuth configuration with your actual URU Portal credentials
        $this->config = [
            'client_id'     => 'research_academic',                              // Your actual Client ID
            'client_secret' => 'secret',                                // Your actual Client Secret
            'redirect_uri'  => 'http://research.academic.uru.ac.th/index.php/oauth', // Your registered callback URL
            'auth_url'      => 'https://uruportal.uru.ac.th/oauth_login',    // URU OAuth login endpoint
            'token_url'     => 'https://uruportal.uru.ac.th/oauth/token',    // URU token endpoint
            'user_url'      => 'https://uruportal.uru.ac.th/me',            // URU user info endpoint
            'scope'         => 'read'
        ];
    }

    /**
     * Generate OAuth authorization URL
     */
    public function getAuthUrl($state = null): string
    {
        $params = [
            'response_type' => 'code',
            'client_id'     => $this->config['client_id'],
            'redirect_uri'  => $this->config['redirect_uri'],
            'scope'         => $this->config['scope']
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return $this->config['auth_url'] . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken($code): array
    {
        $postFields = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->config['redirect_uri'],
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret']
        ];

        // Try cURL first, fallback to file_get_contents
        if (function_exists('curl_init') && function_exists('curl_exec')) {
            log_message('debug', 'Using cURL for token request');
            return $this->makeHttpRequestWithCurl($this->config['token_url'], $postFields);
        } else {
            log_message('debug', 'cURL not available, using file_get_contents for token request');
            return $this->makeHttpRequestWithStream($this->config['token_url'], $postFields);
        }
    }

    /**
     * Get user information using access token
     */
    public function getUserInfo($accessToken): array
    {
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
            'User-Agent: EdocDocument/1.0'
        ];

        // Try cURL first, fallback to file_get_contents
        if (function_exists('curl_init') && function_exists('curl_exec')) {
            log_message('debug', 'Using cURL for user info request');
            return $this->makeGetRequestWithCurl($this->config['user_url'], $headers);
        } else {
            log_message('debug', 'cURL not available, using file_get_contents for user info');
            return $this->makeGetRequestWithStream($this->config['user_url'], $headers);
        }
    }

    /**
     * Make HTTP POST request using cURL
     */
    private function makeHttpRequestWithCurl($url, $postData): array
    {
        $ch = \curl_init();
        \curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'User-Agent: EdocDocument/1.0'
            ]
        ]);

        $response = \curl_exec($ch);
        $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (\curl_errno($ch)) {
            $error = \curl_error($ch);
            \curl_close($ch);
            throw new \Exception('cURL Error: ' . $error);
        }

        \curl_close($ch);

        log_message('debug', 'Token response HTTP code: ' . $httpCode);
        log_message('debug', 'Token response: ' . $response);

        $tokenInfo = json_decode($response, true);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to get access token. HTTP ' . $httpCode . ': ' . $response);
        }

        if (!isset($tokenInfo['access_token'])) {
            throw new \Exception('Access token not found in response: ' . $response);
        }

        return $tokenInfo;
    }

    /**
     * Make HTTP POST request using file_get_contents (fallback)
     */
    private function makeHttpRequestWithStream($url, $postData): array
    {
        $postString = http_build_query($postData);

        $contextOptions = [
            'http' => [
                'method'  => 'POST',
                'header'  => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                    'User-Agent: EdocDocument/1.0',
                    'Content-Length: ' . strlen($postString)
                ],
                'content' => $postString,
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $context = stream_context_create($contextOptions);

        log_message('debug', 'Making POST request to: ' . $url);
        log_message('debug', 'POST data: ' . $postString);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            throw new \Exception('HTTP request failed using file_get_contents: ' . ($error['message'] ?? 'Unknown error'));
        }

        // Check HTTP response code
        $httpCode = 200; // Default
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = (int)$matches[1];
                    break;
                }
            }
        }

        log_message('debug', 'Token response HTTP code: ' . $httpCode);
        log_message('debug', 'Token response: ' . $response);

        $tokenInfo = json_decode($response, true);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to get access token. HTTP ' . $httpCode . ': ' . $response);
        }

        if (!isset($tokenInfo['access_token'])) {
            throw new \Exception('Access token not found in response: ' . $response);
        }

        return $tokenInfo;
    }

    /**
     * Make HTTP GET request using cURL
     */
    private function makeGetRequestWithCurl($url, $headers): array
    {
        $ch = \curl_init();
        \curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => $headers
        ]);

        $response = \curl_exec($ch);
        $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (\curl_errno($ch)) {
            $error = \curl_error($ch);
            \curl_close($ch);
            throw new \Exception('cURL Error: ' . $error);
        }

        \curl_close($ch);

        log_message('debug', 'User info response HTTP code: ' . $httpCode);
        log_message('debug', 'User info response: ' . $response);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to get user info. HTTP ' . $httpCode . ': ' . $response);
        }

        $userInfo = json_decode($response, true);

        if (empty($userInfo)) {
            throw new \Exception('Invalid user information received');
        }

        if (!isset($userInfo['code']) || !isset($userInfo['email'])) {
            throw new \Exception('Required user information missing. Received: ' . json_encode(array_keys($userInfo)));
        }

        return $userInfo;
    }

    /**
     * Make HTTP GET request using file_get_contents (fallback)
     */
    private function makeGetRequestWithStream($url, $headers): array
    {
        $contextOptions = [
            'http' => [
                'method'  => 'GET',
                'header'  => implode("\r\n", $headers),
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $context = stream_context_create($contextOptions);

        log_message('debug', 'Making GET request to: ' . $url);
        log_message('debug', 'Headers: ' . implode(', ', $headers));

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            throw new \Exception('HTTP request failed using file_get_contents: ' . ($error['message'] ?? 'Unknown error'));
        }

        // Check HTTP response code
        $httpCode = 200; // Default
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = (int)$matches[1];
                    break;
                }
            }
        }

        log_message('debug', 'User info response HTTP code: ' . $httpCode);
        log_message('debug', 'User info response: ' . $response);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to get user info. HTTP ' . $httpCode . ': ' . $response);
        }

        $userInfo = json_decode($response, true);

        if (empty($userInfo)) {
            throw new \Exception('Invalid user information received');
        }

        if (!isset($userInfo['code']) || !isset($userInfo['email'])) {
            throw new \Exception('Required user information missing. Received: ' . json_encode(array_keys($userInfo)));
        }

        return $userInfo;
    }

    /**
     * Get configuration value
     */
    public function getConfig($key = null)
    {
        if ($key) {
            return $this->config[$key] ?? null;
        }
        return $this->config;
    }

    /**
     * Set configuration value (useful for testing)
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * Validate OAuth configuration
     */
    public function validateConfig(): array
    {
        $errors = [];

        if (empty($this->config['client_id']) || $this->config['client_id'] === 'your_client_id_here') {
            $errors[] = 'Client ID is not configured';
        }

        if (empty($this->config['client_secret']) || $this->config['client_secret'] === 'your_client_secret_here') {
            $errors[] = 'Client Secret is not configured';
        }

        if (empty($this->config['redirect_uri'])) {
            $errors[] = 'Redirect URI is not configured';
        }

        return $errors;
    }

    /**
     * Test OAuth endpoints connectivity
     */
    public function testConnectivity(): array
    {
        $results = [];

        // Test auth URL accessibility
        $contextOptions = [
            'http' => [
                'method' => 'HEAD',
                'timeout' => 10,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];

        $context = stream_context_create($contextOptions);

        // Test auth URL
        $response = @file_get_contents($this->config['auth_url'], false, $context);
        $httpCode = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = (int)$matches[1];
                    break;
                }
            }
        }

        $results['auth_url'] = [
            'url' => $this->config['auth_url'],
            'status' => $httpCode,
            'reachable' => $response !== false
        ];

        // Test token URL
        $response = @file_get_contents($this->config['token_url'], false, $context);
        $httpCode = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = (int)$matches[1];
                    break;
                }
            }
        }

        $results['token_url'] = [
            'url' => $this->config['token_url'],
            'status' => $httpCode,
            'reachable' => $response !== false
        ];

        return $results;
    }

    /**
     * Debug method to check system capabilities
     */
    public function debugSystemCapabilities(): array
    {
        return [
            'curl_available' => function_exists('curl_init') && function_exists('curl_exec'),
            'curl_init' => function_exists('curl_init'),
            'curl_exec' => function_exists('curl_exec'),
            'file_get_contents' => function_exists('file_get_contents'),
            'stream_context_create' => function_exists('stream_context_create'),
            'allow_url_fopen' => ini_get('allow_url_fopen'),
            'openssl_loaded' => extension_loaded('openssl'),
            'php_version' => phpversion(),
            'loaded_extensions' => get_loaded_extensions()
        ];
    }

    /**
     * Get additional user data from API (optional extended profile)
     * Returns additional information like research interests, position, etc.
     *
     * @param string $accessToken OAuth access token
     * @param string $loginUid User login UID
     * @return array Additional user data or empty array if not available
     */
    public function getAdditionalUserData($accessToken, $loginUid): array
    {
        try {
            // Currently, the URU Portal API may not have an additional data endpoint
            // This method is a placeholder for future API expansion
            // If an endpoint becomes available, implement it here

            // Example implementation if endpoint exists:
            // $additionalDataUrl = 'https://uruportal.uru.ac.th/api/user/' . $loginUid . '/details';
            // $headers = [
            //     'Authorization: Bearer ' . $accessToken,
            //     'Accept: application/json'
            // ];
            // $response = $this->makeGetRequestWithCurl($additionalDataUrl, $headers);
            // return $response;

            log_message('debug', 'Additional user data endpoint not implemented - returning empty array');
            return [];
        } catch (\Exception $e) {
            log_message('warning', 'Failed to fetch additional user data: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Logout from OAuth provider (if logout endpoint exists)
     * This is optional as most OAuth providers handle logout on their side
     *
     * @param string $accessToken OAuth access token (kept for compatibility)
     * @return bool Success status
     */
    public function logout($accessToken = null): bool
    {
        try {
            // Currently, the URU Portal may not have a logout endpoint
            // This method is a placeholder for future API expansion
            // If a logout endpoint becomes available, implement it here

            // Example implementation if endpoint exists:
            // if ($accessToken) {
            //     $logoutUrl = 'https://uruportal.uru.ac.th/oauth/logout';
            //     $headers = [
            //         'Authorization: Bearer ' . $accessToken,
            //         'Accept: application/json'
            //     ];
            //     $response = $this->makeGetRequestWithCurl($logoutUrl, $headers);
            //     return true;
            // }

            log_message('debug', 'OAuth logout endpoint not implemented - skipping remote logout');
            return true;
        } catch (\Exception $e) {
            log_message('warning', 'OAuth logout failed: ' . $e->getMessage());
            // Don't throw exception as local logout should still work
            return false;
        }
    }
}
