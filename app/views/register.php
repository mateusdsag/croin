<?php

session_start();

if(isset($_SESSION['user'])){
    header("Location: ?page=dashboard");
    exit;
}

require_once "../config/database.php";

$error   = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $database = new Database();
    $db = $database->connect();

    if(!$db){
        $error = 'Erro de conexão com o banco de dados.';
    } else {

        $name     = trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES));
        $email    = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if(empty($name) || empty($email) || empty($password)){
            $error = 'Preencha todos os campos.';
        } elseif(strlen($name) < 2){
            $error = 'Nome deve ter pelo menos 2 caracteres.';
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $error = 'Email inválido.';
        } elseif(strlen($password) < 6){
            $error = 'A senha deve ter pelo menos 6 caracteres.';
        } elseif($password !== $confirm){
            $error = 'As senhas não coincidem.';
        } else {

            $check = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->execute([$email]);

            if($check->rowCount() > 0){
                $error = 'Este email já está cadastrado.';
            } else {

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?,?,?)");

                if($stmt->execute([$name, $email, $hash])){
                    header("Location: ?page=login&registered=1");
                    exit;
                } else {
                    $error = 'Erro ao criar conta. Tente novamente.';
                }
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
    <title>Criar Conta — CROIN</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

    <div class="auth-card">

        <h1 class="auth-logo">CROIN</h1>
        <p class="auth-tagline">Crie sua conta gratuita</p>

        <?php if(!empty($error)): ?>
        <div class="auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">

            <label class="auth-label" for="name">Nome</label>
            <input
                class="auth-input"
                type="text"
                id="name"
                name="name"
                required
                placeholder="Seu nome"
                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                autocomplete="name"
            >

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
                placeholder="Mínimo 6 caracteres"
                autocomplete="new-password"
            >

            <label class="auth-label" for="confirm">Confirmar Senha</label>
            <input
                class="auth-input"
                type="password"
                id="confirm"
                name="confirm"
                required
                placeholder="Repita a senha"
                autocomplete="new-password"
            >

            <button type="submit" class="auth-btn">Criar Conta</button>

        </form>

        <div class="auth-footer">
            Já tem conta?
            <a href="?page=login">Entrar</a>
        </div>

    </div>

</body>
</html>