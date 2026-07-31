<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_login();
require __DIR__ . '/inc/header.php';
?>

<div class="page-title">
    <div>
        <h1>Services</h1>
        <p>Active services on all managed OPNsense firewalls.</p>
    </div>

    <button type="button" class="button secondary" id="services-refresh">
        Refresh
    </button>
</div>

<div id="services-summary" class="muted">Loading services…</div>
<div id="services-error" class="alert error hidden"></div>
<div id="services-grid" class="services-grid">
    <section class="card">
        <p class="muted">Loading…</p>
    </section>
</div>

<script>
(function(){
    const grid=document.getElementById('services-grid');
    const summary=document.getElementById('services-summary');
    const errorBox=document.getElementById('services-error');
    const refresh=document.getElementById('services-refresh');

    let rendered=false;
    let refreshing=false;

    function escapeHtml(value){
        const node=document.createElement('div');
        node.textContent=String(value??'');
        return node.innerHTML;
    }

    function formatAge(seconds){
        if(seconds===null||seconds===undefined) return '';
        if(seconds<60) return seconds+' sec ago';
        if(seconds<3600) return Math.floor(seconds/60)+' min ago';
        return Math.floor(seconds/3600)+' h ago';
    }

    function render(data){
        const firewalls=Array.isArray(data.firewalls)?data.firewalls:[];
        const activeTotal=firewalls.reduce(
            (sum,item)=>sum+Number(item.active_count||0),
            0
        );
        const reachable=firewalls.filter(item=>item.ok===true).length;

        summary.textContent=
            activeTotal+' active services across '+
            reachable+' of '+firewalls.length+' firewalls'+
            (data.cache?.age!==null&&data.cache?.age!==undefined
                ? ' · updated '+formatAge(data.cache.age)
                : '');

        if(!firewalls.length){
            grid.innerHTML='<section class="card"><p class="muted">No firewalls configured.</p></section>';
            rendered=true;
            return;
        }

        grid.innerHTML=firewalls.map(function(firewall){
            const services=Array.isArray(firewall.active_services)
                ? firewall.active_services
                : [];

            const serviceMarkup=firewall.ok
                ? (
                    services.length
                        ? '<div class="service-list">'+services.map(function(service){
                            const label=service.description||service.name;
                            const technical=
                                service.description&&service.name&&service.description!==service.name
                                    ? '<small>'+escapeHtml(service.name)+'</small>'
                                    : '';
                            return '<div class="service-row">'+
                                '<span class="status-dot"></span>'+
                                '<div><strong>'+escapeHtml(label)+'</strong>'+
                                technical+'</div>'+
                                '<span class="badge good">Running</span>'+
                            '</div>';
                        }).join('')+'</div>'
                        : '<p class="muted">No active services reported.</p>'
                )
                : '<div class="alert error">'+escapeHtml(firewall.error||'Unavailable')+'</div>';

            return '<section class="card service-firewall-card">'+
                '<div class="card-head">'+
                    '<div><h2>'+escapeHtml(firewall.name)+'</h2>'+
                    '<a class="muted" target="_blank" rel="noopener" href="'+
                        escapeHtml(firewall.base_url)+'">'+
                        escapeHtml(firewall.base_url)+
                    '</a></div>'+
                    (firewall.ok
                        ? '<span class="badge good">'+
                            Number(firewall.active_count||0)+' active</span>'
                        : '<span class="badge bad">Unavailable</span>')+
                '</div>'+
                serviceMarkup+
            '</section>';
        }).join('');

        rendered=true;
    }

    async function requestData(force){
        const response=await fetch(
            '/services_data.php'+(force?'?force=1':''),
            {credentials:'same-origin',cache:'no-store'}
        );

        const raw=await response.text();
        let data;

        try{
            data=JSON.parse(raw);
        }catch(parseError){
            throw new Error(
                'Server returned invalid JSON: '+
                raw.replace(/\s+/g,' ').trim().slice(0,500)
            );
        }

        if(!response.ok||data.ok!==true){
            throw new Error(data.error||'Could not load services.');
        }

        return data;
    }

    async function refreshLive(manual){
        if(refreshing) return;
        refreshing=true;

        const original=refresh.textContent;
        refresh.disabled=true;
        refresh.textContent=manual?'Refreshing…':'Updating…';

        try{
            const data=await requestData(true);
            render(data);
            errorBox.classList.add('hidden');
            errorBox.textContent='';
        }catch(error){
            if(manual||!rendered){
                errorBox.textContent=error.message;
                errorBox.classList.remove('hidden');
            }
        }finally{
            refresh.disabled=false;
            refresh.textContent=original;
            refreshing=false;
        }
    }

    async function loadInitial(){
        refresh.disabled=true;

        try{
            const data=await requestData(false);
            render(data);

            if(data.cache?.refresh_recommended){
                window.setTimeout(()=>refreshLive(false),150);
            }
        }catch(error){
            summary.textContent='Services unavailable';
            grid.innerHTML='<section class="card"><p class="muted">Could not load services.</p></section>';
            errorBox.textContent=error.message;
            errorBox.classList.remove('hidden');
        }finally{
            refresh.disabled=false;
        }
    }

    refresh.addEventListener('click',()=>refreshLive(true));
    loadInitial();
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
