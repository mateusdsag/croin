<?php
// ===============================
// AUTH CHECK
// ===============================
if (!isset($_SESSION['user'])) {
    header("Location: ?page=login");
    exit;
}

// ===============================
// SERVICES
// ===============================
require_once "../app/services/CoinService.php";
require_once "../app/services/FearGreedService.php";

$coins     = CoinService::getMarketData();
$fearGreed = FearGreedService::getIndex();

// ===============================
// TOP GAINERS / LOSERS
// ===============================
$topGainers = $coins;
usort($topGainers, fn($a, $b) => ($b['change'] ?? 0) <=> ($a['change'] ?? 0));
$topGainers = array_slice($topGainers, 0, 5);

$topLosers = $coins;
usort($topLosers, fn($a, $b) => ($a['change'] ?? 0) <=> ($b['change'] ?? 0));
$topLosers = array_slice($topLosers, 0, 5);

// ===============================
// USER DATA
// ===============================
$userName   = htmlspecialchars($_SESSION['user']['name']  ?? 'Usuário');
$userEmail  = htmlspecialchars($_SESSION['user']['email'] ?? '');
$userAvatar = strtoupper(substr($_SESSION['user']['name'] ?? 'U', 0, 1));

// Fear & Greed color
$fgValue = (int)($fearGreed['value'] ?? 50);
if ($fgValue >= 70) {
    $fgColor = '#00D4AA';
    $fgStatus = 'Extreme Greed';
} elseif ($fgValue >= 55) {
    $fgColor = '#00D4AA';
    $fgStatus = 'Greed';
} elseif ($fgValue >= 45) {
    $fgColor = '#FFB800';
    $fgStatus = 'Neutral';
} elseif ($fgValue >= 30) {
    $fgColor = '#FF4560';
    $fgStatus = 'Fear';
} else {
    $fgColor = '#FF4560';
    $fgStatus = 'Extreme Fear';
}

$fgCircumference = 2 * M_PI * 50; // r=50
$fgOffset = $fgCircumference * (1 - $fgValue / 100);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CROIN PRO</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- ================================================
LOADER
================================================ -->
    <div id="loader">
        <div class="loader-logo">CR<em>O</em>IN</div>
        <div class="loader-bar">
            <div class="loader-bar-fill"></div>
        </div>
    </div>

    <!-- SIDEBAR OVERLAY -->
    <div id="sidebarOverlay"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:850;"
        onclick="closeSidebar()"></div>

    <!-- ================================================
SIDEBAR
================================================ -->
    <aside class="sidebar">
        <div class="logo-area">
            <div class="logo">CR<em>O</em>IN</div>
            <div class="logo-sub">Pro Trading</div>
        </div>

        <nav>
            <a href="?page=dashboard" class="menu-item active">
                <span class="menu-icon">⬡</span> Dashboard
            </a>
            <a href="?page=portfolio" class="menu-item">
                <span class="menu-icon">◎</span> Portfolio
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

    <!-- ================================================
