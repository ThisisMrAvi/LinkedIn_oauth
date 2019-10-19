<?php
/*
 * Basic Site Settings and API Configuration
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'seeker');
define('DB_USER_TBL', 'seeker');

// LinkedIn API configuration
define('LIN_CLIENT_ID', '81b6f8jyt7jf1s');
define('LIN_CLIENT_SECRET', 'tKMD2789L1XwvbhI');
define('LIN_REDIRECT_URL', 'http://localhost/signup/index.php');
define('LIN_SCOPE', 'r_liteprofile r_emailaddress'); //API permissions

// Start session
if(!session_id()){
    session_start();
}

// Include the oauth client library
require_once 'LinkedIn/http.php';
require_once 'LinkedIn/oauth_client.php';