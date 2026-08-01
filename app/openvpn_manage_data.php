<?php
declare(strict_types=1);
ini_set('display_errors','0');ini_set('html_errors','0');ob_start();
require_once __DIR__.'/inc/config.php';
require_once __DIR__.'/inc/opnsense.php';
require_login();
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function ovpn_rows(array $value): array {
 if(isset($value['rows'])&&is_array($value['rows']))return $value['rows'];
 foreach($value as $candidate)if(is_array($candidate)&&array_is_list($candidate))return $candidate;
 return [];
}
function ovpn_bool(mixed $value): bool {
 if(is_bool($value))return $value;
 return in_array(strtolower(trim((string)$value)),['1','true','yes','on','enabled','running'],true);
}
try{
 $firewallId=(int)($_GET['firewall_id']??0);
 if($firewallId<1)throw new RuntimeException('Select a firewall.');
 $fw=firewall_by_id($firewallId);
 $requests=[
  'instances'=>['firewall'=>$fw,'path'=>'openvpn/instances/search','method'=>'POST','payload'=>['current'=>1,'rowCount'=>-1,'sort'=>new stdClass(),'searchPhrase'=>''],'timeout'=>20],
  'sessions'=>['firewall'=>$fw,'path'=>'openvpn/service/search_sessions','timeout'=>20],
 ];
 $responses=opn_requests_parallel($requests);
 if(($responses['instances']['ok']??false)!==true)throw new RuntimeException((string)($responses['instances']['error']??'Could not load OpenVPN instances.'));
 $instances=[];
 foreach(ovpn_rows((array)$responses['instances']['value']) as $row){
  if(!is_array($row))continue;
  $uuid=trim((string)($row['uuid']??$row['id']??''));
  if($uuid==='')continue;
  $instances[]=[
   'uuid'=>$uuid,
   'vpnid'=>(string)($row['vpnid']??''),
   'description'=>(string)($row['description']??$row['descr']??''),
   'role'=>(string)($row['role']??''),
   'enabled'=>ovpn_bool($row['enabled']??false),
   'proto'=>(string)($row['proto']??''),
   'port'=>(string)($row['port']??''),
   'local'=>(string)($row['local']??''),
   'server'=>(string)($row['server']??''),
   'remote'=>(string)($row['remote']??''),
   'dev_type'=>(string)($row['dev_type']??''),
  ];
 }
 usort($instances,static fn(array $a,array $b):int=>strnatcasecmp(($a['description']?:$a['vpnid']),($b['description']?:$b['vpnid'])));
 $sessions=($responses['sessions']['ok']??false)===true?ovpn_rows((array)$responses['sessions']['value']):[];
 while(ob_get_level()>0)ob_end_clean();
 echo json_encode(['ok'=>true,'firewall'=>['id'=>(int)$fw['id'],'name'=>(string)$fw['name'],'base_url'=>(string)$fw['base_url']],'instances'=>$instances,'sessions'=>$sessions,'sessions_error'=>$responses['sessions']['error']??null],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}catch(Throwable $e){
 http_response_code(500);while(ob_get_level()>0)ob_end_clean();
 echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
}
