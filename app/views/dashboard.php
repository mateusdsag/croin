<?php

if(!isset($_SESSION['user'])){
    header("Location: ?page=login");
    exit;
}

require_once "../app/services/CoinService.php";
require_once "../app/services/FearGreedService.php";

$coins = CoinService::getMarketData();
$fearGreed = FearGreedService::getIndex();

$topGainers = $coins;

usort($topGainers, function($a, $b){

    $aChange = isset($a['change']) ? $a['change'] : 0;
    $bChange = isset($b['change']) ? $b['change'] : 0;

    if($aChange == $bChange){
        return 0;
    }

    return ($aChange < $bChange) ? 1 : -1;
});

$topGainers = array_slice($topGainers, 0, 5);

$topLosers = $coins;

usort($topLosers, function($a, $b){

    $aChange = isset($a['change']) ? $a['change'] : 0;
    $bChange = isset($b['change']) ? $b['change'] : 0;

    if($aChange == $bChange){
        return 0;
    }

    return ($aChange > $bChange) ? 1 : -1;
});

$topLosers = array_slice($topLosers, 0, 5);

$userName = htmlspecialchars(
    isset($_SESSION['user']['name'])
        ? $_SESSION['user']['name']
        : 'Usuario'
);

$userEmail = htmlspecialchars(
    isset($_SESSION['user']['email'])
        ? $_SESSION['user']['email']
        : ''
);

$userAvatar = strtoupper(
    substr(
        isset($_SESSION['user']['name'])
            ? $_SESSION['user']['name']
            : 'U',
        0,
        1
    )
);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Croin — Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- LOADER -->
<div id="loader">
    <div class="loader-content">
        <div class="loader-logo">CROIN</div>
        <div class="loader-spinner"></div>
        <p class="loader-text">Carregando mercado…</p>
    </div>
</div>

