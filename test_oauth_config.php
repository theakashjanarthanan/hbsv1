<?php
/**
 * OAuth Configuration Test File
 * 
 * This file helps you test if your OAuth configuration is working correctly.
 * Access this file in your browser to see the configuration status.
 * 
 * URL: http://localhost/demo/test_oauth_config.php
 */

// Include the OAuth configuration
include 'google_oauth_config.php';

// Enable debug mode for this test
define('DEBUG_MODE', true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Configuration Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-card { margin: 20px 0; }
        .status-success { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-danger { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Google OAuth Configuration Test</h3>
                    </div>
                    <div class="card-body">
                        
                        <?php
                        // Get configuration status
                        $configStatus = getOAuthConfigStatus();
                        ?>
                        
                        <div class="status-card">
                            <h5>Configuration Status</h5>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Client ID</span>
                                    <span class="<?php echo $configStatus['client_id_configured'] ? 'status-success' : 'status-danger'; ?>">
                                        <?php echo $configStatus['client_id_configured'] ? '✓ Configured' : '✗ Not Configured'; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Client Secret</span>
                                    <span class="<?php echo $configStatus['client_secret_configured'] ? 'status-success' : 'status-danger'; ?>">
                                        <?php echo $configStatus['client_secret_configured'] ? '✓ Configured' : '✗ Not Configured'; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Redirect URI</span>
                                    <span class="<?php echo $configStatus['redirect_uri_configured'] ? 'status-success' : 'status-danger'; ?>">
                                        <?php echo $configStatus['redirect_uri_configured'] ? '✓ Configured' : '✗ Not Configured'; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Base URL</span>
                                    <span class="<?php echo $configStatus['base_url_configured'] ? 'status-success' : 'status-danger'; ?>">
                                        <?php echo $configStatus['base_url_configured'] ? '✓ Configured' : '✗ Not Configured'; ?>
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <div class="status-card">
                            <h5>OAuth URLs</h5>
                            <div class="alert alert-info">
                                <strong>Authorization URL:</strong><br>
                                <small><code><?php echo getGoogleAuthUrl(); ?></code></small>
                            </div>
                        </div>

                        <div class="status-card">
                            <h5>Test OAuth Flow</h5>
                            <p>Click the button below to test the OAuth flow:</p>
                            <a href="<?php echo getGoogleAuthUrl(); ?>" class="btn btn-danger">
                                <i class="bi bi-google me-2"></i>
                                Test Google OAuth Login
                            </a>
                        </div>

                        <div class="status-card">
                            <h5>Configuration Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Project ID:</strong><br>
                                    <code><?php echo $configStatus['project_id']; ?></code>
                                </div>
                                <div class="col-md-6">
                                    <strong>Environment:</strong><br>
                                    <span class="badge bg-info"><?php echo $configStatus['environment']; ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if ($configStatus['fully_configured']): ?>
                        <div class="alert alert-success">
                            <h5 class="alert-heading">✓ Configuration Complete!</h5>
                            <p>Your OAuth configuration is properly set up and ready to use.</p>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">⚠ Configuration Incomplete</h5>
                            <p>Please check the configuration items marked as not configured.</p>
                        </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <a href="index.php" class="btn btn-primary">Go to Login Page</a>
                            <a href="register.php" class="btn btn-success">Go to Register Page</a>
                            <a href="google_oauth_config.php" class="btn btn-outline-secondary">View Config File</a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
