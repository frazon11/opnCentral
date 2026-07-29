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



    <section class="card wide" id="update-settings-card">
        <div class="card-head">
            <div>
                <h2>Updates</h2>
                <p class="muted">Check GitHub for new published opnCentral releases.</p>
            </div>
            <button type="button" class="button secondary" id="update-check-now">Check now</button>
        </div>

        <label class="checkbox">
            <input type="checkbox" id="automatic-update-check" checked>
            Check GitHub automatically every 24 hours
        </label>

        <div class="update-status-grid">
            <div><strong>Installed version</strong><span id="installed-version">v0.4.4.1</span></div>
            <div><strong>Latest version</strong><span id="latest-version">Loading…</span></div>
            <div><strong>Last checked</strong><span id="last-update-check">Loading…</span></div>
            <div><strong>Status</strong><span id="update-check-status">Loading…</span></div>
        </div>

        <div id="update-check-message" class="card-message"></div>
        <a id="release-link" class="button secondary hidden" target="_blank" rel="noopener noreferrer">View release</a>

        <p class="muted update-privacy-note">
            This reads public release information from GitHub. No installation ID,
            firewall details, credentials, networks or VPN data are sent.
        </p>
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


    const automatic=document.getElementById('automatic-update-check');
    const checkNow=document.getElementById('update-check-now');
    const latest=document.getElementById('latest-version');
    const lastChecked=document.getElementById('last-update-check');
    const status=document.getElementById('update-check-status');
    const message=document.getElementById('update-check-message');
    const releaseLink=document.getElementById('release-link');

    function formatDate(value){
        if(!value) return 'Never';
        const date=new Date(value);
        return Number.isNaN(date.getTime())?value:date.toLocaleString();
    }

    function renderUpdate(result){
        const state=result.state||{};
        automatic.checked=state.enabled!==false;
        latest.textContent=state.latest_version?'v'+state.latest_version:'Unknown';
        lastChecked.textContent=formatDate(state.last_checked);

        releaseLink.classList.add('hidden');
        releaseLink.removeAttribute('href');

        if(state.error){
            status.innerHTML='<span class="badge bad">Check failed</span>';
            message.textContent=state.error;
            return;
        }

        if(!state.latest_version){
            status.innerHTML='<span class="badge neutral">Not checked</span>';
            message.textContent='No release information is cached yet.';
            return;
        }

        if(state.update_available){
            status.innerHTML='<span class="badge warning">Update available</span>';
            message.textContent='A newer opnCentral release is available.';
        }else{
            status.innerHTML='<span class="badge good">Up to date</span>';
            message.textContent='This installation is running the latest published release.';
        }

        if(state.release_url){
            releaseLink.href=state.release_url;
            releaseLink.classList.remove('hidden');
        }
    }

    async function loadUpdate(force){
        checkNow.disabled=true;
        if(force) checkNow.textContent='Checking…';

        try{
            const response=await fetch('/update_check.php'+(force?'?force=1':''),{
                credentials:'same-origin',
                cache:'no-store'
            });
            const result=await response.json();
            if(!response.ok||result.ok!==true) throw new Error(result.error||'Update check failed.');
            renderUpdate(result);
        }catch(error){
            status.innerHTML='<span class="badge bad">Check failed</span>';
            message.textContent=error.message;
        }finally{
            checkNow.disabled=false;
            checkNow.textContent='Check now';
        }
    }

    automatic.addEventListener('change',async function(){
        const body=new URLSearchParams();
        body.set('csrf',<?= json_encode(csrf_token(), JSON_UNESCAPED_SLASHES) ?>);
        body.set('enabled',this.checked?'1':'0');

        try{
            const response=await fetch('/update_settings_action.php',{
                method:'POST',
                credentials:'same-origin',
                headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
                body
            });
            const result=await response.json();
            if(!response.ok||result.ok!==true) throw new Error(result.error||'Could not save setting.');
        }catch(error){
            this.checked=!this.checked;
            alert(error.message);
        }
    });

    checkNow.addEventListener('click',()=>loadUpdate(true));
    loadUpdate(false);
})();
</script>
<?php require __DIR__ . '/inc/footer.php'; ?>
