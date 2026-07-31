<?php
declare(strict_types=1);
require_once __DIR__.'/inc/config.php';
require_login();
require __DIR__.'/inc/header.php';
?>
<div class="page-title"><div><h1>Plugins</h1><p>Inventory and single-firewall plugin operations.</p></div><button class="button secondary" id="refresh">Refresh inventory</button></div>
<div class="alert warning"><strong>Safety:</strong> Install, reinstall and remove create a configuration backup first. Only packages beginning with <code>os-</code> can be managed. Bulk operations are not enabled yet.</div>
<div id="plugin-error" class="alert error hidden"></div>
<div id="plugin-summary" class="muted">Loading plugin inventory…</div>
<div id="plugin-grid" class="services-grid"><section class="card">Loading…</section></div>
<div class="card"><h2>Recent plugin jobs</h2><div id="jobs">Loading…</div></div>
<script>
(function(){
 const grid=document.getElementById('plugin-grid'),summary=document.getElementById('plugin-summary'),errorBox=document.getElementById('plugin-error'),refresh=document.getElementById('refresh'),jobs=document.getElementById('jobs');
 const csrf=<?=json_encode(csrf_token(),JSON_UNESCAPED_SLASHES)?>;
 let inventory=[];
 function e(v){const d=document.createElement('div');d.textContent=String(v??'');return d.innerHTML}
 function render(data){
  inventory=data.firewalls||[];let count=0;
  grid.innerHTML=inventory.map(fw=>{count+=(fw.plugins||[]).length;
   if(!fw.ok)return `<section class="card"><div class="card-head"><h2>${e(fw.name)}</h2><span class="badge bad">Unavailable</span></div><div class="alert error">${e(fw.error)}</div></section>`;
   const rows=(fw.plugins||[]).map(p=>`<tr><td><strong>${e(p.name)}</strong><br><small>${e(p.description)}</small></td><td>${e(p.installed?p.version:'—')}</td><td>${e(p.available_version||'—')}</td><td>${p.locked?'<span class="badge warning">Locked</span>':''}</td><td class="plugin-actions">${p.installed?`<button data-fw="${fw.id}" data-pkg="${e(p.name)}" data-op="reinstall">Reinstall</button><button class="danger" data-fw="${fw.id}" data-pkg="${e(p.name)}" data-op="remove">Remove</button><button data-fw="${fw.id}" data-pkg="${e(p.name)}" data-op="${p.locked?'unlock':'lock'}">${p.locked?'Unlock':'Lock'}</button>`:`<button data-fw="${fw.id}" data-pkg="${e(p.name)}" data-op="install">Install</button>`}</td></tr>`).join('');
   return `<section class="card plugin-card"><div class="card-head"><div><h2>${e(fw.name)}</h2><a class="muted" target="_blank" rel="noopener" href="${e(fw.base_url)}">${e(fw.base_url)}</a></div><span class="badge good">${(fw.plugins||[]).length} plugins</span></div><div class="table-scroll"><table><thead><tr><th>Plugin</th><th>Installed</th><th>Available</th><th></th><th>Actions</th></tr></thead><tbody>${rows||'<tr><td colspan="5">No plugin data returned.</td></tr>'}</tbody></table></div></section>`;
  }).join('');
  summary.textContent=`${count} plugin entries across ${inventory.length} firewalls`;
  grid.querySelectorAll('button[data-op]').forEach(b=>b.addEventListener('click',()=>act(b)));
 }
 async function load(force=false){refresh.disabled=true;try{const r=await fetch('/plugins_data.php'+(force?'?force=1':''),{credentials:'same-origin',cache:'no-store'});const d=await r.json();if(!r.ok||!d.ok)throw new Error(d.error||'Load failed');render(d);errorBox.classList.add('hidden');if(d.cache?.refresh_recommended)setTimeout(()=>load(true),200)}catch(x){errorBox.textContent=x.message;errorBox.classList.remove('hidden')}finally{refresh.disabled=false}}
 async function act(button){const op=button.dataset.op,pkg=button.dataset.pkg;if(!confirm(`${op.toUpperCase()} ${pkg}?${['install','reinstall','remove'].includes(op)?'\n\nA configuration backup will be created first.':''}`))return;
  button.disabled=true;const body=new URLSearchParams({csrf,firewall_id:button.dataset.fw,package:pkg,operation:op});
  try{const r=await fetch('/plugin_action.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body});const d=await r.json();if(!r.ok||!d.ok)throw new Error(d.error||'Action failed');alert(d.message);await load(true);await loadJobs()}catch(x){alert(x.message)}finally{button.disabled=false}}
 async function loadJobs(){try{const r=await fetch('/plugin_jobs_data.php',{credentials:'same-origin',cache:'no-store'});const d=await r.json();if(!r.ok||!d.ok)throw new Error(d.error||'Jobs failed');jobs.innerHTML=d.jobs.length?`<table><thead><tr><th>Time</th><th>Firewall</th><th>Operation</th><th>Plugin</th><th>Status</th><th>Backup</th></tr></thead><tbody>${d.jobs.map(j=>`<tr><td>${e(j.created_at)}</td><td>${e(j.firewall_name)}</td><td>${e(j.operation)}</td><td><code>${e(j.package_name)}</code></td><td>${e(j.status)}</td><td>${j.backup_id?`#${j.backup_id}`:'—'}</td></tr>`).join('')}</tbody></table>`:'No plugin jobs yet.'}catch(x){jobs.textContent=x.message}}
 refresh.addEventListener('click',()=>load(true));load();loadJobs();setInterval(loadJobs,10000);
})();
</script>
<?php require __DIR__.'/inc/footer.php'; ?>
