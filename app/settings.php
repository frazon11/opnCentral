<?php
require_once __DIR__ . '/inc/config.php';
require_login();
require __DIR__ . '/inc/header.php';
?>
<div class="page-title">
    <div>
        <h1><?= h(t('settings.title')) ?></h1>
        <p><?= h(t('settings.subtitle')) ?></p>
    </div>
</div>

<div class="settings-grid">
    <section class="card">
        <h2><?= h(t('language')) ?></h2>
        <p class="muted"><?= h(t('settings.language_help')) ?></p>

        <label>
            <?= h(t('language')) ?>
            <select id="settings-language">
                <?php foreach (supported_languages() as $code => $label): ?>
                    <option value="<?= h($code) ?>" <?= current_language()===$code?'selected':'' ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </section>

    <section class="card">
        <h2><?= h(t('settings.theme')) ?></h2>
        <p class="muted"><?= h(t('settings.theme_help')) ?></p>

        <label>
            <?= h(t('settings.theme')) ?>
            <select id="settings-theme">
                <option value="light"><?= h(t('settings.theme_light')) ?></option>
                <option value="dark"><?= h(t('settings.theme_dark')) ?></option>
            </select>
        </label>

        <div class="theme-preview">
            <div class="theme-preview-sidebar"></div>
            <div class="theme-preview-content">
                <span></span><span></span><span></span>
            </div>
        </div>
    </section>

    <section class="card wide">
        <h2><?= h(t('menu.notifications')) ?></h2>
        <p class="muted"><?= h(t('settings.notifications_help')) ?></p>
        <a class="button secondary" href="/notifications.php">
            <?= h(t('settings.open_notifications')) ?>
        </a>
    </section>
</div>

<script>
(function(){
    const language=document.getElementById('settings-language');
    const theme=document.getElementById('settings-theme');

    language?.addEventListener('change',function(){
        const url=new URL(window.location.href);
        url.searchParams.set('lang',this.value);
        window.location.href=url.toString();
    });

    const currentTheme=document.documentElement.dataset.theme==='dark'?'dark':'light';
    theme.value=currentTheme;

    theme?.addEventListener('change',function(){
        window.opnCentralSetTheme(this.value);
    });
})();
</script>
<?php require __DIR__ . '/inc/footer.php'; ?>
