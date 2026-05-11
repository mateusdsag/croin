<?php

session_start();

if(isset($_SESSION['user'])){
    header("Location: ?page=dashboard");
    exit;
}

require_once "../config/database.php";

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $database = new Database();
    $db = $database->connect();

    if(!$db){
        $error = 'Erro de conexão com o banco de dados.';
    } else {

        $email    = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password'] ?? '';

        if(empty($email) || empty($password)){
            $error = 'Preencha todos os campos.';
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $error = 'Email inválido.';
        } else {

            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if($user && password_verify($password, $user['password'])){
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                ];
                header("Location: ?page=dashboard");
                exit;
            } else {
                $error = 'Email ou senha inválidos.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CROIN</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
</head>
<body class="auth-page">

    <div class="auth-card">

        <h1 class="auth-logo">CROIN</h1>
        <p class="auth-tagline">Plataforma de Monitoramento Cripto</p>

        <?php if(!empty($error)): ?>
        <div class="auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">
            <label class="auth-label" for="email">Email</label>
            <input
                class="auth-input"
                type="email"
                id="email"
                name="email"
                required
                placeholder="seu@email.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                autocomplete="email"
            >

            <label class="auth-label" for="password">Senha</label>
            <input
                class="auth-input"
                type="password"
                id="password"
                name="password"
                required
                placeholder="••••••••"
                autocomplete="current-password"
            >

            <button type="submit" class="auth-btn">Entrar</button>
        </form>

        <div class="auth-footer">
            Não tem conta?
            <a href="?page=register">Criar conta</a>
        </div>

    </div>

</body>
</html>