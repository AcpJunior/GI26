<?php
$sessionPath = __DIR__ . '/files/sessions';
if (!is_dir($sessionPath)) { @mkdir($sessionPath, 0755, true); }
if (is_dir($sessionPath) && is_writable($sessionPath)) { session_save_path($sessionPath); }
session_start();

if (function_exists('ob_get_level')) { while (ob_get_level() > 0) { @ob_end_clean(); } }
ini_set('display_errors', '0');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

require_once 'includes/data.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'all';
$allowedModes = ['all','filtered','custom'];
if (!in_array($mode, $allowedModes, true)) { $mode = 'all'; }
$idsParam = isset($_GET['ids']) ? $_GET['ids'] : '';
$ids = array_filter(array_map('intval', explode(',', $idsParam)));

$columnsMap = [
    'idade' => ['label' => 'Idade (Bailarina)', 'value' => function($u){
        $d = $u['nascimento'] ?? '';
        if (!$d || $d == '0000-00-00') return '';
        try {
            $n = new DateTime($d);
            $h = new DateTime();
            return $h->diff($n)->y;
        } catch (Exception $e) { return ''; }
    }],
    'idade_responsavel' => ['label' => 'Idade (Responsável)', 'value' => function($u){
        $d = $u['responsavel_nascimento'] ?? '';
        if (!$d || $d == '0000-00-00') return '';
        try {
            $n = new DateTime($d);
            $h = new DateTime();
            return $h->diff($n)->y;
        } catch (Exception $e) { return ''; }
    }],
    'idade_pai' => ['label' => 'Idade (Pai)', 'value' => function($u){
        $d = $u['pai_nascimento'] ?? '';
        if (!$d || $d == '0000-00-00') return '';
        try {
            $n = new DateTime($d);
            $h = new DateTime();
            return $h->diff($n)->y;
        } catch (Exception $e) { return ''; }
    }],
    'idade_mae' => ['label' => 'Idade (Mãe)', 'value' => function($u){
        $d = $u['mae_nascimento'] ?? '';
        if (!$d || $d == '0000-00-00') return '';
        try {
            $n = new DateTime($d);
            $h = new DateTime();
            return $h->diff($n)->y;
        } catch (Exception $e) { return ''; }
    }],
    'id' => ['label' => 'ID', 'value' => function($u){ return $u['id']; }],
    'nome' => ['label' => 'Nome (Bailarina)', 'value' => function($u){ return $u['nome']; }],
    'nome_social' => ['label' => 'Nome Social', 'value' => function($u){ return $u['nome_social'] ?? ''; }],
    'identidade' => ['label' => 'Identidade (Bailarina)', 'value' => function($u){ return $u['identidade'] ?? ''; }],
    'nascimento' => ['label' => 'Data Nascimento', 'value' => function($u){ $d=$u['nascimento']??''; return ($d && $d!='0000-00-00')?date('d/m/Y',strtotime($d)) : ''; }],
    'data_matricula' => ['label' => 'Data Matrícula', 'value' => function($u){ $d=$u['data_matricula']??''; return ($d && $d!='0000-00-00')?date('d/m/Y',strtotime($d)) : ''; }],
    'status' => ['label' => 'Status', 'value' => function($u){ return $u['status']; }],
    'endereco' => ['label' => 'Endereço', 'value' => function($u){ return $u['endereco'] ?? ''; }],
    'telefone' => ['label' => 'Telefone', 'value' => function($u){ return $u['telefone'] ?? ''; }],
    'turma' => ['label' => 'Turma', 'value' => function($u){ return $u['turma'] ?? ''; }],
    'responsavel' => ['label' => 'Responsável - Nome', 'value' => function($u){ return $u['responsavel'] ?? ''; }],
    'responsavel_identidade' => ['label' => 'Responsável - Identidade', 'value' => function($u){ return $u['responsavel_identidade'] ?? ''; }],
    'responsavel_email' => ['label' => 'Responsável - Email', 'value' => function($u){ return $u['responsavel_email'] ?? ''; }],
    'responsavel_nascimento' => ['label' => 'Responsável - Nascimento', 'value' => function($u){ $d=$u['responsavel_nascimento']??''; return ($d && $d!='0000-00-00')?date('d/m/Y',strtotime($d)) : ''; }],
    'responsavel_telefone' => ['label' => 'Responsável - Telefone', 'value' => function($u){ return $u['responsavel_telefone'] ?? ''; }],
    'pai_nome' => ['label' => 'Pai - Nome', 'value' => function($u){ return $u['pai_nome'] ?? ''; }],
    'pai_identidade' => ['label' => 'Pai - Identidade', 'value' => function($u){ return $u['pai_identidade'] ?? ''; }],
    'pai_email' => ['label' => 'Pai - Email', 'value' => function($u){ return $u['pai_email'] ?? ''; }],
    'pai_nascimento' => ['label' => 'Pai - Nascimento', 'value' => function($u){ $d=$u['pai_nascimento']??''; return ($d && $d!='0000-00-00')?date('d/m/Y',strtotime($d)) : ''; }],
    'pai_telefone' => ['label' => 'Pai - Telefone', 'value' => function($u){ return $u['pai_telefone'] ?? ''; }],
    'mae_nome' => ['label' => 'Mãe - Nome', 'value' => function($u){ return $u['mae_nome'] ?? ''; }],
    'mae_identidade' => ['label' => 'Mãe - Identidade', 'value' => function($u){ return $u['mae_identidade'] ?? ''; }],
    'mae_email' => ['label' => 'Mãe - Email', 'value' => function($u){ return $u['mae_email'] ?? ''; }],
    'mae_nascimento' => ['label' => 'Mãe - Nascimento', 'value' => function($u){ $d=$u['mae_nascimento']??''; return ($d && $d!='0000-00-00')?date('d/m/Y',strtotime($d)) : ''; }],
    'mae_telefone' => ['label' => 'Mãe - Telefone', 'value' => function($u){ return $u['mae_telefone'] ?? ''; }],
    'renda_moradores' => ['label' => 'Renda - Moradores', 'value' => function($u){ return $u['renda_moradores'] ?? ''; }],
    'renda_comodos' => ['label' => 'Renda - Cômodos', 'value' => function($u){ return $u['renda_comodos'] ?? ''; }],
    'renda_telefones' => ['label' => 'Renda - Telefones', 'value' => function($u){ return $u['renda_telefones'] ?? ''; }],
    'renda_valor' => ['label' => 'Renda - Valor', 'value' => function($u){ return $u['renda_valor'] ?? ''; }],
    'doc_responsavel' => ['label' => 'Doc Responsável', 'value' => function($u){ return !empty($u['doc_responsavel']) ? 'Sim' : 'Não'; }],
    'doc_bailarina' => ['label' => 'Doc Bailarina', 'value' => function($u){ return !empty($u['doc_bailarina']) ? 'Sim' : 'Não'; }],
    'doc_residencia' => ['label' => 'Doc Residência', 'value' => function($u){ return !empty($u['doc_residencia']) ? 'Sim' : 'Não'; }],
    'doc_renda' => ['label' => 'Doc Renda', 'value' => function($u){ return !empty($u['doc_renda']) ? 'Sim' : 'Não'; }],
    'email_responsavel_extra' => ['label' => 'Email Responsável', 'value' => function($u){ return $u['responsavel_email'] ?? ''; }],
    'observacoes' => ['label' => 'Observações', 'value' => function($u){ return $u['observacoes'] ?? ''; }],
];

