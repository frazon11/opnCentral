<?php
require_once __DIR__ . '/inc/config.php';
require_login();
require __DIR__ . '/inc/header.php';
?>
<div class="page-title">
    <div>
        <h1>Managed WireGuard</h1>
        <p>Paired site-to-site connections between OPNsense firewalls managed by opnCentral.</p>
    </div>
    <div class="actions">
        <a class="button" href="/wireguard_create.php">Create site-to-site tunnel</a>
        <button type="button" id="refresh-links" class="button secondary">Refresh</button>
    </div>
</div>

<div id="wg-overview-error" class="alert error hidden"></div>

<section class="card">
    <div id="wg-overview-summary" class="vpn-summary">Loading managed connections…</div>
    <div class="table-wrap">
        <table class="opn-table">
            <thead>
                <tr>
                    <th>Connection</th>
                    <th>Side A</th>
                    <th>Side B</th>
                    <th>Status</th>
                    <th>Control</th>
                </tr>
            </thead>
            <tbody id="wg-overview-body">
                <tr><td colspan="5">Loading…</td></tr>
            </tbody>
        </table>
    </div>
</section>

<script>
(function(){
    const csrfToken=<?= json_encode(csrf_token(), JSON_UNESCAPED_SLASHES) ?>;
    const body=document.getElementById('wg-overview-body');
    const summary=document.getElementById('wg-overview-summary');
    const errorBox=document.getElementById('wg-overview-error');
    const refresh=document.getElementById('refresh-links');

    function escapeHtml(value){
        const div=document.createElement('div');
        div.textContent=String(value??'');
        return div.innerHTML;
    }

    function statusBadge(status){
        if(status==='enabled') return '<span class="badge good">Enabled on both sides</span>';
        if(status==='disabled') return '<span class="badge neutral">Disabled on both sides</span>';
        return '<span class="badge bad">Partial state</span>';
    }

    async function changeState(connection, enable, button){
        const verb=enable?'enable':'disable';
        if(!confirm(
            'Really '+verb+' only this WireGuard connection on both managed firewalls?\\n\\n'+
            connection.local.firewall_name+': '+(connection.local.client_name||'peer')+'\\n'+
            connection.remote.firewall_name+': '+(connection.remote.client_name||'peer')+
            '\\n\\nAutomatic backups will be created before the change.'
        )) return;

        const original=button.textContent;
        button.disabled=true;
        button.textContent=enable?'Enabling…':'Disabling…';

        try{
            const params=new URLSearchParams();
            params.set('csrf',csrfToken);
            params.set('local_firewall_id',String(connection.local.firewall_id));
            params.set('remote_firewall_id',String(connection.remote.firewall_id));
            params.set('local_client_uuid',connection.local.client_uuid);
            params.set('remote_client_uuid',connection.remote.client_uuid);
            params.set('local_expected_peer_key',connection.local.expected_peer_key);
            params.set('remote_expected_peer_key',connection.remote.expected_peer_key);
            params.set('enable',enable?'1':'0');

            const response=await fetch('/wireguard_link_action.php',{
                method:'POST',
                credentials:'same-origin',
                cache:'no-store',
                headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
                body:params
            });
            const result=await response.json();
            if(!response.ok||result.ok!==true) throw new Error(result.error||'Action failed.');
            await load();
        }catch(error){
            alert(error.message);
            button.disabled=false;
            button.textContent=original;
        }
    }

    function render(data){
        const connections=Array.isArray(data.connections)?data.connections:[];
        const enabled=connections.filter(c=>c.status==='enabled').length;
        const disabled=connections.filter(c=>c.status==='disabled').length;
        const partial=connections.filter(c=>c.status==='partial').length;

        summary.innerHTML=
            '<span class="badge good">'+enabled+' enabled</span> '+
            '<span class="badge neutral">'+disabled+' disabled</span> '+
            '<span class="badge bad">'+partial+' partial</span>';

        body.textContent='';

        if(!connections.length){
            const row=document.createElement('tr');
            row.innerHTML='<td colspan="5">No reciprocally matched managed WireGuard connections found.</td>';
            body.appendChild(row);
            return;
        }

        connections.forEach(function(connection){
            const row=document.createElement('tr');
            const enable=connection.status!=='enabled';
            row.innerHTML=
                '<td><strong>'+escapeHtml(connection.local.firewall_name)+' ↔ '+escapeHtml(connection.remote.firewall_name)+'</strong></td>'+
                '<td>'+escapeHtml(connection.local.client_name||'peer')+'<br><span class="muted">'+(connection.local.enabled?'Enabled':'Disabled')+'</span></td>'+
                '<td>'+escapeHtml(connection.remote.client_name||'peer')+'<br><span class="muted">'+(connection.remote.enabled?'Enabled':'Disabled')+'</span></td>'+
                '<td>'+statusBadge(connection.status)+'</td>'+
                '<td></td>';

            const button=document.createElement('button');
            button.type='button';
            button.className=enable?'button secondary':'button warning';
            button.textContent=enable?'Enable both sides':'Disable both sides';
            button.addEventListener('click',()=>changeState(connection,enable,button));
            row.lastElementChild.appendChild(button);
            body.appendChild(row);
        });
    }

    async function load(){
        refresh.disabled=true;
        errorBox.classList.add('hidden');
        summary.textContent='Loading managed connections…';
        body.innerHTML='<tr><td colspan="5">Loading…</td></tr>';

        try{
            const response=await fetch('/wireguard_overview_data.php',{
                credentials:'same-origin',
                cache:'no-store'
            });
            const data=await response.json();
            if(!response.ok||data.ok!==true) throw new Error(data.error||'Loading failed.');
            render(data);
            if(Array.isArray(data.errors)&&data.errors.length){
                errorBox.textContent=data.errors.join(' | ');
                errorBox.classList.remove('hidden');
            }
        }catch(error){
            summary.textContent='Unavailable';
            body.innerHTML='<tr><td colspan="5">Could not load managed WireGuard connections.</td></tr>';
            errorBox.textContent=error.message;
            errorBox.classList.remove('hidden');
        }finally{
            refresh.disabled=false;
        }
    }

    refresh.addEventListener('click',load);
    load();
})();
</script>
<?php require __DIR__ . '/inc/footer.php'; ?>
