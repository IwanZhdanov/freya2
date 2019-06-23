<?php
	require '../system/top.php';
	$file = $_SERVER['REQUEST_URI'];
	$i = 0;
	$q = mb_strlen($file);
	for ($a=0;$a<$q;$a++) if (mb_substr($file,$a,1) == '-') $i = $a+1;
	$file = mb_substr ($file, $i);
	$row = $con->query("select * from {$data['mysql']['pref']}_files where path = '$file';")->fetch();
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
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($file));
	readfile($file);
	die();
