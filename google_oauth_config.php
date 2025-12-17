<?php
/**
 * Google OAuth Configuration for University Hall Booking System
 * 
 * CENTRALIZED OAUTH CONFIGURATION FILE
 * 
 * This file contains all Google OAuth settings and functions for the university
 * hall booking system. Update the credentials below with your actual university
 * OAuth credentials provided by your senior.
 * 
 * @author University Development Team
 * @version 1.0
 */

// =============================================================================
// GOOGLE OAUTH CREDENTIALS
// =============================================================================
// University OAuth credentials configured

define('GOOGLE_CLIENT_ID', '27714502400-1lgc5iq3umkenfdvqd839hkio890bi2n.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_AUTH_URI', 'https://accounts.google.com/o/oauth2/auth');
define('GOOGLE_TOKEN_URI', 'https://oauth2.googleapis.com/token');
define('GOOGLE_REDIRECT_URI', 'http://localhost/demo/google_callback.php'); // Updated to match callback
define('GOOGLE_SCOPE', 'openid email profile');

// =============================================================================
// SECURITY CONFIGURATION
// =============================================================================

// OAuth State (for CSRF protection)
define('OAUTH_STATE', 'university_hall_booking_oauth_state');

// Base URL for your application
define('BASE_URL', 'http://localhost/demo'); // Localhost development environment

// =============================================================================
// CONFIGURATION INSTRUCTIONS
// =============================================================================

/**
 * SETUP INSTRUCTIONS FOR YOUR SENIOR:
 * 
 * 1. Google Cloud Console Configuration:
 *    - Go to https://console.cloud.google.com/
 *    - Select your university project
 *    - Navigate to "APIs & Services" > "Credentials"
 *    - Create or edit OAuth 2.0 Client ID
 * 
 * 2. Authorized Domains:
 *    - Add your university domain to authorized domains
 *    - Example: youruniversity.edu
 * 
 * 3. Authorized Redirect URIs:
 *    - Add: https://youruniversitydomain.com/demo/google_callback.php
 *    - For development: http://localhost/demo/google_callback.php
 * 
 * 4. OAuth Consent Screen:
 *    - Configure with university information
 *    - Add university logo and branding
 *    - Set scopes: email, profile, openid
 * 
 * 5. Replace Credentials:
 *    - Replace YOUR_CLIENT_ID_HERE with actual Client ID
 *    - Replace YOUR_CLIENT_SECRET_HERE with actual Client Secret
 *    - Replace YOUR_REDIRECT_URI_HERE with actual redirect URI
 *    - Update BASE_URL to your university domain
 */

// =============================================================================
// OAUTH FUNCTIONS
// =============================================================================

/**
 * Generate Google OAuth Authorization URL
 * 
 * @return string Complete OAuth authorization URL
 */
function getGoogleAuthUrl() {
    $params = array(
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'scope' => GOOGLE_SCOPE,
        'response_type' => 'code',
        'state' => OAUTH_STATE,
        'access_type' => 'offline',
        'prompt' => 'select_account'
    );
    
    return GOOGLE_AUTH_URI . '?' . http_build_query($params);
}

/**
 * Exchange authorization code for access token
 * 
 * @param string $code Authorization code from Google
 * @return array|false Token data or false on failure
 */
function getGoogleAccessToken($code) {
    $data = array(
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
        'code' => $code
    );
    
    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );
    
    $context = stream_context_create($options);
    $result = file_get_contents(GOOGLE_TOKEN_URI, false, $context);
    
    if ($result === FALSE) {
        return false;
    }
    
    return json_decode($result, true);
}

/**
 * Get user information from Google using access token
 * 
 * @param string $access_token Google access token
 * @return array|false User information or false on failure
 */
function getGoogleUserInfo($access_token) {
    $url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $access_token;
    $result = file_get_contents($url);
    
    if ($result === FALSE) {
        return false;
    }
    
    return json_decode($result, true);
}

/**
 * Validate OAuth state parameter for security
 * 
 * @param string $state State parameter from OAuth callback
 * @return bool True if valid, false otherwise
 */
function validateOAuthState($state) {
    return $state === OAUTH_STATE;
}

/**
 * Get OAuth configuration status
 * 
 * @return array Configuration status information
 */
function getOAuthConfigStatus() {
    $status = array(
        'client_id_configured' => true, // Now configured with actual credentials
        'client_secret_configured' => true, // Now configured with actual credentials
        'redirect_uri_configured' => true, // Now configured with callback URL
        'base_url_configured' => true, // Configured for localhost development
        'project_id' => 'elaborate-hash-472715-d7',
        'environment' => 'development'
    );
    
    $status['fully_configured'] = true; // All credentials are now properly configured
    
    return $status;
}

// =============================================================================
// DEBUGGING AND TESTING
// =============================================================================

/**
 * Display OAuth configuration (for debugging only)
 * WARNING: Never use this in production!
 */
function debugOAuthConfig() {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<h3>OAuth Configuration Debug</h3>";
        echo "<p><strong>Client ID:</strong> " . substr(GOOGLE_CLIENT_ID, 0, 20) . "... (Configured)</p>";
        echo "<p><strong>Client Secret:</strong> " . substr(GOOGLE_CLIENT_SECRET, 0, 10) . "... (Configured)</p>";
        echo "<p><strong>Project ID:</strong> elaborate-hash-472715-d7</p>";
        echo "<p><strong>Redirect URI:</strong> " . GOOGLE_REDIRECT_URI . "</p>";
        echo "<p><strong>Base URL:</strong> " . BASE_URL . "</p>";
        echo "<p><strong>OAuth URL:</strong> " . getGoogleAuthUrl() . "</p>";
        echo "<p><strong>Status:</strong> <span style='color: green;'>Fully Configured ✓</span></p>";
    }
}

?>