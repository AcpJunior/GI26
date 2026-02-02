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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_rematricula_todas'])) {
    $ok = function_exists('atualizarStatusTodos') ? atualizarStatusTodos('Pendente') : false;
    if ($ok) {
        $_SESSION['dash_msg'] = "<div class='success-msg'>Todas as alunas foram marcadas como Pendente.</div>";
    } else {
        $_SESSION['dash_msg'] = "<div class='error-msg'>Falha ao atualizar status em massa.</div>";
    }
    header("location: dashboard.php");
    exit;
}
// Atribuir turma via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atribuir_turma'])) {
    $alunoId = intval($_POST['id'] ?? 0);
    $turmaSel = trim($_POST['turma'] ?? '');
    if ($alunoId > 0 && $turmaSel !== '' && function_exists('definirTurmaAluno')) {
        definirTurmaAluno($alunoId, $turmaSel);
    }
}

// Obter usuários do arquivo centralizado
$recalc = function_exists('recalcularIdades') ? recalcularIdades() : false;
$usuariosAll = getUsuarios();
$usuarios = is_array($usuariosAll) ? array_values(array_filter($usuariosAll, function($u){ return ($u['status'] ?? '') !== 'Cancelado'; })) : [];
$dbOk = function_exists('pdoDisponivel') ? pdoDisponivel() : false;
$total = is_array($usuarios) ? count($usuarios) : 0;
// Listar turmas disponíveis
$turmas = function_exists('getTurmas') ? getTurmas() : [];

// Função auxiliar para calcular idade
function calcularIdade($dataNascimento) {
    $dataNasc = new DateTime($dataNascimento);
    $hoje = new DateTime();
    $diferenca = $hoje->diff($dataNasc);
    return $diferenca->y;
}
?>
<?php include 'includes/header.php'; ?>

