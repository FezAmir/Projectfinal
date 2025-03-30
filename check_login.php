<?php
session_start();
header('Content-Type: application/json');

$response = [
    'loggedIn' => false,
    'name' => '',
    'profile_picture' => ''
];

if (isset($_SESSION['user_id'])) {
    $response['loggedIn'] = true;
    $response['name'] = $_SESSION['name'] ?? '';
    $response['profile_picture'] = $_SESSION['profile_picture'] ?? '';
}

echo json_encode($response);
?> 