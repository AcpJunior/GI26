<?php
$sessionPath = __DIR__ . '/files/sessions';
if (!is_dir($sessionPath)) { @mkdir($sessionPath, 0755, true); }
if (is_dir($sessionPath) && is_writable($sessionPath)) { session_save_path($sessionPath); }
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}
require_once 'includes/data.php';
$conf = getInstagramConfig();
$clientId = $conf['client_id'] ?? '';
$redirectUri = $conf['redirect_uri'] ?? '';
if (!$clientId || !$redirectUri) {
    header("location: settings.php?ig_error=missing_app");
    exit;
}
$state = bin2hex(random_bytes(16));
$_SESSION['ig_oauth_state'] = $state;
$scope = 'user_profile,user_media';
$authUrl = 'https://api.instagram.com/oauth/authorize'
    . '?client_id=' . urlencode($clientId)
    . '&redirect_uri=' . urlencode($redirectUri)
    . '&scope=' . urlencode($scope)
    . '&response_type=code'
    . '&state=' . urlencode($state);
header("location: $authUrl");
exit;
