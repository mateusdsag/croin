/*
==================================================
CROIN - APP.JS
URL dinâmica baseada no location atual
==================================================
*/

const BASE_URL = (() => {
    const { origin, pathname } = window.location;
    // Remove tudo depois de /public/ ou /public
    const match = pathname.match(/^(.*\/public)\/?/);
    return match ? origin + match[1] : origin;
})();

/*
==================================================
LOADER
==================================================
*/

window.addEventListener('load', () => {
    setTimeout(() => {
        const loader = document.getElementById('loader');
        if (loader) loader.classList.add('hidden');
    }, 6);
});

/*
==================================================
ATUALIZAR MOEDAS
==================================================
*/

async function updateCoins() {
    try {
        const response = await fetch(`${BASE_URL}/api/coin.php`);
        if (!response.ok) return;
        const coins = await response.json();

        const coinGrid = document.getElementById('coinGrid');
        if (!coinGrid || !Array.isArray(coins)) return;

        // Preservar search
        const searchVal = document.getElementById('searchInput')?.value?.toLowerCase() || '';

        coinGrid.innerHTML = '';

        coins.forEach((coin) => {
            const card = document.createElement('a');
            card.href = `?page=coin&symbol=${coin.symbol.toLowerCase()}`;
            card.className = 'crypto-card coin-item';
            card.dataset.name = coin.name.toLowerCase();
            card.dataset.symbol = coin.symbol.toLowerCase();

            const isPos = Number(coin.change) >= 0;
            const changeClass = isPos ? 'positive' : 'negative';
            const changePrefix = isPos ? '+' : '';

            card.innerHTML = `
<div class="coin-top">
    <div class="coin-info">
        <img src="${escHtml(coin.image)}" class="coin-image" alt="${escHtml(coin.name)}" onerror="this.src='https://via.placeholder.com/42/131920/22d3ee?text=${escHtml(coin.symbol)}'">
        <div>
            <h3 class="coin-name">${escHtml(coin.name)}</h3>
            <p class="coin-symbol">${escHtml(coin.symbol)}</p>
        </div>
    </div>
    <div class="coin-actions">
        <button class="favorite-btn" title="Watchlist"
            onclick="event.preventDefault(); event.stopPropagation(); addToWatchlist('${escHtml(coin.symbol)}','${escHtml(coin.name)}',this)">★</button>
        <button class="portfolio-btn" title="Adicionar ao Portfolio"
            onclick="event.preventDefault(); event.stopPropagation(); openPortfolioModal('${escHtml(coin.symbol)}','${escHtml(coin.name)}','${coin.price}')">+</button>
    </div>
</div>
<div class="rank-badge">#${coin.rank}</div>
<div class="coin-price-area">
    <h2 class="coin-price">$${fmtPrice(coin.price)}</h2>
    <p class="${changeClass}">${changePrefix}${Number(coin.change).toFixed(2)}%</p>
</div>
<div class="coin-stats">
    <p class="market-cap"><span>Market Cap</span><span>$${fmtCompact(coin.market_cap)}</span></p>
    <p class="market-volume"><span>Volume 24h</span><span>$${fmtCompact(coin.volume)}</span></p>
</div>`;

            // Ocultar se não passar no filtro
            if (searchVal && !coin.name.toLowerCase().includes(searchVal) && !coin.symbol.toLowerCase().includes(searchVal)) {
                card.style.display = 'none';
            }

            coinGrid.appendChild(card);
        });

    } catch (err) {
        console.error('[CROIN] updateCoins:', err);
    }
}

/*
==================================================
UTILITÁRIOS DE FORMATAÇÃO
==================================================
*/

function fmtPrice(val) {
    const n = Number(val);
    if (n >= 1000) return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (n >= 1)    return n.toFixed(4);
    return n.toFixed(6);
}

