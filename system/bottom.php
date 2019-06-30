<?php
	$body = ob_get_clean();
	$session['err'] .= $err;
	$session['msg'] .= $msg;
	if (isset ($data)) $_SESSION[$data['site']['id']] = $session;
	if ($direct) {
		header ('Location: '.$direct);
		die();
	}
	
	
	if (!$html_code && !$body) {
		header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
		$elem = $con->query("select * from {$data['mysql']['pref']}_struct where alias='404';")->fetch();
		$dat = $con->query("select * from {$data['mysql']['pref']}_data where elem='{$elem['id']}' and var in (select id from {$data['mysql']['pref']}_columns where caption='HTML');")->fetch();
		if ($dat) {
			if (is_array ($input)) foreach ($input as $vr => $vl) {
				addVars ($vars, $vr, $vl, ['get']);
			}
			if (isset ($input['p'])) addVars ($vars, 'pageId', ($input['p']+1));
			addVars ($vars, 'template.id', $elem['id']);
			addVars ($vars, 'title', $elem['caption'], ['page']);
			addVarsFrom ($vars, $elem['id'], ['page']);
			$html_code = $dat['value'];
			$html_code = applyTemplates ($html_code, $vars);
			$html_code = applyWiki ($html_code); 
		}
	}
	if ($html_code) $x = $html_code; else {
		if (!$body) {
			$body = '<p>Ошибка 404. Страница не найдена.</p>';
			$body .= '<p><a href="/">Вернуться на главную</a></p>';
		}
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
<script src="/static/texteditor.js"></script>
<link rel="shortcut icon" href="/static/favicon.png" type="image/png">
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
	header('Last-Modified: '. gmdate("D, d M Y H:i:s \G\M\T"));
	if (mb_strpos ($x, 'http-equiv="Last-Modified"') === false) {
		$x = preg_replace ('/(<head[^>]*>)/ui', '$1'."\n".'<meta http-equiv="Last-Modified" content="'.gmdate("D, d M Y H:i:s \G\M\T").'">', $x);
	}
	if ($cache_hash) {
		$f = fopen ($cache_filename, 'w');
		fwrite ($f, $x);
		fclose ($f);
	}
	/**/
	echo $x;
	unset ($session['lastform']);
	if (isset ($data)) $_SESSION[$data['site']['id']] = $session;
	
