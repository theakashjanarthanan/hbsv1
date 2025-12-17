# Google OAuth Setup Guide for University Hall Booking System

## Overview
This guide will help you configure Google OAuth authentication for your university's hall booking system using the credentials provided by your senior.

## Files Modified/Created
- `google_oauth_config.php` - **CENTRALIZED** OAuth configuration file
- `google_callback.php` - OAuth callback handler
- `index.php` - Updated login page with Google OAuth button
- `GOOGLE_OAUTH_SETUP.md` - This setup guide

## Step 1: Configure OAuth Credentials

Edit `google_oauth_config.php` and replace the placeholder values with your actual university credentials:

```php
// Replace these with your actual university credentials
define('GOOGLE_CLIENT_ID', 'YOUR_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_CLIENT_SECRET_HERE');
define('GOOGLE_AUTH_URI', 'https://accounts.google.com/o/oauth2/auth');
define('GOOGLE_TOKEN_URI', 'https://oauth2.googleapis.com/token');
define('GOOGLE_REDIRECT_URI', 'YOUR_REDIRECT_URI_HERE');
```

## Step 2: Set Up Redirect URI

The redirect URI should be set to:
```
https://youruniversitydomain.com/demo/google_callback.php
```

Make sure this matches exactly what's configured in your Google Cloud Console.

## Step 3: Update Base URL

In `google_oauth_config.php`, update the BASE_URL to match your university's domain:

```php
define('BASE_URL', 'https://youruniversitydomain.com/demo');
```

## Step 4: Test the Integration

1. **Test with existing users**: Users with existing accounts should be able to log in using their Google email
2. **Test with new users**: Users without accounts will see a message to register first
3. **Verify redirects**: Ensure users are redirected to the correct dashboard based on their role

## Security Features

- **State parameter validation**: Prevents CSRF attacks
- **Secure token exchange**: Uses server-side token exchange
- **Email verification**: Only allows login for existing university accounts
- **Role-based redirects**: Redirects users to appropriate dashboards

## Troubleshooting

### Common Issues:

1. **"Invalid state parameter"**
   - Check that the state parameter in your OAuth configuration matches what's expected

2. **"No authorization code received"**
   - Verify the redirect URI is correctly configured in Google Console
   - Check that the OAuth flow is properly initiated

3. **"Failed to get access token"**
   - Verify your client ID and client secret are correct
   - Check that the redirect URI matches exactly

4. **"No account found with this Google email"**
   - This is expected behavior for users without existing accounts
   - Users need to register first using the regular registration form

## University Domain Configuration

Make sure your senior has configured the following in Google Cloud Console:

1. **Authorized domains**: Include your university domain
2. **Authorized redirect URIs**: Include your callback URL
3. **OAuth consent screen**: Configure with university information
4. **Scopes**: Ensure email and profile scopes are enabled

## Support

If you encounter any issues, check:
1. Google Cloud Console configuration
2. Server error logs
3. Network connectivity
4. Credential validity

The OAuth integration is now ready for your university's hall booking system!
