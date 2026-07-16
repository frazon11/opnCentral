<?php
require_once __DIR__.'/inc/config.php';require_once __DIR__.'/inc/opnsense.php';require_login();
$fs=db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll();require __DIR__.'/inc/header.php';?>
<div class="page-title"><div><h1>Firewalls</h1><p>Central status, backups and maintenance.</p></div><a class="button" href="/firewall_edit.php">Add firewall</a></div>
<?php if(!$fs):?><div class="empty">No firewalls configured.</div><?php else:?><div class="grid">
<?php foreach($fs as $f):$st=$fw=null;$err=null;try{$st=opn_request($f,'core/system/status');try{$fw=opn_request($f,'core/firmware/status');}catch(Throwable $x){}}catch(Throwable $x){$err=$x->getMessage();}?>
<article class="card"><div class="card-head"><div><h2><?=h($f['name'])?></h2><a class="muted" target="_blank" rel="noopener" href="<?=h($f['base_url'])?>"><?=h($f['base_url'])?></a></div><span class="badge <?=$err?'bad':'good'?>"><?=$err?'Offline':'Online'?></span></div>
<?php if($err):?><div class="alert error"><?=h($err)?></div><?php else:?><dl><dt>Status</dt><dd><?=h((string)($st['status']??$st['result']??'reachable'))?></dd><dt>Firmware</dt><dd><?=h((string)($fw['product_version']??$fw['status_msg']??'API reachable'))?></dd></dl><?php endif;?>
<div class="actions"><a class="button secondary" href="/firewall_view.php?id=<?=(int)$f['id']?>">Details</a><a class="button secondary" href="/firewall_edit.php?id=<?=(int)$f['id']?>">Edit</a></div></article>
<?php endforeach;?></div><?php endif;require __DIR__.'/inc/footer.php';?>
