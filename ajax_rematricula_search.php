<?php
require_once 'includes/data.php';

header('Content-Type: application/json');

$term = trim($_GET['term'] ?? '');
if (strlen($term) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

// Função para normalizar strings (remove acentos e caracteres especiais)
function normalizeString($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[áàãâä]/u', 'a', $str);
    $str = preg_replace('/[éèêë]/u', 'e', $str);
    $str = preg_replace('/[íìîï]/u', 'i', $str);
    $str = preg_replace('/[óòõôö]/u', 'o', $str);
    $str = preg_replace('/[úùûü]/u', 'u', $str);
    $str = preg_replace('/[ç]/u', 'c', $str);
    $str = preg_replace('/[ñ]/u', 'n', $str);
    $str = preg_replace('/[^a-z0-9\s]/', '', $str);
    return trim($str);
}

$users = getUsuarios();
$results = [];

$termNorm = normalizeString($term);
$termParts = array_filter(explode(' ', $termNorm));

foreach ($users as $u) {
    // Ignora cancelados e já rematriculados (apenas Pendente)
    if (isset($u['status']) && $u['status'] !== 'Pendente') continue;
    
    $name = $u['nome'];
    $nameNorm = normalizeString($name);
    
    // Score inicial
    $score = 0;
    
    // 1. Match Exato
    if ($nameNorm === $termNorm) {
        $score = 100;
        $results[] = ['id' => $u['id'], 'nome' => $name, 'score' => $score, 'is_exact' => true];
        continue;
    }
    
    // 2. Contém todas as palavras da busca (ordem não importa)
    $matchesAllParts = true;
    $partsFound = 0;
    foreach ($termParts as $part) {
        if (strpos($nameNorm, $part) !== false) {
            $partsFound++;
        } else {
            $matchesAllParts = false;
        }
    }
    
    if ($matchesAllParts && count($termParts) > 0) {
        // Base score alto pois contém todas as palavras buscadas
        // Penaliza levemente pelo comprimento extra (ex: "Ana Silva" busca "Ana" -> score menor que "Ana")
        // Mas o usuário quer priorizar "Alexsander Junior" match em "Alexsander ... Junior"
        
        // Vamos usar similar_text para refinar o score entre 80 e 99
        similar_text($termNorm, $nameNorm, $perc);
        $score = 80 + ($perc * 0.19); // Garante entre 80 e 99
        $results[] = ['id' => $u['id'], 'nome' => $name, 'score' => $score, 'is_exact' => false];
        continue;
    }
    
    // 3. Fuzzy parcial (algumas palavras batem ou similaridade alta)
    similar_text($termNorm, $nameNorm, $perc);
    
    // Se a similaridade for alta o suficiente, ou se encontrou parte significativa
    if ($perc > 65) {
        $results[] = ['id' => $u['id'], 'nome' => $name, 'score' => $perc, 'is_exact' => false];
    }
}

// Ordenar por score decrescente
usort($results, function($a, $b) {
    if ($a['score'] == $b['score']) {
        return 0; 
    }
    return ($a['score'] < $b['score']) ? 1 : -1;
});

// Limitar resultados e retornar
echo json_encode(['results' => array_slice($results, 0, 10)]);
