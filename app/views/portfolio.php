<?php


if(!isset($_SESSION['user'])){
    header("Location: ?page=login");
    exit;
}

require_once "../config/database.php";
require_once "../app/services/CoinService.php";

$database = new Database();
$db = $database->connect();

$userId = (int)$_SESSION['user']['id'];

$stmt = $db->prepare("SELECT * FROM portfolio WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$userId]);
$portfolio = $stmt->fetchAll(PDO::FETCH_ASSOC);

$marketData = CoinService::getMarketData();
$marketMap  = [];
foreach($marketData as $coin){
    $marketMap[strtoupper($coin['symbol'])] = $coin;
}

$totalBalance = 0;
$totalInvested = 0;
$totalProfit = 0;

$userName  = htmlspecialchars($_SESSION['user']['name'] ?? 'Usuário');
$userEmail = htmlspecialchars($_SESSION['user']['email'] ?? '');
$userAvatar = strtoupper(substr($_SESSION['user']['name'] ?? 'U', 0, 1));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio — CROIN</title>
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
        <a href="?page=dashboard" class="menu-item">
            <span class="menu-icon">◈</span> Dashboard
        </a>
        <a href="?page=portfolio" class="menu-item active">
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

    <header class="topbar">
        <div style="display:flex;align-items:center;gap:16px;">
            <button id="menu-toggle" class="mobile-menu-btn">☰</button>
            <div>
                <h2 class="title">Portfolio</h2>
                <p class="subtitle">Seus investimentos em cripto</p>
            </div>
        </div>
        <a href="?page=dashboard" class="back-btn">← Dashboard</a>
    </header>

    <!-- STATS -->
    <div class="portfolio-stats">
        <div class="portfolio-stat">
            <p class="portfolio-stat-label">Patrimônio Atual</p>
            <h2 class="portfolio-stat-value" id="totalBalance" style="color:var(--accent);">$0</h2>
        </div>
        <div class="portfolio-stat">
            <p class="portfolio-stat-label">Total Investido</p>
            <h2 class="portfolio-stat-value" id="totalInvested" style="color:var(--text);">$0</h2>
        </div>
        <div class="portfolio-stat">
            <p class="portfolio-stat-label">Lucro / Prejuízo</p>
            <h2 class="portfolio-stat-value" id="totalProfit">$0</h2>
        </div>
        <div class="portfolio-stat">
            <p class="portfolio-stat-label">Ativos</p>
            <h2 class="portfolio-stat-value" style="color:var(--text);"><?= count($portfolio) ?></h2>
        </div>
    </div>

    <!-- TABLE -->
    <?php if(!empty($portfolio)): ?>
    <div class="table-wrap">
        <table class="croin-table">
            <thead>
                <tr>
                    <th>Moeda</th>
                    <th>Quantidade</th>
                    <th>Compra</th>
                    <th>Atual</th>
                    <th>Valor</th>
                    <th>P&L</th>
                    <th>P&L %</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($portfolio as $item):
                $symbol       = strtoupper($item['coin_symbol']);
                $currentPrice = isset($marketMap[$symbol]) ? (float)$marketMap[$symbol]['price'] : 0;
                $quantity     = (float)$item['quantity'];
                $buyPrice     = (float)$item['buy_price'];
                $currentValue = $quantity * $currentPrice;
                $invested     = $quantity * $buyPrice;
                $profit       = $currentValue - $invested;
                $profitPct    = $invested > 0 ? (($currentValue / $invested) - 1) * 100 : 0;

                $totalBalance  += $currentValue;
                $totalInvested += $invested;
                $totalProfit   += $profit;

                $isPos = $profit >= 0;
            ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:15px;"><?= htmlspecialchars($item['coin_name']) ?></div>
                    <div style="font-size:12px;color:var(--muted);"><?= $symbol ?></div>
                </td>
                <td><?= rtrim(rtrim(number_format($quantity, 8), '0'), '.') ?></td>
                <td>$<?= number_format($buyPrice, 2) ?></td>
                <td style="color:var(--accent);font-weight:600;">$<?= number_format($currentPrice, 2) ?></td>
                <td style="font-weight:700;">$<?= number_format($currentValue, 2) ?></td>
                <td style="font-weight:700;color:<?= $isPos ? 'var(--green)' : 'var(--red)' ?>">
                    <?= $isPos ? '+' : '' ?>$<?= number_format($profit, 2) ?>
                </td>
                <td style="font-weight:600;color:<?= $isPos ? 'var(--green)' : 'var(--red)' ?>">
                    <?= $isPos ? '+' : '' ?><?= number_format($profitPct, 2) ?>%
                </td>
                <td>
                    <button class="remove-btn" onclick="removeAsset(<?= (int)$item['id'] ?>)">Remover</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">◉</div>
        <p>Nenhum ativo no portfolio ainda.</p>
        <p style="margin-top:8px;font-size:14px;">Vá ao Dashboard e adicione moedas clicando em <strong>+</strong></p>
    </div>
    <?php endif; ?>

</main>

<!-- TOAST -->
<div id="toastContainer"></div>

<script>
// Totais calculados no PHP
document.getElementById('totalBalance').textContent  = '$<?= number_format($totalBalance, 2) ?>';
document.getElementById('totalInvested').textContent = '$<?= number_format($totalInvested, 2) ?>';

const totalProfit = <?= $totalProfit ?>;
const profitEl = document.getElementById('totalProfit');
profitEl.textContent = (totalProfit >= 0 ? '+' : '') + '$' + Math.abs(totalProfit).toFixed(2);
profitEl.style.color = totalProfit >= 0 ? 'var(--green)' : 'var(--red)';

const BASE_URL = (() => {
    const { origin, pathname } = window.location;
    const match = pathname.match(/^(.*\/public)\/?/);
    return match ? origin + match[1] : origin;
})();

async function removeAsset(id){
    if(!confirm('Remover este ativo do portfolio?')) return;
    try {
        const fd = new FormData();
        fd.append('action', 'remove');
        fd.append('id', id);
        const res = await fetch(`${BASE_URL}/api/portfolio.php`, { method:'POST', body:fd });
        const data = await res.json();
        if(data.success){
            location.reload();
        } else {
            alert(data.message || 'Erro ao remover');
        }
    } catch(err){
        alert('Erro de conexão');
    }
}

// Mobile menu
const toggle = document.getElementById('menu-toggle');
const sidebar = document.querySelector('.sidebar');
const overlay = document.getElementById('sidebarOverlay');
if(toggle){ toggle.addEventListener('click',()=>{ sidebar.classList.toggle('active'); overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none'; }); }

function showToast(msg, type='info'){
    const c = document.getElementById('toastContainer');
    if(!c) return;
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(()=>t.remove(), 3500);
}
</script>

</body>
</html>