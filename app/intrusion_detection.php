<?php

declare(strict_types=1);
require_once __DIR__ . '/inc/config.php';
require_login();
require __DIR__ . '/inc/header.php';

$allowed = [
    'administration'=>'Administration',
    'download'=>'Download',
    'policies'=>'Policies',
    'rules'=>'Rules',
    'user-defined'=>'User defined',
    'alerts'=>'Alerts',
    'schedule'=>'Schedule',
    'log-file'=>'Log File',
];
$view = (string) ($_GET['view'] ?? 'administration');
if (!isset($allowed[$view])) $view = 'administration';
$title = $allowed[$view];
?>

<div class="page-title management-page-title">
    <div>
        <h1>Intrusion Detection · <?= h($title) ?></h1>
        <p>Read-only IDS/IPS information across all managed OPNsense firewalls.</p>
    </div>
    <div class="management-toolbar">
        <input id="ids-search" type="search" placeholder="Filter table…" style="width:240px;margin:0">
        <button type="button" class="button secondary" id="ids-refresh">Refresh</button>
    </div>
</div>

<div id="ids-error" class="alert error hidden"></div>
<div id="ids-summary" class="management-overview-bar"><strong>Loading <?= h($title) ?>…</strong></div>
<div id="ids-content" class="card management-card"><p class="muted" style="padding:16px">Loading…</p></div>

<script>
(function(){
    const view=<?= json_encode($view) ?>;
    const content=document.getElementById('ids-content');
    const summary=document.getElementById('ids-summary');
    const errorBox=document.getElementById('ids-error');
    const refresh=document.getElementById('ids-refresh');
    const search=document.getElementById('ids-search');
    let lastData=null;

    function esc(value){
        const node=document.createElement('div');
        node.textContent=String(value??'');
        return node.innerHTML;
    }

    function display(value){
        if(value===null||value===undefined||value==='') return '—';
        if(typeof value==='boolean') return value?'Yes':'No';
        if(typeof value==='object') return JSON.stringify(value);
        return String(value);
    }

    function flatten(row,prefix='',target={}){
        if(!row||typeof row!=='object'){
            target[prefix||'Value']=row;
            return target;
        }
        Object.entries(row).forEach(function(entry){
            const key=prefix?prefix+' · '+entry[0]:entry[0];
            const value=entry[1];
            if(value&&typeof value==='object'&&!Array.isArray(value)) flatten(value,key,target);
            else target[key]=value;
        });
        return target;
    }

    function render(data){
        lastData=data;
        const filter=search.value.trim().toLowerCase();
        const firewalls=Array.isArray(data.firewalls)?data.firewalls:[];
        const reachable=firewalls.filter(item=>item.ok).length;
        summary.innerHTML='<div><strong>'+esc(data.title)+'</strong><div class="management-summary">'+reachable+' of '+firewalls.length+' firewalls returned IDS data</div></div>';

        const flattened=[];
        firewalls.forEach(function(firewall){
            if(!firewall.ok){
                flattened.push({Firewall:firewall.name,Status:'Unavailable',Error:firewall.error||'Unavailable'});
                return;
            }
            const rows=Array.isArray(firewall.rows)?firewall.rows:[];
            if(!rows.length){
                flattened.push({Firewall:firewall.name,Status:'No rows returned',Endpoint:firewall.endpoint||'—'});
                return;
            }
            rows.forEach(function(row){
                flattened.push(Object.assign({Firewall:firewall.name},flatten(row)));
            });
        });

        const visible=flattened.filter(row=>!filter||JSON.stringify(row).toLowerCase().includes(filter));
        const columns=[];
        visible.forEach(row=>Object.keys(row).forEach(key=>{if(!columns.includes(key)) columns.push(key);}));
        const preferred=['Firewall','Status','Error','Endpoint'];
        columns.sort((a,b)=>{
            const ai=preferred.indexOf(a),bi=preferred.indexOf(b);
            if(ai>=0||bi>=0) return (ai<0?999:ai)-(bi<0?999:bi);
            return a.localeCompare(b);
        });

        if(!visible.length){
            content.innerHTML='<p class="muted" style="padding:16px">No matching rows.</p>';
            return;
        }

        content.innerHTML='<div class="table-wrap"><table class="management-table"><thead><tr>'+columns.map(c=>'<th>'+esc(c)+'</th>').join('')+'</tr></thead><tbody>'+visible.map(row=>'<tr>'+columns.map(c=>'<td>'+esc(display(row[c]))+'</td>').join('')+'</tr>').join('')+'</tbody></table></div>';
    }

    async function load(){
        refresh.disabled=true;
        refresh.textContent='Refreshing…';
        try{
            const response=await fetch('/intrusion_detection_data.php?view='+encodeURIComponent(view),{credentials:'same-origin',cache:'no-store'});
            const raw=await response.text();
            let data;
            try{data=JSON.parse(raw);}catch(e){throw new Error('Invalid server response: '+raw.replace(/\s+/g,' ').slice(0,400));}
            if(!response.ok||data.ok!==true) throw new Error(data.error||'Could not load IDS data.');
            errorBox.classList.add('hidden');
            errorBox.textContent='';
            render(data);
        }catch(error){
            errorBox.textContent=error.message;
            errorBox.classList.remove('hidden');
            content.innerHTML='<p class="muted" style="padding:16px">Could not load Intrusion Detection data.</p>';
        }finally{
            refresh.disabled=false;
            refresh.textContent='Refresh';
        }
    }

    refresh.addEventListener('click',load);
    search.addEventListener('input',()=>{if(lastData) render(lastData);});
    load();
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
