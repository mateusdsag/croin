<?php

if(!isset($_SESSION['user'])){
    header("Location: ?page=login");
    exit;
}

require_once "../app/services/CoinService.php";

$symbol = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $_GET['symbol'] ?? 'btc')));

$coins = CoinService::getMarketData();
$currentCoin = null;

foreach($coins as $coin){
    if(strtolower(trim($coin['symbol'] ?? '')) === $symbol){
        $currentCoin = $coin;
        break;
    }
}

$userName   = htmlspecialchars($_SESSION['user']['name'] ?? 'Usuário');
$userEmail  = htmlspecialchars($_SESSION['user']['email'] ?? '');
$userAvatar = strtoupper(substr($_SESSION['user']['name'] ?? 'U', 0, 1));

if(!$currentCoin):
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moeda não encontrada — CROIN</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;">
    <div style="text-align:center;max-width:400px;">
        <div style="font-size:64px;margin-bottom:16px;">🔍</div>
        <h1 style="font-family:var(--font-head);font-size:28px;color:var(--red);margin-bottom:8px;">Moeda não encontrada</h1>
        <p style="color:var(--muted);margin-bottom:24px;">A moeda <strong><?= htmlspecialchars($symbol) ?></strong> não foi encontrada ou a API não retornou dados.</p>
        <a href="?page=dashboard" class="back-btn" style="display:inline-flex;">← Voltar ao Dashboard</a>
    </div>
</body>
</html>
<?php
    exit;
endif;

// Dados
$name        = $currentCoin['name'] ?? 'Unknown';
$coinSymbol  = strtoupper($currentCoin['symbol'] ?? '---');
$image       = $currentCoin['image'] ?? '';
$price       = (float)($currentCoin['price'] ?? 0);
$change      = (float)($currentCoin['change'] ?? 0);
$marketCap   = (float)($currentCoin['market_cap'] ?? 0);
$volume      = (float)($currentCoin['volume'] ?? 0);
$ath         = (float)($currentCoin['ath'] ?? 0);
$supply      = (float)($currentCoin['supply'] ?? 0);
$rank        = $currentCoin['rank'] ?? 'N/A';

$isPos = $change >= 0;

if($price >= 1000) $priceFmt = number_format($price, 2);
elseif($price >= 1) $priceFmt = number_format($price, 4);
else $priceFmt = number_format($price, 6);

$tradingPair = "BINANCE:{$coinSymbol}USDT";

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($name) ?> — CROIN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- SIDEBAR OVERLAY -->
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);z-index:998;" onclick="document.querySelector('.sidebar').classList.remove('active');this.style.display='none';"></div>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo-area">
        <h1 class="logo">CROIN</h1>
        <p class="logo-sub">Crypto Monitor</p>
    </div>
    <nav>
        <a href="?page=dashboard" class="menu-item active">
            <span class="menu-icon">◈</span> Dashboard
        </a>
        <a href="?page=portfolio" class="menu-item">
            <span class="menu-icon">◉</span> Portfolio
        </a>
        <a href="?page=watchlist" class="menu-item">
            <span class="menu-icon">★</span> Watchlist
        </a>
        <a href="?page=logout" class="menu-item menu-logout" style="margin-top:auto;">
            <span class="menu-icon">⏻</span> Logout
        </a>
    </nav>
    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <div class="sidebar-avatar"><?= $userAvatar ?></div>
            <div style="min-width:0;">
                <div class="sidebar-name"><?= $userName ?></div>
                <div class="sidebar-email"><?= $userEmail ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- MAIN -->
