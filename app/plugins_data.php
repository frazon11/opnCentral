<?php
declare(strict_types=1);
require_once __DIR__.'/inc/config.php';
require_once __DIR__.'/inc/opnsense.php';
require_login();
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function plugin_cache_path():string{return DATA_DIR.'/plugins-cache.json';}
function plugin_read_cache():?array{
 $p=plugin_cache_path();if(!is_file($p))return null;
 $raw=file_get_contents($p);$j=$raw===false?null:json_decode($raw,true);
 if(!is_array($j)||!isset($j['firewalls']))return null;
 $m=filemtime($p);$j['age']=$m===false?null:max(0,time()-$m);return $j;
}
function plugin_write_cache(array $data):void{
 if(!is_dir(DATA_DIR))@mkdir(DATA_DIR,0770,true);
 $tmp=plugin_cache_path().'.tmp-'.bin2hex(random_bytes(4));
 $payload=json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
 if(file_put_contents($tmp,$payload,LOCK_EX)!==false){@chmod($tmp,0660);@rename($tmp,plugin_cache_path());}else @unlink($tmp);
}
function plugin_find_packages(mixed $node,array &$out):void{
 if(!is_array($node))return;
 $name=(string)($node['name']??$node['pkg_name']??$node['package']??'');
 if(str_starts_with($name,'os-')){
  $out[$name]=[
   'name'=>$name,
   'version'=>(string)($node['version']??$node['installed_version']??''),
   'available_version'=>(string)($node['available_version']??$node['new_version']??$node['version']??''),
   'installed'=>(bool)($node['installed']??($node['status']??'')==='installed'||($node['current']??'')!==''),
   'locked'=>(bool)($node['locked']??false),
   'description'=>(string)($node['comment']??$node['description']??'')
  ];
 }
 foreach($node as $v)plugin_find_packages($v,$out);
}
function plugin_live(array $firewalls):array{
 $req=[];foreach($firewalls as $fw)$req[(string)$fw['id']]=['firewall'=>$fw,'path'=>'core/firmware/info','timeout'=>30];
 $res=opn_requests_parallel($req);$rows=[];
 foreach($firewalls as $fw){
  $r=$res[(string)$fw['id']]??['ok'=>false,'error'=>'No result'];$plugins=[];
  if(($r['ok']??false)===true)plugin_find_packages($r['value'],$plugins);
  ksort($plugins,SORT_NATURAL|SORT_FLAG_CASE);
  $rows[]=['id'=>(int)$fw['id'],'name'=>(string)$fw['name'],'base_url'=>(string)$fw['base_url'],'ok'=>($r['ok']??false)===true,'error'=>$r['error']??null,'plugins'=>array_values($plugins)];
 }
 $data=['created_at'=>gmdate('c'),'firewalls'=>$rows];plugin_write_cache($data);$data['age']=0;return $data;
}
try{
 $force=($_GET['force']??'')==='1';$cache=plugin_read_cache();$source='cache';
 if($force||$cache===null){$cache=plugin_live(db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll());$source='live';}
 echo json_encode(['ok'=>true,'firewalls'=>$cache['firewalls'],'cache'=>['source'=>$source,'age'=>$cache['age']??null,'refresh_recommended'=>$source==='cache'&&(($cache['age']??999)>=300)]],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}catch(Throwable $e){http_response_code(500);echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);}
