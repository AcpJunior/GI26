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
$clientSecret = $conf['client_secret'] ?? '';
$state = isset($_GET['state']) ? $_GET['state'] : '';
$code = isset($_GET['code']) ? $_GET['code'] : '';
if (!$clientId || !$clientSecret || !$redirectUri) {
    header("location: settings.php?ig_error=missing_app");
    exit;
}
if (!$code || !$state || !isset($_SESSION['ig_oauth_state']) || $state !== $_SESSION['ig_oauth_state']) {
    header("location: settings.php?ig_error=invalid_state");
    exit;
}
unset($_SESSION['ig_oauth_state']);

$ch = curl_init('https://api.instagram.com/oauth/access_token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_POSTFIELDS => [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirectUri,
        'code' => $code
    ],
]);
$resp = curl_exec($ch);
curl_close($ch);
if (!$resp) {
    header("location: settings.php?ig_error=token_exchange_failed");
    exit;
}
$data = json_decode($resp, true);
if (!is_array($data) || empty($data['access_token'])) {
    header("location: settings.php?ig_error=token_invalid");
    exit;
}
$token = $data['access_token'];

// Fetch username
$ch2 = curl_init('https://graph.instagram.com/me?fields=username&access_token=' . urlencode($token));
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$resp2 = curl_exec($ch2);
curl_close($ch2);
$uinfo = json_decode($resp2, true);
$username = isset($uinfo['username']) ? $uinfo['username'] : '';

// Save
salvarInstagramConfig($username, $token, $conf['post_limit'] ?? 6);
// Also store connected_username for display
global $pdo;
if (isset($pdo) && ($pdo instanceof PDO)) {
    try {
        $stmt = $pdo->query("SELECT id FROM instagram_config ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['id'])) {
            $upd = $pdo->prepare("UPDATE instagram_config SET connected_username = ? WHERE id = ?");
            $upd->execute([$username, intval($row['id'])]);
        }
    } catch (Exception $e) {}
}

header("location: settings.php?ig_connected=1");
exit;
