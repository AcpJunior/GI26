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

$user = getUsuarioPorId($_GET['id']);

if (!$user) {
    echo "Usuário não encontrado.";
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<style>
    .details-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        padding: 40px;
        margin-top: 20px;
        animation: fadeIn 0.5s ease;
    }
    
    .details-header {
        border-bottom: 1px solid #eee;
        padding-bottom: 20px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    .detail-item {
        margin-bottom: 20px;
    }

    .detail-label {
        font-size: 0.85rem;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .detail-value {
        font-size: 1.1rem;
        color: var(--heading-color);
        font-weight: 500;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #666;
        font-weight: 500;
        margin-bottom: 20px;
    }
    
    .back-btn:hover {
        color: var(--primary-color);
    }
</style>

<div class="container">
    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Voltar para o Painel
    </a>

    <div class="details-card">
        <div class="details-header">
            <div>
                <h2 style="margin-bottom: 5px;"><?php echo $user['nome']; ?></h2>
                <span class="status-badge <?php echo $user['status'] == 'Ativo' ? 'status-active' : 'status-pending'; ?>">
                    <?php echo $user['status']; ?>
                </span>
            </div>
            <div>
                <?php $foto = $user['foto_bailarina'] ?? null; ?>
                <?php if($foto && file_exists($foto)): ?>
                    <img src="<?php echo $foto; ?>" alt="Foto da Bailarina" style="width: 96px; height: 96px; border-radius: 50%; object-fit: cover; border: 2px solid #eee;">
                <?php else: ?>
                    <div style="font-size: 3rem; color: #f0f0f0;">
                        <i class="fas fa-user-circle"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="details-grid">
            <div class="detail-column">
                <h3 style="margin-bottom: 20px; color: var(--primary-color); font-size: 1.2rem;">Informações Pessoais</h3>
                
                <div class="detail-item">
                    <div class="detail-label">ID do Aluno</div>
                    <div class="detail-value">#<?php echo $user['id']; ?></div>
                </div>

                <!-- Email principal removido: usar email do responsável na seção própria -->

                <div class="detail-item">
                    <div class="detail-label">Data de Nascimento</div>
                    <div class="detail-value"><?php echo date('d/m/Y', strtotime($user['nascimento'])); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Nome do Responsável</div>
                    <div class="detail-value"><?php echo $user['responsavel'] ?? 'N/A'; ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Identidade (RG/CPF)</div>
                    <div class="detail-value"><?php echo $user['identidade'] ?? 'N/A'; ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Nome Social</div>
                    <div class="detail-value"><?php echo $user['nome_social'] ?? 'N/A'; ?></div>
                </div>
            </div>

            <div class="detail-column">
                <h3 style="margin-bottom: 20px; color: var(--primary-color); font-size: 1.2rem;">Dados de Matrícula e Contato</h3>
                
                <div class="detail-item">
                    <div class="detail-label">Turma</div>
                    <div class="detail-value"><?php echo $user['turma']; ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Telefone</div>
                    <div class="detail-value"><?php echo $user['telefone']; ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Endereço</div>
                    <div class="detail-value"><?php echo $user['endereco']; ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Data da Matrícula</div>
                    <div class="detail-value"><?php echo isset($user['data_matricula']) ? date('d/m/Y', strtotime($user['data_matricula'])) : 'N/A'; ?></div>
                </div>
            </div>
        </div>

        <!-- Seção Pais e Responsáveis -->
        <div style="margin-top: 40px;">
            <h3 style="margin-bottom: 20px; color: var(--primary-color); font-size: 1.2rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">Pais e Responsáveis</h3>
            <div class="details-grid">
                <!-- Responsável -->
                <div class="detail-column">
                    <h4 style="margin-bottom: 15px; color: #666;">Responsável Legal</h4>
                    <div class="detail-item">
                        <div class="detail-label">Nome</div>
                        <div class="detail-value"><?php echo $user['responsavel'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Identidade</div>
                        <div class="detail-value"><?php echo $user['responsavel_identidade'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo $user['responsavel_email'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Telefone</div>
                        <div class="detail-value"><?php echo $user['responsavel_telefone'] ?? 'N/A'; ?></div>
                    </div>
                </div>
                
                <!-- Pai -->
                <div class="detail-column">
                    <h4 style="margin-bottom: 15px; color: #666;">Pai</h4>
                    <div class="detail-item">
                        <div class="detail-label">Nome</div>
                        <div class="detail-value"><?php echo $user['pai_nome'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Telefone</div>
                        <div class="detail-value"><?php echo $user['pai_telefone'] ?? 'N/A'; ?></div>
                    </div>
                </div>

                <!-- Mãe -->
                <div class="detail-column">
                    <h4 style="margin-bottom: 15px; color: #666;">Mãe</h4>
                    <div class="detail-item">
                        <div class="detail-label">Nome</div>
                        <div class="detail-value"><?php echo $user['mae_nome'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Telefone</div>
                        <div class="detail-value"><?php echo $user['mae_telefone'] ?? 'N/A'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção Renda -->
        <div style="margin-top: 40px;">
            <h3 style="margin-bottom: 20px; color: var(--primary-color); font-size: 1.2rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">Informações Socioeconômicas</h3>
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Moradores na Casa</div>
                    <div class="detail-value"><?php echo $user['renda_moradores'] ?? 'N/A'; ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Cômodos</div>
                    <div class="detail-value"><?php echo $user['renda_comodos'] ?? 'N/A'; ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Renda Aproximada</div>
                    <div class="detail-value"><?php echo isset($user['renda_valor']) ? 'R$ ' . number_format($user['renda_valor'], 2, ',', '.') : 'N/A'; ?></div>
                </div>
            </div>
        </div>

        <!-- Seção Documentos -->
        <div style="margin-top: 40px;">
            <h3 style="margin-bottom: 20px; color: var(--primary-color); font-size: 1.2rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">Documentos Anexados</h3>
            <div class="details-grid">
                <?php 
                $docs = [
                    'Documento Responsável' => $user['doc_responsavel'] ?? null,
                    'Documento Bailarina' => $user['doc_bailarina'] ?? null,
                    'Comprovante Residência' => $user['doc_residencia'] ?? null,
                    'Comprovante Renda' => $user['doc_renda'] ?? null,
                    'Foto da Bailarina' => $user['foto_bailarina'] ?? null
                ];
                
                foreach($docs as $label => $path): 
                ?>
                <div class="detail-item">
                    <div class="detail-label"><?php echo $label; ?></div>
                    <div class="detail-value">
                        <?php if($path && file_exists($path)): ?>
                            <?php if($label === 'Foto da Bailarina'): ?>
                                <img src="<?php echo $path; ?>" alt="Foto da Bailarina" style="width: 160px; height: 160px; object-fit: cover; border-radius: 8px; border: 1px solid #eee;">
                            <?php else: ?>
                                <a href="<?php echo $path; ?>" target="_blank" class="btn btn-sm" style="background-color: #3498db; color: white;">
                                    <i class="fas fa-download"></i> Baixar Arquivo
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: #ccc; font-style: italic;">Não enviado</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        
        <!-- Observações removidas conforme solicitação -->
    </div>
</div>

<?php include 'includes/footer.php'; ?>
