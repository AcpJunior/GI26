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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $turma = isset($_POST['turma']) ? trim($_POST['turma']) : '';
    if ($id > 0 && $turma !== '') {
        definirTurmaAluno($id, $turma);
    }
}
header("location: dashboard.php");
exit;