<div class="container">
    <?php if (!empty($_SESSION['dash_msg'])) { echo $_SESSION['dash_msg']; unset($_SESSION['dash_msg']); } ?>
    <div class="dashboard-header">
        <h2 class="page-title" style="margin-bottom: 0; text-align: left;">Painel Administrativo</h2>
        <div class="header-actions">
            <a href="settings.php" class="btn btn-export" title="Configurações">
                <i class="fas fa-cog"></i> Configurações
            </a>
            <form method="post" style="display:inline-block; margin-left:8px;" onsubmit="return confirm('Marcar TODAS como Pendente para solicitar rematrícula?');">
                <input type="hidden" name="solicitar_rematricula_todas" value="1">
                <button type="submit" class="btn btn-export" title="Solicitar rematrícula de todas">
                    <i class="fas fa-bell"></i> Solicitar rematrícula de todas
                </button>
            </form>
            <div id="exportContainer" style="position:relative; display:inline-block;">
                <button type="button" class="btn btn-export" onclick="toggleExportMenu()">
                    <i class="fas fa-file-excel"></i> Exportar
                </button>
                <div id="exportMenu" style="position:absolute; right:0; top:110%; background:#fff; border:1px solid #eee; border-radius:6px; box-shadow:0 6px 16px rgba(0,0,0,0.08); display:none; min-width:220px;">
                    <button type="button" class="btn btn-sm" style="width:100%; text-align:left;" onclick="exportAll()">Exportar tudo</button>
                    <button type="button" class="btn btn-sm" style="width:100%; text-align:left;" onclick="exportFiltered()">Exportar filtro</button>
                    <button type="button" class="btn btn-sm" style="width:100%; text-align:left;" onclick="openCustomExport()">Exportar personalizável</button>
                </div>
            </div>
            <a href="logout.php" class="btn btn-logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <?php if (!$dbOk): ?>
        <div class="error-msg" style="margin-bottom: 20px;">
            Banco de dados indisponível. Exibindo painel sem dados.
        </div>
    <?php endif; ?>
    <div style="margin-bottom:10px; color:#666; font-size:0.95rem;">
        Total de alunos: <?php echo $total; ?>
    </div>
    <!-- Barra de Filtro -->
    <div class="filter-container" style="margin-bottom: 20px;">
        <div id="smartFilter" style="background:#fafafa; border:1px solid #eee; border-radius:8px; padding:12px;">
            <div style="margin-bottom:8px; color:#666; font-weight:500;">Filtros avançados</div>
            <div id="tokenInput" style="display:flex; flex-wrap:wrap; gap:6px; align-items:center; border:1px solid #e5e5e5; border-radius:8px; padding:6px; background:#fff;">
                <input id="filterText" type="text" class="form-control" style="flex:1; border:none; box-shadow:none;" placeholder="Digite: campo + critério + valor...">
            </div>
            <div id="suggestions" style="position:relative;">
                <div id="suggestionsList" style="position:absolute; z-index:10; left:0; right:0; background:#fff; border:1px solid #eee; border-radius:6px; box-shadow:0 6px 16px rgba(0,0,0,0.06); display:none;"></div>
            </div>
            <div id="activeRules" style="margin-top:10px; display:flex; flex-wrap:wrap; gap:6px;"></div>
            <div style="display:flex; gap:8px; margin-top:10px;">
                <button type="button" class="btn" style="background-color:#bdc3c7;" onclick="clearAllRules()">Limpar</button>
            </div>
        </div>
    </div>

    <div class="dashboard-table-container">
        <table class="dashboard-table" id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Idade</th>
                    <th>Email Responsável</th>
                    <th>Turma</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total === 0): ?>
                <tr><td colspan="7" style="text-align:center; color:#666;">Nenhum registro encontrado.</td></tr>
                <?php else: foreach($usuarios as $user): 
                    $idade = isset($user['idade']) ? $user['idade'] : (isset($user['nascimento']) ? calcularIdade($user['nascimento']) : 'N/A');
                ?>
                <tr class="user-row">
                    <td data-label="ID">#<?php echo $user['id']; ?></td>
                    <td data-label="Nome" class="user-name">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <?php $thumb = $user['foto_bailarina'] ?? null; ?>
                            <?php if($thumb && file_exists($thumb)): ?>
                                <img src="<?php echo $thumb; ?>" alt="Foto" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid #eee;">
                            <?php else: ?>
                                <i class="fas fa-user-circle" style="font-size: 24px; color: #ddd;"></i>
                            <?php endif; ?>
                            <strong><?php echo $user['nome']; ?></strong>
                        </div>
                    </td>
                    <td data-label="Idade" class="user-age"><?php echo $idade; ?> anos</td>
                    <td data-label="Email Responsável" class="user-email"><?php echo $user['responsavel_email'] ?? ''; ?></td>
                    <td data-label="Turma" class="user-turma">
                        <?php if (!empty($user['turma'])): ?>
                            <?php echo $user['turma']; ?>
                        <?php else: ?>
                            <form method="post" style="display:flex; gap:8px; align-items:center;">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="atribuir_turma" value="1">
                                <select name="turma" class="form-control" style="min-width:180px;">
                                    <option value="">Selecionar turma...</option>
                                    <?php foreach ($turmas as $t): ?>
                                        <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm" style="background-color:#3498db; color:#fff;">Atribuir</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td data-label="Status">
                        <span class="status-badge <?php echo $user['status'] == 'Ativo' ? 'status-active' : 'status-pending'; ?>">
                            <?php echo $user['status']; ?>
                        </span>
                    </td>
                    <td data-label="Ações">
                        <div class="action-buttons">
                            <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-edit" title="Editar">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <div id="noResults" style="display: none; text-align: center; padding: 20px; color: #666;">
            Nenhum usuário encontrado.
        </div>
    </div>
</div>

