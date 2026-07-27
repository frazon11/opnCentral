<?php
declare(strict_types=1);

function supported_languages(): array { return ['en'=>'English','de'=>'Deutsch','fr'=>'Français','nl'=>'Nederlands']; }
function current_language(): string {
    start_session_secure();
    $supported=supported_languages();
    $requested=(string)($_GET['lang'] ?? '');
    if ($requested!=='' && isset($supported[$requested])) {
        $_SESSION['lang']=$requested;
        setcookie('opncentral_lang',$requested,['expires'=>time()+31536000,'path'=>'/','httponly'=>true,'secure'=>filter_var(envv('SESSION_SECURE','false'),FILTER_VALIDATE_BOOL),'samesite'=>'Lax']);
    }
    $lang=(string)($_SESSION['lang'] ?? $_COOKIE['opncentral_lang'] ?? envv('DEFAULT_LANGUAGE','en'));
    return isset($supported[$lang]) ? $lang : 'en';
}
function translations(string $lang): array { static $cache=[]; if(isset($cache[$lang]))return $cache[$lang]; $file=__DIR__.'/../lang/'.$lang.'.php'; $cache[$lang]=is_file($file)?require $file:[]; return $cache[$lang]; }
function t(string $key,array $vars=[]): string { $lang=current_language(); $value=translations($lang)[$key] ?? translations('en')[$key] ?? $key; foreach($vars as $name=>$replacement){$value=str_replace('{'.$name.'}',(string)$replacement,$value);} return $value; }
