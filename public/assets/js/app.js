/*
 * CROIN PRO — app.js
 * Global JS: coin updates, search, watchlist, portfolio modal, toast, mobile nav
 */

/* ============================================================
   BASE URL
   ============================================================ */

const BASE_URL = (() => {
    const { origin, pathname } = window.location;
    const match = pathname.match(/^(.*\/public)\/?/);
    return match ? origin + match[1] : origin;
})();

/* ============================================================
   LOADER
   ============================================================ */

window.addEventListener('load', () => {
    setTimeout(() => {
        document.getElementById('loader')?.classList.add('hidden');
    }, 50);
});

/* ============================================================
   FORMAT HELPERS
   ============================================================ */

function fmtPrice(val) {
    const n = Number(val);
    if (n >= 1000) return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (n >= 1)    return n.toFixed(4);
    return n.toFixed(6);
}

function fmtCompact(val) {
    const n = Number(val);
    if (n >= 1e12) return (n / 1e12).toFixed(2) + 'T';
    if (n >= 1e9)  return (n / 1e9).toFixed(2)  + 'B';
    if (n >= 1e6)  return (n / 1e6).toFixed(2)  + 'M';
    return n.toLocaleString('en-US');
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/* ============================================================
   COIN GRID UPDATE
   ============================================================ */

async function updateCoins() {
    try {
        const res = await fetch(`${BASE_URL}/api/coin.php`);
        if (!res.ok) return;
        const coins = await res.json();

        const grid = document.getElementById('coinGrid');
        if (!grid || !Array.isArray(coins)) return;

        const searchVal = document.getElementById('searchInput')?.value?.toLowerCase() || '';

        grid.innerHTML = '';

        coins.forEach(coin => {
            const isPos   = Number(coin.change) >= 0;
            const pctCls  = isPos ? 'positive' : 'negative';
            const pctPfx  = isPos ? '+' : '';
            const symbol  = (coin.symbol ?? '').toUpperCase();

            const card = document.createElement('a');
            card.href        = `?page=coin&symbol=${coin.symbol.toLowerCase()}`;
            card.className   = 'crypto-card coin-item';
            card.dataset.name   = (coin.name ?? '').toLowerCase();
            card.dataset.symbol = coin.symbol.toLowerCase();

            card.innerHTML = `
<div class="coin-top">
    <div class="coin-info">
        <img src="${escHtml(coin.image)}"
             class="coin-image" alt="${escHtml(coin.name)}"
             loading="lazy"
             onerror="this.src='https://via.placeholder.com/40/111520/0066FF?text=${symbol}'">
        <div>
            <div class="coin-name">${escHtml(coin.name)}</div>
            <div class="coin-symbol">${symbol}</div>
        </div>
    </div>
    <div class="coin-actions" onclick="event.preventDefault()">
        <button class="favorite-btn" title="Watchlist"
                onclick="addToWatchlist('${escHtml(coin.symbol)}','${escHtml(coin.name)}',this)">★</button>
        <button class="portfolio-btn" title="Portfolio"
                onclick="openPortfolioModal('${escHtml(coin.symbol)}','${escHtml(coin.name)}','${coin.price}')">+</button>
    </div>
</div>
${coin.rank ? `<div class="rank-badge">#${coin.rank}</div>` : ''}
<div class="coin-price-area">
    <div class="coin-price">$${fmtPrice(coin.price)}</div>
    <div class="${pctCls}">${pctPfx}${Number(coin.change).toFixed(2)}%</div>
</div>
${(coin.market_cap || coin.volume) ? `
<div class="coin-stats">
    ${coin.market_cap ? `<div class="market-cap"><span>Market Cap</span><span>$${fmtCompact(coin.market_cap)}</span></div>` : ''}
    ${coin.volume ? `<div class="market-volume"><span>Volume 24h</span><span>$${fmtCompact(coin.volume)}</span></div>` : ''}
</div>` : ''}`;

            if (searchVal && !card.dataset.name.includes(searchVal) && !card.dataset.symbol.includes(searchVal)) {
                card.style.display = 'none';
            }

            grid.appendChild(card);
        });

    } catch (err) {
        console.error('[CROIN] updateCoins:', err);
    }
}

/* ============================================================
   AUTO UPDATE (15s)
   ============================================================ */

updateCoins();
setInterval(updateCoins, 15000);

/* ============================================================
   SEARCH
   ============================================================ */

document.getElementById('searchInput')?.addEventListener('input', function () {
    const val = this.value.toLowerCase().trim();
    document.querySelectorAll('.coin-item').forEach(card => {
        const match = !val
            || card.dataset.name?.includes(val)
            || card.dataset.symbol?.includes(val);
        card.style.display = match ? '' : 'none';
    });
});

/* ============================================================
   MOBILE SIDEBAR
   ============================================================ */

const _menuToggle = document.getElementById('menu-toggle');
const _sidebar    = document.querySelector('.sidebar');
const _overlay    = document.getElementById('sidebarOverlay');

_menuToggle?.addEventListener('click', () => {
    _sidebar?.classList.toggle('active');
    if (_overlay) _overlay.style.display = _sidebar?.classList.contains('active') ? 'block' : 'none';
});

window.closeSidebar = function () {
    _sidebar?.classList.remove('active');
    if (_overlay) _overlay.style.display = 'none';
};

/* ============================================================
   WATCHLIST
   ============================================================ */

async function addToWatchlist(symbol, name, button) {
    button.disabled = true;
    try {
        const fd = new FormData();
        fd.append('action', 'add');
        fd.append('symbol', symbol);
        fd.append('name', name);
        const res  = await fetch(`${BASE_URL}/api/watchlist.php`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            button.classList.add('active-favorite');
            showToast(`★ ${name} adicionado à watchlist`, 'success');
        } else {
            showToast(data.message || 'Erro ao adicionar', 'error');
        }
    } catch {
        showToast('Erro de conexão', 'error');
    } finally {
        setTimeout(() => { button.disabled = false; }, 1500);
    }
}

/* ============================================================
   PORTFOLIO MODAL
   ============================================================ */

window.openPortfolioModal = function (symbol, name, price) {
    const modal = document.getElementById('portfolioModal');
    if (!modal) return;
    document.getElementById('portfolioSymbol').value = symbol;
    document.getElementById('portfolioName').value   = name;
    document.getElementById('portfolioPrice').value  = price;
    const label = document.getElementById('modalCoinLabel');
    if (label) label.textContent = `${name} (${symbol})`;
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('portfolioQuantity')?.focus(), 50);
};

window.closePortfolioModal = function () {
    const modal = document.getElementById('portfolioModal');
    if (modal) modal.style.display = 'none';
    const qty = document.getElementById('portfolioQuantity');
    if (qty) qty.value = '';
};

// Close on backdrop click
document.addEventListener('click', e => {
    const modal = document.getElementById('portfolioModal');
    if (modal && e.target === modal) closePortfolioModal();
});

// Close on ESC
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closePortfolioModal();
});

/* ============================================================
   SAVE PORTFOLIO
   ============================================================ */

window.savePortfolio = async function () {
    const symbol   = document.getElementById('portfolioSymbol')?.value;
    const name     = document.getElementById('portfolioName')?.value;
    const quantity = document.getElementById('portfolioQuantity')?.value;
    const price    = document.getElementById('portfolioPrice')?.value;

    if (!quantity || Number(quantity) <= 0) { showToast('Quantidade inválida', 'error'); return; }
    if (!price    || Number(price) <= 0)    { showToast('Preço inválido', 'error'); return; }

    const saveBtn = document.querySelector('.save-btn');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Salvando...'; }

    try {
        const fd = new FormData();
        fd.append('action', 'add');
        fd.append('symbol', symbol);
        fd.append('name', name);
        fd.append('quantity', quantity);
        fd.append('buy_price', price);

        const res  = await fetch(`${BASE_URL}/api/portfolio.php`, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            showToast(`✓ ${name} adicionado ao portfolio`, 'success');
            closePortfolioModal();
        } else {
            showToast(data.message || 'Erro ao salvar', 'error');
        }
    } catch {
        showToast('Erro de conexão', 'error');
    } finally {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Salvar'; }
    }
};

/* ============================================================
   TOAST
   ============================================================ */

window.showToast = function (message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = { success: '✓', error: '✕', info: 'ℹ' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<span>${icons[type] || ''}</span><span>${escHtml(message)}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        toast.style.opacity    = '0';
        toast.style.transform  = 'translateX(20px) scale(0.96)';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
};