<!-- Modal Exportação Personalizável -->
<div id="customExportModal" style="display:none; position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(0,0,0,0.35); z-index:100000; padding:16px; overflow:auto;">
    <div style="background:#fff; border-radius:8px; width:100%; max-width:720px; margin:40px auto; padding:20px; box-shadow:0 8px 24px rgba(0,0,0,0.15); max-height:85vh; overflow:auto;">
        <h3 style="margin:0 0 12px 0; color:var(--primary-color);">Exportar personalizável</h3>
        <p style="color:#666; margin:0 0 12px 0;">Selecione os campos e escolha se deseja usar o filtro atual.</p>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:10px;" id="customFieldsWrap"></div>
        <div style="margin-top:12px;">
            <label style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" id="customUseFilter">
                <span>Usar filtro atual</span>
            </label>
        </div>
        <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:16px;">
            <button type="button" class="btn" style="background:#bdc3c7;" onclick="closeCustomExport()">Cancelar</button>
            <button type="button" class="btn" style="background:var(--primary-color);" onclick="submitCustomExport()">Exportar</button>
        </div>
    </div>
</div>

<script>
const fields = [
    { key: 'nome', label: 'Nome', type: 'text', selector: '.user-name' },
    { key: 'idade', label: 'Idade', type: 'number', selector: '.user-age' },
    { key: 'email', label: 'Email Responsável', type: 'text', selector: '.user-email' },
    { key: 'turma', label: 'Turma', type: 'text', selector: '.user-turma' },
    { key: 'status', label: 'Status', type: 'text', selector: 'td[data-label="Status"] .status-badge' }
];

const operators = [
    { key: 'eq', label: 'igual a' },
    { key: 'neq', label: 'diferente de' },
    { key: 'contains', label: 'contém' },
    { key: 'ncontains', label: 'não contém' },
    { key: 'starts', label: 'começa com' },
    { key: 'ends', label: 'termina com' },
    { key: 'gt', label: 'maior que', allow: ['number'] },
    { key: 'lt', label: 'menor que', allow: ['number'] }
];

let rules = [];
let stage = 'field'; // field -> operator -> value
let currentField = null;
let currentOperator = null;

function normalizeText(t) { return (t || '').toString().trim(); }

function renderChip(container, text, type, payload) {
    const chip = document.createElement('span');
    chip.className = 'chip';
    chip.style = 'display:inline-flex; align-items:center; gap:6px; padding:4px 8px; border:1px solid #eee; border-radius:12px; background:#f7f7f7; font-size:0.9rem;';
    chip.textContent = text + ' ';
    const x = document.createElement('span');
    x.textContent = '×';
    x.style = 'cursor:pointer; color:#999;';
    x.onclick = () => {
        chip.remove();
        if (type === 'field') { currentField = null; stage = 'field'; }
        if (type === 'op')    { currentOperator = null; stage = 'operator'; }
        document.getElementById('filterText').value = '';
        showSuggestions();
        focusFilter();
    };
    chip.appendChild(x);
    container.insertBefore(chip, document.getElementById('filterText'));
}

function focusFilter() {
    const input = document.getElementById('filterText');
    input.focus();
}

function showSuggestions(list = []) {
    const box = document.getElementById('suggestionsList');
    if (!list || list.length === 0) {
        box.style.display = 'none';
        box.innerHTML = '';
        return;
    }
    box.innerHTML = list.map(item => `
        <div class="sugg-item" data-key="${item.key}" style="padding:8px 10px; cursor:pointer;">
            ${item.label}
        </div>
    `).join('');
    box.style.display = 'block';
    Array.from(box.querySelectorAll('.sugg-item')).forEach(el => {
        el.addEventListener('mouseenter', () => { el.style.background = '#f6fbff'; });
        el.addEventListener('mouseleave', () => { el.style.background = '#fff'; });
        el.addEventListener('click', () => {
            const key = el.getAttribute('data-key');
            onSuggestionSelected(key);
        });
    });
}

