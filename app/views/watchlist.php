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

// Buscar watchlist
$stmt = $db->prepare("SELECT * FROM watchlist WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$userId]);
$watchlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar preços atuais
$marketData = CoinService::getMarketData();
$marketMap  = [];
foreach($marketData as $coin){
    $marketMap[strtoupper($coin['symbol'])] = $coin;
}

$userName   = htmlspecialchars($_SESSION['user']['name'] ?? 'Usuário');
$userEmail  = htmlspecialchars($_SESSION['user']['email'] ?? '');
$userAvatar = strtoupper(substr($_SESSION['user']['name'] ?? 'U', 0, 1));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watchlist — CROIN</title>
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
        <a href="?page=portfolio" class="menu-item">
            <span class="menu-icon">◉</span> Portfolio
        </a>
        <a href="?page=watchlist" class="menu-item active">
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
                <h2 class="title">Watchlist</h2>
                <p class="subtitle">Suas moedas favoritas</p>
            </div>
        </div>
        <a href="?page=dashboard" class="back-btn">← Dashboard</a>
    </header>

    <?php if(!empty($watchlist)): ?>
    <div class="watchlist-grid">
        <?php foreach($watchlist as $item):
            $sym = strtoupper($item['coin_symbol']);
            $mkt = $marketMap[$sym] ?? null;
            $price  = $mkt ? (float)$mkt['price']  : 0;
            $change = $mkt ? (float)$mkt['change']  : 0;
            $image  = $mkt['image'] ?? '';
            $isPos  = $change >= 0;
        ?>
        <div class="watchlist-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <?php if($image): ?>
                <img src="<?= htmlspecialchars($image) ?>" width="44" height="44" style="border-radius:50%;object-fit:cover;" alt="">
                <?php endif; ?>
                <div>
                    <div style="font-weight:700;font-size:16px;"><?= htmlspecialchars($item['coin_name']) ?></div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px;"><?= $sym ?></div>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-family:var(--font-head);font-size:18px;font-weight:700;color:var(--accent);">
                    $<?= $price > 0 ? number_format($price, 2) : '—' ?>
                </div>
                <?php if($mkt): ?>
                <div style="font-size:13px;font-weight:600;color:<?= $isPos ? 'var(--green)' : 'var(--red)' ?>;">
                    <?= $isPos ? '+' : '' ?><?= number_format($change, 2) ?>%
                </div>
                <?php endif; ?>
                <button class="remove-btn" style="margin-top:8px;"
                    onclick="removeFromWatchlist(<?= (int)$item['id'] ?>, this)">Remover</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">★</div>
        <p>Nenhuma moeda na watchlist ainda.</p>
        <p style="margin-top:8px;font-size:14px;">No Dashboard, clique em <strong>★</strong> nos cards para adicionar.</p>
    </div>
    <?php endif; ?>

</main>

<div id="toastContainer"></div>

<script>
const BASE_URL = (() => {
    const { origin, pathname } = window.location;
    const match = pathname.match(/^(.*\/public)\/?/);
    return match ? origin + match[1] : origin;
})();

async function removeFromWatchlist(id, btn){
    if(!confirm('Remover da watchlist?')) return;
    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('action', 'remove');
        fd.append('id', id);
        const res = await fetch(`${BASE_URL}/api/watchlist.php`, { method:'POST', body:fd });
        const data = await res.json();
        if(data.success){
            btn.closest('.watchlist-card').style.opacity = '0';
            btn.closest('.watchlist-card').style.transition = 'opacity 0.3s';
            setTimeout(()=>{ btn.closest('.watchlist-card').remove(); }, 300);
            showToast('Removido da watchlist', 'success');
        } else {
            showToast(data.message || 'Erro', 'error');
            btn.disabled = false;
        }
    } catch(err){
        showToast('Erro de conexão', 'error');
        btn.disabled = false;
    }
}

function showToast(msg, type='info'){
    const c = document.getElementById('toastContainer');
    if(!c) return;
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(()=>t.remove(), 3500);
}

const toggle = document.getElementById('menu-toggle');
const sidebar = document.querySelector('.sidebar');
const overlay = document.getElementById('sidebarOverlay');
if(toggle){ toggle.addEventListener('click',()=>{ sidebar.classList.toggle('active'); overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none'; }); }
</script>

</body>
</html>