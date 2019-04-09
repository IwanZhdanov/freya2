<?php
	$body = ob_get_clean();
	$session['err'] .= $err;
	$_SESSION[$data['site']['id']] = $session;
	if ($direct) {
		header ('Location: '.$direct);
		die();
	}
	
	if ($html_code) $x = $html_code; else {
		ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<title><?=$title?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
<link rel="stylesheet" href="/static/style.css">
<link rel="stylesheet" href="/static/freya.css">
<script src="/static/script.js"></script>
</head>
<body>
<? echo $err; $session['err'] = ''; ?>
<?=$body?>
</body>
</html>
<?php
		$x = ob_get_clean();
	}
	//$x = deobfuscateHTML ($x);
	//*
	if ($cache_hash) {
		$f = fopen ($cache_filename, 'w');
		fwrite ($f, $x);
		fclose ($f);
	}
	/**/
	header('Last-Modified: '. gmdate("D, d M Y H:i:s \G\M\T"));
	header('Location: google.ru');
	echo $x;
	$_SESSION[$data['site']['id']] = $session;
	