function fmtCompact(val) {
    const n = Number(val);
    if (n >= 1e12) return (n / 1e12).toFixed(2) + 'T';
    if (n >= 1e9)  return (n / 1e9).toFixed(2) + 'B';
    if (n >= 1e6)  return (n / 1e6).toFixed(2) + 'M';
    return n.toLocaleString('en-US');
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/*
==================================================
AUTO UPDATE (15s)
==================================================
*/

updateCoins();
setInterval(updateCoins, 15000);

/*
==================================================
SEARCH
==================================================
*/

const searchInput = document.getElementById('searchInput');

if (searchInput) {
    searchInput.addEventListener('input', function () {
        const val = this.value.toLowerCase().trim();
        document.querySelectorAll('.coin-item').forEach((card) => {
            const match = card.dataset.name?.includes(val) || card.dataset.symbol?.includes(val);
            card.style.display = match ? '' : 'none';
        });
    });
}

/*
==================================================
MENU MOBILE
==================================================
*/

const menuToggle = document.getElementById('menu-toggle');
const sidebar = document.querySelector('.sidebar');
const overlay = document.getElementById('sidebarOverlay');

if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        sidebar?.classList.toggle('active');
        overlay?.classList.toggle('active');
    });
}

if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar?.classList.remove('active');
        overlay?.classList.remove('active');
    });
}

/*
==================================================
WATCHLIST
==================================================
*/

async function addToWatchlist(symbol, name, button) {
    button.disabled = true;
    try {
        const fd = new FormData();
        fd.append('action', 'add');
        fd.append('symbol', symbol);
        fd.append('name', name);

        const res = await fetch(`${BASE_URL}/api/watchlist.php`, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            button.classList.add('active-favorite');
            showToast('★ ' + name + ' adicionado à watchlist', 'success');
        } else {
            showToast(data.message || 'Erro ao adicionar', 'error');
        }
    } catch (err) {
        console.error('[CROIN] watchlist:', err);
        showToast('Erro de conexão', 'error');
    } finally {
        setTimeout(() => { button.disabled = false; }, 1500);
    }
}

/*
==================================================
PORTFOLIO MODAL
==================================================
*/

function openPortfolioModal(symbol, name, price) {
    const modal = document.getElementById('portfolioModal');
    if (!modal) return;
    document.getElementById('portfolioSymbol').value = symbol;
    document.getElementById('portfolioName').value = name;
    document.getElementById('portfolioPrice').value = price;

    const coinLabel = document.getElementById('modalCoinLabel');
    if (coinLabel) coinLabel.textContent = `${name} (${symbol})`;

    modal.style.display = 'flex';
    document.getElementById('portfolioQuantity')?.focus();
}

function closePortfolioModal() {
    const modal = document.getElementById('portfolioModal');
    if (modal) modal.style.display = 'none';
    document.getElementById('portfolioQuantity').value = '';
}

// Fechar modal clicando fora
document.addEventListener('click', (e) => {
    const modal = document.getElementById('portfolioModal');
    if (modal && e.target === modal) closePortfolioModal();
});

// Fechar com ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePortfolioModal();
});

/*
==================================================
SALVAR PORTFOLIO
==================================================
*/

async function savePortfolio() {
    const symbol   = document.getElementById('portfolioSymbol')?.value;
    const name     = document.getElementById('portfolioName')?.value;
    const quantity = document.getElementById('portfolioQuantity')?.value;
    const price    = document.getElementById('portfolioPrice')?.value;

    if (!quantity || quantity <= 0) {
        showToast('Informe uma quantidade válida', 'error');
        return;
    }
    if (!price || price <= 0) {
        showToast('Informe um preço válido', 'error');
        return;
    }

    const saveBtn = document.querySelector('.save-btn');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Salvando...'; }

    try {
        const fd = new FormData();
        fd.append('action', 'add');
        fd.append('symbol', symbol);
        fd.append('name', name);
        fd.append('quantity', quantity);
        fd.append('buy_price', price);

        const res = await fetch(`${BASE_URL}/api/portfolio.php`, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            showToast('✓ ' + name + ' adicionado ao portfolio', 'success');
            closePortfolioModal();
        } else {
            showToast(data.message || 'Erro ao salvar', 'error');
        }
    } catch (err) {
        console.error('[CROIN] portfolio:', err);
        showToast('Erro de conexão', 'error');
    } finally {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Salvar'; }
    }
}

/*
==================================================
TOAST
==================================================
*/

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = { success: '✓', error: '✕', info: 'ℹ' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<span>${icons[type] || ''}</span><span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(30px)';
        toast.style.transition = 'all 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}