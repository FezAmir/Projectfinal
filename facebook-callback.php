<?php
session_start();
require_once 'config.php';
require_once 'vendor/autoload.php';

$fb = new Facebook\Facebook([
    'app_id' => FACEBOOK_APP_ID,
    'app_secret' => FACEBOOK_APP_SECRET,
    'default_graph_version' => 'v12.0'
]);

if (isset($_GET['code'])) {
    try {
        $helper = $fb->getRedirectLoginHelper();
        $accessToken = $helper->getAccessToken();
        
        if (!$accessToken) {
            header('Location: login.php');
            exit;
        }

        $response = $fb->get('/me?fields=id,name,email', $accessToken);
        $user = $response->getGraphUser();
        
        $email = $user->getEmail();
        $name = $user->getName();

        // Check if user exists in database
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User exists - log them in
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
        } else {
            // New user - create account
            $query = "INSERT INTO users (name, email, oauth_provider) VALUES (?, ?, 'facebook')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $name, $email);
            
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
            }
        }

        header('Location: index.html');
        exit();
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
} else {
    $_SESSION['error'] = "Facebook login failed. Please try again.";
    header('Location: login.php');
    exit();
}
?> 