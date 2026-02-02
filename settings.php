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

$msg = '';
$ig = function_exists('getInstagramConfig') ? getInstagramConfig() : ['username'=>'','access_token'=>'','post_limit'=>6];
$config = function_exists('getSiteConfig') ? getSiteConfig() : ['matriculas_abertas' => false, 'rematriculas_abertas' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_matriculas'])) {
        $abertas = isset($_POST['matriculas_abertas']) ? true : false;
        if (function_exists('updateSiteConfig')) {
            if (updateSiteConfig('matriculas_abertas', $abertas)) {
                $msg = "<div class='success-msg'>Status das matrículas atualizado.</div>";
                $config['matriculas_abertas'] = $abertas;
            } else {
                $msg = "<div class='error-msg'>Falha ao atualizar configuração.</div>";
            }
        }
    }
    if (isset($_POST['update_rematriculas'])) {
        $abertas = isset($_POST['rematriculas_abertas']) ? true : false;
        $buscaAvancada = isset($_POST['rematricula_busca_avancada']) ? true : false;
        $comoMatricula = isset($_POST['usar_rematricula_como_matricula']) ? true : false;
        if (function_exists('updateSiteConfig')) {
            $ok1 = updateSiteConfig('rematriculas_abertas', $abertas);
            $ok2 = updateSiteConfig('rematricula_busca_avancada', $buscaAvancada);
            $ok3 = updateSiteConfig('usar_rematricula_como_matricula', $comoMatricula);
            if ($ok1 && $ok2 && $ok3) {
                $msg = "<div class='success-msg'>Configurações de rematrícula atualizadas.</div>";
                $config['rematriculas_abertas'] = $abertas;
                $config['rematricula_busca_avancada'] = $buscaAvancada;
                $config['usar_rematricula_como_matricula'] = $comoMatricula;
            } else {
                $msg = "<div class='error-msg'>Falha ao atualizar configuração.</div>";
            }
        }
    }
    if (isset($_POST['add_turma'])) {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome !== '' && function_exists('adicionarTurma')) {
            if (adicionarTurma($nome)) {
                $msg = "<div class='success-msg'>Turma adicionada com sucesso.</div>";
            } else {
                $msg = "<div class='error-msg'>Falha ao adicionar turma. Verifique se já existe.</div>";
            }
        }
    }
    if (isset($_POST['remove_turma'])) {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome !== '' && function_exists('removerTurma')) {
            if (removerTurma($nome)) {
                $msg = "<div class='success-msg'>Turma removida com sucesso.</div>";
            } else {
                $msg = "<div class='error-msg'>Falha ao remover turma.</div>";
            }
        }
    }
    if (isset($_POST['save_instagram'])) {
        $username = trim($_POST['ig_username'] ?? '');
        $limit = intval($_POST['ig_limit'] ?? 6);
        $clientId = trim($_POST['ig_client_id'] ?? '');
        $clientSecret = trim($_POST['ig_client_secret'] ?? '');
        $redirectUri = trim($_POST['ig_redirect_uri'] ?? '');
        $accessToken = trim($_POST['ig_access_token'] ?? ($ig['access_token'] ?? ''));
        $ok1 = function_exists('salvarInstagramConfig') ? salvarInstagramConfig($username, $accessToken, $limit) : false;
        $ok2 = function_exists('salvarInstagramAppConfig') ? salvarInstagramAppConfig($clientId, $clientSecret, $redirectUri, $limit) : false;
        if ($ok1 || $ok2) {
            $msg = "<div class='success-msg'>Instagram atualizado.</div>";
            $ig = getInstagramConfig();
        } else {
            $msg = "<div class='error-msg'>Não foi possível salvar o Instagram.</div>";
        }
    }
    if (isset($_POST['refresh_instagram'])) {
        $username = trim($ig['username'] ?? '');
        if ($username !== '' && function_exists('fetchInstagramPosts')) {
            $items = fetchInstagramPosts(null, true);
            if (!empty($items)) {
                $msg = "<div class='success-msg'>Atualizado: " . count($items) . " posts.</div>";
            } else {
                $msg = "<div class='error-msg'>Não foi possível obter posts agora. Verifique os logs.</div>";
            }
        } else {
            $msg = "<div class='error-msg'>Defina o usuário do Instagram antes de atualizar.</div>";
        }
    }
}