<main class="main-content">

    <!-- HEADER -->
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:16px;">
            <button id="menu-toggle" class="mobile-menu-btn">☰</button>
            <div class="coin-hero">
                <?php if($image): ?>
                <img src="<?= htmlspecialchars($image) ?>" class="coin-hero-img" alt="<?= htmlspecialchars($name) ?>">
                <?php endif; ?>
                <div>
                    <h1 class="coin-hero-name"><?= htmlspecialchars($name) ?></h1>
                    <p class="coin-hero-symbol"><?= $coinSymbol ?> · Rank #<?= htmlspecialchars((string)$rank) ?></p>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="favorite-btn" style="width:auto;padding:0 16px;height:40px;font-size:14px;"
                onclick="addToWatchlist('<?= $coinSymbol ?>','<?= htmlspecialchars(addslashes($name)) ?>',this)">
                ★ Watchlist
            </button>
            <button class="portfolio-btn" style="width:auto;padding:0 16px;height:40px;font-size:14px;font-weight:600;"
                onclick="openPortfolioModal('<?= $coinSymbol ?>','<?= htmlspecialchars(addslashes($name)) ?>','<?= $price ?>')">
                + Portfolio
            </button>
            <a href="?page=dashboard" class="back-btn">← Dashboard</a>
        </div>
    </header>

    <!-- STATS GRID -->
    <div class="stats-grid">
        <div class="stat-card">
            <p class="stat-label">Preço Atual</p>
            <h2 class="stat-value" style="color:var(--accent);">$<?= $priceFmt ?></h2>
        </div>
        <div class="stat-card">
            <p class="stat-label">Variação 24h</p>
            <h2 class="stat-value" style="color:<?= $isPos ? 'var(--green)' : 'var(--red)' ?>;">
                <?= $isPos ? '+' : '' ?><?= number_format($change, 2) ?>%
            </h2>
        </div>
        <div class="stat-card">
            <p class="stat-label">Market Cap</p>
            <h2 class="stat-value">$<?= number_format($marketCap / 1e9, 2) ?>B</h2>
        </div>
        <div class="stat-card">
            <p class="stat-label">Volume 24h</p>
            <h2 class="stat-value" style="font-size:20px;">$<?= number_format($volume / 1e6, 1) ?>M</h2>
        </div>
        <div class="stat-card">
            <p class="stat-label">All Time High</p>
            <h2 class="stat-value" style="color:var(--yellow);">$<?= number_format($ath, 2) ?></h2>
        </div>
        <div class="stat-card">
            <p class="stat-label">Circulating Supply</p>
            <h2 class="stat-value" style="font-size:18px;"><?= number_format($supply / 1e6, 2) ?>M</h2>
        </div>
    </div>

    <!-- CHART -->
    <section style="margin-bottom:32px;">
        <div class="section-header">
            <h2 class="section-title"><?= htmlspecialchars($name) ?> / USDT</h2>
            <span class="live-badge"><span class="live-dot"></span>LIVE</span>
        </div>
        <div class="trading-card">
            <div id="tradingview_coin_chart"></div>
        </div>
    </section>

</main>

<!-- PORTFOLIO MODAL -->
<div id="portfolioModal" class="portfolio-modal">
    <div class="portfolio-content">
        <h2 class="modal-title">Adicionar ao Portfolio</h2>
        <div class="modal-coin-info">
            <span style="font-size:20px;">◉</span>
            <span id="modalCoinLabel" style="font-weight:600;font-size:15px;color:var(--accent);">—</span>
        </div>
        <input type="hidden" id="portfolioSymbol">
        <input type="hidden" id="portfolioName">
        <label class="modal-label">Quantidade</label>
        <input type="number" id="portfolioQuantity" placeholder="Ex: 0.5" step="any" min="0" class="portfolio-input">
        <label class="modal-label">Preço de Compra (USD)</label>
        <input type="number" id="portfolioPrice" placeholder="Ex: 45000.00" step="any" min="0" class="portfolio-input">
        <div class="modal-actions">
            <button class="save-btn" onclick="savePortfolio()">Salvar</button>
            <button class="close-btn" onclick="closePortfolioModal()">Cancelar</button>
        </div>
    </div>
</div>

<div id="toastContainer"></div>

<script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
<script>
new TradingView.widget({
    "width": "100%",
    "height": 560,
    "symbol": "<?= $tradingPair ?>",
    "interval": "30",
    "timezone": "America/Sao_Paulo",
    "theme": "dark",
    "style": "1",
    "locale": "pt",
    "toolbar_bg": "#131920",
    "enable_publishing": false,
    "allow_symbol_change": true,
    "container_id": "tradingview_coin_chart",
    "backgroundColor": "rgba(13,17,23,1)"
});
</script>

<script src="assets/js/app.js"></script>

</body>
</html>