<!-- SIDEBAR OVERLAY (mobile) -->
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

    <!-- TOPBAR -->
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:16px;">
            <button id="menu-toggle" class="mobile-menu-btn">☰</button>
            <div>
                <h2 class="title">Crypto Market</h2>
                <p class="subtitle">Monitoramento em tempo real</p>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <span class="live-badge"><span class="live-dot"></span>LIVE</span>
            <div class="search-box">
                <span class="search-icon">⌕</span>
                <input type="text" id="searchInput" placeholder="Pesquisar moeda…">
            </div>
        </div>
    </header>

    <!-- TRADINGVIEW CHART -->
    <section class="trading-section">
        <div class="section-header">
            <div>
                <h2 class="section-title">Bitcoin Live Chart</h2>
                <p class="subtitle">Gráfico profissional em tempo real</p>
            </div>
            <span class="live-badge"><span class="live-dot"></span>LIVE</span>
        </div>
        <div class="trading-card">
            <div id="tradingview_chart"></div>
        </div>
    </section>

    <!-- FEAR & GREED -->
    <?php if($fearGreed):
        $fgValue = (int)($fearGreed['value'] ?? 0);
        $fgClass = htmlspecialchars($fearGreed['value_classification'] ?? 'Unknown');
        $fgColor = $fgValue >= 70 ? 'var(--green)' : ($fgValue <= 30 ? 'var(--red)' : 'var(--yellow)');
        $circumference = 2 * M_PI * 34;
        $offset = $circumference - ($fgValue / 100) * $circumference;
    ?>
    <section class="fear-section">
        <div class="fear-card">
            <div>
                <h2 class="fear-title">Fear &amp; Greed Index</h2>
                <p class="fear-subtitle">Sentimento atual do mercado cripto</p>
            </div>
            <div class="fear-right">
                <svg class="fear-gauge" width="80" height="80" viewBox="0 0 80 80">
                    <circle class="fear-gauge-bg" cx="40" cy="40" r="34" stroke-dasharray="<?= $circumference ?>" />
                    <circle class="fear-gauge-fill" cx="40" cy="40" r="34"
                        stroke="<?= $fgColor ?>"
                        stroke-dasharray="<?= $circumference ?>"
                        stroke-dashoffset="<?= $offset ?>"
                        style="filter:drop-shadow(0 0 6px <?= $fgColor ?>)" />
                    <text x="40" y="46" text-anchor="middle" font-size="18" font-weight="800" fill="<?= $fgColor ?>" font-family="Syne,sans-serif" transform="rotate(90 40 40)"><?= $fgValue ?></text>
                </svg>
                <div>
                    <h2 class="fear-value" style="color:<?= $fgColor ?>"><?= $fgValue ?></h2>
                    <p class="fear-status"><?= $fgClass ?></p>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- MARKET MOVERS -->
    <section class="movers-section">
        <!-- GAINERS -->
        <div class="movers-card">
            <h2 class="movers-title" style="color:var(--green);">🚀 Top Gainers</h2>
            <?php foreach($topGainers as $coin):
                $chg = (float)($coin['change'] ?? 0);
            ?>
            <a href="?page=coin&symbol=<?= strtolower($coin['symbol'] ?? '') ?>" class="mover-item" style="text-decoration:none;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <img src="<?= htmlspecialchars($coin['image'] ?? '') ?>" width="32" height="32" style="border-radius:50%;object-fit:cover;" alt="">
                    <div>
                        <div class="mover-name"><?= htmlspecialchars($coin['name'] ?? '') ?></div>
                        <div class="mover-symbol"><?= strtoupper($coin['symbol'] ?? '') ?></div>
                    </div>
                </div>
                <div style="color:var(--green);font-weight:700;font-size:14px;">+<?= number_format($chg,2) ?>%</div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- LOSERS -->
        <div class="movers-card">
            <h2 class="movers-title" style="color:var(--red);">📉 Top Losers</h2>
            <?php foreach($topLosers as $coin):
                $chg = (float)($coin['change'] ?? 0);
            ?>
            <a href="?page=coin&symbol=<?= strtolower($coin['symbol'] ?? '') ?>" class="mover-item" style="text-decoration:none;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <img src="<?= htmlspecialchars($coin['image'] ?? '') ?>" width="32" height="32" style="border-radius:50%;object-fit:cover;" alt="">
                    <div>
                        <div class="mover-name"><?= htmlspecialchars($coin['name'] ?? '') ?></div>
                        <div class="mover-symbol"><?= strtoupper($coin['symbol'] ?? '') ?></div>
                    </div>
                </div>
                <div style="color:var(--red);font-weight:700;font-size:14px;"><?= number_format($chg,2) ?>%</div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- COIN GRID -->
    <section class="section-header">
        <h2 class="section-title">Mercado — Top 20</h2>
        <span class="live-badge" id="lastUpdate" style="font-size:11px;color:var(--muted);">Atualiza a cada 5s</span>
    </section>

    <section class="coin-grid" id="coinGrid">
        <?php if(!empty($coins)): ?>
        <?php foreach($coins as $index => $coin):
            $symbol = strtoupper($coin['symbol'] ?? '');
            $name   = $coin['name'] ?? '';
            $price  = (float)($coin['price'] ?? 0);
            $change = (float)($coin['change'] ?? 0);
            $isPos  = $change >= 0;

            if($price >= 1000) {
                $priceFmt = number_format($price, 2);
            } elseif($price >= 1) {
                $priceFmt = number_format($price, 4);
            } else {
                $priceFmt = number_format($price, 6);
            }
        ?>
        <a href="?page=coin&symbol=<?= strtolower($symbol) ?>"
           class="crypto-card coin-item"
           data-name="<?= strtolower($name) ?>"
           data-symbol="<?= strtolower($symbol) ?>">

            <div class="coin-top">
                <div class="coin-info">
                    <img src="<?= htmlspecialchars($coin['image'] ?? '') ?>"
                         class="coin-image"
                         alt="<?= htmlspecialchars($name) ?>"
                         onerror="this.src='https://via.placeholder.com/42/131920/22d3ee?text=<?= $symbol ?>'">
                    <div>
                        <h3 class="coin-name"><?= htmlspecialchars($name) ?></h3>
                        <p class="coin-symbol"><?= $symbol ?></p>
                    </div>
                </div>
                <div class="coin-actions">
                    <button type="button" class="favorite-btn" title="Adicionar à Watchlist"
                        onclick="event.preventDefault();event.stopPropagation();addToWatchlist('<?= $symbol ?>','<?= htmlspecialchars(addslashes($name)) ?>',this)">★</button>
                    <button type="button" class="portfolio-btn" title="Adicionar ao Portfolio"
                        onclick="event.preventDefault();event.stopPropagation();openPortfolioModal('<?= $symbol ?>','<?= htmlspecialchars(addslashes($name)) ?>','<?= $price ?>')">+</button>
                </div>
            </div>

            <div class="rank-badge">#<?= $coin['rank'] ?? 0 ?></div>

            <div class="coin-price-area">
                <h2 class="coin-price">$<?= $priceFmt ?></h2>
                <p class="<?= $isPos ? 'positive' : 'negative' ?>">
                    <?= $isPos ? '+' : '' ?><?= number_format($change,2) ?>%
                </p>
            </div>

            <div class="coin-stats">
                <p class="market-cap">
                    <span>Market Cap</span>
                    <span>$<?= number_format((float)($coin['market_cap'] ?? 0)/1e9, 2) ?>B</span>
                </p>
                <p class="market-volume">
                    <span>Volume 24h</span>
                    <span>$<?= number_format((float)($coin['volume'] ?? 0)/1e6, 1) ?>M</span>
                </p>
            </div>
        </a>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📡</div>
            <p>API indisponível no momento. Tente novamente em instantes.</p>
        </div>
        <?php endif; ?>
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

<!-- TOAST -->
<div id="toastContainer"></div>

<!-- TRADINGVIEW -->
<script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
<script>
new TradingView.widget({
    "width": "100%",
    "height": 480,
    "symbol": "BINANCE:BTCUSDT",
    "interval": "30",
    "timezone": "America/Sao_Paulo",
    "theme": "dark",
    "style": "1",
    "locale": "pt",
    "toolbar_bg": "#131920",
    "enable_publishing": false,
    "hide_side_toolbar": false,
    "allow_symbol_change": true,
    "container_id": "tradingview_chart",
    "backgroundColor": "rgba(13,17,23,1)"
});
</script>

<script src="assets/js/app.js"></script>

</body>
</html>