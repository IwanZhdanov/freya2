<?php
	session_start();
	require $_SERVER['DOCUMENT_ROOT'].'/settings.php';
	require $_SERVER['DOCUMENT_ROOT'].'/system/funcs.php';
	require $_SERVER['DOCUMENT_ROOT'].'/system/func_wiki.php';
	require $_SERVER['DOCUMENT_ROOT'].'/system/systems.php';
	$con = new PDO('mysql:host='.$data['mysql']['host'].';dbname='.$data['mysql']['base'].';charset=utf8', $data['mysql']['user'], $data['mysql']['pass']);
	
	$session = isset ($_SESSION[$data['site']['id']]) ? $_SESSION[$data['site']['id']] : [];
	if (!isset ($session['err'])) $session['err'] = '';
	if (isset ($session['user'])) {
		$user = $con->query("select * from {$data['mysql']['pref']}_users where id='{$session['user']}';")->fetch();
	} else {
		$user = [];
	}
	$title = 'Фрейя v2.0';
	$direct = '';
	$err = '';
	$input = add_arr ($_GET);
	$input = add_arr ($_POST, $input);
	if (isset ($_FILES) && isset ($_FILES['dat']) && is_array ($_FILES['dat'])) foreach ($_FILES['dat']['name'] as $m => $n) $input['dat'][$m] = 'file';
	$html_code = '';
	$cache_hash = '';
	
	define ('PROCESSING', 'ok');
	
	ob_start();
