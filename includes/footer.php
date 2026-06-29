<footer class="main-footer">
    <div class="footer-container">
        <p class="footer-copyright">
            &copy; 2026 <span class="brand-name">PABSON PARSA</span>. All rights reserved.
        </p>
        <p class="footer-credits">
        <script>
            (function(){
                var script = document.createElement('script');
                script.src = '<?php echo APP_URL; ?>/assets/js/live_update.js';
                script.defer = true;
                document.body.appendChild(script);
            })();
        </script>
            Powered by <a href="#" class="credit-link">NepRau Technologies</a>
        </p>
    </div>
</footer>

<style>
:root {
    --sidebar-width:179px;
    --footer-bg: #f9f9f9;
    --footer-border: #e2e8f0;
    --footer-text: #64748b;
    --footer-link-hover: #1d4ed8;
}
/* In your styling, change background to var(--footer-bg) and add: */
/* border-top: 1px solid var(--footer-border); */

.main-footer {
    background-color: var(--footer-bg);
    color: var(--footer-text);
    padding: 20px 0;
    margin-left: var(--sidebar-width);
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    transition: margin-left 0.3s ease;
}

.footer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.footer-copyright,
.footer-credits {
    margin: 0;
    font-size: 0.875rem; /* Standardized 14px equivalent */
    font-weight: 500;
    line-height: 1.5;
}

.brand-name {
    font-weight: 700;
}

.credit-link {
    color: inherit;
    text-decoration: none;
    font-weight: 700;
    transition: opacity 0.2s ease;
}

.credit-link:hover {
    opacity: 0.8;
    text-decoration: underline;
}

/* Responsive Breakpoints */
@media (max-width: 768px) {
    .main-footer {
        margin-left: 0; /* Remove sidebar margin when sidebar collapses on mobile */
    }
    
    .footer-container {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
}
</style>