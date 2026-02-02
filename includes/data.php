<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = dirname(__DIR__) . '/files/sessions';
    if (!is_dir($sessionPath)) { @mkdir($sessionPath, 0755, true); }
    if (is_dir($sessionPath) && is_writable($sessionPath)) { session_save_path($sessionPath); }
    session_start();
}

require_once 'db_connect.php';

function pdoDisponivel() {
    global $pdo;
    return isset($pdo) && ($pdo instanceof PDO);
}

function getSiteConfig() {
    $defaultConfig = ['matriculas_abertas' => false, 'rematriculas_abertas' => false];
    if (pdoDisponivel()) {
        try {
            global $pdo;
            $stmt = $pdo->query("SELECT cfg_key, cfg_value FROM site_config");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $map = [];
            foreach ($rows as $r) {
                $map[$r['cfg_key']] = $r['cfg_value'];
            }
            $cfg = $defaultConfig;
            if (isset($map['matriculas_abertas'])) {
                $cfg['matriculas_abertas'] = intval($map['matriculas_abertas']) === 1;
            }
            if (isset($map['rematriculas_abertas'])) {
                $cfg['rematriculas_abertas'] = intval($map['rematriculas_abertas']) === 1;
            }
            return $cfg;
        } catch (PDOException $e) {
        }
    }
    $configFile = dirname(__DIR__) . '/files/site_config.json';
    if (!file_exists($configFile)) {
        return $defaultConfig;
    }
    $json = @file_get_contents($configFile);
    $config = @json_decode($json, true);
    if (!is_array($config)) {
        return $defaultConfig;
    }
    return array_merge($defaultConfig, $config);
}

function updateSiteConfig($key, $value) {
    if (pdoDisponivel()) {
        try {
            global $pdo;
            $val = $value;
            if (is_bool($val)) {
                $val = $val ? '1' : '0';
            }
            $stmt = $pdo->prepare("INSERT INTO site_config (cfg_key, cfg_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE cfg_value = VALUES(cfg_value)");
            return $stmt->execute([$key, strval($val)]);
        } catch (PDOException $e) {
            return false;
        }
    }
    $configFile = dirname(__DIR__) . '/files/site_config.json';
    $config = getSiteConfig();
    $config[$key] = $value;
    $dir = dirname($configFile);
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    return @file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) !== false;
}

