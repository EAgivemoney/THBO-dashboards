</main>

    <footer class="footer">
        <div class="footer__inner">
            <span>&copy; <?= date('Y') ?> THBO Dashboards</span>
            <span class="footer__meta">Gebouwd met PHP, MySQL en SCSS</span>
        </div>
    </footer>

    <script src="<?= base_url('/js/app.js') ?>?v=<?= filemtime(dirname(__DIR__, 3) . '/public/js/app.js') ?>"></script>
</body>
</html>