MAIN
================================================ -->
    <main class="main-content">

        <!-- TOPBAR -->
        <div class="topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <button id="menu-toggle" class="mobile-menu-btn">☰</button>
                <div>
                    <div class="title">Dashboard</div>
                    <div class="subtitle">Visão geral do mercado</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="live-badge">
                    <span class="live-dot"></span> LIVE
                </div>
            </div>
        </div>

        <!-- ================================================
    DASHBOARD GRID
    ================================================ -->
        <div class="dashboard-grid" id="dashboardGrid">

            <!-- CHART -->
            <div class="widget col-8" data-id="chart">
                <div class="widget-header">
                    <span class="widget-title">BTC / USDT</span>
                    <span class="widget-icon">⠿</span>
                </div>
                <div class="chart-wrapper">
                    <div class="chart-placeholder" id="chartPlaceholder">
                        <div class="chart-spinner"></div>
                        <span>A carregar gráfico...</span>
                    </div>
                    <div id="tradingview_chart"></div>
                </div>
            </div>

            <!-- FEAR & GREED -->
            <div class="widget col-4" data-id="feargreed">
                <div class="widget-header">
                    <span class="widget-title">Fear &amp; Greed</span>
                    <span class="widget-icon">⠿</span>
                </div>
                <div class="fear-gauge-wrap">
                    <div class="fear-gauge">
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <circle class="fear-gauge-bg" cx="60" cy="60" r="50" />
                            <circle class="fear-gauge-fill"
                                cx="60" cy="60" r="50"
                                stroke="<?= $fgColor ?>"
                                stroke-dasharray="<?= $fgCircumference ?>"
                                stroke-dashoffset="<?= $fgOffset ?>" />
                        </svg>
                        <div class="fear-gauge-center">
                            <div class="fear-number" style="color:<?= $fgColor ?>">
                                <?= $fgValue ?>
                            </div>
                            <div class="fear-label">INDEX</div>
                        </div>
                    </div>
                    <div class="fear-status" style="color:<?= $fgColor ?>"><?= $fgStatus ?></div>
                </div>
            </div>

            <!-- TOP GAINERS -->
            <div class="widget col-6" data-id="gainers">
                <div class="widget-header">
                    <span class="widget-title" style="color:var(--green)">▲ Top Gainers</span>
                    <span class="widget-icon">⠿</span>
                </div>
                <?php foreach ($topGainers as $coin):
                    $change = (float)($coin['change'] ?? 0);
                ?>
                    <div class="mover-item">
                        <div class="mover-left">
                            <?php if (!empty($coin['image'])): ?>
                                <img src="<?= htmlspecialchars($coin['image']) ?>" class="mover-img" alt="">
                            <?php endif; ?>
                            <div>
                                <div class="mover-name"><?= htmlspecialchars($coin['name'] ?? $coin['symbol']) ?></div>
                                <div class="mover-symbol"><?= strtoupper($coin['symbol']) ?></div>
                            </div>
                        </div>
                        <span class="pct pct-up">+<?= number_format($change, 2) ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- TOP LOSERS -->
            <div class="widget col-6" data-id="losers">
                <div class="widget-header">
                    <span class="widget-title" style="color:var(--red)">▼ Top Losers</span>
                    <span class="widget-icon">⠿</span>
                </div>
                <?php foreach ($topLosers as $coin):
                    $change = (float)($coin['change'] ?? 0);
                ?>
                    <div class="mover-item">
                        <div class="mover-left">
                            <?php if (!empty($coin['image'])): ?>
                                <img src="<?= htmlspecialchars($coin['image']) ?>" class="mover-img" alt="">
                            <?php endif; ?>
                            <div>
                                <div class="mover-name"><?= htmlspecialchars($coin['name'] ?? $coin['symbol']) ?></div>
                                <div class="mover-symbol"><?= strtoupper($coin['symbol']) ?></div>
                            </div>
                        </div>
                        <span class="pct pct-down"><?= number_format($change, 2) ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
        <!-- /dashboard-grid -->

        <!-- ================================================
    COIN GRID
    ================================================ -->
        <div class="section-header">
            <div class="section-title">Mercado</div>
            <div class="live-badge">
                <span class="live-dot"></span> Auto-actualiza
            </div>
        </div>

        <div class="coin-grid" id="coinGrid">
            <?php foreach ($coins as $coin):
                $symbol = strtoupper($coin['symbol']);
                $price  = (float)$coin['price'];
                $change = (float)$coin['change'];
                $isPos  = $change >= 0;
            ?>
                <a href="?page=coin&symbol=<?= strtolower($coin['symbol']) ?>"
                    class="crypto-card coin-item"
                    data-name="<?= strtolower($coin['name'] ?? '') ?>"
                    data-symbol="<?= strtolower($coin['symbol']) ?>">

                    <div class="coin-top">
                        <div class="coin-info">
                            <img src="<?= htmlspecialchars($coin['image'] ?? '') ?>"
                                class="coin-image" alt="<?= htmlspecialchars($coin['name'] ?? '') ?>"
                                loading="lazy"
                                onerror="this.src='https://via.placeholder.com/40/111520/0066FF?text=<?= $symbol ?>'">
                            <div>
                                <div class="coin-name"><?= htmlspecialchars($coin['name'] ?? $symbol) ?></div>
                                <div class="coin-symbol"><?= $symbol ?></div>
                            </div>
                        </div>
                        <div class="coin-actions" onclick="event.preventDefault()">
                            <button class="favorite-btn" title="Watchlist"
                                onclick="addToWatchlist('<?= htmlspecialchars($coin['symbol']) ?>','<?= htmlspecialchars($coin['name'] ?? '') ?>',this)">★</button>
                            <button class="portfolio-btn" title="Portfolio"
                                onclick="openPortfolioModal('<?= htmlspecialchars($coin['symbol']) ?>','<?= htmlspecialchars($coin['name'] ?? '') ?>','<?= $price ?>')">+</button>
                        </div>
                    </div>

                    <?php if (!empty($coin['rank'])): ?>
                        <div class="rank-badge">#<?= (int)$coin['rank'] ?></div>
                    <?php endif; ?>

                    <div class="coin-price-area">
                        <div class="coin-price">$<?= number_format($price, $price >= 1 ? 2 : 6) ?></div>
                        <div class="<?= $isPos ? 'positive' : 'negative' ?>">
                            <?= $isPos ? '+' : '' ?><?= number_format($change, 2) ?>%
                        </div>
                    </div>

                    <?php if (!empty($coin['market_cap']) || !empty($coin['volume'])): ?>
                        <div class="coin-stats">
                            <?php if (!empty($coin['market_cap'])): ?>
                                <div class="market-cap">
                                    <span>Market Cap</span>
                                    <span>$<?= fmtCompact($coin['market_cap']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($coin['volume'])): ?>
                                <div class="market-volume">
                                    <span>Volume 24h</span>
                                    <span>$<?= fmtCompact($coin['volume']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </a>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- ================================================
PORTFOLIO MODAL
================================================ -->
    <div id="portfolioModal" class="portfolio-modal">
        <div class="portfolio-content">
            <div class="modal-title">Adicionar ao Portfolio</div>
            <div class="modal-coin-info">
                <span>💰</span>
                <span id="modalCoinLabel" style="font-weight:600;font-size:14px;"></span>
            </div>
            <input type="hidden" id="portfolioSymbol">
            <input type="hidden" id="portfolioName">

            <label class="modal-label">Quantidade</label>
            <input type="number" class="portfolio-input" id="portfolioQuantity" placeholder="0.00" step="any" min="0">

            <label class="modal-label">Preço de compra (USD)</label>
            <input type="number" class="portfolio-input" id="portfolioPrice" placeholder="0.00" step="any" min="0">

            <div class="modal-actions">
                <button class="save-btn" onclick="savePortfolio()">Salvar</button>
                <button class="close-btn" onclick="closePortfolioModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <div id="toastContainer"></div>

    <!-- ================================================
TRADINGVIEW + LOGIC
================================================ -->
    <script>
        window.addEventListener('load', () => {
            setTimeout(() => {
                document.getElementById('loader')?.classList.add('hidden');
            }, 200);
            setTimeout(loadTV, 1000);
        });

        function loadTV() {
            const s = document.createElement('script');
            s.src = 'https://s3.tradingview.com/tv.js';
            s.async = true;
            s.onload = () => {
                document.getElementById('chartPlaceholder')?.remove();
                new TradingView.widget({
                    container_id: 'tradingview_chart',
                    autosize: true,
                    symbol: 'BINANCE:BTCUSDT',
                    interval: '5',
                    timezone: 'Etc/UTC',
                    theme: 'dark',
                    style: '1',
                    locale: 'en',
                    studies: ['Volume@tv-basicstudies'],
                    toolbar_bg: '#0D1018',
                    enable_publishing: false,
                    hide_top_toolbar: false,
                    allow_symbol_change: true,
                    details: true,
                    withdateranges: true,
                    backgroundColor: '#080A0F',
                    gridColor: 'rgba(255,255,255,0.03)',
                });
            };
            document.body.appendChild(s);
        }

        /* --- Sortable drag-and-drop --- */
        document.addEventListener('DOMContentLoaded', () => {
            const grid = document.getElementById('dashboardGrid');
            if (!grid) return;

            // Restore layout
            try {
                const saved = localStorage.getItem('croin-layout-v2');
                if (saved) {
                    JSON.parse(saved).forEach(id => {
                        const el = grid.querySelector(`[data-id="${id}"]`);
                        if (el) grid.appendChild(el);
                    });
                }
            } catch {}

            // Drag logic
            let dragged;
            grid.querySelectorAll('.widget').forEach(w => {
                w.setAttribute('draggable', true);
                w.addEventListener('dragstart', () => {
                    dragged = w;
                });
                w.addEventListener('dragend', () => {
                    const order = [...grid.querySelectorAll('.widget')].map(el => el.dataset.id);
                    localStorage.setItem('croin-layout-v2', JSON.stringify(order));
                });
            });

            grid.addEventListener('dragover', e => {
                e.preventDefault();
                const after = getDragAfter(grid, e.clientY);
                after ? grid.insertBefore(dragged, after) : grid.appendChild(dragged);
            });

            function getDragAfter(container, y) {
                return [...container.querySelectorAll('.widget:not([style*="opacity: 0"])')].reduce((closest, el) => {
                    const box = el.getBoundingClientRect();
                    const offset = y - box.top - box.height / 2;
                    return (offset < 0 && offset > closest.offset) ? {
                        offset,
                        element: el
                    } : closest;
                }, {
                    offset: Number.NEGATIVE_INFINITY
                }).element;
            }
        });

        /* --- Mobile sidebar --- */
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        menuToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        });

        function closeSidebar() {
            sidebar?.classList.remove('active');
            overlay.style.display = 'none';
        }
    </script>
    <script src="assets/js/app.js"></script>
</body>

</html>

<?php
function fmtCompact($val)
{
    $n = (float)$val;
    if ($n >= 1e12) return number_format($n / 1e12, 2) . 'T';
    if ($n >= 1e9)  return number_format($n / 1e9, 2) . 'B';
    if ($n >= 1e6)  return number_format($n / 1e6, 2) . 'M';
    return number_format($n, 0);
}
?>