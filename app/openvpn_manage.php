<?php
declare(strict_types=1);
require_once __DIR__.'/inc/config.php';
require_login();
$firewalls=db()->query('SELECT id,name,base_url FROM firewalls ORDER BY name')->fetchAll();
$selectedId=(int)($_GET['firewall_id']??0);
if($selectedId<1&&$firewalls)$selectedId=(int)$firewalls[0]['id'];
require __DIR__.'/inc/header.php';
?>
<div class="page-title">
 <div><h1>Manage OpenVPN</h1><p>Instances and active sessions on one managed OPNsense.</p></div>
 <div class="plugin-toolbar">
  <select id="firewall-select"><?php foreach($firewalls as $fw):?><option value="<?=(int)$fw['id']?>" <?=$selectedId===(int)$fw['id']?'selected':''?>><?=h((string)$fw['name'])?></option><?php endforeach;?></select>
  <button class="button secondary" id="refresh">Refresh</button>
  <a class="button" href="/openvpn_roadwarrior_create.php">Create Roadwarrior Server</a>
 </div>
</div>
<div id="ovpn-error" class="alert error hidden"></div>
<div id="ovpn-summary" class="muted">Loading OpenVPN instances…</div>
<div class="card">
 <div class="table-scroll"><table>
 <thead><tr><th>Instance</th><th>Role</th><th>Listener / Remote</th><th>Tunnel</th><th>Status</th><th>Actions</th></tr></thead>
 <tbody id="ovpn-body"><tr><td colspan="6">Loading…</td></tr></tbody>
 </table></div>
</div>
<div class="card"><h2>Active sessions</h2><div id="session-summary" class="muted">Loading…</div><div id="session-table"></div></div>
<script>
(function(){
 const firewallId=()=>document.getElementById('firewall-select').value;
 const csrf=<?=json_encode(csrf_token(),JSON_UNESCAPED_SLASHES)?>;
 const body=document.getElementById('ovpn-body'),summary=document.getElementById('ovpn-summary'),errorBox=document.getElementById('ovpn-error'),refresh=document.getElementById('refresh'),sessionSummary=document.getElementById('session-summary'),sessionTable=document.getElementById('session-table');
 function e(v){const d=document.createElement('div');d.textContent=String(v??'');return d.innerHTML}
 async function readJson(r){const raw=await r.text();try{return JSON.parse(raw)}catch(_){throw new Error('Invalid JSON: '+raw.replace(/\s+/g,' ').slice(0,700))}}
 function actions(i){
  const toggle=i.enabled?`<button data-action="disable">Disable</button>`:`<button data-action="enable">Enable</button>`;
  return `<div class="plugin-row-actions" data-uuid="${e(i.uuid)}" data-vpnid="${e(i.vpnid)}">${toggle}<button data-action="start">Start</button><button data-action="stop">Stop</button><button data-action="restart">Restart</button><button class="danger" data-action="delete">Delete</button></div>`;
 }
 function render(data){
  const list=data.instances||[];
  summary.textContent=`${list.length} OpenVPN instance${list.length===1?'':'s'} on ${data.firewall.name}`;
  body.innerHTML=list.length?list.map(i=>`<tr><td><strong>${e(i.description||'Unnamed')}</strong><br><small>ID ${e(i.vpnid)} · ${e(i.uuid)}</small></td><td>${e(i.role||'—')}<br><small>${e(i.dev_type||'')}</small></td><td>${e((i.local||'*')+(i.port?':'+i.port:'')+' '+(i.proto||''))}${i.remote?'<br><small>'+e(i.remote)+'</small>':''}</td><td>${e(i.server||'—')}</td><td><span class="badge ${i.enabled?'good':'neutral'}">${i.enabled?'Enabled':'Disabled'}</span></td><td>${actions(i)}</td></tr>`).join(''):'<tr><td colspan="6">No OpenVPN instances found.</td></tr>';
  body.querySelectorAll('[data-action]').forEach(b=>b.addEventListener('click',()=>runAction(b)));
  const sessions=Array.isArray(data.sessions)?data.sessions:[];
  sessionSummary.textContent=data.sessions_error?data.sessions_error:`${sessions.length} active session${sessions.length===1?'':'s'}`;
  sessionTable.innerHTML=sessions.length?`<div class="table-scroll"><table><thead><tr><th>User / Common Name</th><th>Virtual address</th><th>Remote address</th><th>Connected</th></tr></thead><tbody>${sessions.map(s=>`<tr><td>${e(s.common_name||s.username||s.user_name||'—')}</td><td>${e(s.virtual_address||s.virtual_addr||s.vpn_ip||'—')}</td><td>${e(s.real_address||s.remote_address||s.remote_host||'—')}</td><td>${e(s.connected_since||s.connect_time||s.since||'—')}</td></tr>`).join('')}</tbody></table></div>`:'';
 }
 async function load(){
  refresh.disabled=true;errorBox.classList.add('hidden');
  try{
   const r=await fetch('/openvpn_manage_data.php?firewall_id='+encodeURIComponent(firewallId()),{credentials:'same-origin',cache:'no-store'});
   const d=await readJson(r);if(!r.ok||!d.ok)throw new Error(d.error||'Load failed');render(d);
  }catch(x){errorBox.textContent=x.message;errorBox.classList.remove('hidden');body.innerHTML='<tr><td colspan="6">Could not load OpenVPN instances.</td></tr>'}
  finally{refresh.disabled=false}
 }
 async function runAction(button){
  const row=button.closest('[data-uuid]'),action=button.dataset.action,uuid=row.dataset.uuid,vpnid=row.dataset.vpnid;
  const destructive=['delete','disable'].includes(action);
  if(!confirm(`${action.toUpperCase()} OpenVPN instance ${vpnid}?${destructive?'\n\nA configuration backup will be created first.':''}`))return;
  button.disabled=true;
  try{
   const form=new URLSearchParams({csrf,firewall_id:firewallId(),uuid,vpnid,action});
   const r=await fetch('/openvpn_manage_action.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:form});
   const d=await readJson(r);if(!r.ok||!d.ok)throw new Error(d.error||'Action failed');await load();
  }catch(x){alert(x.message)}finally{button.disabled=false}
 }
 document.getElementById('firewall-select').addEventListener('change',load);
 refresh.addEventListener('click',load);load();
})();
</script>
<?php require __DIR__.'/inc/footer.php'; ?>
