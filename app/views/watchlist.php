<?php
if (!isset($_SESSION['user'])) {
    header("Location: ?page=login");
    exit;
}

require_once "../config/database.php";
require_once "../app/services/CoinService.php";

$database = new Database();
$db       = $database->connect();
$userId   = (int)$_SESSION['user']['id'];

// Buscar watchlist
$stmt = $db->prepare("SELECT * FROM watchlist WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$userId]);
$watchlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar preços atuais
$marketData = CoinService::getMarketData();
$marketMap  = [];
foreach ($marketData as $coin) {
    $marketMap[strtoupper($coin['symbol'])] = $coin;
}

$userName   = htmlspecialchars($_SESSION['user']['name']  ?? 'Usuário');
$userEmail  = htmlspecialchars($_SESSION['user']['email'] ?? '');
$userAvatar = strtoupper(substr($_SESSION['user']['name'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watchlist — CROIN PRO</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- SIDEBAR OVERLAY -->
    <div id="sidebarOverlay"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:850;"
        onclick="closeSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo-area">
            <div class="logo">CR<em>O</em>IN</div>
            <div class="logo-sub">Pro Trading</div>
        </div>

        <nav>
            <a href="?page=dashboard" class="menu-item">
                <span class="menu-icon">⬡</span> Dashboard
            </a>
            <a href="?page=portfolio" class="menu-item">
                <span class="menu-icon">◎</span> Portfolio
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

        <div class="topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <button id="menu-toggle" class="mobile-menu-btn">☰</button>
                <div>
                    <div class="title">Watchlist</div>
                    <div class="subtitle">Suas moedas favoritas</div>
                </div>
            </div>
            <a href="?page=dashboard" class="back-btn">← Dashboard</a>
        </div>

        <?php if (!empty($watchlist)): ?>

            <div class="watchlist-grid">

                <?php foreach ($watchlist as $item):
                    $sym    = strtoupper($item['coin_symbol']);
                    $mkt    = $marketMap[$sym] ?? null;
                    $price  = $mkt ? (float)$mkt['price']  : 0;
                    $change = $mkt ? (float)$mkt['change']  : 0;
                    $image  = $mkt['image'] ?? '';
                    $isPos  = $change >= 0;
                ?>
                    <div class="watchlist-card">

                        <div style="display:flex;align-items:center;gap:12px;">
                            <?php if ($image): ?>
                                <img src="<?= htmlspecialchars($image) ?>"
                                    width="44" height="44"
                                    style="border-radius:50%;object-fit:cover;border:1px solid var(--border);"
                                    alt="">
                            <?php endif; ?>
                            <div>
                                <div style="font-weight:600;font-size:15px;">
                                    <?= htmlspecialchars($item['coin_name']) ?>
                                </div>
                                <div style="font-size:11px;color:var(--text-3);margin-top:3px;text-transform:uppercase;letter-spacing:0.5px;">
                                    <?= $sym ?>
                                </div>
                            </div>
                        </div>

                        <div style="text-align:right;">
                            <div style="font-family:var(--font-mono);font-size:18px;font-weight:500;color:var(--text-1);font-variant-numeric:tabular-nums;">
                                <?= $price > 0 ? '$' . number_format($price, 2) : '—' ?>
                            </div>
                            <?php if ($mkt): ?>
                                <div style="margin-top:4px;">
                                    <span class="pct <?= $isPos ? 'pct-up' : 'pct-down' ?>">
                                        <?= $isPos ? '+' : '' ?><?= number_format($change, 2) ?>%
                                    </span>
                                </div>
                            <?php endif; ?>
                            <button class="remove-btn"
                                onclick="removeFromWatchlist(<?= (int)$item['id'] ?>, this)">
                                Remover
                            </button>
                        </div>

                    </div>
                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">
                <div class="empty-state-icon">★</div>
                <p style="font-size:16px;font-weight:600;color:var(--text-2);">Nenhuma moeda ainda</p>
                <p style="margin-top:8px;font-size:13px;">No Dashboard, clique em <strong style="color:var(--yellow);">★</strong> nos cards para adicionar.</p>
            </div>

        <?php endif; ?>

    </main>

    <div id="toastContainer"></div>

    <script>
        const BASE_URL = (() => {
            const {
                origin,
                pathname
            } = window.location;
            const match = pathname.match(/^(.*\/public)\/?/);
            return match ? origin + match[1] : origin;
        })();

        async function removeFromWatchlist(id, btn) {
            if (!confirm('Remover da watchlist?')) return;
            btn.disabled = true;
            try {
                const fd = new FormData();
                fd.append('action', 'remove');
                fd.append('id', id);
                const res = await fetch(`${BASE_URL}/api/watchlist.php`, {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    const card = btn.closest('.watchlist-card');
                    card.style.transition = 'opacity 0.3s, transform 0.3s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => card.remove(), 300);
                    showToast('Removido da watchlist', 'success');
                } else {
                    showToast(data.message || 'Erro', 'error');
                    btn.disabled = false;
                }
            } catch {
                showToast('Erro de conexão', 'error');
                btn.disabled = false;
            }
        }

        function showToast(msg, type = 'info') {
            const c = document.getElementById('toastContainer');
            if (!c) return;
            const t = document.createElement('div');
            t.className = `toast toast-${type}`;
            t.innerHTML = `<span>${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span><span>${msg}</span>`;
            c.appendChild(t);
            setTimeout(() => {
                t.style.transition = 'opacity 0.3s, transform 0.3s';
                t.style.opacity = '0';
                t.style.transform = 'translateX(20px)';
                setTimeout(() => t.remove(), 300);
            }, 3200);
        }

        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        menuToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        });

        function closeSidebar() {
            sidebar?.classList.remove('active');
            if (overlay) overlay.style.display = 'none';
        }
    </script>
</body>

</html>