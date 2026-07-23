<?php
require_once __DIR__ . '/inc/config.php';
require_login();

$firewalls = db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll();
require __DIR__ . '/inc/header.php';
?>

<style>
.view-toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.view-switch{display:inline-flex;gap:4px;padding:4px;border:1px solid rgba(127,127,127,.25);border-radius:10px;background:rgba(127,127,127,.08)}.view-switch button{border:0;border-radius:7px;padding:8px 12px;cursor:pointer;background:transparent;color:inherit}.view-switch button.active{background:rgba(127,127,127,.2);font-weight:700}.firewall-list{display:grid;gap:16px}.view-cards .firewall-list{grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}.view-compact .firewall-list{display:table;width:100%;border-collapse:collapse}.view-compact .firewall-card{display:table-row}.view-compact .compact-cell{display:table-cell;padding:12px 10px;vertical-align:middle;border-bottom:1px solid rgba(127,127,127,.18)}.view-compact .card{border:0;border-radius:0;box-shadow:none;background:transparent;padding:0}.view-compact .card-head,.view-compact dl,.view-compact .actions{display:contents}.view-compact .compact-hide{display:none}.view-details .firewall-list{grid-template-columns:1fr}.view-details .details-extra{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:16px}.view-details .details-box{padding:12px;border-radius:8px;background:rgba(127,127,127,.08)}.view-details .details-box strong{display:block;margin-bottom:4px}.view-cards .details-extra,.view-compact .details-extra{display:none}.compact-label{display:none}.status-loading{opacity:.65}.status-loading::before{content:"● ";animation:dashboardPulse 1s infinite}.status-good{color:#35a853}.status-bad{color:#d74747}@keyframes dashboardPulse{0%,100%{opacity:.25}50%{opacity:1}}@media(max-width:760px){.view-compact .firewall-list,.view-compact .firewall-card,.view-compact .compact-cell{display:block;width:100%}.view-compact .firewall-card{padding:12px 0;border-bottom:1px solid rgba(127,127,127,.18)}.view-compact .compact-label{display:inline-block;min-width:90px;font-weight:700}}
</style>

<div class="page-title">
  <div><h1>Firewalls</h1><p>The page opens immediately; live status loads in the background.</p></div>
  <div class="view-toolbar">
    <div class="view-switch" aria-label="Dashboard view">
      <button type="button" data-view="cards">Cards</button>
      <button type="button" data-view="compact">Compact</button>
      <button type="button" data-view="details">Details</button>
    </div>
    <button type="button" class="button secondary" id="refresh-all">Refresh status</button>
    <a class="button" href="/firewall_edit.php">Add firewall</a>
  </div>
</div>

<div id="firewall-dashboard" class="view-cards">
<?php if (!$firewalls): ?>
  <div class="empty">No firewalls configured.</div>
<?php else: ?>
  <div class="firewall-list">
  <?php foreach ($firewalls as $firewall): ?>
    <article class="card firewall-card" data-firewall-id="<?= (int)$firewall['id'] ?>">
      <div class="card-head compact-cell">
        <div>
          <h2><?= h((string)$firewall['name']) ?></h2>
          <a class="muted compact-hide" target="_blank" rel="noopener" href="<?= h((string)$firewall['base_url']) ?>"><?= h((string)$firewall['base_url']) ?></a>
        </div>
      </div>

      <div class="compact-cell">
        <span class="compact-label">Status:</span>
        <span class="badge status-badge status-loading">Loading</span>
      </div>

      <dl>
        <div class="compact-cell"><dt class="compact-label">System:</dt><dd class="system-value status-loading">Loading live status…</dd></div>
        <div class="compact-cell"><dt class="compact-label">Firmware:</dt><dd class="firmware-value status-loading">Loading firmware…</dd></div>
      </dl>

      <div class="details-extra">
        <div class="details-box"><strong>WebGUI</strong><a target="_blank" rel="noopener" href="<?= h((string)$firewall['base_url']) ?>"><?= h((string)$firewall['base_url']) ?></a></div>
        <div class="details-box"><strong>API status</strong><span class="api-value status-loading">Loading…</span></div>
        <div class="details-box"><strong>Firmware information</strong><span class="firmware-detail status-loading">Loading…</span></div>
      </div>

      <div class="actions compact-cell">
        <button type="button" class="button secondary refresh-one">Refresh</button>
        <a class="button secondary" href="/firewall_view.php?id=<?= (int)$firewall['id'] ?>">Details</a>
        <a class="button secondary" href="/firewall_edit.php?id=<?= (int)$firewall['id'] ?>">Edit</a>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
</div>

<script>
(function(){
 const viewKey='opncentral-dashboard-view';
 const dashboard=document.getElementById('firewall-dashboard');
 const viewButtons=document.querySelectorAll('[data-view]');
 const cards=[...document.querySelectorAll('.firewall-card[data-firewall-id]')];

 function applyView(view){
   const allowed=['cards','compact','details'];
   if(!allowed.includes(view))view='cards';
   dashboard.classList.remove('view-cards','view-compact','view-details');
   dashboard.classList.add('view-'+view);
   viewButtons.forEach(button=>button.classList.toggle('active',button.dataset.view===view));
   localStorage.setItem(viewKey,view);
 }

 function setLoading(card){
   card.querySelector('.status-badge').className='badge status-badge status-loading';
   card.querySelector('.status-badge').textContent='Loading';
   ['.system-value','.firmware-value','.api-value','.firmware-detail'].forEach(selector=>{
     const element=card.querySelector(selector);
     element.classList.add('status-loading');
     element.classList.remove('status-good','status-bad');
     element.textContent='Loading…';
   });
 }

 async function fetchType(id,type){
   const response=await fetch('/firewall_status.php?id='+encodeURIComponent(id)+'&type='+encodeURIComponent(type),{credentials:'same-origin',cache:'no-store'});
   const result=await response.json();
   if(!response.ok||result.ok!==true)throw new Error(result.error||('HTTP '+response.status));
   return result.data[type];
 }

 function textFrom(value,keys,fallback){
   if(!value||typeof value!=='object')return fallback;
   for(const key of keys){if(value[key]!==undefined&&value[key]!==null&&String(value[key])!=='')return String(value[key]);}
   return fallback;
 }

 async function loadCard(card){
   const id=card.dataset.firewallId;
   setLoading(card);

   const systemPromise=fetchType(id,'system');
   const firmwarePromise=fetchType(id,'firmware');
   const [systemResult,firmwareResult]=await Promise.allSettled([systemPromise,firmwarePromise]);

   const badge=card.querySelector('.status-badge');
   const systemEl=card.querySelector('.system-value');
   const firmwareEl=card.querySelector('.firmware-value');
   const apiEl=card.querySelector('.api-value');
   const detailEl=card.querySelector('.firmware-detail');
   [systemEl,firmwareEl,apiEl,detailEl].forEach(el=>el.classList.remove('status-loading','status-good','status-bad'));

   if(systemResult.status==='fulfilled'&&systemResult.value&&systemResult.value.ok===true){
     const value=systemResult.value.value||{};
     badge.className='badge status-badge good';
     badge.textContent='Online';
     systemEl.textContent=textFrom(value,['status','result','message'],'Reachable');
     apiEl.textContent='API reachable';
     systemEl.classList.add('status-good');
     apiEl.classList.add('status-good');
   }else{
     const error=systemResult.status==='rejected'?systemResult.reason.message:(systemResult.value?.error||'Unavailable');
     badge.className='badge status-badge bad';
     badge.textContent='Offline';
     systemEl.textContent=error;
     apiEl.textContent=error;
     systemEl.classList.add('status-bad');
     apiEl.classList.add('status-bad');
   }

   if(firmwareResult.status==='fulfilled'&&firmwareResult.value&&firmwareResult.value.ok===true){
     const value=firmwareResult.value.value||{};
     firmwareEl.textContent=textFrom(value,['product_version','version','status_msg'],'API reachable');
     detailEl.textContent=textFrom(value,['status_msg','message','product_name'],'No additional information');
     firmwareEl.classList.add('status-good');
   }else{
     const error=firmwareResult.status==='rejected'?firmwareResult.reason.message:(firmwareResult.value?.error||'Unavailable');
     firmwareEl.textContent=error;
     detailEl.textContent=error;
     firmwareEl.classList.add('status-bad');
     detailEl.classList.add('status-bad');
   }
 }

 async function loadAll(){
   cards.forEach((card,index)=>setTimeout(()=>loadCard(card),index*150));
 }

 viewButtons.forEach(button=>button.addEventListener('click',()=>applyView(button.dataset.view)));
 document.getElementById('refresh-all')?.addEventListener('click',loadAll);
 cards.forEach(card=>card.querySelector('.refresh-one')?.addEventListener('click',()=>loadCard(card)));
 applyView(localStorage.getItem(viewKey)||'cards');
 loadAll();
})();
</script>
<?php require __DIR__ . '/inc/footer.php'; ?>
