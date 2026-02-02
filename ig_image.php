<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/includes/data.php';

// Recebe em Base64 e decodifica de volta para URL
$u = isset($_GET['u']) ? base64_decode($_GET['u']) : '';

if (!$u) { http_response_code(400); exit; }

$parsed = parse_url($u);
if (!$parsed || !isset($parsed['host'])) { http_response_code(400); exit; }

$host = strtolower($parsed['host']);
// LISTA EXPANDIDA DE DOMÍNIOS PERMITIDOS
if (!preg_match('/(instagram\.com|cdninstagram\.com|fbcdn\.net|fbcdn\.com|akamaihd\.net)$/', $host)) {
    if (isset($_GET['debug'])) { echo "Invalid host: $host"; }
    http_response_code(403);
    exit;
}

while (ob_get_level()) ob_end_clean();

$cacheDir = __DIR__ . '/files/ig_img_cache';
if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0755, true); }

$key = sha1($u);
$bin = $cacheDir . '/' . $key . '.bin';
$meta = $cacheDir . '/' . $key . '.txt';

if (!isset($_GET['refresh']) && file_exists($bin) && file_exists($meta)) {
    $ctype = trim(@file_get_contents($meta));
    if (!$ctype) $ctype = 'image/jpeg';
    header('Content-Type: ' . $ctype);
    header('Cache-Control: public, max-age=86400'); // Aumentado para 24h
    readfile($bin);
    exit;
}

$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $u,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => ['User-Agent: ' . $ua],
]);

$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($code === 200 && $resp) {
    if (!$ctype) $ctype = 'image/jpeg';
    @file_put_contents($bin, $resp);
    @file_put_contents($meta, $ctype);
    header('Content-Type: ' . $ctype);
    header('Cache-Control: public, max-age=86400');
    echo $resp;
} else {
    if (isset($_GET['debug'])) echo "Erro ao baixar: Código $code";
    http_response_code(502);
}