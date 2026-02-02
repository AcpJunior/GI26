<?php
// Configuração de exibição de erros para debug durante instalação
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$messages = [];
$status = 'success'; // ou 'error'

try {
    require_once 'includes/db_connect.php';
    
    // 1. Criar Tabela
    $sql = "CREATE TABLE IF NOT EXISTS alunos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        
        -- Dados da Bailarina
        nome VARCHAR(255) NOT NULL,
        identidade VARCHAR(50),
        data_matricula DATE,
        status VARCHAR(50) DEFAULT 'Pendente',
        nome_social VARCHAR(255),
        nascimento DATE,
        endereco TEXT,
        telefone VARCHAR(50),
        turma VARCHAR(100),
        
        -- Dados do Responsável
        responsavel VARCHAR(255),
        responsavel_identidade VARCHAR(50),
        responsavel_email VARCHAR(255),
        responsavel_nascimento DATE,
        responsavel_endereco TEXT,
        responsavel_telefone VARCHAR(50),
        
        -- Dados do Pai
        pai_nome VARCHAR(255),
        pai_identidade VARCHAR(50),
        pai_email VARCHAR(255),
        pai_nascimento DATE,
        pai_telefone VARCHAR(50),
        
        -- Dados da Mãe
        mae_nome VARCHAR(255),
        mae_identidade VARCHAR(50),
        mae_email VARCHAR(255),
        mae_nascimento DATE,
        mae_telefone VARCHAR(50),
        
        -- Renda da Casa
        renda_moradores INT,
        renda_comodos INT,
        renda_telefones INT,
        renda_valor DECIMAL(10, 2),
        
        -- Documentos (caminhos dos arquivos)
        doc_responsavel VARCHAR(255),
        doc_bailarina VARCHAR(255),
        doc_residencia VARCHAR(255),
        doc_renda VARCHAR(255),
        foto_bailarina VARCHAR(255),
        
        -- Outros
        email VARCHAR(255),
        observacoes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    $messages[] = "✅ Tabela 'alunos' verificada/criada com sucesso.";

    $chkEmail = $pdo->query("SHOW COLUMNS FROM alunos LIKE 'email'");
    if ($chkEmail && $chkEmail->fetch()) {
        $pdo->exec("ALTER TABLE alunos DROP COLUMN email");
        $messages[] = "✅ Campo 'email' removido. Usando apenas 'responsavel_email'.";
    }

    $extraCols = [
        'cuidados_especiais TEXT',
        'possui_doenca TINYINT(1) DEFAULT 0',
        'doenca_qual TEXT',
        'usa_medicacao TINYINT(1) DEFAULT 0',
        'medicacao_qual TEXT',
        'apoio_psicologico TINYINT(1) DEFAULT 0',
        'foto_bailarina VARCHAR(255)',
        'idade INT'
    ];
    foreach ($extraCols as $def) {
        $col = explode(' ', $def)[0];
        // SHOW COLUMNS não aceita placeholders; como a lista é fixa e controlada, podemos interpolar com segurança
        $chkSql = "SHOW COLUMNS FROM alunos LIKE '$col'";
        $chk = $pdo->query($chkSql);
        if (!$chk->fetch()) {
            $pdo->exec("ALTER TABLE alunos ADD COLUMN $def");
            $messages[] = "✅ Campo '$col' adicionado em 'alunos'.";
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $messages[] = "✅ Tabela 'admins' verificada/criada com sucesso.";

    $pdo->exec("CREATE TABLE IF NOT EXISTS turmas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $messages[] = "✅ Tabela 'turmas' verificada/criada com sucesso.";

    $pdo->exec("CREATE TABLE IF NOT EXISTS instagram_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100),
        connected_username VARCHAR(100),
        access_token TEXT,
        client_id VARCHAR(100),
        client_secret TEXT,
        redirect_uri VARCHAR(255),
        post_limit INT DEFAULT 6,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $messages[] = "✅ Tabela 'instagram_config' verificada/criada com sucesso.";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cfg_key VARCHAR(100) UNIQUE,
        cfg_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $messages[] = "✅ Tabela 'site_config' verificada/criada com sucesso.";
    
    $stmtCfg = $pdo->prepare("SELECT COUNT(*) AS c FROM site_config WHERE cfg_key = ?");
    $stmtCfg->execute(['matriculas_abertas']);
    $rowCfg = $stmtCfg->fetch(PDO::FETCH_ASSOC);
    if (!$rowCfg || intval($rowCfg['c']) === 0) {
        $insCfg = $pdo->prepare("INSERT INTO site_config (cfg_key, cfg_value) VALUES (?, ?)");
        $insCfg->execute(['matriculas_abertas', '0']);
        $messages[] = "✅ Configuração inicial 'matriculas_abertas' definida para fechado.";
    } else {
        $messages[] = "ℹ️ Configuração 'matriculas_abertas' já existe.";
    }

    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM admins WHERE username = 'admin'");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$exists || intval($exists['c']) === 0) {
        $hash = password_hash('admin', PASSWORD_BCRYPT);
        $ins = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $ins->execute(['admin', $hash]);
        $messages[] = "✅ Usuário administrativo 'admin' criado.";
    } else {
        $messages[] = "ℹ️ Usuário 'admin' já existe.";
    }

    // 2. Criar Pasta de Arquivos
    $filesDir = __DIR__ . '/files';
    if (!file_exists($filesDir)) {
        if (mkdir($filesDir, 0755, true)) {
            $messages[] = "✅ Pasta 'files' criada com sucesso.";
        } else {
            $messages[] = "❌ Erro ao criar pasta 'files'. Verifique as permissões da pasta raiz.";
            $status = 'warning';
        }
    } else {
        $messages[] = "ℹ️ Pasta 'files' já existe.";
    }
    
    // 3. Proteger Pasta
    $htaccess = $filesDir . '/.htaccess';
    if (!file_exists($htaccess)) {
        if (file_put_contents($htaccess, "Options -Indexes\n")) {
             $messages[] = "✅ Proteção de diretório (.htaccess) criada.";
        }
    }

} catch (PDOException $e) {
    $status = 'error';
    $messages[] = "❌ Erro de Banco de Dados: " . $e->getMessage();
} catch (Exception $e) {
    $status = 'error';
    $messages[] = "❌ Erro Geral: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação do Banco de Dados - Grupo Independance</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 90%;
            text-align: center;
        }
        h1 { color: #333; margin-bottom: 1.5rem; }
        .message-list { text-align: left; background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem; }
        .message-item { margin-bottom: 0.5rem; font-size: 0.95rem; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #E0B0B6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background 0.3s;
        }
        .btn:hover { background-color: #d49aacee; }
        .status-icon { font-size: 3rem; margin-bottom: 1rem; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($status === 'success'): ?>
            <div class="status-icon success">✓</div>
            <h1>Instalação Concluída!</h1>
        <?php elseif ($status === 'warning'): ?>
            <div class="status-icon warning">!</div>
            <h1>Instalação com Avisos</h1>
        <?php else: ?>
            <div class="status-icon error">✕</div>
            <h1>Erro na Instalação</h1>
        <?php endif; ?>

        <div class="message-list">
            <?php foreach ($messages as $msg): ?>
                <div class="message-item"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>

        <?php if ($status !== 'error'): ?>
            <p>O banco de dados foi configurado corretamente.</p>
            <p style="color: #666; font-size: 0.9em;">Por segurança, você pode remover o arquivo <code>setup_db.php</code> do servidor.</p>
            <a href="login.php" class="btn">Ir para Login</a>
        <?php else: ?>
            <p>Verifique as configurações em <code>includes/db_connect.php</code> e tente novamente.</p>
            <button onclick="location.reload()" class="btn" style="background-color: #6c757d;">Tentar Novamente</button>
        <?php endif; ?>
    </div>
</body>
</html>
