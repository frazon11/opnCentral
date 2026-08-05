</main>
<?php if (logged_in()): ?></div><?php endif; ?>
<footer class="app-footer"><?= h(t('footer')) ?></footer>
<link rel="stylesheet" href="/assets/topbar-controls.css?v=06115">
<script>
(function(){
    const version = document.querySelector('.sidebar-meta span:first-child');
    if(version){
        version.textContent = 'v0.6.11.5';
    }
})();
</script>
</body></html>
