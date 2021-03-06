<?php
	session_start();
	if (is_file ($_SERVER['DOCUMENT_ROOT'].'/settings/settings.php')) require $_SERVER['DOCUMENT_ROOT'].'/settings/settings.php';
	if (!isset ($data) && mb_substr($_SERVER['REQUEST_URI'],0,16) != '/freya/setup.php') {
		header ('Location: /freya/setup.php');
		die();
	}
	require $_SERVER['DOCUMENT_ROOT'].'/system/funcs.php';
	require $_SERVER['DOCUMENT_ROOT'].'/system/func_wiki.php';
	require $_SERVER['DOCUMENT_ROOT'].'/system/func_sitemap.php';
	require $_SERVER['DOCUMENT_ROOT'].'/system/systems.php';
	if (isset ($data)) $con = new PDO('mysql:host='.$data['mysql']['host'].';dbname='.$data['mysql']['base'].';charset=utf8', $data['mysql']['user'], $data['mysql']['pass']);
	
	$session = isset ($data) && isset ($_SESSION[$data['site']['id']]) ? $_SESSION[$data['site']['id']] : [];
	if (!isset ($session['err'])) $session['err'] = '';
	if (!isset ($session['msg'])) $session['msg'] = '';
	if (isset ($session['user'])) {
		$user = $con->query("select * from {$data['mysql']['pref']}_users where id='{$session['user']}';")->fetch();
	} else {
		$user = [];
	}
	$title = 'Фрейя v2.1';
	$cms_version = 'v 2.1.526';
	$direct = '';
	$directcode = 302;
	$err = '';
	$msg = '';
	$input = add_arr ($_GET);
	$input = add_arr ($_POST, $input);
	if (isset ($_FILES) && isset ($_FILES['dat']) && is_array ($_FILES['dat'])) foreach ($_FILES['dat']['name'] as $m => $n) $input['dat'][$m] = 'file';
	$html_code = '';
	$cache_hash = '';
	
	define ('PROCESSING', 'ok');
	
	ob_start();
