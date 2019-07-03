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

	// Добавляем языки во все ссылки
	if ($session['lang'] && strpos ($_SERVER['REQUEST_URI'], '/freya/') === false) {
		$curr = $_SERVER['REQUEST_URI'];
		$tmp = explode ('/', $curr);
		if (isset ($tmp[1]) && $tmp[1] == $session['lang']) unset ($tmp[1]);
		$curr = implode ('/', $tmp);
		preg_match_all ('/=([\'"])?((https?:)?(\/\/'.$_SERVER['HTTP_HOST'].')?(\/'.$session['lang'].')?(\/[^\/][^\'" >]*?|\/)??(?:[?&]lang=([^\'"&>]+))?)\1/ui', $x, $arr);
		$q = count ($arr[0]);
		$replaceList = [];
		for ($a=0;$a<$q;$a++) {
			$src = $arr[0][$a];
			if (strpos ($src, '/cache/') !== false) continue;
			if (strpos ($src, '/static/') !== false) continue;
			if (strpos ($src, '/files/') !== false) continue;
			if (strpos ($src, '/freya/') !== false) continue;
			if (strpos ($src, '/system/') !== false) continue;
			if (strpos ($src, '/user/') !== false) continue;
			if (isset ($arr[7][$a]) && $arr[7][$a]) {
				$dest = $arr[3][$a].$arr[4][$a].'/'.$arr[7][$a].$curr;
			} else {
				$dest = $arr[3][$a].$arr[4][$a].'/'.$session['lang'].$arr[6][$a];
			}
			$dest = '='.$arr[1][$a].$dest.$arr[1][$a];
			if ($arr[2][$a]) $replaceList[$src] = $dest;
		}
		foreach ($replaceList as $src => $dest) {
			$x = str_replace ($src, $dest, $x);
			//$x .= '<!-- 12345 replace '.$src.' '.$dest.' -->'."\n";
		}
	}

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
	