$lista = function_exists('getTurmas') ? getTurmas() : [];
$logText = '';
$logPath = __DIR__ . '/files/instagram_log.txt';
if (file_exists($logPath)) {
    $raw = @file($logPath, FILE_IGNORE_NEW_LINES);
    if (is_array($raw) && !empty($raw)) {
        $slice = array_slice($raw, max(0, count($raw) - 60), 60);
        $logText = implode("\n", $slice);
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container">
    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Voltar para o Painel
    </a>
    <div class="settings-card">
        <div class="settings-header">
            <div class="settings-title"><i class="fas fa-cog"></i> Configurações</div>
        </div>
        <?php echo $msg; ?>
        <div class="settings-grid">
            <div class="section-card">
                <div class="section-header"><i class="fas fa-id-card"></i> Matrículas</div>
                <form method="post">
                    <div class="switch-row">
                        <label class="switch">
                            <input type="checkbox" name="matriculas_abertas" <?php echo ($config['matriculas_abertas'] ?? false) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <span class="status-pill <?php echo ($config['matriculas_abertas'] ?? false) ? 'on' : 'off'; ?>">
                            <?php echo ($config['matriculas_abertas'] ?? false) ? 'Matrículas Abertas' : 'Matrículas Fechadas'; ?>
                        </span>
                    </div>
                    <p class="muted">Quando ativado, o formulário de matrícula ficará visível no site. Quando desativado, será exibida a mensagem de encerramento.</p>
                    <div class="form-actions">
                        <input type="hidden" name="update_matriculas" value="1">
                        <button type="submit" class="btn" style="background-color: var(--primary-color);">Salvar Status</button>
                    </div>
                </form>
            </div>
            <div class="section-card">
                <div class="section-header"><i class="fas fa-sync-alt"></i> Rematrículas</div>
                <form method="post">
                    <div class="switch-row">
                        <label class="switch">
                            <input type="checkbox" name="rematriculas_abertas" <?php echo ($config['rematriculas_abertas'] ?? false) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <span class="status-pill <?php echo ($config['rematriculas_abertas'] ?? false) ? 'on' : 'off'; ?>">
                            <?php echo ($config['rematriculas_abertas'] ?? false) ? 'Rematrículas Abertas' : 'Rematrículas Fechadas'; ?>
                        </span>
                    </div>
                    <div class="switch-row" style="margin-top:15px; border-top:1px solid #eee; padding-top:15px;">
                        <label class="switch">
                            <input type="checkbox" name="rematricula_busca_avancada" <?php echo ($config['rematricula_busca_avancada'] ?? true) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <span class="status-pill <?php echo ($config['rematricula_busca_avancada'] ?? true) ? 'blue' : 'gray'; ?>">
                            <?php echo ($config['rematricula_busca_avancada'] ?? true) ? 'Busca Avançada (Fuzzy)' : 'Modo Lista Simples'; ?>
                        </span>
                    </div>
                    <div class="switch-row" style="margin-top:15px; border-top:1px solid #eee; padding-top:15px;">
                        <label class="switch">
                            <input type="checkbox" name="usar_rematricula_como_matricula" <?php echo ($config['usar_rematricula_como_matricula'] ?? false) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <span class="status-pill <?php echo ($config['usar_rematricula_como_matricula'] ?? false) ? 'orange' : 'gray'; ?>">
                            <?php echo ($config['usar_rematricula_como_matricula'] ?? false) ? 'Modo Matrícula Unificada (Novo)' : 'Modo Padrão (Matrícula/Rematrícula)'; ?>
                        </span>
                    </div>
                    <p class="muted">
                        <strong>Busca Avançada:</strong> Permite digitar o nome e busca por similaridade.<br>
                        <strong>Modo Lista Simples:</strong> Exibe uma lista com nomes dos alunos pendentes para seleção.<br>
                        <strong>Modo Matrícula Unificada:</strong> Substitui o link de Rematrícula por Matrícula (novos alunos).
                    </p>
                    <div class="form-actions">
                        <input type="hidden" name="update_rematriculas" value="1">
                        <button type="submit" class="btn" style="background-color: var(--primary-color);">Salvar Status</button>
                    </div>
                </form>
            </div>

            <div class="section-card">
                <div class="section-header"><i class="fas fa-users"></i> Turmas existentes</div>
                <?php if (empty($lista)): ?>
                    <div class="turmas-empty">Nenhuma turma cadastrada.</div>
                <?php else: ?>
                    <div class="turmas-list">
                        <?php foreach ($lista as $t): ?>
                            <div class="turma-item">
                                <span><?php echo htmlspecialchars($t); ?></span>
                                <form method="post" onsubmit="return confirm('Remover turma &quot;<?php echo htmlspecialchars($t); ?>&quot;?');">
                                    <input type="hidden" name="nome" value="<?php echo htmlspecialchars($t); ?>">
                                    <input type="hidden" name="remove_turma" value="1">
                                    <button type="submit" class="btn btn-danger btn-sm">Remover</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="section-card">
                <div class="section-header"><i class="fas fa-plus-circle"></i> Adicionar nova turma</div>
                <form method="post">
                    <div class="form-group">
                        <label>Nome da turma</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="form-actions">
                        <input type="hidden" name="add_turma" value="1">
                        <button type="submit" class="btn" style="background-color: var(--primary-color);">Adicionar</button>
                    </div>
                </form>
            </div>
            <div class="section-card">
                <div class="section-header"><i class="fab fa-instagram"></i> Instagram</div>
                <form method="post">
                    <div class="instagram-grid">
                        <div class="form-group">
                            <label>Usuário (@)</label>
                            <input type="text" name="ig_username" class="form-control" value="<?php echo htmlspecialchars($ig['username'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Qtd. de posts</label>
                            <input type="number" name="ig_limit" min="1" max="20" class="form-control" value="<?php echo intval($ig['post_limit'] ?? 6); ?>">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Access Token</label>
                            <input type="text" name="ig_access_token" class="form-control" value="<?php echo htmlspecialchars($ig['access_token'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Client ID (Instagram)</label>
                            <input type="text" name="ig_client_id" class="form-control" value="<?php echo htmlspecialchars($ig['client_id'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Client Secret (Instagram)</label>
                            <input type="text" name="ig_client_secret" class="form-control" value="<?php echo htmlspecialchars($ig['client_secret'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Redirect URI</label>
                            <input type="text" name="ig_redirect_uri" class="form-control" value="<?php echo htmlspecialchars($ig['redirect_uri'] ?? ( (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '') . '/instagram_callback.php' )); ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <input type="hidden" name="save_instagram" value="1">
                        <button type="submit" class="btn" style="background-color: var(--primary-color);">Salvar</button>
                        <button type="button" class="btn" onclick="location.href='instagram_connect.php'"><i class="fas fa-link"></i> Conectar</button>
                        <?php if (!empty($ig['access_token'])): ?>
                            <span style="color:#27ae60;">Conectado como <?php echo htmlspecialchars($ig['connected_username'] ?? $ig['username'] ?? ''); ?></span>
                        <?php else: ?>
                            <span style="color:#c0392b;">Não conectado</span>
                        <?php endif; ?>
                    </div>
                </form>
                <form method="post" style="margin-top:12px;">
                    <input type="hidden" name="refresh_instagram" value="1">
                    <button type="submit" class="btn"><i class="fas fa-sync"></i> Atualizar posts agora</button>
                </form>
                <div style="margin-top:16px;">
                    <h4 style="margin-bottom:8px;">Logs recentes</h4>
                    <div class="logs-box"><?php echo htmlspecialchars($logText); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
