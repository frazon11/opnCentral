<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';
function firewall_by_id(int $id): array {$s=db()->prepare('SELECT * FROM firewalls WHERE id=?');$s->execute([$id]);$f=$s->fetch();if(!$f)throw new RuntimeException('Firewall not found.');return $f;}
function opn_request(array $f,string $path,string $method='GET',?array $payload=null,int $timeout=20): array {
 $ch=curl_init(rtrim($f['base_url'],'/').'/api/'.ltrim($path,'/'));
 $o=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_USERPWD=>decrypt_value($f['api_key_enc']).':'.decrypt_value($f['api_secret_enc']),
 CURLOPT_HTTPAUTH=>CURLAUTH_BASIC,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>$timeout,CURLOPT_FOLLOWLOCATION=>false,
 CURLOPT_SSL_VERIFYPEER=>(bool)$f['verify_tls'],CURLOPT_SSL_VERIFYHOST=>(bool)$f['verify_tls']?2:0,CURLOPT_HTTPHEADER=>['Accept: application/json']];
 if($method==='POST'){$o[CURLOPT_POST]=true;$o[CURLOPT_POSTFIELDS]=json_encode($payload??new stdClass(),JSON_THROW_ON_ERROR);$o[CURLOPT_HTTPHEADER][]='Content-Type: application/json';}
 curl_setopt_array($ch,$o);$b=curl_exec($ch);$e=curl_error($ch);$s=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
 if($b===false)throw new RuntimeException('Connection failed: '.$e);if($s<200||$s>=300)throw new RuntimeException("OPNsense API HTTP $s: ".substr($b,0,300));
 $j=json_decode($b,true);return is_array($j)?$j:['raw'=>$b];
}
function opn_download(array $f,string $path): string {
 $ch=curl_init(rtrim($f['base_url'],'/').'/api/'.ltrim($path,'/'));
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_USERPWD=>decrypt_value($f['api_key_enc']).':'.decrypt_value($f['api_secret_enc']),
 CURLOPT_HTTPAUTH=>CURLAUTH_BASIC,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>60,CURLOPT_SSL_VERIFYPEER=>(bool)$f['verify_tls'],CURLOPT_SSL_VERIFYHOST=>(bool)$f['verify_tls']?2:0]);
 $b=curl_exec($ch);$e=curl_error($ch);$s=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
 if($b===false)throw new RuntimeException('Backup failed: '.$e);if($s<200||$s>=300)throw new RuntimeException("Backup API HTTP $s");return $b;
}
