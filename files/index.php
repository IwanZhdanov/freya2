<?php
	require '../system/top.php';
	$file = $_SERVER['REQUEST_URI'];
	$i = 0;
	$q = mb_strlen($file);
	for ($a=0;$a<$q;$a++) {
		$ch = mb_substr($file,$a,1);
		if ($ch == '-') $i = $a+1;
		if ($ch == '.') break;
	}
	$file = mb_substr ($file, $i);
	if (!is_file ($file)) die('Not found');
	$db_file = preg_replace ('/s.*\./ui', '.', $file);
	$row = $con->query("select * from {$data['mysql']['pref']}_files where path = '$db_file';")->fetch();
	if (!$row) {
		header ('Location: /');
		die();
	}
	$name = $row['nam'];

	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=' . preg_replace('/ /ui','_',$name));
	//header('Content-Disposition: attachment; filename=' . basename($name));
	header('Content-Transfer-Encoding: binary');
	//header('Expires: 0');
	header("Expires: " . gmdate("D, d M Y H:i:s", time() + 365*60*60*24) . " GMT");
	//header('Cache-Control: must-revalidate');
	header('Cache-Control: public');
	header('Pragma: public');
	header('Content-Length: ' . filesize($file));
	readfile($file);
	die();
