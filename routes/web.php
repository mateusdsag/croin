<?php

$page = preg_replace('/[^a-z]/', '', strtolower($_GET['page'] ?? 'dashboard'));

switch($page){

    case 'dashboard':
        require_once '../app/views/dashboard.php';
        break;

    case 'login':
        require_once '../app/views/login.php';
        break;

    case 'register':
        require_once '../app/views/register.php';
        break;

    case 'logout':
        $_SESSION = [];
        session_destroy();
        header('Location: ?page=login');
        exit;

    case 'watchlist':
        require_once '../app/views/watchlist.php';
        break;

    case 'portfolio':
        require_once '../app/views/portfolio.php';
        break;

    case 'coin':
        require_once '../app/views/coin.php';
        break;

    default:
        http_response_code(404);
        echo '<div style="font-family:sans-serif;text-align:center;padding:60px;">
            <h1 style="color:#ff4444;">404 — Página não encontrada</h1>
            <a href="?page=dashboard" style="color:#00d4ff;">Voltar ao Dashboard</a>
        </div>';
}