function onSuggestionSelected(key) {
    const tokenInput = document.getElementById('tokenInput');
    const input = document.getElementById('filterText');

    if (stage === 'field') {
        const f = fields.find(x => x.key === key);
        if (!f) return;
        currentField = f;
        renderChip(tokenInput, f.label, 'field', f);
        stage = 'operator';
        input.value = '';
        updateSuggestions();
    } else if (stage === 'operator') {
        const o = operators.find(x => x.key === key);
        if (!o) return;
        currentOperator = o;
        renderChip(tokenInput, o.label, 'op', o);
        stage = 'value';
        input.value = '';
        showSuggestions(); // sem sugestões para valor
        focusFilter();
    }
}

function updateSuggestions() {
    const q = (document.getElementById('filterText').value || '').toLowerCase();
    if (!q) { 
        showSuggestions(); 
        return; 
    }
    if (stage === 'field') {
        const list = fields.filter(f => f.label.toLowerCase().includes(q));
        showSuggestions(list);
    } else if (stage === 'operator') {
        let list = operators;
        if (currentField && currentField.type === 'number') {
            list = operators.filter(o => !o.allow || o.allow.includes('number'));
        } else {
            list = operators.filter(o => !o.allow);
        }
        list = list.filter(o => o.label.toLowerCase().includes(q));
        showSuggestions(list);
    } else {
        showSuggestions(); // valor sem sugestões
    }
}

function getCellValue(tr, selector, type) {
    const el = tr.querySelector(selector);
    if (!el) return '';
    let text = normalizeText(el.textContent || el.innerText);
    if (type === 'number') {
        const m = text.match(/-?\d+(\.\d+)?/);
        return m ? parseFloat(m[0]) : NaN;
    }
    return text;
}

function matchOperator(fieldType, cellValue, operator, filterValue) {
    if (fieldType === 'number') {
        const numCell = Number(cellValue);
        const numFilter = Number(filterValue);
        if (isNaN(numFilter)) return true;
        switch (operator) {
            case 'eq': return numCell === numFilter;
            case 'neq': return numCell !== numFilter;
            case 'gt': return numCell > numFilter;
            case 'lt': return numCell < numFilter;
            default: return true;
        }
    } else {
        const a = (cellValue || '').toLowerCase();
        const b = (filterValue || '').toLowerCase();
        if (!b) return true;
        switch (operator) {
            case 'eq': return a === b;
            case 'neq': return a !== b;
            case 'contains': return a.includes(b);
            case 'ncontains': return !a.includes(b);
            case 'starts': return a.startsWith(b);
            case 'ends': return a.endsWith(b);
            default: return true;
        }
    }
}

function applyFiltersFromRules() {
    const rows = document.getElementsByClassName('user-row');
    let hasResults = false;
    for (let i = 0; i < rows.length; i++) {
        const tr = rows[i];
        let visible = true;
        for (const r of rules) {
            const conf = fields.find(f => f.key === r.field.key);
            if (!conf) continue;
            const cellVal = getCellValue(tr, conf.selector, conf.type);
            const ok = matchOperator(conf.type, cellVal, r.operator.key, r.value);
            if (!ok) { visible = false; break; }
        }
        tr.style.display = visible ? '' : 'none';
        if (visible) hasResults = true;
    }
    document.getElementById('noResults').style.display = hasResults ? 'none' : 'block';
}

function addRule(field, operator, value) {
    rules.push({ field, operator, value });
    renderRules();
    applyFiltersFromRules();
}

