<?php
$sessionPath = __DIR__ . '/files/sessions';
if (!is_dir($sessionPath)) { @mkdir($sessionPath, 0755, true); }
if (is_dir($sessionPath) && is_writable($sessionPath)) { session_save_path($sessionPath); }
session_start();

// Verificar login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

require_once 'includes/data.php';

// Verificar ID
if (!isset($_GET['id'])) {
    header("location: dashboard.php");
    exit;
}

$id = $_GET['id'];
$user = getUsuarioPorId($id);

if (!$user) {
    echo "Usuário não encontrado.";
    exit;
}

$msg = '';

// Processar formulário de edição
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['cancelar_matricula'])) {
        if (cancelarMatricula($id)) {
            $msg = "<div class='success-msg'>Matrícula cancelada com sucesso!</div>";
            $user = getUsuarioPorId($id); // Recarregar dados
        } else {
            $msg = "<div class='error-msg'>Erro ao cancelar matrícula.</div>";
        }
    } else {
        // Campos básicos
        $dadosAtualizados = [
            'nome' => $_POST['nome'],
            'identidade' => $_POST['identidade'],
            'nome_social' => $_POST['nome_social'],
            'nascimento' => $_POST['nascimento'],
            'endereco' => $_POST['endereco'],
            'telefone' => $_POST['telefone'],
            'turma' => $_POST['turma'],
            'status' => $_POST['status'],
            
            // Responsável
            'responsavel' => $_POST['responsavel'],
            'responsavel_identidade' => $_POST['responsavel_identidade'],
            'responsavel_email' => $_POST['responsavel_email'],
            'responsavel_nascimento' => $_POST['responsavel_nascimento'],
            'responsavel_telefone' => $_POST['responsavel_telefone'],
            
            // Pai
            'pai_nome' => $_POST['pai_nome'],
            'pai_identidade' => $_POST['pai_identidade'],
            'pai_email' => $_POST['pai_email'],
            'pai_nascimento' => $_POST['pai_nascimento'],
            'pai_telefone' => $_POST['pai_telefone'],
            
            // Mãe
            'mae_nome' => $_POST['mae_nome'],
            'mae_identidade' => $_POST['mae_identidade'],
            'mae_email' => $_POST['mae_email'],
            'mae_nascimento' => $_POST['mae_nascimento'],
            'mae_telefone' => $_POST['mae_telefone'],
            
            // Renda
            'renda_moradores' => $_POST['renda_moradores'],
            'renda_comodos' => $_POST['renda_comodos'],
            'renda_telefones' => $_POST['renda_telefones'],
            'renda_valor' => $_POST['renda_valor'],
            
            'observacoes' => $_POST['observacoes']
        ];

        // Processar Uploads de Arquivos
        $uploadDir = 'files/' . $id . '/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileFields = ['doc_responsavel', 'doc_bailarina', 'doc_residencia', 'doc_renda', 'foto_bailarina'];
        $uploadErrors = [];

        foreach ($fileFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                // Nome aleatório: md5(uniqid())
                $randomName = md5(uniqid()) . '.' . $ext;
                $targetFile = $uploadDir . $randomName;
                
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetFile)) {
                    $dadosAtualizados[$field] = $targetFile;
                } else {
                    $uploadErrors[] = "Erro ao enviar arquivo para o campo $field.";
                }
            }
        }

        if (atualizarUsuario($id, $dadosAtualizados)) {
            $msg = "<div class='success-msg'>Dados atualizados com sucesso!</div>";
            if (!empty($uploadErrors)) {
                $msg .= "<div class='error-msg'>" . implode('<br>', $uploadErrors) . "</div>";
            }
            $user = getUsuarioPorId($id); // Recarregar dados
        } else {
            $msg = "<div class='error-msg'>Erro ao atualizar dados.</div>";
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<style>
    .edit-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        padding: 40px;
        margin-top: 20px;
        animation: fadeIn 0.5s ease;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }

    .success-msg {
        color: #27ae60;
        background-color: #eafaf1;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        text-align: center;
        border: 1px solid #d5f5e3;
    }
    
    .danger-zone {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 2px solid #fee;
    }
    
    .danger-title {
        color: #c0392b;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .section-title {
        grid-column: 1 / -1;
        margin-top: 20px;
        margin-bottom: 10px;
        color: var(--primary-color);
        font-size: 1.2rem;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }

    .file-input-wrapper {
        border: 1px dashed #ddd;
        padding: 15px;
        border-radius: 4px;
        background: #fafafa;
    }
</style>

