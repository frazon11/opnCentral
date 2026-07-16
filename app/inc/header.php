<?php require_once __DIR__.'/config.php';start_session_secure();?><!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h(app_name())?></title><link rel="stylesheet" href="/assets/style.css"></head><body>
<header><div class="brand"><?=h(app_name())?></div><?php if(logged_in()):?><nav><a href="/">Dashboard</a><a href="/firewall_edit.php">Add firewall</a><a href="/logout.php">Logout</a></nav><?php endif;?></header><main>
