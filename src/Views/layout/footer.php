</main>

    <footer class="footer">
        <div class="footer__inner">
            <span>&copy; <?= date('Y') ?> THBO Dashboards</span>
            <span class="footer__meta"><?= t('ui.layout.built_with') ?></span>
        </div>
    </footer>

    <!-- Texts for app.js in the chosen language (resources/lang, group "js") -->
    <script>window.I18N = <?= json_encode(\App\Core\Lang::forJs(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="<?= base_url('/js/app.js') ?>?v=<?= filemtime(dirname(__DIR__, 3) . '/public/js/app.js') ?>"></script>
</body>
</html>
