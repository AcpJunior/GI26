<?php
// Obter o nome do arquivo atual para destacar o menu
$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/data.php';
$siteConfig = getSiteConfig();
$modoMatriculaUnificada = $siteConfig['usar_rematricula_como_matricula'] ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupo Independance | Ballet Clássico</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <a href="index.php">Grupo Independance</a>
                </div>
                <div class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
                <ul class="nav-links">
                    <li><a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="sobre.php" class="<?php echo $current_page == 'sobre.php' ? 'active' : ''; ?>">Sobre</a></li>
                    
                    <li><a href="matriculas.php" class="<?php echo $current_page == 'matriculas.php' ? 'active' : ''; ?>">Matrículas</a></li>
                    <li><a href="rematriculas.php" class="<?php echo $current_page == 'rematriculas.php' ? 'active' : ''; ?>">Rematrículas</a></li>

                    <?php 
                    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): 
                    ?>
                        <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                        <li><a href="logout.php" style="color: #e74c3c;">Sair</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="<?php echo $current_page == 'login.php' ? 'active' : ''; ?>">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <div class="main-content">
