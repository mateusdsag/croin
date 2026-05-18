```php
</main>

<div id="toastContainer"></div>

<div id="portfolioModal" class="portfolio-modal">

    <div class="portfolio-content">

        <h2 class="modal-title">
            Adicionar ao Portfolio
        </h2>

        <div class="modal-coin-info">

            <span style="font-size:20px;">◉</span>

            <span id="modalCoinLabel">
                —
            </span>

        </div>

        <input type="hidden" id="portfolioSymbol">
        <input type="hidden" id="portfolioName">

        <label class="modal-label">
            Quantidade
        </label>

        <input
            type="number"
            id="portfolioQuantity"
            class="portfolio-input"
            placeholder="0.5"
            step="any"
        >

        <label class="modal-label">
            Preço de Compra
        </label>

        <input
            type="number"
            id="portfolioPrice"
            class="portfolio-input"
            placeholder="45000"
            step="any"
        >

        <div class="modal-actions">

            <button
                class="save-btn"
                onclick="savePortfolio()"
            >
                Salvar
            </button>

            <button
                class="close-btn"
                onclick="closePortfolioModal()"
            >
                Cancelar
            </button>

        </div>

    </div>

</div>

<script src="./assets/js/app.js"></script>

</body>
</html>
```
