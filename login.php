<?php
$sessionPath = __DIR__ . '/files/sessions';
if (!is_dir($sessionPath)) { @mkdir($sessionPath, 0755, true); }
if (is_dir($sessionPath) && is_writable($sessionPath)) { session_save_path($sessionPath); }
session_start();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header("location: dashboard.php");
        exit;
    }

    require_once 'includes/db_connect.php';
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            header("location: dashboard.php");
            exit;
        } else {
            $error = "Usuário ou senha inválidos.";
        }
    } catch (Exception $e) {
        $error = "Erro ao autenticar.";
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="login-container">
        <h2 style="text-align: center; margin-bottom: 30px;">Acesso Administrativo</h2>
        
        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">Usuário</label>
                <input type="text" name="username" id="username" class="form-control" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
            </div>
            <div style="margin-top: 30px;">
                <button type="submit" class="btn">Entrar</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
