```php
<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$userName = htmlspecialchars($_SESSION['user']['name'] ?? 'Usuário');
$userEmail = htmlspecialchars($_SESSION['user']['email'] ?? '');
$userAvatar = strtoupper(substr($userName, 0, 1));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= $pageTitle ?? 'CROIN' ?></title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="./assets/css/style.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

</head>

<body>

<div class="bg-effects">
    <div class="bg-orb orb1"></div>
    <div class="bg-orb orb2"></div>
    <div class="grid-overlay"></div>
</div>

<div id="sidebarOverlay"></div>

<aside class="sidebar">

    <div class="logo-area">

        <h1 class="logo">CROIN</h1>
        <p class="logo-sub">Crypto Intelligence</p>

    </div>

    <nav>

        <a href="?page=dashboard" class="menu-item">
            <span class="menu-icon">◈</span>
            Dashboard
        </a>

        <a href="?page=portfolio" class="menu-item">
            <span class="menu-icon">◉</span>
            Portfolio
        </a>

        <a href="?page=watchlist" class="menu-item">
            <span class="menu-icon">★</span>
            Watchlist
        </a>

        <a href="?page=logout" class="menu-item menu-logout">
            <span class="menu-icon">⏻</span>
            Logout
        </a>

    </nav>

    <div class="sidebar-user">

        <div class="sidebar-user-info">

            <div class="sidebar-avatar">
                <?= $userAvatar ?>
            </div>

            <div>

                <div class="sidebar-name">
                    <?= $userName ?>
                </div>

                <div class="sidebar-email">
                    <?= $userEmail ?>
                </div>

            </div>

        </div>

    </div>

</aside>

<main class="main-content">
```
