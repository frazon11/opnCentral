</main>
<?php if (logged_in()): ?></div><?php endif; ?>
<footer class="app-footer"><?= h(t('footer')) ?></footer>
<link rel="stylesheet" href="/assets/topbar-controls.css?v=06115">
<link rel="stylesheet" href="/assets/sidebar-opnsense.css?v=06116">
<link rel="stylesheet" href="/assets/sidebar-submenus.css?v=06117">
<script src="/assets/ids-menu.js?v=06118"></script>
<script src="/assets/sidebar-opnsense.js?v=06118"></script>
<script>
(function(){
    const version = document.querySelector('.sidebar-meta span:first-child');
    if(version){
        version.textContent = 'v0.6.11.8';
    }
})();
</script>
</body></html>
