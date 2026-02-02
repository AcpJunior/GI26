<?php
require_once 'includes/data.php';
$config = getSiteConfig();
$matriculas_abertas = $config['matriculas_abertas'] ?? false;

$sucesso = false;
$erro = '';
if ($matriculas_abertas && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $updates = [];
            $fileFields = ['doc_responsavel', 'doc_bailarina', 'doc_residencia', 'doc_renda', 'foto_bailarina'];
            foreach ($fileFields as $field) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
                    $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                    $randomName = md5(uniqid('', true)) . ($ext ? '.' . $ext : '');
                    $target = $uploadDir . $randomName;
                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
                        $updates[$field] = $target;
                    }
                }
            }
            if (!empty($updates)) {
                atualizarUsuario($id, $updates);
            }
            $sucesso = true;
        } else {
            $erro = 'Falha ao enviar matrícula. Tente novamente.';
        }
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h2 class="page-title" style="text-align:center;">Matrícula</h2>
    <?php if (!$matriculas_abertas): ?>
        <div class="message-box">
            <div class="dev-icon">
                <i class="fas fa-clock" style="color:#dc3545;"></i>
            </div>
            <h3>Matrículas Encerradas</h3>
            <p>No momento as matrículas não estão disponíveis. Por favor, aguarde o próximo período.</p>
            <div style="margin-top: 20px;">
                <a href="index.php" class="btn" style="width:auto;">Voltar para Home</a>
            </div>
        </div>
    <?php elseif ($sucesso): ?>
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
        <form method="post" action="matriculas.php" enctype="multipart/form-data">
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
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
