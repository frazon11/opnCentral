<?php
declare(strict_types=1);
const DATA_DIR = '/var/www/data';
const BACKUP_DIR = '/var/www/backups';
function envv(string $n, ?string $d=null): string { $v=getenv($n); return ($v===false||$v==='')?(string)$d:$v; }
function app_name(): string { return envv('APP_NAME','OPNsense Central Lite'); }
function db(): PDO {
 static $p=null; if($p instanceof PDO)return $p;
 if(!is_dir(DATA_DIR))mkdir(DATA_DIR,0770,true);
 $p=new PDO('sqlite:'.DATA_DIR.'/central.sqlite');
 $p->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 $p->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
 $p->exec('PRAGMA journal_mode=WAL');
 $p->exec('CREATE TABLE IF NOT EXISTS firewalls (
 id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,base_url TEXT NOT NULL,
 api_key_enc TEXT NOT NULL,api_secret_enc TEXT NOT NULL,verify_tls INTEGER NOT NULL DEFAULT 1,
 notes TEXT NOT NULL DEFAULT "",created_at TEXT NOT NULL,updated_at TEXT NOT NULL)');
 return $p;
}
function crypto_key(): string {
 $h=envv('APP_KEY'); if(!preg_match('/^[a-f0-9]{64}$/i',$h))throw new RuntimeException('APP_KEY must be 64 hex characters.');
 $k=hex2bin($h); if($k===false)throw new RuntimeException('Invalid APP_KEY.'); return $k;
}
function encrypt_value(string $v): string {
 $iv=random_bytes(12);$tag='';$c=openssl_encrypt($v,'aes-256-gcm',crypto_key(),OPENSSL_RAW_DATA,$iv,$tag);
 if($c===false)throw new RuntimeException('Encryption failed.'); return base64_encode($iv.$tag.$c);
}
function decrypt_value(string $v): string {
 $r=base64_decode($v,true); if($r===false||strlen($r)<29)throw new RuntimeException('Invalid encrypted value.');
 $p=openssl_decrypt(substr($r,28),'aes-256-gcm',crypto_key(),OPENSSL_RAW_DATA,substr($r,0,12),substr($r,12,16));
 if($p===false)throw new RuntimeException('Decryption failed; APP_KEY may have changed.'); return $p;
}
function start_session_secure(): void {
 if(session_status()===PHP_SESSION_ACTIVE)return;
 session_name('opncentral');
 session_set_cookie_params(['httponly'=>true,'secure'=>filter_var(envv('SESSION_SECURE','false'),FILTER_VALIDATE_BOOL),'samesite'=>'Strict','path'=>'/']);
 session_start();
}
function csrf_token(): string { start_session_secure(); if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function require_csrf(): void { start_session_secure();$v=(string)($_POST['csrf']??'');if(!hash_equals((string)($_SESSION['csrf']??''),$v)){http_response_code(400);exit('Invalid CSRF token');}}
function logged_in(): bool { start_session_secure(); return ($_SESSION['auth']??false)===true; }
function require_login(): void { if(!logged_in()){header('Location: /login.php');exit;}}
function h(string $v): string { return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function normalize_url(string $u): string {$u=rtrim(trim($u),'/');if(!preg_match('#^https?://#i',$u))$u='https://'.$u;if(filter_var($u,FILTER_VALIDATE_URL)===false)throw new InvalidArgumentException('Invalid URL.');return $u;}
