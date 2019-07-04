<?php
	require $_SERVER['DOCUMENT_ROOT'].'/system/top.php';

	if ((!isset ($_POST) || $_POST==[]) && isset($input['lang'])) {
		$res = $con->query("select * from {$data['mysql']['pref']}_data where elem in (select id from {$data['mysql']['pref']}_struct where parent in (select id from {$data['mysql']['pref']}_struct where alias='multilang')) and var in (select id from {$data['mysql']['pref']}_columns where vrname = 'lang') order by sort;");
		$lang = '';
		while ($row = $res->fetch()) {
			if ($row['value'] == $input['lang']) $lang = $row['value'];
		}
		if ($lang) {
			$session['lang'] = $lang;
			$chlang = '/'.$lang;
		}
		$link = makeLink($_GET, ['lang'=>0], ['lang'=>0]);
		$x = explode ('/', $link);
		$row2 = $con->query("select * from {$data['mysql']['pref']}_data where elem in (select id from {$data['mysql']['pref']}_struct where parent in (select id from {$data['mysql']['pref']}_struct where alias='multilang')) and var in (select id from {$data['mysql']['pref']}_columns where vrname = 'lang') and value = '{$x[1]}' order by sort limit 0,1;")->fetch();
		if ($row2) $link = str_replace ('/'.$x[1].'/', $chlang.'/', $link);
		$direct = $link;
		require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php';
	}
	if (!isset ($session['lang']) || !$session['lang']) {
		$row = $con->query("select * from {$data['mysql']['pref']}_data where elem in (select id from {$data['mysql']['pref']}_struct where parent in (select id from {$data['mysql']['pref']}_struct where alias='multilang')) and var in (select id from {$data['mysql']['pref']}_columns where vrname = 'lang') order by sort limit 0,1;")->fetch();
		if ($row) $session['lang'] = $row['value']; else $session['lang'] = '';
	}

			if (!isset ($_POST) || $_POST==[]) {
				$cache_hash = hash('SHA256', $_SERVER['HTTP_HOST'].' -> '.$_SERVER['REQUEST_URI'].' -> '.$session['lang']);
				$cache_filename = $_SERVER['DOCUMENT_ROOT'].'/cache/'.$cache_hash.'.html';
				if (is_file($cache_filename)) {

					$LastModified_unix = filemtime($cache_filename);
					$LastModified = gmdate("D, d M Y H:i:s \G\M\T", $LastModified_unix);
					$IfModifiedSince = false;
					if (isset($_ENV['HTTP_IF_MODIFIED_SINCE']))
						$IfModifiedSince = strtotime(substr($_ENV['HTTP_IF_MODIFIED_SINCE'], 5));  
					if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
						$IfModifiedSince = strtotime(substr($_SERVER['HTTP_IF_MODIFIED_SINCE'], 5));
					if ($IfModifiedSince && $IfModifiedSince >= $LastModified_unix) {
						header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
						exit;
					}
					header ("Content-type: text/html");
					header ('Last-Modified: '. $LastModified);

					$html = file_get_contents ($cache_filename);
					echo $html;
					die();
				}
			}
		
	preg_match_all ('/\/([^\/]+)/ui', $_SERVER['REQUEST_URI'], $x);
	$links = [];
	$row = $con->query("select * from {$data['mysql']['pref']}_data where elem in (select id from {$data['mysql']['pref']}_struct where parent in (select id from {$data['mysql']['pref']}_struct where alias='multilang')) and var in (select id from {$data['mysql']['pref']}_columns where vrname = 'lang') order by sort limit 0,1;")->fetch();
	$links['lang'] = '';
	$q = count ($x[0]);
	if ($q && $row) {
		if ($q >= 1) $links['lang'] = $x[1][0];
		if ($q >= 2) $links['page'] = $x[1][1];
		if ($q >= 3) $links['id'] = $x[1][2];
		for ($a=1;$a<=$q-1;$a++) $links['par'.$a] = $x[1][$a];
		$row2 = $con->query("select * from {$data['mysql']['pref']}_data where elem in (select id from {$data['mysql']['pref']}_struct where parent in (select id from {$data['mysql']['pref']}_struct where alias='multilang')) and var in (select id from {$data['mysql']['pref']}_columns where vrname = 'lang') and value = '{$links['lang']}' order by sort limit 0,1;")->fetch();
		if (!$row2) $links['lang'] = '';
	}
	if (!$links['lang'] && $row) {
		$link = $_SERVER['REQUEST_URI'];
		$tmp = explode ('/', $link);
		if (!isset ($tmp[1]) || strpos ($tmp[1], '.') === false) {
			$lg = $row['value'];
			if (isset ($session['lang'])) $lg = $session['lang'];
			$direct = '/'.$lg.$_SERVER['REQUEST_URI'];
			require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php';
		}
	}
	if ($q && !$links['lang']) {
		if ($q >= 1) $links['page'] = $x[1][0];
		if ($q >= 2) $links['id'] = $x[1][1];
		for ($a=1;$a<=$q;$a++) $links['par'.$a] = $x[1][$a-1];
	}
	if (isset ($links['lang']) && $links['lang']) {
		$session['lang'] = $links['lang'];
	} else
	if (isset ($session['lang']) && $session['lang']) {
		$links['lang'] = $session['lang'];
	} else 
	if (isset ($row) && $row['value']) {
		$session['lang'] = $row['value'];
		$links['lang'] = $row['value'];
	} else {
		$session['lang'] = '';
		$links['lang'] = '';
	}
	$input = add_arr ($links, $input);
	$needCahce = false;
	$vars = [];
	$struct = isset ($input['page']) ? $input['page'] : 'index';
	
	if (isset ($session['msg'])) addVars ($vars, 'msg', $session['msg'], ['sys']); else addVars ($vars, 'msg', '', ['sys']);
	if (isset ($session['err'])) addVars ($vars, 'err', $session['err'], ['sys']); else addVars ($vars, 'err', '', ['sys']);
	
	//while (true) {
		$elem = $con->query("select * from {$data['mysql']['pref']}_struct where alias='$struct' or hid='$struct';")->fetch();
	//	if ($elem || $struct == 'index') break;
	//	$struct = 'index';
	//}
	addVars ($vars, 'lang', $session['lang'], ['page','get']);
	if ($elem && grantedForMe($elem['id'], VIEW_PAGE)) {
		$dat = $con->query("select * from {$data['mysql']['pref']}_data where elem='{$elem['id']}' and var in (select id from {$data['mysql']['pref']}_columns where caption='HTML');")->fetch();
		if ($dat) {
			$needCache = true;
			if (is_array ($input)) foreach ($input as $vr => $vl) {
				addVars ($vars, $vr, $vl, ['get']);
			}
			inCacheAdd ($elem['id']);
			if (isset ($input['p'])) addVars ($vars, 'pageId', ($input['p']+1));
			addVars ($vars, 'template.id', $elem['id']);
			addVars ($vars, 'title', $elem['caption'], ['page']);
			addVarsFrom ($vars, $elem['id'], ['page']);
			$html_code = $dat['value'];//applyWiki ($dat['value']);
		}
		if (!isset ($input['type']) || $input['type'] == 'css') {
			$dat = $con->query("select * from {$data['mysql']['pref']}_data where elem='{$elem['id']}' and var in (select id from {$data['mysql']['pref']}_columns where caption='CSS');")->fetch();
			if ($dat && $dat['value']) {
				header('Expires: '.date ('D, d M Y', time()+86400*365).' 00:00:00 GMT');
				header ('Content-type: text/css');
				echo applyCode ($dat['value'], $vars);
				die();
			}
		}
		if (!isset ($input['type']) || $input['type'] == 'js') {
			$dat = $con->query("select * from {$data['mysql']['pref']}_data where elem='{$elem['id']}' and var in (select id from {$data['mysql']['pref']}_columns where caption='JS');")->fetch();
			if ($dat && $dat['value']) {
				header('Expires: '.date ('D, d M Y', time()+86400*365).' 00:00:00 GMT');
				header ('Content-type: application/javascript');
				echo applyCode ($dat['value'], $vars);
				die();
			}
		}
	}
	if ($session['msg']) addVars ($vars, 'msg', $session['msg'], ['sys']); else addVars ($vars, 'msg', '', ['sys']);
	if ($session['err']) addVars ($vars, 'err', $session['err'], ['sys']); else addVars ($vars, 'err', '', ['sys']);
	addVars ($vars, 'errmsg', 'Ошибка', []);
	addVars ($vars, 'errmsg_csrf', 'Форма устарела. Повторите попытку.', []);
	addVars ($vars, 'errmsg_lnk', 'Ссылки не разрешены', []);
	addVars ($vars, 'okmsg', 'Отправлено', []);
	$html_code = applyTemplates ($html_code, $vars);
	$html_code = applyWiki ($html_code); 
	
	if (!isset ($needCache) || !$needCache) $cache_hash = '';
	require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php';