<div class="container">
    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Voltar para o Painel
    </a>

    <?php echo $msg; ?>

    <div class="edit-card">
        <h2 style="margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 15px;">Editar Aluno: <?php echo $user['nome']; ?></h2>

        <form method="post" action="user_edit.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
            <div class="form-grid">
                <!-- Bailarina -->
                <div class="section-title">Dados da Bailarina</div>
                
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" name="nome" id="nome" class="form-control" value="<?php echo $user['nome']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="identidade">Identidade (RG/CPF)</label>
                    <input type="text" name="identidade" id="identidade" class="form-control" value="<?php echo $user['identidade'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label for="nome_social">Nome Social</label>
                    <input type="text" name="nome_social" id="nome_social" class="form-control" value="<?php echo $user['nome_social'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label for="nascimento">Data de Nascimento</label>
                    <input type="date" name="nascimento" id="nascimento" class="form-control" value="<?php echo $user['nascimento']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="text" name="telefone" id="telefone" class="form-control" value="<?php echo $user['telefone']; ?>" required>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="endereco">Endereço</label>
                    <input type="text" name="endereco" id="endereco" class="form-control" value="<?php echo $user['endereco']; ?>">
                </div>

                <div class="form-group">
                    <label for="turma">Turma</label>
                    <select name="turma" id="turma" class="form-control">
                        <?php 
                            $turmas = function_exists('getTurmas') ? getTurmas() : [];
                            $turmaAtual = $user['turma'] ?? '';
                        ?>
                        <option value="" <?php echo empty($turmaAtual) ? 'selected' : ''; ?>>Selecionar turma...</option>
                        <?php foreach ($turmas as $t): ?>
                            <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $turmaAtual === $t ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="Ativo" <?php echo $user['status'] == 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="Pendente" <?php echo $user['status'] == 'Pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="Cancelado" <?php echo $user['status'] == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>

                <!-- Responsável -->
                <div class="section-title">Dados do Responsável</div>
                
                <div class="form-group">
                    <label for="responsavel">Nome do Responsável</label>
                    <input type="text" name="responsavel" id="responsavel" class="form-control" value="<?php echo $user['responsavel'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="responsavel_identidade">Identidade</label>
                    <input type="text" name="responsavel_identidade" id="responsavel_identidade" class="form-control" value="<?php echo $user['responsavel_identidade'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="responsavel_email">Email</label>
                    <input type="email" name="responsavel_email" id="responsavel_email" class="form-control" value="<?php echo $user['responsavel_email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="responsavel_nascimento">Data Nascimento</label>
                    <input type="date" name="responsavel_nascimento" id="responsavel_nascimento" class="form-control" value="<?php echo $user['responsavel_nascimento'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="responsavel_telefone">Telefone</label>
                    <input type="text" name="responsavel_telefone" id="responsavel_telefone" class="form-control" value="<?php echo $user['responsavel_telefone'] ?? ''; ?>">
                </div>

                <!-- Pai -->
                <div class="section-title">Dados do Pai</div>
                
                <div class="form-group">
                    <label for="pai_nome">Nome do Pai</label>
                    <input type="text" name="pai_nome" id="pai_nome" class="form-control" value="<?php echo $user['pai_nome'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="pai_identidade">Identidade</label>
                    <input type="text" name="pai_identidade" id="pai_identidade" class="form-control" value="<?php echo $user['pai_identidade'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="pai_email">Email</label>
                    <input type="email" name="pai_email" id="pai_email" class="form-control" value="<?php echo $user['pai_email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="pai_nascimento">Data Nascimento</label>
                    <input type="date" name="pai_nascimento" id="pai_nascimento" class="form-control" value="<?php echo $user['pai_nascimento'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="pai_telefone">Telefone</label>
                    <input type="text" name="pai_telefone" id="pai_telefone" class="form-control" value="<?php echo $user['pai_telefone'] ?? ''; ?>">
                </div>

                <!-- Mãe -->
                <div class="section-title">Dados da Mãe</div>
                
                <div class="form-group">
                    <label for="mae_nome">Nome da Mãe</label>
                    <input type="text" name="mae_nome" id="mae_nome" class="form-control" value="<?php echo $user['mae_nome'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="mae_identidade">Identidade</label>
                    <input type="text" name="mae_identidade" id="mae_identidade" class="form-control" value="<?php echo $user['mae_identidade'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="mae_email">Email</label>
                    <input type="email" name="mae_email" id="mae_email" class="form-control" value="<?php echo $user['mae_email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="mae_nascimento">Data Nascimento</label>
                    <input type="date" name="mae_nascimento" id="mae_nascimento" class="form-control" value="<?php echo $user['mae_nascimento'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="mae_telefone">Telefone</label>
                    <input type="text" name="mae_telefone" id="mae_telefone" class="form-control" value="<?php echo $user['mae_telefone'] ?? ''; ?>">
                </div>

                <!-- Renda -->
                <div class="section-title">Renda da Casa</div>
                
                <div class="form-group">
                    <label for="renda_moradores">Qtd. Moradores</label>
                    <input type="number" name="renda_moradores" id="renda_moradores" class="form-control" value="<?php echo $user['renda_moradores'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="renda_comodos">Qtd. Cômodos</label>
                    <input type="number" name="renda_comodos" id="renda_comodos" class="form-control" value="<?php echo $user['renda_comodos'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="renda_telefones">Qtd. Telefones</label>
                    <input type="number" name="renda_telefones" id="renda_telefones" class="form-control" value="<?php echo $user['renda_telefones'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="renda_valor">Renda Aproximada (R$)</label>
                    <input type="number" step="0.01" name="renda_valor" id="renda_valor" class="form-control" value="<?php echo $user['renda_valor'] ?? ''; ?>">
                </div>

                <!-- Documentos -->
                <div class="section-title">Upload de Documentos</div>
                
                <div class="form-group file-input-wrapper">
                    <label for="doc_responsavel">Documento Responsável</label>
                    <input type="file" name="doc_responsavel" id="doc_responsavel" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp">
                    <?php if(!empty($user['doc_responsavel']) && file_exists($user['doc_responsavel'])): ?>
                        <?php $p=$user['doc_responsavel']; $e=strtolower(pathinfo($p, PATHINFO_EXTENSION)); $isImg=in_array($e,['jpg','jpeg','png','gif','webp']); ?>
                        <div style="margin-top:8px;">
                            <?php if($isImg): ?>
                                <img src="<?php echo $p; ?>" alt="Documento Responsável" style="max-width:200px; border:1px solid #eee; border-radius:6px;">
                            <?php endif; ?>
                            <div><a href="<?php echo $p; ?>" target="_blank">Abrir atual</a></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group file-input-wrapper">
                    <label for="doc_bailarina">Documento Bailarina</label>
                    <input type="file" name="doc_bailarina" id="doc_bailarina" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp">
                    <?php if(!empty($user['doc_bailarina']) && file_exists($user['doc_bailarina'])): ?>
                        <?php $p=$user['doc_bailarina']; $e=strtolower(pathinfo($p, PATHINFO_EXTENSION)); $isImg=in_array($e,['jpg','jpeg','png','gif','webp']); ?>
                        <div style="margin-top:8px;">
                            <?php if($isImg): ?>
                                <img src="<?php echo $p; ?>" alt="Documento Bailarina" style="max-width:200px; border:1px solid #eee; border-radius:6px;">
                            <?php endif; ?>
                            <div><a href="<?php echo $p; ?>" target="_blank">Abrir atual</a></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group file-input-wrapper">
                    <label for="doc_residencia">Comprovante de Residência</label>
                    <input type="file" name="doc_residencia" id="doc_residencia" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp">
                    <?php if(!empty($user['doc_residencia']) && file_exists($user['doc_residencia'])): ?>
                        <?php $p=$user['doc_residencia']; $e=strtolower(pathinfo($p, PATHINFO_EXTENSION)); $isImg=in_array($e,['jpg','jpeg','png','gif','webp']); ?>
                        <div style="margin-top:8px;">
                            <?php if($isImg): ?>
                                <img src="<?php echo $p; ?>" alt="Comprovante de Residência" style="max-width:200px; border:1px solid #eee; border-radius:6px;">
                            <?php endif; ?>
                            <div><a href="<?php echo $p; ?>" target="_blank">Abrir atual</a></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group file-input-wrapper">
                    <label for="doc_renda">Comprovante de Renda</label>
                    <input type="file" name="doc_renda" id="doc_renda" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp">
                    <?php if(!empty($user['doc_renda']) && file_exists($user['doc_renda'])): ?>
                        <?php $p=$user['doc_renda']; $e=strtolower(pathinfo($p, PATHINFO_EXTENSION)); $isImg=in_array($e,['jpg','jpeg','png','gif','webp']); ?>
                        <div style="margin-top:8px;">
                            <?php if($isImg): ?>
                                <img src="<?php echo $p; ?>" alt="Comprovante de Renda" style="max-width:200px; border:1px solid #eee; border-radius:6px;">
                            <?php endif; ?>
                            <div><a href="<?php echo $p; ?>" target="_blank">Abrir atual</a></div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group file-input-wrapper">
                    <label for="foto_bailarina">Foto da Bailarina</label>
                    <input type="file" name="foto_bailarina" id="foto_bailarina" class="form-control" accept="image/*">
                    <?php if(!empty($user['foto_bailarina'])): ?>
                        <div style="margin-top:8px;">
                            <img src="<?php echo $user['foto_bailarina']; ?>" alt="Foto" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #eee;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="observacoes">Observações</label>
                    <textarea name="observacoes" id="observacoes" class="form-control" rows="4"><?php echo $user['observacoes']; ?></textarea>
                </div>
            </div>

            <div style="margin-top: 30px; text-align: right;">
                <button type="submit" class="btn" style="width: auto; background-color: var(--primary-color);">Salvar Alterações</button>
            </div>
        </form>

        <?php if($user['status'] != 'Cancelado'): ?>
        <div class="danger-zone">
            <div class="danger-title">Zona de Perigo</div>
            <p style="margin-bottom: 15px; font-size: 0.9rem; color: #666;">Cancelar a matrícula irá alterar o status do aluno para "Cancelado" e registrar a data do cancelamento.</p>
            <form method="post" onsubmit="return confirm('Tem certeza que deseja CANCELAR a matrícula deste aluno?');">
                <input type="hidden" name="cancelar_matricula" value="1">
                <button type="submit" class="btn" style="width: auto; background-color: #c0392b;">Cancelar Matrícula</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
