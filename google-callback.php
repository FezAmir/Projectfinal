<?php
session_start();
require_once 'config.php';
require_once 'vendor/autoload.php';

try {
    // Initialize the Google Client
    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->addScope("email");
    $client->addScope("profile");

    // Handle the OAuth 2.0 server response
    if (isset($_GET['code'])) {
        // Exchange authorization code for access token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        // Get user information
        $oauth2 = new Google_Service_Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        // Connect to database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR (oauth_provider = 'google' AND oauth_id = ?)");
        $stmt->bind_param("ss", $userInfo->email, $userInfo->id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User exists - update their information
            $user = $result->fetch_assoc();
            $stmt = $conn->prepare("UPDATE users SET name = ?, profile_picture = ?, oauth_provider = 'google', oauth_id = ? WHERE id = ?");
            $stmt->bind_param("sssi", $userInfo->name, $userInfo->picture, $userInfo->id, $user['id']);
            $stmt->execute();
            $_SESSION['user_id'] = $user['id'];
        } else {
            // New user - create account
            $stmt = $conn->prepare("INSERT INTO users (name, email, oauth_provider, oauth_id, profile_picture) VALUES (?, ?, 'google', ?, ?)");
            $stmt->bind_param("ssss", $userInfo->name, $userInfo->email, $userInfo->id, $userInfo->picture);
            $stmt->execute();
            $_SESSION['user_id'] = $conn->insert_id;
        }

        $_SESSION['name'] = $userInfo->name;
        $_SESSION['email'] = $userInfo->email;
        $_SESSION['profile_picture'] = $userInfo->picture;
        
        // Close database connection
        $conn->close();

        // Redirect to home page
        header('Location: index.html');
        exit();
    } else {
        header('Location: login.php');
        exit();
    }
} catch (Exception $e) {
    // Log the error
    error_log('Google OAuth error: ' . $e->getMessage());
    
    // Redirect to login page with error message
    $_SESSION['error'] = "Google login failed. Please try again.";
    header('Location: login.php?error=' . urlencode('Authentication failed. Please try again.'));
    exit();
}
?> 