$fieldsParam = isset($_GET['fields']) ? $_GET['fields'] : '';
$selectedKeys = array_filter(array_map('trim', explode(',', $fieldsParam)));
if ($mode !== 'custom' || empty($selectedKeys)) {
    $selectedKeys = [
        'id','nome','nome_social','identidade','nascimento','data_matricula','status','endereco','telefone','turma',
        'responsavel','responsavel_identidade','responsavel_email','responsavel_nascimento','responsavel_telefone',
        'pai_nome','pai_identidade','pai_email','pai_nascimento','pai_telefone',
        'mae_nome','mae_identidade','mae_email','mae_nascimento','mae_telefone',
        'renda_moradores','renda_comodos','renda_telefones','renda_valor',
        'doc_responsavel','doc_bailarina','doc_residencia','doc_renda',
        'email_responsavel_extra','observacoes'
    ];
}

$usuarios = getUsuarios();
if (!empty($ids)) {
    $usuarios = array_values(array_filter($usuarios, function($u) use ($ids) {
        return in_array(intval($u['id']), $ids, true);
    }));
}

$filename = "alunos_independance_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
$headers = array_map(function($key) use ($columnsMap){
    return isset($columnsMap[$key]) ? $columnsMap[$key]['label'] : $key;
}, $selectedKeys);
fputcsv($out, $headers, ';');

foreach ($usuarios as $user) {
    $row = [];
    foreach ($selectedKeys as $k) {
        if (isset($columnsMap[$k])) {
            $row[] = $columnsMap[$k]['value']($user);
        } else {
            $row[] = isset($user[$k]) ? $user[$k] : '';
        }
    }
    fputcsv($out, $row, ';');
}

fclose($out);
exit;