function getTurmas() {
    global $pdo;
    if (!pdoDisponivel()) return [];
    try {
        $stmt = $pdo->query("SELECT nome FROM turmas ORDER BY nome ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function($r){ return $r['nome']; }, $rows);
    } catch (PDOException $e) {
        return [];
    }
}

function adicionarTurma($nome) {
    global $pdo;
    if (!pdoDisponivel()) return false;
    try {
        $stmt = $pdo->prepare("INSERT INTO turmas (nome) VALUES (?)");
        return $stmt->execute([$nome]);
    } catch (PDOException $e) {
        return false;
    }
}

function removerTurma($nome) {
    global $pdo;
    if (!pdoDisponivel()) return false;
    try {
        $stmt = $pdo->prepare("DELETE FROM turmas WHERE nome = ?");
        return $stmt->execute([$nome]);
    } catch (PDOException $e) {
        return false;
    }
}

function definirTurmaAluno($id, $turma) {
    global $pdo;
    if (!pdoDisponivel()) return false;
    try {
        $stmt = $pdo->prepare("UPDATE alunos SET turma = ? WHERE id = ?");
        return $stmt->execute([$turma, $id]);
    } catch (PDOException $e) {
        return false;
    }
}

function getUsuarios() {
    global $pdo;
    if (!pdoDisponivel()) return [];
    try {
        $stmt = $pdo->query("SELECT * FROM alunos ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getInstagramConfig() {
    global $pdo;
    if (!pdoDisponivel()) return ['username' => '', 'access_token' => '', 'post_limit' => 6];
    try {
        $stmt = $pdo->query("SELECT * FROM instagram_config ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
        return ['username' => '', 'connected_username' => '', 'access_token' => '', 'client_id' => '', 'client_secret' => '', 'redirect_uri' => '', 'post_limit' => 6];
    } catch (PDOException $e) {
        return ['username' => '', 'connected_username' => '', 'access_token' => '', 'client_id' => '', 'client_secret' => '', 'redirect_uri' => '', 'post_limit' => 6];
    }
}

function salvarInstagramAppConfig($clientId, $clientSecret, $redirectUri, $postLimit) {
    global $pdo;
    if (!pdoDisponivel()) return false;
    try {
        $stmt = $pdo->query("SELECT id FROM instagram_config ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['id'])) {
            $upd = $pdo->prepare("UPDATE instagram_config SET client_id = ?, client_secret = ?, redirect_uri = ?, post_limit = ? WHERE id = ?");
            return $upd->execute([$clientId, $clientSecret, $redirectUri, intval($postLimit), intval($row['id'])]);
        } else {
            $ins = $pdo->prepare("INSERT INTO instagram_config (client_id, client_secret, redirect_uri, post_limit) VALUES (?, ?, ?, ?)");
            return $ins->execute([$clientId, $clientSecret, $redirectUri, intval($postLimit)]);
        }
    } catch (PDOException $e) {
        return false;
    }
}

function salvarInstagramConfig($username, $accessToken, $postLimit) {
    global $pdo;
    if (!pdoDisponivel()) return false;
    try {
        $username = igSanitizeUsername($username);
        $stmt = $pdo->query("SELECT id FROM instagram_config ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['id'])) {
            $upd = $pdo->prepare("UPDATE instagram_config SET username = ?, access_token = ?, post_limit = ? WHERE id = ?");
            return $upd->execute([$username, $accessToken, intval($postLimit), intval($row['id'])]);
        } else {
            $ins = $pdo->prepare("INSERT INTO instagram_config (username, access_token, post_limit) VALUES (?, ?, ?)");
            return $ins->execute([$username, $accessToken, intval($postLimit)]);
        }
    } catch (PDOException $e) {
        return false;
    }
}

function desconectarInstagram() {
    global $pdo;
    if (!pdoDisponivel()) return false;
    try {
        $stmt = $pdo->query("SELECT id FROM instagram_config ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['id'])) {
            $upd = $pdo->prepare("UPDATE instagram_config SET access_token = NULL, username = NULL, connected_username = NULL WHERE id = ?");
            return $upd->execute([intval($row['id'])]);
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function readInstagramCache($username) {
    $username = igSanitizeUsername($username);
    if (!$username) return null;
    $cacheDir = dirname(__DIR__) . '/files';
    $file = $cacheDir . '/instagram_cache_' . preg_replace('/[^a-z0-9_\\-]+/i', '_', $username) . '.json';
    if (!file_exists($file)) return null;
    $age = time() - filemtime($file);
    if ($age > 3600) return null; // 1h
    $json = @file_get_contents($file);
    if (!$json) return null;
    $data = @json_decode($json, true);
    return is_array($data) ? $data : null;
}

function readInstagramCacheStale($username) {
    $username = igSanitizeUsername($username);
    if (!$username) return null;
    $cacheDir = dirname(__DIR__) . '/files';
    $file = $cacheDir . '/instagram_cache_' . preg_replace('/[^a-z0-9_\\-]+/i', '_', $username) . '.json';
    if (!file_exists($file)) return null;
    $json = @file_get_contents($file);
    if (!$json) return null;
    $data = @json_decode($json, true);
    return is_array($data) ? $data : null;
}

function writeInstagramCache($username, $items) {
    $username = igSanitizeUsername($username);
    if (!$username || !is_array($items)) return false;
    $cacheDir = dirname(__DIR__) . '/files';
    if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0755, true); }
    $file = $cacheDir . '/instagram_cache_' . preg_replace('/[^a-z0-9_\\-]+/i', '_', $username) . '.json';
    $ok = @file_put_contents($file, json_encode($items)) !== false;
    if ($ok) {
        $bak = $cacheDir . '/instagram_backup_' . preg_replace('/[^a-z0-9_\\-]+/i', '_', $username) . '.json';
        @file_put_contents($bak, json_encode($items));
        $meta = $cacheDir . '/instagram_meta_' . preg_replace('/[^a-z0-9_\\-]+/i', '_', $username) . '.json';
        @file_put_contents($meta, json_encode(['updated_at' => time()]));
    }
    return $ok;
}

function getInstagramCachePath($username) {
    $username = igSanitizeUsername($username);
    if (!$username) return null;
    $cacheDir = dirname(__DIR__) . '/files';
    return $cacheDir . '/instagram_cache_' . preg_replace('/[^a-z0-9_\\-]+/i', '_', $username) . '.json';
}

function clearInstagramCache($username) {
    $path = getInstagramCachePath($username);
    if ($path && file_exists($path)) { @unlink($path); return true; }
    return false;
}

function igLog($msg) {
    $logDir = dirname(__DIR__) . '/files';
    if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
    $file = $logDir . '/instagram_log.txt';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    @file_put_contents($file, $line, FILE_APPEND);
}

function getUsuarioPorId($id) {
    global $pdo;
    if (!pdoDisponivel()) return null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
        $stmt->execute([intval($id)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function buscarUsuarioPorNome($nome) {
    global $pdo;
    if (!pdoDisponivel()) return [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE TRIM(LOWER(nome)) = TRIM(LOWER(?)) ORDER BY created_at DESC");
        $stmt->execute([$nome]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function atualizarUsuario($id, $dados) {
    global $pdo;
    if (!pdoDisponivel() || !is_array($dados) || empty($dados)) return false;
    try {
        $cols = [];
        $vals = [];
        foreach ($dados as $k => $v) {
            $cols[] = "$k = ?";
            $vals[] = $v;
        }
        $vals[] = intval($id);
        $sql = "UPDATE alunos SET " . implode(', ', $cols) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($vals);
    } catch (PDOException $e) {
        return false;
    }
}

function atualizarStatusTodos($status) {
    global $pdo;
    if (!pdoDisponivel()) return false;
    try {
        $stmt = $pdo->prepare("UPDATE alunos SET status = ?");
        return $stmt->execute([$status]);
    } catch (PDOException $e) {
        return false;
    }
}

function cancelarMatricula($id) {
    global $pdo;
    if (!pdoDisponivel()) return false;
    try {
        $stmt = $pdo->prepare("UPDATE alunos SET status = ? WHERE id = ?");
        return $stmt->execute(['Cancelado', intval($id)]);
    } catch (PDOException $e) {
        return false;
    }
}

// --- NOVAS FUNÇÕES ADICIONADAS ---

function igSanitizeUsername($username) {
    if (!$username) return 'ig_user';
    return preg_replace('/[^a-z0-9_\\-]+/i', '_', $username);
}

function fetchInstagramPosts($limit = 6) {
    $conf = getInstagramConfig();
    $token = $conf['access_token'] ?? '';
    $username = $conf['username'] ?? 'ig_user';

    if (!$token) return [];

    $cache = readInstagramCache($username);
    if ($cache) return array_slice($cache, 0, $limit);

    $fields = "id,caption,media_type,media_url,permalink,thumbnail_url,timestamp";
    $url = "https://graph.instagram.com/me/media?fields={$fields}&access_token={$token}&limit=20";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        $data = json_decode($resp, true);
        if (isset($data['data'])) {
            $posts = $data['data'];
            foreach ($posts as &$p) {
                if (isset($p['timestamp'])) $p['timestamp'] = strtotime($p['timestamp']);
            }
            writeInstagramCache($username, $posts);
            return array_slice($posts, 0, $limit);
        }
    }

    $stale = readInstagramCacheStale($username);
    if ($stale) return array_slice($stale, 0, $limit);

    igLog("API Fail code $code: $resp");
    return [];
}