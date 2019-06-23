<?php
	require $_SERVER['DOCUMENT_ROOT'].'/system/top.php';

			if (!isset ($_POST) || $_POST==[]) {
				$cache_hash = hash('SHA256', $_SERVER['HTTP_HOST'].' -> '.$_SERVER['REQUEST_URI']);
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
	if ($q = count ($x[0])) {
		if ($q >= 1) $links['page'] = $x[1][0];
		if ($q >= 2) $links['id'] = $x[1][1];
		for ($a=1;$a<=$q;$a++) $links['par'.$a] = $x[1][$a-1];
	}
	$input = add_arr ($links, $input);
	$needCahce = false;
	$html_code = 'Freya v2.0<br />';
	$vars = [];
	$struct = isset ($input['page']) ? $input['page'] : 'index';
	
	if (isset ($session['msg'])) addVars ($vars, 'msg', $session['msg'], ['sys']); else addVars ($vars, 'msg', '', ['sys']);
	if (isset ($session['err'])) addVars ($vars, 'err', $session['err'], ['sys']); else addVars ($vars, 'err', '', ['sys']);
	
	//while (true) {
		$elem = $con->query("select * from {$data['mysql']['pref']}_struct where alias='$struct' or hid='$struct';")->fetch();
	//	if ($elem || $struct == 'index') break;
	//	$struct = 'index';
	//}
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
	
	if (!$needCache) $cache_hash = '';
	require $_SERVER['DOCUMENT_ROOT'].'/system/bottom.php';

