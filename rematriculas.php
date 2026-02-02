<?php
require_once 'includes/data.php';
$config = getSiteConfig();
$abertas = $config['rematriculas_abertas'] ?? false;
$buscaAvancada = $config['rematricula_busca_avancada'] ?? true;
$unificacao = $config['usar_rematricula_como_matricula'] ?? false;
$erro = '';
$sucesso = false;
$sucessoNova = false; // Flag específica para nova matrícula
$step = $_POST['step'] ?? 1;
// Normalizar step para lidar com valores numéricos ou string 'nova'
if ($step !== 'nova') {
    $step = intval($step);
}

$user = null;
$userId = intval($_POST['user_id'] ?? 0);
$nomeBusca = trim($_POST['nome_busca'] ?? '');
$bailarinaNasc = $_POST['nascimento'] ?? '';
$bailarinaId = trim($_POST['identidade'] ?? '');
$respIdentidade = trim($_POST['responsavel_identidade'] ?? '');
function normId($v){ return preg_replace('/[^0-9A-Za-z]+/', '', strtolower(trim((string)$v))); }
function sameDate($a, $b){ $da = strtotime($a); $db = strtotime($b); if(!$da || !$db) return false; return date('Y-m-d', $da) === date('Y-m-d', $db); }

if ($abertas && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se usuário clicou em "Fazer Nova Matrícula"
    if (isset($_POST['action']) && $_POST['action'] === 'nova_matricula' && $unificacao) {
        $step = 'nova';
    }
    
    // Lógica para Nova Matrícula (Cópia adaptada de matriculas.php)
    elseif ($step === 'nova' && $unificacao) {
        $missing = [];
        $nome = $_POST['nome'] ?? '';
        $nome_social = $_POST['nome_social'] ?? '';
        $identidade = $_POST['identidade'] ?? '';
        $nascimento = $_POST['nascimento'] ?? null;
        $endereco = $_POST['endereco'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $responsavel = $_POST['responsavel'] ?? '';
        $responsavel_identidade = $_POST['responsavel_identidade'] ?? '';
        $responsavel_email = $_POST['responsavel_email'] ?? '';
        $responsavel_nascimento = $_POST['responsavel_nascimento'] ?? null;
        $responsavel_endereco = $_POST['responsavel_endereco'] ?? '';
        $responsavel_telefone = $_POST['responsavel_telefone'] ?? '';
        $renda_moradores = $_POST['renda_moradores'] ?? null;
        $renda_comodos = $_POST['renda_comodos'] ?? null;
        $renda_telefones = $_POST['renda_telefones'] ?? null;
        $renda_valor = $_POST['renda_valor'] ?? null;
        $possui_doenca = $_POST['possui_doenca'] ?? '';
        $doenca_qual = $_POST['doenca_qual'] ?? '';
        $usa_medicacao = $_POST['usa_medicacao'] ?? '';
        $medicacao_qual = $_POST['medicacao_qual'] ?? '';
        $apoio_psicologico = $_POST['apoio_psicologico'] ?? '';
        $cuidados_especiais = $_POST['cuidados_especiais'] ?? '';

        foreach ([
            'nome' => $nome, 'nome_social' => $nome_social, 'identidade' => $identidade, 'nascimento' => $nascimento,
            'endereco' => $endereco, 'telefone' => $telefone,
            'responsavel' => $responsavel, 'responsavel_identidade' => $responsavel_identidade, 'responsavel_email' => $responsavel_email,
            'responsavel_nascimento' => $responsavel_nascimento, 'responsavel_endereco' => $responsavel_endereco, 'responsavel_telefone' => $responsavel_telefone,
            'renda_moradores' => $renda_moradores, 'renda_comodos' => $renda_comodos, 'renda_telefones' => $renda_telefones, 'renda_valor' => $renda_valor,
            'possui_doenca' => $possui_doenca, 'usa_medicacao' => $usa_medicacao, 'apoio_psicologico' => $apoio_psicologico
        ] as $k => $v) { if ($v === '' || $v === null) { $missing[] = $k; } }
        
        if ($possui_doenca === 'sim' && $doenca_qual === '') { $missing[] = 'doenca_qual'; }
        if ($usa_medicacao === 'sim' && $medicacao_qual === '') { $missing[] = 'medicacao_qual'; }
        
        foreach (['doc_responsavel','doc_bailarina','doc_residencia','doc_renda'] as $f) {
            if (!isset($_FILES[$f]) || $_FILES[$f]['error'] !== 0) { $missing[] = $f; }
        }
        
        if (!empty($missing)) {
            $erro = 'Preencha todos os campos obrigatórios.';
        } else {
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'identidade' => $_POST['identidade'] ?? '',
                'data_matricula' => date('Y-m-d'),
                'status' => 'Pendente',
                'nome_social' => $_POST['nome_social'] ?? '',
                'nascimento' => $_POST['nascimento'] ?? null,
                'endereco' => $_POST['endereco'] ?? '',
                'telefone' => $_POST['telefone'] ?? '',
                'responsavel' => $_POST['responsavel'] ?? '',
                'responsavel_identidade' => $_POST['responsavel_identidade'] ?? '',
                'responsavel_email' => $_POST['responsavel_email'] ?? '',
                'responsavel_nascimento' => $_POST['responsavel_nascimento'] ?? null,
                'responsavel_endereco' => $_POST['responsavel_endereco'] ?? '',
                'responsavel_telefone' => $_POST['responsavel_telefone'] ?? '',
                'pai_nome' => $_POST['pai_nome'] ?? '',
                'pai_identidade' => $_POST['pai_identidade'] ?? '',
                'pai_email' => $_POST['pai_email'] ?? '',
                'pai_nascimento' => $_POST['pai_nascimento'] ?? null,
                'pai_telefone' => $_POST['pai_telefone'] ?? '',
                'mae_nome' => $_POST['mae_nome'] ?? '',
                'mae_identidade' => $_POST['mae_identidade'] ?? '',
                'mae_email' => $_POST['mae_email'] ?? '',
                'mae_nascimento' => $_POST['mae_nascimento'] ?? null,
                'mae_telefone' => $_POST['mae_telefone'] ?? '',
                'renda_moradores' => $_POST['renda_moradores'] ?? null,
                'renda_comodos' => $_POST['renda_comodos'] ?? null,
                'renda_telefones' => $_POST['renda_telefones'] ?? null,
                'renda_valor' => $_POST['renda_valor'] ?? null,
                'possui_doenca' => $possui_doenca === 'sim' ? 1 : 0,
                'doenca_qual' => $doenca_qual,
                'usa_medicacao' => $usa_medicacao === 'sim' ? 1 : 0,
                'medicacao_qual' => $medicacao_qual,
                'apoio_psicologico' => $apoio_psicologico === 'sim' ? 1 : 0,
                'cuidados_especiais' => $cuidados_especiais
            ];
            
            $id = adicionarUsuario($dados);
            
            if ($id) {
                $uploadDir = 'files/' . $id . '/';
                if (!file_exists($uploadDir)) { mkdir($uploadDir, 0755, true); }
                $updates = [];
                $fileFields = ['doc_responsavel', 'doc_bailarina', 'doc_residencia', 'doc_renda', 'foto_bailarina'];
                foreach ($fileFields as $field) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
                        $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                        $randomName = md5(uniqid('', true)) . ($ext ? '.' . $ext : '');
                        $target = $uploadDir . $randomName;
                        if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) { $updates[$field] = $target; }
                    }
                }
                if (!empty($updates)) { atualizarUsuario($id, $updates); }
                $sucessoNova = true;
            } else {
                $erro = 'Falha ao enviar matrícula. Tente novamente.';
            }
        }
    }
    elseif ($step === 1) {
        // Se for busca simples (lista), user_id vem do select
        if (!$buscaAvancada && $userId > 0) {
            $user = getUsuarioPorId($userId);
            if ($user && $user['status'] === 'Pendente') {
                $step = 2;
            } else {
                $erro = 'Aluno não encontrado ou já rematriculado.';
            }
        } 
        // Se for busca avançada, lógica antiga
        elseif ($buscaAvancada) {
            if ($nomeBusca === '') {
                $erro = 'Informe o nome completo da bailarina.';
            } else {
                $encontrados = buscarUsuarioPorNome($nomeBusca);
                // Filtrar apenas Pendentes
                $encontrados = array_filter($encontrados, function($u){ return isset($u['status']) && $u['status'] === 'Pendente'; });
                $encontrados = array_values($encontrados);

                if (empty($encontrados)) {
                    $erro = 'Matrícula não encontrada ou já regularizada.';
                } else {
                    $user = $encontrados[0];
                    $step = 2;
                }
            }
        } else {
            // Caso lista mas sem ID (erro)
            if (!$buscaAvancada && $userId <= 0) {
                 $erro = 'Selecione uma bailarina.';
            }
        }
    } elseif ($step === 2) {
        if ($userId > 0) {
            $user = getUsuarioPorId($userId);
        }
        if (!$user) {
            $erro = 'Registro não encontrado.';
            $step = 1;
        } else {
            $dbNasc = $user['nascimento'] ?? '';
            $dbId = $user['identidade'] ?? '';
            $okNasc = ($dbNasc !== '' && $bailarinaNasc !== '' && sameDate($bailarinaNasc, $dbNasc));
            
            if (!$buscaAvancada) {
                // Modo simples: Apenas Data de Nascimento, pula validação de identidade e responsável
                if ($okNasc) {
                    $step = 4;
                } else {
                    $erro = 'Data de nascimento incorreta.';
                    $step = 2;
                }
            } else {
                // Modo avançado: Validação completa
                $okId = ($dbId !== '' && $bailarinaId !== '' && normId($bailarinaId) === normId($dbId));
                if ($okNasc && $okId) { 
                    $step = 3; 
                } else { 
                    $erro = 'Dados da bailarina não conferem. Verifique a data de nascimento e a identidade.'; 
                    $step = 2; 
                }
            }
        }
    } elseif ($step === 3) {
        if ($userId > 0) {
            $user = getUsuarioPorId($userId);
        }
        if (!$user) {
            $erro = 'Registro não encontrado.';
            $step = 1;
        } else {
            $primeiroNomeResp = explode(' ', trim($user['responsavel'] ?? ''))[0] ?? '';
            $okRespId = ($respIdentidade !== '' && normId($respIdentidade) === normId($user['responsavel_identidade'] ?? ''));
            if ($okRespId) { $step = 4; }
            else { $erro = 'Identidade do responsável não confere.'; $step = 3; }
        }
    } elseif ($step === 4) {
        if ($userId > 0) { $user = getUsuarioPorId($userId); }
        if (!$user) { $erro = 'Registro não encontrado.'; $step = 1; }
        else {
            $okRespId = ($respIdentidade !== '' && normId($respIdentidade) === normId($user['responsavel_identidade'] ?? ''));
            if (!$okRespId) { $erro = 'Identidade do responsável não confere.'; $step = 3; }
            else {
                $campos = $_POST;
                unset($campos['step'], $campos['user_id']);
                $campos['status'] = 'Em dia';
                if (!empty($campos['nascimento'])) { $campos['nascimento'] = date('Y-m-d', strtotime($campos['nascimento'])); }
                if (!empty($campos['responsavel_nascimento'])) { $campos['responsavel_nascimento'] = date('Y-m-d', strtotime($campos['responsavel_nascimento'])); }
                if (!empty($campos['pai_nascimento'])) { $campos['pai_nascimento'] = date('Y-m-d', strtotime($campos['pai_nascimento'])); }
                if (!empty($campos['mae_nascimento'])) { $campos['mae_nascimento'] = date('Y-m-d', strtotime($campos['mae_nascimento'])); }
                $uploadDir = 'files/' . $user['id'] . '/';
                if (!file_exists($uploadDir)) { mkdir($uploadDir, 0755, true); }
                $fileFields = ['doc_responsavel', 'doc_bailarina', 'doc_residencia', 'doc_renda', 'foto_bailarina'];
                foreach ($fileFields as $field) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
                        $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                        $randomName = md5(uniqid('', true)) . ($ext ? '.' . $ext : '');
                        $target = $uploadDir . $randomName;
                        if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) { $campos[$field] = $target; }
                    }
                }
                $ok = atualizarUsuario($user['id'], $campos);
                if ($ok) { $sucesso = true; }
                else { $erro = 'Não foi possível salvar a rematrícula.'; }
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h2 class="page-title" style="text-align:center;">Rematrícula</h2>
    <?php if (!$abertas): ?>
        <div class="message-box">
            <div class="dev-icon">
                <i class="fas fa-clock" style="color:#dc3545;"></i>
            </div>
            <h3>Rematrículas Encerradas</h3>
            <p>No momento as rematrículas não estão disponíveis.</p>
            <div style="margin-top: 20px;">
                <a href="index.php" class="btn" style="width:auto;">Voltar para Home</a>
            </div>
        </div>
    <?php elseif ($sucesso): ?>
        <div class="message-box">
            <div class="dev-icon">
                <i class="fas fa-check-circle" style="color:#28a745;"></i>
            </div>
            <h3>Rematrícula concluída!</h3>
            <p>Status atualizado para Em dia.</p>
            <div style="margin-top: 20px;">
                <a href="index.php" class="btn" style="width:auto;">Voltar para Home</a>
            </div>
        </div>
    <?php elseif ($sucessoNova): ?>
        <div class="message-box">
            <div class="dev-icon">
                <i class="fas fa-check-circle" style="color:#28a745;"></i>
            </div>
            <h3>Matrícula enviada com sucesso!</h3>
            <p>Em breve entraremos em contato pelo e-mail informado.</p>
            <div style="margin-top: 20px;">
                <a href="index.php" class="btn" style="width:auto;">Voltar para Home</a>
            </div>
        </div>
    <?php else: ?>
        <?php if ($erro): ?>
            <div class="error-msg" style="margin-bottom:20px;"><?php echo $erro; ?></div>
        <?php endif; ?>
        <?php if ($step === 1): ?>
            <?php if ($buscaAvancada): ?>
                <form method="post">
                    <div class="form-group input-wrapper">
                        <label>Nome completo da bailarina</label>
                        <input type="text" name="nome_busca" id="nome_busca" class="form-control" required autocomplete="off">
                        <div class="loading-spinner"></div>
                        <div id="search_feedback" class="search-feedback"></div>
                    </div>
                    <input type="hidden" name="step" value="1">
                    <div style="text-align:center; margin-top:20px;">
                        <button type="submit" class="btn">Buscar</button>
                    </div>
                </form>
                <?php if ($unificacao): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="nova_matricula">
                    <input type="hidden" name="step" value="nova">
                    <div style="text-align:center; margin-top:20px;">
                        <button type="submit" class="btn">Fazer Nova Matrícula</button>
                    </div>
                </form>
                <?php endif; ?>
            <?php else: ?>
                <?php 
                    $alunosPendentes = getAlunosPendentes(); 
                    // Remover duplicatas por ID (segurança extra)
                    $uniqueAlunos = [];
                    foreach($alunosPendentes as $a) {
                        $uniqueAlunos[$a['id']] = $a;
                    }
                    $alunosPendentes = $uniqueAlunos;
                ?>
                <form method="post">
                    <div class="form-group">
                        <label>Selecione a Bailarina</label>
                        <select name="user_id" class="form-control" required style="height: 50px; font-size: 16px;">
                            <option value="">-- Selecione --</option>
                            <?php foreach ($alunosPendentes as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($alunosPendentes)): ?>
                            <div style="margin-top:10px; color:#856404; background-color:#fff3cd; padding:10px; border-radius:5px;">
                                Não há alunos pendentes de rematrícula no momento.
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="step" value="1">
                    <div style="text-align:center; margin-top:20px;">
                        <button type="submit" class="btn" <?php echo empty($alunosPendentes) ? 'disabled' : ''; ?>>Continuar</button>
                    </div>
                </form>
                <?php if ($unificacao): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="nova_matricula">
                    <input type="hidden" name="step" value="nova">
                    <div style="text-align:center; margin-top:20px;">
                        <button type="submit" class="btn">Fazer Nova Matrícula</button>
                    </div>
                </form>
                <?php endif; ?>
            <?php endif; ?>
        <?php elseif ($step === 'nova'): ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="step" value="nova">
                <input type="hidden" name="action" value="nova_matricula">
                <h3>Dados da Bailarina</h3>
                <div class="form-group">
                    <label>Nome completo *</label>
                    <input type="text" name="nome" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nome social *</label>
                    <input type="text" name="nome_social" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Identidade *</label>
                    <input type="text" name="identidade" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Data de nascimento *</label>
                    <input type="date" name="nascimento" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Endereço *</label>
                    <input type="text" name="endereco" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Telefone *</label>
                    <input type="text" name="telefone" class="form-control" required>
                </div>
                <h3>Responsável</h3>
                <div class="form-group">
                    <label>Nome *</label>
                    <input type="text" name="responsavel" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Identidade *</label>
                    <input type="text" name="responsavel_identidade" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>E-mail *</label>
                    <input type="email" name="responsavel_email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Data de nascimento *</label>
                    <input type="date" name="responsavel_nascimento" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Endereço *</label>
                    <input type="text" name="responsavel_endereco" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Telefone *</label>
                    <input type="text" name="responsavel_telefone" class="form-control" required>
                </div>
                <h3>Pai</h3>
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="pai_nome" class="form-control">
                </div>
                <div class="form-group">
                    <label>Identidade</label>
                    <input type="text" name="pai_identidade" class="form-control">
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="pai_email" class="form-control">
                </div>
                <div class="form-group">
                    <label>Data de nascimento</label>
                    <input type="date" name="pai_nascimento" class="form-control">
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" name="pai_telefone" class="form-control">
                </div>
                <h3>Mãe</h3>
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="mae_nome" class="form-control">
                </div>
                <div class="form-group">
                    <label>Identidade</label>
                    <input type="text" name="mae_identidade" class="form-control">
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="mae_email" class="form-control">
                </div>
                <div class="form-group">
                    <label>Data de nascimento</label>
                    <input type="date" name="mae_nascimento" class="form-control">
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" name="mae_telefone" class="form-control">
                </div>
                <h3>Renda da casa</h3>
                <div class="form-group">
                    <label>Quantidade de moradores *</label>
                    <input type="number" name="renda_moradores" class="form-control" min="0" required>
                </div>
                <div class="form-group">
                    <label>Quantidade de cômodos *</label>
                    <input type="number" name="renda_comodos" class="form-control" min="0" required>
                </div>
                <div class="form-group">
                    <label>Quantidade de telefones *</label>
                    <input type="number" name="renda_telefones" class="form-control" min="0" required>
                </div>
                <div class="form-group">
                    <label>Renda aproximada *</label>
                    <input type="number" name="renda_valor" class="form-control" step="0.01" min="0" required>
                </div>
                <h3>Documentos</h3>
                <div class="form-group">
                    <label>Documento do responsável *</label>
                    <input type="file" name="doc_responsavel" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label>Documento da bailarina *</label>
                    <input type="file" name="doc_bailarina" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label>Comprovante de residência *</label>
                    <input type="file" name="doc_residencia" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label>Comprovante de renda *</label>
                    <input type="file" name="doc_renda" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label>Foto da bailarina</label>
                    <input type="file" name="foto_bailarina" class="form-control" accept="image/*">
                </div>
                <h3>Cuidados especiais</h3>
                <div class="form-group">
                    <label>Possui alguma doença? *</label>
                    <div>
                        <label style="margin-right:10px;"><input type="radio" name="possui_doenca" value="sim" required> Sim</label>
                        <label><input type="radio" name="possui_doenca" value="nao" required> Não</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Se sim, qual?</label>
                    <input type="text" name="doenca_qual" class="form-control">
                </div>
                <div class="form-group">
                    <label>Faz uso de alguma medicação? *</label>
                    <div>
                        <label style="margin-right:10px;"><input type="radio" name="usa_medicacao" value="sim" required> Sim</label>
                        <label><input type="radio" name="usa_medicacao" value="nao" required> Não</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Se sim, qual?</label>
                    <input type="text" name="medicacao_qual" class="form-control">
                </div>
                <div class="form-group">
                    <label>Possui necessidade de apoio psicológico? *</label>
                    <div>
                        <label style="margin-right:10px;"><input type="radio" name="apoio_psicologico" value="sim" required> Sim</label>
                        <label><input type="radio" name="apoio_psicologico" value="nao" required> Não</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Cuidados especiais</label>
                    <textarea name="cuidados_especiais" class="form-control" rows="3"></textarea>
                </div>
                <div style="margin-top: 30px; text-align:center;">
                    <button type="submit" class="btn" style="min-width:200px;">Enviar Matrícula</button>
                </div>
            </form>
        <?php elseif ($step === 2): ?>
            <form method="post">
                <input type="hidden" name="user_id" value="<?php echo intval($user['id']); ?>">
                <div class="form-group">
                    <label>Data de nascimento da bailarina</label>
                    <input type="date" name="nascimento" class="form-control" required>
                </div>
                <?php if ($buscaAvancada): ?>
                <div class="form-group">
                    <label>Identidade da bailarina</label>
                    <input type="text" name="identidade" class="form-control" required>
                </div>
                <?php endif; ?>
                <input type="hidden" name="step" value="2">
                <div style="text-align:center; margin-top:20px;">
                    <button type="submit" class="btn">Validar</button>
                </div>
            </form>
        <?php elseif ($step === 3): ?>
            <?php $primeiroNomeResp = explode(' ', trim($user['responsavel'] ?? ''))[0] ?? ''; ?>
            <form method="post">
                <input type="hidden" name="user_id" value="<?php echo intval($user['id']); ?>">
                <div class="form-group">
                    <label>Identidade do responsável (<?php echo htmlspecialchars($primeiroNomeResp); ?>)</label>
                    <input type="text" name="responsavel_identidade" class="form-control" required>
                </div>
                <input type="hidden" name="step" value="3">
                <div style="text-align:center; margin-top:20px;">
                    <button type="submit" class="btn">Validar</button>
                </div>
            </form>
        <?php elseif ($step === 4): ?>
            <?php $primeiroNomeResp = explode(' ', trim($user['responsavel'] ?? ''))[0] ?? ''; ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?php echo intval($user['id']); ?>">
                <input type="hidden" name="responsavel_identidade" value="<?php echo htmlspecialchars($respIdentidade); ?>">
                <h3>Dados da Bailarina</h3>
                <div class="form-group">
                    <label>Nome completo</label>
                    <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($user['nome'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Nome social</label>
                    <input type="text" name="nome_social" class="form-control" value="<?php echo htmlspecialchars($user['nome_social'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Identidade</label>
                    <input type="text" name="identidade" class="form-control" value="<?php echo htmlspecialchars($user['identidade'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Data de nascimento</label>
                    <input type="date" name="nascimento" class="form-control" value="<?php echo htmlspecialchars($user['nascimento'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Endereço</label>
                    <input type="text" name="endereco" class="form-control" value="<?php echo htmlspecialchars($user['endereco'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" name="telefone" class="form-control" value="<?php echo htmlspecialchars($user['telefone'] ?? ''); ?>" required>
                </div>
                <h3>Responsável</h3>
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="responsavel" class="form-control" value="<?php echo htmlspecialchars($user['responsavel'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="responsavel_email" class="form-control" value="<?php echo htmlspecialchars($user['responsavel_email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Data de nascimento</label>
                    <input type="date" name="responsavel_nascimento" class="form-control" value="<?php echo htmlspecialchars($user['responsavel_nascimento'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Endereço</label>
                    <input type="text" name="responsavel_endereco" class="form-control" value="<?php echo htmlspecialchars($user['responsavel_endereco'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" name="responsavel_telefone" class="form-control" value="<?php echo htmlspecialchars($user['responsavel_telefone'] ?? ''); ?>">
                </div>
                <h3>Pai</h3>
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="pai_nome" class="form-control" value="<?php echo htmlspecialchars($user['pai_nome'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Identidade</label>
                    <input type="text" name="pai_identidade" class="form-control" value="<?php echo htmlspecialchars($user['pai_identidade'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="pai_email" class="form-control" value="<?php echo htmlspecialchars($user['pai_email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Data de nascimento</label>
                    <input type="date" name="pai_nascimento" class="form-control" value="<?php echo htmlspecialchars($user['pai_nascimento'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" name="pai_telefone" class="form-control" value="<?php echo htmlspecialchars($user['pai_telefone'] ?? ''); ?>">
                </div>
                <h3>Mãe</h3>
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="mae_nome" class="form-control" value="<?php echo htmlspecialchars($user['mae_nome'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Identidade</label>
                    <input type="text" name="mae_identidade" class="form-control" value="<?php echo htmlspecialchars($user['mae_identidade'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="mae_email" class="form-control" value="<?php echo htmlspecialchars($user['mae_email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Data de nascimento</label>
                    <input type="date" name="mae_nascimento" class="form-control" value="<?php echo htmlspecialchars($user['mae_nascimento'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" name="mae_telefone" class="form-control" value="<?php echo htmlspecialchars($user['mae_telefone'] ?? ''); ?>">
                </div>
                <h3>Renda da casa</h3>
                <div class="form-group">
                    <label>Quantidade de moradores</label>
                    <input type="number" name="renda_moradores" class="form-control" value="<?php echo htmlspecialchars($user['renda_moradores'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Quantidade de cômodos</label>
                    <input type="number" name="renda_comodos" class="form-control" value="<?php echo htmlspecialchars($user['renda_comodos'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Quantidade de telefones</label>
                    <input type="number" name="renda_telefones" class="form-control" value="<?php echo htmlspecialchars($user['renda_telefones'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Renda aproximada</label>
                    <input type="number" name="renda_valor" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($user['renda_valor'] ?? ''); ?>">
                </div>
                <h3>Documentos</h3>
                <div class="form-group">
                    <label>Documento do responsável</label>
                    <?php if (!empty($user['doc_responsavel']) && file_exists($user['doc_responsavel'])): ?>
                        <div style="margin-bottom:8px;">
                            <a href="<?php echo $user['doc_responsavel']; ?>" target="_blank">Abrir atual</a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="doc_responsavel" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="form-group">
                    <label>Documento da bailarina</label>
                    <?php if (!empty($user['doc_bailarina']) && file_exists($user['doc_bailarina'])): ?>
                        <div style="margin-bottom:8px;">
                            <a href="<?php echo $user['doc_bailarina']; ?>" target="_blank">Abrir atual</a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="doc_bailarina" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="form-group">
                    <label>Comprovante de residência</label>
                    <?php if (!empty($user['doc_residencia']) && file_exists($user['doc_residencia'])): ?>
                        <div style="margin-bottom:8px;">
                            <a href="<?php echo $user['doc_residencia']; ?>" target="_blank">Abrir atual</a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="doc_residencia" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="form-group">
                    <label>Comprovante de renda</label>
                    <?php if (!empty($user['doc_renda']) && file_exists($user['doc_renda'])): ?>
                        <div style="margin-bottom:8px;">
                            <a href="<?php echo $user['doc_renda']; ?>" target="_blank">Abrir atual</a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="doc_renda" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="form-group">
                    <label>Foto da bailarina</label>
                    <?php if (!empty($user['foto_bailarina']) && file_exists($user['foto_bailarina'])): ?>
                        <div style="margin-bottom:8px;">
                            <img src="<?php echo $user['foto_bailarina']; ?>" alt="Foto" style="max-width:160px; border:1px solid #eee; border-radius:6px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="foto_bailarina" class="form-control" accept="image/*">
                </div>
                <input type="hidden" name="step" value="4">
                <div style="margin-top: 30px; text-align:center;">
                    <button type="submit" class="btn" style="min-width:200px;">Salvar Rematrícula</button>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
@keyframes spin { 0% { transform: translateY(-50%) rotate(0deg); } 100% { transform: translateY(-50%) rotate(360deg); } }
.input-wrapper { position: relative; }
.loading-spinner {
    position: absolute; right: 10px; top: 38px;
    width: 20px; height: 20px;
    border: 2px solid #f3f3f3; border-top: 2px solid #3498db; border-radius: 50%;
    animation: spin 1s linear infinite;
    display: none;
    pointer-events: none;
}
.input-wrapper.loading .loading-spinner { display: block; }
.search-feedback { font-size: 0.85em; margin-top: 5px; min-height: 20px; font-weight: 500; }
.input-success { border-color: #28a745 !important; box-shadow: 0 0 0 0.2rem rgba(40,167,69,.25) !important; }
.input-error { border-color: #dc3545 !important; box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25) !important; }
.input-warning { border-color: #ffc107 !important; box-shadow: 0 0 0 0.2rem rgba(255,193,7,.25) !important; }
.text-success { color: #28a745; }
.text-danger { color: #dc3545; }
.text-warning { color: #856404; }
</style>
<script>
(function() {
    const input = document.getElementById('nome_busca');
    if (!input) return;

    const wrapper = input.closest('.input-wrapper');
    const feedback = document.getElementById('search_feedback');
    let debounceTimer;

    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const term = this.value.trim();
        
        // Reset states
        wrapper.classList.remove('loading');
        input.classList.remove('input-success', 'input-error', 'input-warning');
        feedback.className = 'search-feedback';
        feedback.textContent = '';
        
        if (term.length < 3) return;

        wrapper.classList.add('loading');
        
        debounceTimer = setTimeout(() => {
            fetch('ajax_rematricula_search.php?term=' + encodeURIComponent(term))
                .then(res => res.json())
                .then(data => {
                    wrapper.classList.remove('loading');
                    processResults(data.results, term);
                })
                .catch(err => {
                    wrapper.classList.remove('loading');
                    console.error(err);
                });
        }, 300);
    });

    function processResults(results, term) {
        input.classList.remove('input-success', 'input-error', 'input-warning');
        feedback.textContent = '';
        feedback.className = 'search-feedback';

        if (!results || results.length === 0) {
            input.classList.add('input-error');
            feedback.classList.add('text-danger');
            feedback.textContent = 'Cadastro não localizado. Verifique a digitação.';
            return;
        }

        // Filtra scores altos (> 80) e exatos
        const highConfidence = results.filter(r => r.score >= 80);
        const exactMatches = results.filter(r => r.is_exact === true);
        
        // Prioriza Match Exato (mesmo se houver duplicatas exatas, pega o primeiro/mais recente)
        if (exactMatches.length >= 1) {
            const match = exactMatches[0];
            
            input.classList.add('input-success');
            feedback.classList.add('text-success');
            feedback.textContent = 'Encontrado: ' + match.nome;
            
            // Preencher valor correto se necessário
            if (input.value !== match.nome) {
                input.value = match.nome;
            }
            
            // Auto-submit
            input.readOnly = true;
            setTimeout(() => {
                if(input.form) input.form.submit();
            }, 800);
            return;
        }

        // Se não tem exato, tenta match único de alta confiança
        if (highConfidence.length === 1) {
            const match = highConfidence[0];
            
            input.classList.add('input-success');
            feedback.classList.add('text-success');
            feedback.textContent = 'Encontrado: ' + match.nome;
            
            if (input.value !== match.nome) {
                input.value = match.nome;
            }
            
            input.readOnly = true;
            setTimeout(() => {
                if(input.form) input.form.submit();
            }, 800);
            
        } else if (highConfidence.length > 1) {
            // Ambiguidade alta (sem match exato)
            // Ambiguidade alta
            input.classList.add('input-warning');
            feedback.classList.add('text-warning');
            feedback.textContent = 'Encontramos mais de um cadastro com esse nome. Por favor, digite seu nome completo para filtrar.';
        } else {
            // Resultados parciais
            input.classList.add('input-warning');
            feedback.classList.add('text-warning');
            feedback.textContent = 'Encontramos nomes parecidos. Continue digitando.';
            if (results.length > 0 && results[0].score > 60) {
                 feedback.textContent += ' Você quis dizer: ' + results[0].nome + '?';
            }
        }
    }
})();
</script>

<?php include 'includes/footer.php'; ?>