function renderRules() {
    const wrap = document.getElementById('activeRules');
    wrap.innerHTML = '';
    rules.forEach((r, idx) => {
        const chip = document.createElement('span');
        chip.style = 'display:inline-flex; align-items:center; gap:6px; padding:4px 8px; border:1px solid #e5e5e5; border-radius:12px; background:#fdfdfd;';
        chip.textContent = `${r.field.label} • ${r.operator.label} • ${r.value} `;
        const x = document.createElement('span');
        x.textContent = '×';
        x.style = 'cursor:pointer; color:#999;';
        x.onclick = () => {
            rules.splice(idx, 1);
            renderRules();
            applyFiltersFromRules();
        };
        chip.appendChild(x);
        wrap.appendChild(chip);
    });
}

function clearAllRules() {
    rules = [];
    document.getElementById('activeRules').innerHTML = '';
    document.getElementById('tokenInput').querySelectorAll('.chip').forEach(c => c.remove());
    document.getElementById('filterText').value = '';
    stage = 'field';
    currentField = null;
    currentOperator = null;
    showSuggestions();
    applyFiltersFromRules();
}

// Export helpers
function toggleExportMenu() {
    const menu = document.getElementById('exportMenu');
    const container = document.getElementById('exportContainer');
    const isOpen = menu.style.display === 'block';
    menu.style.display = isOpen ? 'none' : 'block';
    function onDocClick(e) {
        if (!container.contains(e.target)) {
            menu.style.display = 'none';
            document.removeEventListener('click', onDocClick);
        }
    }
    if (!isOpen) {
        setTimeout(() => document.addEventListener('click', onDocClick), 0);
    }
}

function getVisibleIds() {
    const rows = Array.from(document.getElementsByClassName('user-row'));
    const ids = [];
    rows.forEach(tr => {
        const style = window.getComputedStyle(tr);
        if (style.display !== 'none') {
            const idCell = tr.querySelector('td[data-label="ID"]');
            if (idCell) {
                const text = (idCell.textContent || '').trim();
                const num = parseInt(text.replace('#',''), 10);
                if (!isNaN(num)) ids.push(num);
            }
        }
    });
    return ids;
}

function exportAll() {
    window.location.href = 'export.php?mode=all';
}

function exportFiltered() {
    const ids = getVisibleIds();
    const url = 'export.php?mode=filtered' + (ids.length ? '&ids=' + encodeURIComponent(ids.join(',')) : '');
    window.location.href = url;
}

const exportFields = [
    { key: 'id', label: 'ID' },
    { key: 'nome', label: 'Nome (Bailarina)' },
    { key: 'idade', label: 'Idade (Bailarina)' },
    { key: 'nome_social', label: 'Nome Social' },
    { key: 'identidade', label: 'Identidade (Bailarina)' },
    { key: 'nascimento', label: 'Data Nascimento' },
    { key: 'data_matricula', label: 'Data Matrícula' },
    { key: 'status', label: 'Status' },
    { key: 'endereco', label: 'Endereço' },
    { key: 'telefone', label: 'Telefone' },
    { key: 'turma', label: 'Turma' },
    { key: 'responsavel', label: 'Responsável - Nome' },
    { key: 'idade_responsavel', label: 'Idade (Responsável)' },
    { key: 'responsavel_identidade', label: 'Responsável - Identidade' },
    { key: 'responsavel_email', label: 'Responsável - Email' },
    { key: 'responsavel_nascimento', label: 'Responsável - Nascimento' },
    { key: 'responsavel_telefone', label: 'Responsável - Telefone' },
    { key: 'pai_nome', label: 'Pai - Nome' },
    { key: 'idade_pai', label: 'Idade (Pai)' },
    { key: 'pai_identidade', label: 'Pai - Identidade' },
    { key: 'pai_email', label: 'Pai - Email' },
    { key: 'pai_nascimento', label: 'Pai - Nascimento' },
    { key: 'pai_telefone', label: 'Pai - Telefone' },
    { key: 'mae_nome', label: 'Mãe - Nome' },
    { key: 'idade_mae', label: 'Idade (Mãe)' },
    { key: 'mae_identidade', label: 'Mãe - Identidade' },
    { key: 'mae_email', label: 'Mãe - Email' },
    { key: 'mae_nascimento', label: 'Mãe - Nascimento' },
    { key: 'mae_telefone', label: 'Mãe - Telefone' },
    { key: 'renda_moradores', label: 'Renda - Moradores' },
    { key: 'renda_comodos', label: 'Renda - Cômodos' },
    { key: 'renda_telefones', label: 'Renda - Telefones' },
    { key: 'renda_valor', label: 'Renda - Valor' },
    { key: 'doc_responsavel', label: 'Doc Responsável' },
    { key: 'doc_bailarina', label: 'Doc Bailarina' },
    { key: 'doc_residencia', label: 'Doc Residência' },
    { key: 'doc_renda', label: 'Doc Renda' },
    { key: 'email_responsavel_extra', label: 'Email Responsável' },
    { key: 'observacoes', label: 'Observações' }
];

