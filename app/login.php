<?php
require_once __DIR__.'/inc/config.php';start_session_secure();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){require_csrf();if(hash_equals(envv('ADMIN_USER','admin'),(string)($_POST['username']??''))&&hash_equals(envv('ADMIN_PASSWORD'),(string)($_POST['password']??''))){session_regenerate_id(true);$_SESSION['auth']=true;header('Location: /');exit;}usleep(350000);$error='Invalid username or password.';}
require __DIR__.'/inc/header.php';?>
<section class="login-card"><h1>Sign in</h1><?php if($error):?><div class="alert error"><?=h($error)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>"><label>Username<input name="username" required></label><label>Password<input type="password" name="password" required></label><button>Sign in</button></form></section>
<?php require __DIR__.'/inc/footer.php';?>