function openCustomExport() {
    const wrap = document.getElementById('customFieldsWrap');
    wrap.innerHTML = exportFields.map(f => `
        <label style="display:flex; align-items:center; gap:8px; border:1px solid #eee; border-radius:6px; padding:8px;">
            <input type="checkbox" class="custom-field" value="${f.key}">
            <span>${f.label}</span>
        </label>
    `).join('');
    document.getElementById('customUseFilter').checked = false;
    document.getElementById('customExportModal').style.display = 'block';
}

function closeCustomExport() {
    document.getElementById('customExportModal').style.display = 'none';
}

function submitCustomExport() {
    const selected = Array.from(document.querySelectorAll('.custom-field'))
        .filter(cb => cb.checked)
        .map(cb => cb.value);
    if (!selected.length) { alert('Selecione ao menos um campo.'); return; }
    const useFilter = document.getElementById('customUseFilter').checked;
    const ids = useFilter ? getVisibleIds() : [];
    const params = new URLSearchParams();
    params.set('mode', 'custom');
    params.set('fields', selected.join(','));
    if (ids.length) params.set('ids', ids.join(','));
    window.location.href = 'export.php?' + params.toString();
    closeCustomExport();
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('filterText');
    input.addEventListener('input', updateSuggestions);
    input.addEventListener('focus', () => {
        const q = (input.value || '').trim();
        if (stage === 'field') {
            const list = q ? fields.filter(f => f.label.toLowerCase().includes(q.toLowerCase())) : fields;
            showSuggestions(list);
        } else if (stage === 'operator') {
            let list = operators;
            if (currentField && currentField.type === 'number') {
                list = operators.filter(o => !o.allow || o.allow.includes('number'));
            } else {
                list = operators.filter(o => !o.allow);
            }
            list = q ? list.filter(o => o.label.toLowerCase().includes(q.toLowerCase())) : list;
            showSuggestions(list);
        } else {
            showSuggestions(); // valor não tem sugestões
        }
    });
    input.addEventListener('blur', () => {
        setTimeout(() => showSuggestions(), 120);
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && stage === 'value') {
            e.preventDefault();
            const val = normalizeText(input.value);
            if (!val) return;
            addRule(currentField, currentOperator, val);
            input.value = '';
            document.getElementById('tokenInput').querySelectorAll('.chip').forEach(c => c.remove());
            stage = 'field';
            currentField = null;
            currentOperator = null;
            showSuggestions();
        } else if (e.key === 'Backspace') {
            const hasText = normalizeText(input.value).length > 0;
            if (!hasText) {
                // Remover último chip da construção atual
                const chips = document.getElementById('tokenInput').querySelectorAll('.chip');
                const last = chips[chips.length - 1];
                if (last) {
                    last.remove();
                    if (stage === 'value') { stage = 'operator'; currentOperator = null; }
                    else if (stage === 'operator') { stage = 'field'; currentField = null; }
                    showSuggestions();
                }
            }
        }
    });
    showSuggestions();
});
</script>

<?php include 'includes/footer.php'; ?>
